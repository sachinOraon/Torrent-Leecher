<?php
 if($_REQUEST['getProcess'] == true){
    $info = shell_exec('ps -o pid,etime,%mem,%cpu,cmd -C python3');
    $count = shell_exec('ps -C python3 | wc -l');
    if($count > 1)
        echo '<pre>'.$info.'</pre>';
    else echo '<p class="text-success font-weight-bold">No active process found</p>';
 }
 if($_REQUEST['getStorage'] == true){
    $str=shell_exec('df -H --sync --output=size,used,avail,pcent --type=ext4');
    $str=trim(preg_replace('/\s+/',' ', $str));
    $arr=explode(" ", $str);
    $info='';
    for($i=0, $j=4; $i<count($arr)/2; $i++, $j++)
        $info .= $arr[$i].' : '.$arr[$j].'<br>';
    echo '<p class="text-monospace font-weight-bold">'.$info.'</p>';
 }
 if(isset($_POST['getLog'])){
    $out=shell_exec('cat '.$_REQUEST['getLog']);
    echo '<pre style="overflow: hidden; text-overflow: ellipsis;">'.$out.'</pre>';
 }
?>