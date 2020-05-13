# Torrent-Leecher
A simple web interface for downloading torrent files. Just provide the magnet link or .torrent file url and sit back. The request is forwaded to a python script that gets the job done. This is very minimalistic implementation of python's `libtorrent` library and `PHP`. Currently the main drawback of this web interface is that it doesn't show live status of torrent being downloaded, however it fulfills my purpose.

- Home Page
<p align="center"><img src="img/snap_shot.png"></p>

- Steps to setup
1. Clone this repository to `/var/www/html/torrent`
2. Make sure `python3.x` is installed. After that, install following packages `sudo apt install python3-libtorrent apache2 php libapache2-mod-php php7.2-gd ffmpeg zip graphicsmagick -y`
3. Edit the file `/etc/apache2/mods-enabled/dir.conf`
4. At the end of line beginning with DirectoryIndex, add the given line `/torrent/files/_h5ai/public/index.php`
5. Restart the `apache2` server and go to `localhost/torrent`