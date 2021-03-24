<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

date_default_timezone_set("Asia/Kolkata");

class Handler implements MessageComponentInterface {
    protected $clients;

    public function __construct(){
        $this->clients = new \SplObjectStorage;
        echo "[".date("d-m-Y H:i:s")."]"." Websocket Server started [localhost:8080]\n";
    }
    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);
        echo "[".date("d-m-Y H:i:s")."]"." New connection [ResourceID:{$conn->resourceId}]\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $input=json_decode($msg);
        //fetch running process info
        if(!strcmp($input->action, "getProcess")){
            $info = shell_exec('ps -u www-data > ../files/_log/.psraw && ps -o pid,etime,%mem,%cpu,cmd | head -n1 > ../files/_log/.psinfo && for line in $(grep -n goLeecher ../files/_log/.psraw | cut -d":" -f1); do ps -o pid,etime,%mem,%cpu,cmd -u www-data | head -n${line} | tail -n1 >> ../files/_log/.psinfo; done && cat ../files/_log/.psinfo');
            $count = shell_exec('ps -u www-data | grep -c "goLeecher"');
            $response["action"]="getProcess";
            if($count > 0)
                $response["reply"] = '<pre style="overflow: hidden; text-overflow: ellipsis; cursor: default;">'.$info.'</pre>';
            else $response["reply"] = '<span class="text-success text-monospace font-weight-bold">No active process found</span>';
        }
        //fetch the log of currently downloading file
        if(!strncmp($input->action, "getLog", 6)){
            $logfile=substr($input->action, 7);
            $response["action"]="getLog";
            $response["reply"]='<pre style="overflow: hidden; text-overflow: ellipsis;">'.shell_exec('cat ../'.$logfile).'</pre>';
            $linecnt=shell_exec('wc -l '.'../'.$logfile.' | cut -d" " -f1 | tr -d "\n"');
            if($linecnt >= 8)
                $pcent=shell_exec('head -n9 '.'../'.$logfile.' | tail -n1 | tr -d "\n[*] "');
            else $pcent="0%";
            $response["pcent"]=$pcent;
        }
        //fetch the file name
        if(!strncmp($input->action, "getFileName", 11)){
            $response["action"]="getFileName";
            foreach($input as $idx => $logfile){
                if(!strcmp($idx, "action")) continue;
                $found=shell_exec('grep -c "Name   : " '.'../'.$logfile);
                $failed=shell_exec('grep -c "Process Terminated" '.'../'.$logfile);
                if($found == 1){
                    $cmd='grep -oP "(?<=Name   : ).*" '.'../'.$logfile.' | tr -d "\n"';
                    $fname=shell_exec($cmd);
                    $linecnt=shell_exec('wc -l '.'../'.$logfile.' | cut -d" " -f1 | tr -d "\n"');
                    if($linecnt >= 8)
                        $status=shell_exec('head -n9 '.'../'.$logfile.' | tail -n1 | tr -d "\n[*] "');
                    else $status='0%';
                }
                else if($failed == 1){
                  $fname='Failed to download';
                  $status='NA';
                }
                else{
                  $fname='Getting file info';
                  $status='<div class="spinner-grow text-success spinner-grow-sm"></div>';
                }
                $response["reply"][$idx]["fname"]=$fname;
                $response["reply"][$idx]["status"]=$status;
            }
        }
        //fetch the download % of files
        if(!strncmp($input->action, "getFileStatus", 12)){
            $response["action"]=$input->action;
            foreach($input as $idx => $logfile){
                if(!strcmp($idx, "action")) continue;
                $linecnt=shell_exec('wc -l '.'../'.$logfile.' | cut -d" " -f1 | tr -d "\n"');
                if($linecnt >= 8)
                    $pcent=shell_exec('head -n9 '.'../'.$logfile.' | tail -n1 | tr -d "\n[*] "');
                else $pcent="0%";
                $response["reply"][$idx]["status"]=$pcent;
            }
        }
        //send the response to the client
        $from->send(json_encode($response));
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);
        echo "[".date("d-m-Y H:i:s")."]"." Connection closed [ResourceID:{$conn->resourceId}]\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "[".date("d-m-Y H:i:s")."]"." An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}