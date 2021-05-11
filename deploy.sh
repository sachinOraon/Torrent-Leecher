#!/bin/bash

set -e
red='\e[1;31m'
green='\e[1;32m'
yellow='\e[1;33m'
cyan='\e[1;36m'
nocol='\033[0m'
tick="\033[1m[${green}✓${nocol}\033[1m]${nocol}"
cross="\033[1m[${red}✗${nocol}\033[1m]${nocol}"
wait="\033[1m[${yellow}★${nocol}\033[1m]${nocol}"

APP_DIR=$HOME/.torrent-leecher
GO_SRC=$APP_DIR/goLeecher
HTTPD_SRC=$APP_DIR/src
STATUS=$APP_DIR/files-status

function fetch_files {
    echo -en "${wait} ${cyan}Cloning${nocol} ${yellow}sachinOraon/Torrent-Leecher.git${nocol}"
    git clone -b nodejs --single-branch --quiet https://github.com/sachinOraon/Torrent-Leecher.git $HTTPD_SRC
    echo -e "\r${tick} ${cyan}Cloning${nocol} ${green}sachinOraon/Torrent-Leecher.git${nocol}"
    chmod -R o+w $HTTPD_SRC/files

    echo -e "${wait} ${cyan}Installing node modules${nocol}"
    rm -r $HTTPD_SRC/websocket/node_modules
    docker run --rm -it -v $HTTPD_SRC/websocket:/usr/src/app -w /usr/src/app node:latest npm install
    if [[ -e $HTTPD_SRC/websocket/node_modules ]]; then
        echo -e "${tick} ${cyan}Done installing ${nocol}${green}node modules${nocol}"
    else
        echo -e "${cross} ${cyan}Failed to install ${nocol}${yellow}node modules${nocol}"
        exit 1
    fi
    echo -e "${wait} ${cyan}Building image${nocol} ${yellow}php_apache:torrent${nocol}"
    echo -e "#!/usr/bin/env bash\nsed -i 's/index.html/index.html \/files\/_h5ai\/public\/index.php/' /etc/apache2/conf-available/docker-php.conf\napache2-foreground" > $APP_DIR/start_apache
    chmod u+x $APP_DIR/start_apache
    echo -e "FROM php:apache\nCOPY start_apache /usr/local/bin" > $APP_DIR/php_apache
    docker build --rm -qt php_apache:torrent -f $APP_DIR/php_apache $APP_DIR
    echo -e "${tick} ${cyan}New image created ${nocol} ${green}php_apache:torrent${nocol}"

    echo -en "${wait} ${cyan}Cloning${nocol} ${yellow}anacrolix/torrent.git${nocol}"
    git clone -b master --single-branch --quiet https://github.com/anacrolix/torrent.git $GO_SRC
    echo -e "\r${tick} ${cyan}Cloning${nocol} ${green}anacrolix/torrent.git${nocol}"

    rm $GO_SRC/config.go
    rm $GO_SRC/cmd/torrent/main.go
    cp $HTTPD_SRC/go/src/github.com/anacrolix/torrent/config.go $GO_SRC
    cp $HTTPD_SRC/go/src/github.com/anacrolix/torrent/cmd/torrent/main.go $GO_SRC/cmd/torrent
    echo -e "${wait} ${cyan}Adding golang to${nocol} ${yellow}node:alpine3.13${nocol}"
    echo -e "FROM node:alpine3.13\nRUN apk update && apk add --no-cache build-base go" > $APP_DIR/node_alpine3.13_go
    docker build --rm -qt node_alpine3.13:go -f $APP_DIR/node_alpine3.13_go .
    echo -e "${tick} ${cyan}New image created${nocol} ${green}node_alpine3.13:go${nocol}"

    echo -e "${wait} ${cyan}Compiling${nocol} ${yellow}goLeecher binary${nocol}"
    docker run --rm -v $GO_SRC:/usr/src/myapp -w /usr/src/myapp/cmd/torrent node_alpine3.13:go go build -v
    echo -e "${tick} ${cyan}Done compiling${nocol} ${green}goLeecher binary${nocol}"
    rm $HTTPD_SRC/websocket/goLeecher
    cp $GO_SRC/cmd/torrent/torrent $HTTPD_SRC/websocket/goLeecher

    echo -e "${tick} ${green}Files downloaded and setup successfully${nocol}" | tee $STATUS
}

function start_server {
    echo -e "${wait} ${cyan}Starting Apache server at${nocol} ${green}localhost:8090${nocol}"
    docker run --rm -dit --name torrent-httpd -p 8090:80/tcp -v $HTTPD_SRC:/var/www/html php_apache:torrent start_apache
    echo -e "${wait} ${cyan}Starting Node.js server for${nocol} ${green}socket.io${nocol}"
    docker run --rm -dit --name torrent-ws -p 8080:8080/tcp -v $HTTPD_SRC:/usr/src/app -w /usr/src/app/websocket node_alpine3.13:go node server.js
    for container in torrent-httpd torrent-ws; do
        if [[ -z $(docker ps --all --format {{.Names}} --filter=name=${container}) ]]; then
            echo -e "${cross} ${cyan}Failed to start ${nocol}${yellow}${container}${nocol}"
            exit 1
        fi
    done
    echo -e "${tick} ${yellow}To stop the containers execute${nocol} ⬎"
    echo -e "${wait} ${green}docker stop torrent-ws torrent-httpd${nocol}"
}

function stop_server {
    for container in torrent-httpd torrent-ws; do
        if [[ -n $(docker ps --all --format {{.Names}} --filter=name=${container}) ]]; then
            echo -e "${wait} ${cyan}Stopping${nocol} ${yellow}${container}${nocol}"
            docker stop $container
            echo -e "${tick} ${green}Stopped${nocol} ${yellow}${container}${nocol}"
        fi
    done
}

if [[ "$UID" -ne 0 ]]; then
    echo -e "${cross} ${red}Run as${nocol} ${cyan}root${nocol}"
    exit 1
fi

for package in git docker; do
    if [[ -z $(which $package) ]]; then
        echo -e "${cross} ${cyan}${package}${nocol} is ${red}missing${nocol}"
        exit 1
    fi
done

stop_server
if [[ ! -e $STATUS ||\
    "$(docker images --filter 'reference=php_apache:torrent' | wc -l)" -eq 1 ||\
    "$(docker images --filter 'reference=node_alpine3.13:go' | wc -l)" -eq 1 ]]; then
    echo -e "${wait} ${cyan}Setting up environment${nocol}"
    rm -rf $APP_DIR
    fetch_files
fi
start_server
