// Downloads torrents from the command-line.
// Taken from github.com/anacrolix/torrent/cmd/torrent
package main

import (
	"expvar"
	"fmt"
	"net"
	"net/http"
	"os"
	"os/signal"
	"strings"
	"syscall"
	"time"
	"bufio"
	"io/ioutil"
	"math/rand"

	"github.com/anacrolix/missinggo"
	"github.com/dustin/go-humanize"
	"golang.org/x/xerrors"

	"github.com/anacrolix/log"

	"github.com/anacrolix/envpprof"
	"github.com/anacrolix/tagflag"
	"golang.org/x/time/rate"

	"github.com/anacrolix/torrent"
	"github.com/anacrolix/torrent/iplist"
	"github.com/anacrolix/torrent/metainfo"
	"github.com/anacrolix/torrent/storage"
)

func torrentBar(t *torrent.Torrent, pieceStates bool, logFile *os.File, updatedAt time.Time, prevDownload uint64) {
	go func() {
		// wait until metadata is fetched
		if t.Info() == nil {
			//log.Printf("[*] Fetching info for %q", t.Name())
			<-t.GotInfo()
		}
		//log.Printf("[*] File info received : %q", t.Name())

		// insert fetched metadata into logfile
		w := bufio.NewWriter(logFile)
		fmt.Fprintf(w, "[*] Name   : %s\n[*] Size   : %s\n[*] Pieces : %d\n", t.Name(), humanize.Bytes(uint64(t.Length())), t.NumPieces())
		w.Flush()

		for {
			// wait for a second to update download status
			time.Sleep(time.Second)

			// compute download progress
			var completedPieces, partialPieces int
			psrs := t.PieceStateRuns()
			completed := 0
			for _, r := range psrs {
				if r.Complete {
					completedPieces += r.Length
					completed++
				}
				if r.Partial {
					partialPieces += r.Length
				}
			}

			//calculate download speed
			var speed float32
			now := time.Now()
			bytes := uint64(t.BytesCompleted())
			if !updatedAt.IsZero(){
				dt := float32(now.Sub(updatedAt))
				db := float32(bytes - prevDownload)
				speed = db * (float32(time.Second) / dt)
			}
			prevDownload = bytes
			updatedAt = now

			// insert data into logfile
			fmt.Fprintf(w, "[*] Fetched: %6s\n[*] Speed  : %6s/s\n[*] %3.0f%%", humanize.Bytes(uint64(t.BytesCompleted())), humanize.Bytes(uint64(speed)), float64(t.BytesCompleted())/float64(t.Length())*100)
			w.Flush()
			logFile.Seek(-50, 1)

			/*log.Printf(
				"%6s|%6s|%3d|%3d|%3d|%3.0f%%|%6s/s",
				humanize.Bytes(uint64(t.BytesCompleted())),
				humanize.Bytes(uint64(t.Length())),
				completedPieces,
				t.NumPieces(),
				partialPieces,
				float64(t.BytesCompleted())/float64(t.Length())*100,
				humanize.Bytes(uint64(speed)),
			)*/

			if pieceStates {
				fmt.Println(psrs)
			}
		}
	}()
}

func addTorrents(client *torrent.Client, logFile *os.File) error {
	// create iobuffer for inserting data into logfile
	w := bufio.NewWriter(logFile)
	// insert the start datetime in logfile
	clk := time.Now()
	fmt.Fprintf(w, "[*] [%d/%d/%d %d:%d:%d]\n[*] Getting file info\n[*] URL    : %s\n", clk.Day(), clk.Month(), clk.Year(), clk.Hour(), clk.Minute(), clk.Second(), os.Args[1])
	w.Flush()
	//for _, arg := range flags.Torrent {
		t, err := func() (*torrent.Torrent, error) {
			if strings.HasPrefix(os.Args[1], "magnet:") {
				t, err := client.AddMagnet(os.Args[1])
				if err != nil {
					return nil, xerrors.Errorf("[*] Error adding magnet: %w", err)
				}
				return t, nil
			} else if strings.HasPrefix(os.Args[1], "http://") || strings.HasPrefix(os.Args[1], "https://") {
				response, err := http.Get(os.Args[1])
				if err != nil {
					return nil, xerrors.Errorf("[*] Error downloading torrent file: %s", err)
				}
				metaInfo, err := metainfo.Load(response.Body)
				defer response.Body.Close()
				if err != nil {
					fmt.Fprintf(w, "[*] Unable to download .torrent file\n[*] Process Terminated")
					w.Flush()
					return nil, xerrors.Errorf("[*] Error loading torrent file %q: %s\n", os.Args[1], err)
				}
				t, err := client.AddTorrent(metaInfo)
				if err != nil {
					return nil, xerrors.Errorf("[*] Unable to add torrent: %w", err)
				}
				return t, nil
			} else if strings.HasPrefix(os.Args[1], "infohash:") {
				t, _ := client.AddTorrentInfoHash(metainfo.NewHashFromHex(strings.TrimPrefix(os.Args[1], "infohash:")))
				return t, nil
			} else {
				metaInfo, err := metainfo.LoadFromFile(os.Args[1])
				if err != nil {
					fmt.Fprintf(w, "[*] Unable to get file info\n[*] Process Terminated")
					w.Flush()
					return nil, xerrors.Errorf("[*] Error loading torrent file %q: %s\n", os.Args[1], err)
				}
				t, err := client.AddTorrent(metaInfo)
				if err != nil {
					return nil, xerrors.Errorf("[*] Error adding torrent: %w", err)
				}
				return t, nil
			}
		}()
		if err != nil {
			return xerrors.Errorf("[*] Error adding torrent for %q: %w", os.Args[1], err)
		}

		// some vars for calculating speed
		var prevDownload uint64
		prevDownload = 0

		// start the download process
		if flags.Progress {
			torrentBar(t, flags.PieceStates, logFile, clk, prevDownload)
		}

		t.AddPeers(func() (ret []torrent.Peer) {
			for _, ta := range flags.TestPeer {
				ret = append(ret, torrent.Peer{
					Addr: ta,
				})
			}
			return
		}())
		go func() {
			<-t.GotInfo()
			t.DownloadAll()
		}()
	//}
	return nil
}

var flags = struct {
	Mmap            bool           `help:"memory-map torrent data"`
	TestPeer        []*net.TCPAddr `help:"addresses of some starting peers"`
	Seed            bool           `help:"seed after download is complete"`
	Addr            string         `help:"network listen addr"`
	UploadRate      tagflag.Bytes  `help:"max piece bytes to send per second"`
	DownloadRate    tagflag.Bytes  `help:"max bytes per second down from peers"`
	Debug           bool
	PackedBlocklist string
	Stats           *bool
	PublicIP        net.IP
	Progress        bool
	PieceStates     bool
	Quiet           bool `help:"discard client logging"`
	Dht             bool
	tagflag.StartPos
	Torrent []string `arity:"+" help:"torrent file path or magnet uri"`
}{
	UploadRate:   -1,
	DownloadRate: -1,
	Progress:     true,
	Dht:          true,
	Quiet:        false,
}

func stdoutAndStderrAreSameFile() bool {
	fi1, _ := os.Stdout.Stat()
	fi2, _ := os.Stderr.Stat()
	return os.SameFile(fi1, fi2)
}

func statsEnabled() bool {
	if flags.Stats == nil {
		return flags.Debug
	}
	return *flags.Stats
}

func exitSignalHandlers(notify *missinggo.SynchronizedEvent) {
	c := make(chan os.Signal, 1)
	signal.Notify(c, syscall.SIGINT, syscall.SIGTERM)
	for {
		log.Printf("[*] Stop signal received: %+v", <-c)
		notify.Set()
	}
}

func main() {
	if err := mainErr(); err != nil {
		log.Printf("[*] Error in main: %v", err)
		os.Exit(1)
	}
}

func mainErr() error {
	tagflag.Parse(&flags)
	defer envpprof.Stop()
	clientConfig := torrent.NewDefaultClientConfig()
	clientConfig.DisableAcceptRateLimiting = true
	clientConfig.NoDHT = !flags.Dht
	clientConfig.Debug = flags.Debug
	clientConfig.Seed = flags.Seed
	clientConfig.PublicIp4 = flags.PublicIP
	clientConfig.PublicIp6 = flags.PublicIP
	if flags.PackedBlocklist != "" {
		blocklist, err := iplist.MMapPackedFile(flags.PackedBlocklist)
		if err != nil {
			return xerrors.Errorf("[*] Error blocklist: %v", err)
		}
		defer blocklist.Close()
		clientConfig.IPBlocklist = blocklist
	}
	if flags.Mmap {
		clientConfig.DefaultStorage = storage.NewMMap("")
	}
	if flags.Addr != "" {
		clientConfig.SetListenAddr(flags.Addr)
	}
	if flags.UploadRate != -1 {
		clientConfig.UploadRateLimiter = rate.NewLimiter(rate.Limit(flags.UploadRate), 256<<10)
	}
	if flags.DownloadRate != -1 {
		clientConfig.DownloadRateLimiter = rate.NewLimiter(rate.Limit(flags.DownloadRate), 1<<20)
	}
	clientConfig.Logger = log.Discard
	if flags.Quiet {
		clientConfig.Logger = log.Discard
	}
	// set randomly generated listen port
	rand.Seed(time.Now().UnixNano())
	clientConfig.ListenPort = rand.Intn(45000 - 40000 + 1) + 40000

	var stop missinggo.SynchronizedEvent
	defer func() {
		stop.Set()
	}()

	// create a new client for this session
	client, err := torrent.NewClient(clientConfig)
	if err != nil {
		return xerrors.Errorf("[*] Unable to create client: %v", err)
	}
	defer client.Close()
	go exitSignalHandlers(&stop)
	go func() {
		<-stop.C()
		client.Close()
	}()

	// Write status on the root path on the default HTTP muxer. This will be bound to localhost
	// somewhere if GOPPROF is set, thanks to the envpprof import.
	http.HandleFunc("/", func(w http.ResponseWriter, req *http.Request) {
		client.WriteStatus(w)
	})

	// create a log file for storing download status
	file, err := os.OpenFile(os.Args[2], os.O_WRONLY|os.O_CREATE, 0666)
	if err != nil {
		fmt.Println("[*] Unable to create logfile")
		os.Exit(1)
	}

	// bind torrent link to created client
	addTorrents(client, file)

	// wait for client session to terminate
	if client.WaitAll() {
		data, err := ioutil.ReadFile(os.Args[2])
		if err == nil {
			if !strings.Contains(string(data[:]), "Process Terminated") {
				// insert ending time in logfile
				eclk := time.Now()
				w := bufio.NewWriter(file)
				file.Seek(-8, 2)
				fmt.Fprintf(w, "[*] 100%%\n[*] Completed\n[*] [%d/%d/%d %d:%d:%d]", eclk.Day(), eclk.Month(), eclk.Year(), eclk.Hour(), eclk.Minute(), eclk.Second())
				w.Flush()
			}
			//else { log.Printf("Fatal error") }
		}
		defer file.Close()
		//log.Print("[*] Torrent downloaded")
	} else {
		return xerrors.New("[*] Unable to download")
	}
	if flags.Seed {
		outputStats(client)
		<-stop.C()
	}
	outputStats(client)
	return nil
}

func outputStats(cl *torrent.Client) {
	if !statsEnabled() {
		return
	}
	expvar.Do(func(kv expvar.KeyValue) {
		fmt.Printf("%s: %s\n", kv.Key, kv.Value)
	})
	cl.WriteStatus(os.Stdout)
}
