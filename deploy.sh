#!/bin/bash

set -e
APP_DIR=$HOME/.torrent-leecher
GO_SRC=$APP_DIR/goLeecher
HTTPD_SRC=$APP_DIR/src
STATUS=$APP_DIR/files-status

if [[ "$UID" -ne 0 ]]; then
    echo "run as root"
    exit 1
fi

for package in git docker; do
    if [[ -z $(which $package) ]]; then
        echo "$package is missing"
        exit 1
    fi
done

function fetch_files {
    echo "cloning sachinOraon/Torrent-Leecher.git"
    git clone -b nodejs --single-branch --quiet https://github.com/sachinOraon/Torrent-Leecher.git $HTTPD_SRC

    echo "cloning anacrolix/torrent.git"
    git clone -b master --single-branch --quiet https://github.com/anacrolix/torrent.git $GO_SRC

    echo "building goLeecher binary"
    rm $GO_SRC/config.go
    rm $GO_SRC/cmd/torrent/main.go
    cp $HTTPD_SRC/go/src/github.com/anacrolix/torrent/config.go $GO_SRC
    cp $HTTPD_SRC/go/src/github.com/anacrolix/torrent/cmd/torrent/main.go $GO_SRC/cmd/torrent
    echo -e "FROM node:alpine3.13\nRUN apk update && apk add --no-cache build-base go" | tee $APP_DIR/node_alpine3.13_go
    docker build --rm -t node_alpine3.13:go -f $APP_DIR/node_alpine3.13_go .
    docker run --rm -v $GO_SRC:/usr/src/myapp -w /usr/src/myapp/cmd/torrent node_alpine3.13:go go build -v
    rm $HTTPD_SRC/websocket/goLeecher
    cp $GO_SRC/cmd/torrent/torrent $HTTPD_SRC/websocket/goLeecher
    echo "all files downloaded" | tee $STATUS
}

function setup_server {
    echo "setting up apache server [localhost:8090]"
    docker run --rm -dit --name torrent-httpd -p 8090:80/tcp -v $HTTPD_SRC:/usr/local/apache2/htdocs/ httpd:latest
    echo "setting up nodejs server"
    docker run --rm -dit --name torrent-ws -p 8080:8080/tcp -v $HTTPD_SRC:/usr/src/app -w /usr/src/app/websocket node_alpine3.13:go node server.js
    echo -e "to stop the containers execute\ndocker stop torrent-ws torrent-httpd"
}

if [[ ! -e $APP_DIR ]]; then
    fetch_files
elif [[ ! -e $STATUS ]]; then
    echo "removing old files"
    rm -r $APP_DIR
    fetch_files
fi

setup_server
