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
        echo "[*] [ResourceID:{$conn->resourceId}] New connection\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        //default reply
        $response=array("action"=>"undefined", "reply"=>"undefined");
        //fetch running process info
        if(!strcmp($msg, "getProcess")){
            $info = shell_exec('ps -u www-data > ../files/_log/.psraw && ps -o pid,etime,%mem,%cpu,cmd | head -n1 > ../files/_log/.psinfo && for line in $(grep -n goLeecher ../files/_log/.psraw | cut -d":" -f1); do ps -o pid,etime,%mem,%cpu,cmd -u www-data | head -n${line} | tail -n1 >> ../files/_log/.psinfo; done && cat ../files/_log/.psinfo');
            $count = shell_exec('ps -u www-data | grep -c "goLeecher"');
            $response["action"]=$msg;
            if($count > 0)
                $response["reply"] = '<pre>'.$info.'</pre>';
            else $response["reply"] = '<span class="text-success text-monospace font-weight-bold">No active process found</span>';
        }
        //fetch the log of currently downloading file
        if(!strncmp($msg, "getLog", 6)){
            $logfile=substr($msg, 7);
            $response["action"]="getLog";
            $response["reply"]='<pre style="overflow: hidden; text-overflow: ellipsis;">'.shell_exec('cat ../'.$logfile).'</pre>';
        }
        //send the response to the client
        $from->send(json_encode($response));
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);
        echo "[*] [ResourceID:{$conn->resourceId}] Connection has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "[*] An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}