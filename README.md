# Torrent-Leecher
A simple web interface for downloading torrent files. Just provide the `magnet` link or `.torrent` file url and sit back. The request is forwaded to a golang executable(compiled from `github.com/anacrolix/torrent/tree/master/cmd/torrent`) that gets the job done. This is very minimalistic implementation of `github.com/anacrolix/torrent` library along with `Node.js` and `jQuery`. File browsing is provided by `h5ai` file indexer that has many great features.

- Home Page
<p align="center"><img src="img/snap_shot.JPG"></p>

- Steps to setup
1. Clone this repository to `/var/www/html/torrent`
2. Give `rwx` permissions to the `files` directory by running `sudo chmod -R o+rwx files`
3. After that, install following packages `sudo apt install apache2 php libapache2-mod-php php7.4-gd ffmpeg zip graphicsmagick -y`
4. Install latest version of [Node.js](https://nodejs.org/en/download/) Make sure to select `Linux Binaries (x64)`
5. Open the file `/etc/apache2/mods-available/dir.conf` and edit the line beginning with `DirectoryIndex`, add this `/torrent/files/_h5ai/public/index.php` at the end
6. Start the websocket server on a new terminal by executing `cd /var/www/html/torrent/websocket && node server.js` command
7. Restart the `apache2` server and go to `localhost/torrent`
