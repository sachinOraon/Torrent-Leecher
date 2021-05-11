// Downloads torrents from the command-line.
package main

import (
	"expvar"
	"fmt"
	"io"
	stdLog "log"
	"net"
	"net/http"
	"os"
	"os/signal"
	"strings"
	"syscall"
	"time"
	"math/rand"
	"bufio"

	"github.com/alexflint/go-arg"
	"github.com/anacrolix/missinggo"
	"github.com/anacrolix/torrent/bencode"
	"github.com/davecgh/go-spew/spew"
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

/*
	Error Code	Reason
		2		error adding magnet (invalid magnet link)
		3		error downloading torrent file (invalid .torrent file link)
		4		error loading torrent file (unable to get .torrent file metainfo)
		5		error adding torrent (unable to initiate download process)
*/

// to store the torrent file info
type TorrentFileInfo struct {
	url			string
	name		string
	size		uint64
	avg_speed	string
	pieces		int
	files		int
	start_time	time.Time
}
var torrent_file TorrentFileInfo

func torrentBar(t *torrent.Torrent, pieceStates bool, updatedAt time.Time, prevDownload uint64) {
	go func() {
		if t.Info() == nil {
			log.Print("getting info for ", t.Name())
			<-t.GotInfo()
		}
		// store the torrent file info into struct
		torrent_file.name = t.Name()
		torrent_file.size = uint64(t.Length())
		torrent_file.pieces = t.NumPieces()
		torrent_file.files = len(t.Files())
		var lastLine string
		for {
			var completedPieces, partialPieces int
			psrs := t.PieceStateRuns()
			for _, r := range psrs {
				if r.Complete {
					completedPieces += r.Length
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

			line := fmt.Sprintf("{\"filename\":%q,\"size\":\"%s\",\"downloaded\":\"%s\",\"speed\":\"%s/s\",\"total_pieces\":%d,\"dwnld_pieces\":%d,\"percent\":\"%.0f%%\"}", t.Name(), humanize.Bytes(uint64(t.Length())), humanize.Bytes(uint64(t.BytesCompleted())), humanize.Bytes(uint64(speed)), t.NumPieces(), completedPieces, float64(t.BytesCompleted())/float64(t.Length())*100)

			// display the line to stdout
			if line != lastLine {
				lastLine = line
				os.Stdout.WriteString(line)
			}
			if pieceStates {
				fmt.Println(psrs)
			}
			time.Sleep(500 * time.Millisecond)
		}
	}()
}

type stringAddr string

func (stringAddr) Network() string   { return "" }
func (me stringAddr) String() string { return string(me) }

func resolveTestPeers(addrs []string) (ret []torrent.PeerInfo) {
	for _, ta := range flags.TestPeer {
		ret = append(ret, torrent.PeerInfo{
			Addr: stringAddr(ta),
		})
	}
	return
}

func addTorrents(client *torrent.Client) error {
	testPeers := resolveTestPeers(flags.TestPeer)
	for _, arg := range flags.Torrent {
		t, err := func() (*torrent.Torrent, error) {
			if strings.HasPrefix(arg, "magnet:") {
				t, err := client.AddMagnet(arg)
				if err != nil {
					os.Exit(2)
					//return nil, xerrors.Errorf("error adding magnet: %w", err)
				}
				return t, nil
			} else if strings.HasPrefix(arg, "http://") || strings.HasPrefix(arg, "https://") {
				response, err := http.Get(arg)
				if err != nil {
					os.Exit(3)
					//return nil, xerrors.Errorf("Error downloading torrent file: %s", err)
				}

				metaInfo, err := metainfo.Load(response.Body)
				defer response.Body.Close()
				if err != nil {
					os.Exit(4)
					//return nil, xerrors.Errorf("error loading torrent file %q: %s\n", arg, err)
				}
				t, err := client.AddTorrent(metaInfo)
				if err != nil {
					os.Exit(5)
					//return nil, xerrors.Errorf("adding torrent: %w", err)
				}
				return t, nil
			} else if strings.HasPrefix(arg, "infohash:") {
				t, _ := client.AddTorrentInfoHash(metainfo.NewHashFromHex(strings.TrimPrefix(arg, "infohash:")))
				return t, nil
			} else {
				metaInfo, err := metainfo.LoadFromFile(arg)
				if err != nil {
					return nil, xerrors.Errorf("error loading torrent file %q: %s\n", arg, err)
				}
				t, err := client.AddTorrent(metaInfo)
				if err != nil {
					return nil, xerrors.Errorf("adding torrent: %w", err)
				}
				return t, nil
			}
		}()
		if err != nil {
			return xerrors.Errorf("adding torrent for %q: %w", arg, err)
		}
		// vars for calculating speed
		var prevDownload uint64
		prevDownload = 0

		loc, _ := time.LoadLocation("Asia/Calcutta")
		start_time := time.Now().In(loc)
		torrent_file.start_time = start_time
		torrent_file.url = flags.Torrent[0]

		if flags.Progress {
			torrentBar(t, flags.PieceStates, start_time, prevDownload)
		}
		t.AddPeers(testPeers)
		go func() {
			<-t.GotInfo()
			if len(flags.File) == 0 {
				t.DownloadAll()
			} else {
				for _, f := range t.Files() {
					for _, fileArg := range flags.File {
						if f.DisplayPath() == fileArg {
							f.Download()
						}
					}
				}
			}
		}()
	}
	return nil
}

var flags struct {
	Debug bool
	Stats *bool

	*DownloadCmd      `arg:"subcommand:download"`
	*ListFilesCmd     `arg:"subcommand:list-files"`
	*SpewBencodingCmd `arg:"subcommand:spew-bencoding"`
	*AnnounceCmd      `arg:"subcommand:announce"`
}

type SpewBencodingCmd struct{}

type DownloadCmd struct {
	Mmap            bool           `help:"memory-map torrent data"`
	TestPeer        []string       `help:"addresses of some starting peers"`
	Seed            bool           `help:"seed after download is complete"`
	Addr            string         `help:"network listen addr"`
	UploadRate      *tagflag.Bytes `help:"max piece bytes to send per second"`
	DownloadRate    *tagflag.Bytes `help:"max bytes per second down from peers"`
	PackedBlocklist string
	PublicIP        net.IP
	Progress        bool `default:"true"`
	PieceStates     bool
	Quiet           bool `help:"discard client logging"`
	Dht             bool `default:"true"`

	TcpPeers        bool `default:"true"`
	UtpPeers        bool `default:"true"`
	Webtorrent      bool `default:"true"`
	DisableWebseeds bool

	Ipv4 bool `default:"true"`
	Ipv6 bool `default:"true"`
	Pex  bool `default:"true"`

	File    []string
	Torrent []string `arity:"+" help:"torrent file path or magnet uri" arg:"positional"`
	Logfile string
}

type ListFilesCmd struct {
	TorrentPath string `arg:"positional"`
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
		log.Printf("close signal received: %+v", <-c)
		notify.Set()
	}
}

func main() {
	if err := mainErr(); err != nil {
		log.Printf("error in main: %v", err)
		os.Exit(1)
	}
}

func mainErr() error {
	stdLog.SetFlags(stdLog.Flags() | stdLog.Lshortfile)
	p := arg.MustParse(&flags)
	switch {
	case flags.AnnounceCmd != nil:
		return announceErr()
	//case :
	//	return announceErr(flags.Args, parser)
	case flags.DownloadCmd != nil:
		return downloadErr()
	case flags.ListFilesCmd != nil:
		mi, err := metainfo.LoadFromFile(flags.ListFilesCmd.TorrentPath)
		if err != nil {
			return fmt.Errorf("loading from file %q: %v", flags.ListFilesCmd.TorrentPath, err)
		}
		info, err := mi.UnmarshalInfo()
		if err != nil {
			return fmt.Errorf("unmarshalling info from metainfo at %q: %v", flags.ListFilesCmd.TorrentPath, err)
		}
		for _, f := range info.UpvertedFiles() {
			fmt.Println(f.DisplayPath(&info))
		}
		return nil
	case flags.SpewBencodingCmd != nil:
		d := bencode.NewDecoder(os.Stdin)
		for i := 0; ; i++ {
			var v interface{}
			err := d.Decode(&v)
			if err == io.EOF {
				break
			}
			if err != nil {
				return fmt.Errorf("decoding message index %d: %w", i, err)
			}
			spew.Dump(v)
		}
		return nil
	default:
		p.Fail(fmt.Sprintf("unexpected subcommand: %v", p.Subcommand()))
		panic("unreachable")
	}
}

func downloadErr() error {
	defer envpprof.Stop()
	clientConfig := torrent.NewDefaultClientConfig()
	clientConfig.DisableWebseeds = flags.DisableWebseeds
	clientConfig.DisableTCP = !flags.TcpPeers
	clientConfig.DisableUTP = !flags.UtpPeers
	clientConfig.DisableIPv4 = !flags.Ipv4
	clientConfig.DisableIPv6 = !flags.Ipv6
	clientConfig.DisableAcceptRateLimiting = true
	clientConfig.NoDHT = !flags.Dht
	clientConfig.Debug = flags.Debug
	clientConfig.Seed = flags.Seed
	clientConfig.PublicIp4 = flags.PublicIP
	clientConfig.PublicIp6 = flags.PublicIP
	clientConfig.DisablePEX = !flags.Pex
	clientConfig.DisableWebtorrent = !flags.Webtorrent
	if flags.PackedBlocklist != "" {
		blocklist, err := iplist.MMapPackedFile(flags.PackedBlocklist)
		if err != nil {
			return xerrors.Errorf("loading blocklist: %v", err)
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
	if flags.UploadRate != nil {
		clientConfig.UploadRateLimiter = rate.NewLimiter(rate.Limit(*flags.UploadRate), 256<<10)
	}
	if flags.DownloadRate != nil {
		clientConfig.DownloadRateLimiter = rate.NewLimiter(rate.Limit(*flags.DownloadRate), 1<<20)
	}
	if flags.Quiet {
		clientConfig.Logger = log.Discard
	}

	var stop missinggo.SynchronizedEvent
	defer func() {
		stop.Set()
	}()

	// set randomly generated listen port
	rand.Seed(time.Now().UnixNano())
	clientConfig.ListenPort = rand.Intn(45000 - 40000 + 1) + 40000

	client, err := torrent.NewClient(clientConfig)
	if err != nil {
		return xerrors.Errorf("creating client: %v", err)
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

	err = addTorrents(client)
	if err != nil {
		return fmt.Errorf("adding torrents: %w", err)
	}
	defer outputStats(client)
	if client.WaitAll() {
		// create a log file for storing download status
		logfile, err := os.OpenFile(flags.Logfile, os.O_WRONLY|os.O_CREATE, 0666)
		if err != nil {
			fmt.Errorf("unable to create logfile: %s", flags.Logfile)
			os.Exit(1)
		}
		loc, _ := time.LoadLocation("Asia/Calcutta")
		eclk := time.Now().In(loc)
		torrent_file.avg_speed = humanize.Bytes(torrent_file.size/uint64(eclk.Unix() - torrent_file.start_time.Unix()))
		// create iobuffer for inserting data into logfile
		w := bufio.NewWriter(logfile)
		fmt.Fprintf(w, "[*] URL      : %s\n[*] Name     : %s\n[*] Size     : %s\n[*] Files    : %d\n[*] Pieces   : %d\n[*] Speed    : %s/s\n[*] Started  : %d-%d-%d %d:%d:%d\n[*] Ended    : %d-%d-%d %d:%d:%d\n[*] Duration : %s", torrent_file.url, torrent_file.name, humanize.Bytes(torrent_file.size), torrent_file.files, torrent_file.pieces, torrent_file.avg_speed, torrent_file.start_time.Day(), torrent_file.start_time.Month(), torrent_file.start_time.Year(), torrent_file.start_time.Hour(), torrent_file.start_time.Minute(), torrent_file.start_time.Second(), eclk.Day(), eclk.Month(), eclk.Year(), eclk.Hour(), eclk.Minute(), eclk.Second(), time.Since(torrent_file.start_time).Round(time.Second).String())
		w.Flush()
		defer logfile.Close()
		log.Print("downloaded ALL the torrents")
	} else {
		return xerrors.New("y u no complete torrents?!")
	}
	if flags.Seed {
		outputStats(client)
		<-stop.C()
	}
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
