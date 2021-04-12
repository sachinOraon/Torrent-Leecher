#!/usr/bin/env node

const ps = require('ps-node');
const fs = require('fs');
const { spawn } = require("child_process");
const httpServer = require("http").createServer();
const io = require('socket.io')(httpServer, {
    cors: {
        origin: "http://localhost",
        methods: ["GET", "POST"]
    }
});
httpServer.listen(8080);
console.log("[*] socket.io server started [http://localhost:8080]");

io.on('connection', client => {
    console.log("[*] client connected ["+client.id+"]");

    // start torrent download
    client.on("torrent_url", (data, callback) => {
        var file_size;
        // execute goLecheer_x64 file
        const child = spawn("./goLeecher_x64", ["download", "--quiet=true", '--logfile='+data.logfile, data.url]);
        child.stdout.on("data", goOut => {
            let output=JSON.parse(`${goOut}`);
            output["index"]=data.idx;
            file_size=output["size"];
            // send the data to client
            io.emit("file_info", output)
        });
        // send the exit code and signal to client
        child.on("exit", function(code, signal) {
            io.emit("dwnld_exit_code", {"code": `${code}`, "idx": data.idx, "fsize": file_size, "url": data.url});
        });

        callback({status: "ok"});
    });

    // fetch logfile data
    client.on("get_log", (data, callback) => {
        fs.readFile(data.file, "utf8", (err, content) => {
            let reply={};
            if(err)
            {
                reply["errno"]=err["errno"];
                reply["data"]="Failed to fetch details for "+data.name;
            }
            else reply["data"]=content;
            io.to(client.id).emit("log_data", reply);
        });

        callback({status: "ok"});
    });

    // prepare session data to be sent
    client.on("get_file_info", req => {
        let reply={"idx": req.idx, "url": req.url};
        // process table lookup
        ps.lookup({command: 'goLeecher_x64', arguments: '--logfile='+req.file}, function(err, resultList) {
            if (err) {
                throw new Error( err );
            }
            if(resultList.length){
                // process found
                resultList.forEach(function(process){
                    if(process){
                        reply["proc"]=true;
                        io.emit("sess_data", reply);
                    }
                });
            }
            else{
                // process not found
                reply["proc"]=false;
                // read file info from file
                fs.readFile(req.file, "utf8", (err, data) => {
                    if(err){
                        // process must have terminated without downloading
                        reply["fname"]="NA";
                        reply["fsize"]="NA";
                    }
                    else{
                        reply["fname"]=data.slice(data.indexOf("[*] Name") + 15, data.indexOf("[*] Size") - 1);
                        reply["fsize"]=data.slice(data.indexOf("[*] Size") + 15, data.indexOf("[*] Files") - 1);
                    }
                    // send file name and size to client
                    io.emit("sess_data", reply);
                });
            }
        });
    });

    // stopping download
    client.on('stop_dwnld', req => {
        // find the process id
        ps.lookup({command: 'goLeecher_x64', arguments: '--logfile='+req.logfile}, function(err, resultList){
            if(err){
                io.emit('stop_dwnld_msg', {msg: err.code+': ps.lookup() error!', idx: req.idx});
            }
            else{
                if(resultList.length){
                    // process found
                    resultList.forEach(function(process){
                        if(process){
                            // kill the process
                            ps.kill(process.pid, 'SIGKILL', function(err){
                                if(err){
                                    io.emit('stop_dwnld_msg', {msg: err.code+' Process found but unable to kill', idx: req.idx});
                                }
                                else{
                                    // also remove the partially downloaded file if exists
                                    if(fs.existsSync('../files/'+req.fname)){
                                        fs.rm("../files/"+req.fname, { recursive: true }, (err) => {
                                            if(err){
                                                msg=err.code+' Unable to delete file';
                                            }
                                            else{
                                                msg='ok';
                                            }
                                            io.emit('stop_dwnld_msg', {msg: msg, idx: req.idx});
                                        });
                                    }
                                    else io.emit('stop_dwnld_msg', {msg: 'File does not exists', idx: req.idx});
                                }
                            });
                        }
                    });
                }
                else io.emit('stop_dwnld_msg', {msg: msg, idx: req.idx});
            }
        });
    });

    // client disconnection log
    client.on('disconnect', (reason) => { console.log("[*] "+reason+" ["+client.id+"]") });
});
