<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Handler implements MessageComponentInterface {
    protected $clients;

    public function __construct(){
        $this->clients = new \SplObjectStorage;
        echo "[*] Websocket Server started [localhost:8080]\n";
    }
    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);
        echo "[*] New connection [ResourceID:{$conn->resourceId}]\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);
        echo "[*] Connection [ResourceID:{$conn->resourceId}] has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "[*] An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}