#!/usr/bin/env node

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

let req_lst=[];
io.on('connection', client => {
    console.log("[*] client connected ["+client.id+"]");
    io.to(client.id).emit("sess_data", req_lst);

    // torrent download
    client.on("torrent_url", (data, callback) => {
        var file_size;
        let url=data.url;
        let logfile="../files/_log/"+Math.floor(Date.now() / 1000)+".txt";
        req_lst.push(logfile);
        let cur_idx=req_lst.length;

        // execute goLecheer_x64 file
        const child = spawn("./goLeecher_x64", ["download", "--quiet=true", '--logfile='+logfile, url]);
        child.stdout.on("data", data => {
            let output=JSON.parse(`${data}`);
            output["index"]=cur_idx;
            file_size=output["size"];
            io.to(client.id).emit("file_info", output);
        });
        child.on("exit", function(code, signal, idx) {
            io.to(client.id).emit("dwnld_exit_code", {"code": `${code}`, "idx": cur_idx, "fsize": file_size});
        });

        callback({
            status: "ok",
            index: req_lst.length,
            logfile: logfile
        });
    });

    // fetch logfile data
    client.on("get_log", (data, callback) => {
        let file=data.file;
        const cat=spawn("cat", [file]);
        cat.stdout.on("data", output => {io.to(client.id).emit("log_data", `${output}`)});
        callback({status: "ok"});
    });

    // client disconnection log
    client.on('disconnect', (reason) => { console.log("[*] "+reason+" ["+client.id+"]") });
});
