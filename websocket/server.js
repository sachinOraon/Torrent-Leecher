#!/usr/bin/env node

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
    //client.on('event', data => { /* … */ });
    //client.on('disconnect', () => { /* … */ });
});
