# Torrent-Leecher
A simple web interface for downloading torrent files. Just provide the `magnet` link or `.torrent` file url and sit back. The request is forwaded to a golang executable(compiled from `github.com/anacrolix/torrent/tree/master/cmd/torrent`) that gets the job done. This is very minimalistic implementation of `github.com/anacrolix/torrent` library along with `Node.js` and `jQuery`. File browsing is provided by `h5ai` file indexer that has many great features.

- Home Page
<p align="center"><img src="img/snap_shot.png"></p>

- Download List
<p align="center"><img src="img/download_list.png"></p>

- Steps to setup
1. Clone this repository to `/var/www/html/torrent`
2. Give `rwx` permissions to the `files` directory by running `sudo chmod -R o+rwx files`
3. After that, install following packages `sudo apt install apache2 php libapache2-mod-php php7.4-gd ffmpeg zip graphicsmagick -y`
4. Install latest version of [Node.js](https://nodejs.org/en/download/) Make sure to select `Linux Binaries (x64)`
5. Open the file `/etc/apache2/mods-available/dir.conf` and edit the line beginning with `DirectoryIndex`, add this `/torrent/files/_h5ai/public/index.php` at the end
6. The goLeecher_x64 binary provided here may not work on your system so we need to build it.
    1. Install the latest version of [go](https://golang.org/doc/install)
    2. Install `build-essential` package by `sudo apt install build-essential -y`
    3. Clone this repository [github.com/anacrolix/torrent.git](https://github.com/anacrolix/torrent.git) at `$HOME`
    4. Replace `$HOME/torrent/config.go` and `$HOME/torrent/cmd/torrent/main.go` with `/var/www/html/torrent/go/src/github.com/anacrolix/torrent/config.go` and `/var/www/html/torrent/go/src/github.com/anacrolix/torrent/cmd/torrent/main.go` respectively
    5. Open a terminal and navigate to `cd $HOME/torrent/cmd/torrent/` and enter `go install` command
    6. After the process completes, you'll find the binary at `$HOME/go/bin/torrent` Rename it to `goLeecher_x64`
    7. Now replace `/var/www/html/torrent/websocket/goLeecher_x64` with the `$HOME/go/bin/goLeecher_x64` 
7. Start the websocket server by executing `cd /var/www/html/torrent/websocket && node server.js` command
8. Restart the `apache2` server and go to `localhost/torrent`