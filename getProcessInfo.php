<?php
 if($_REQUEST['getProcess']){
    $info = shell_exec('ps -o pid,cmd -C python3');
    $count = shell_exec('ps -o pid,cmd -C python3 | wc -l');
    if($count > 1)
        echo '<pre>'.$info.'</pre>';
    else echo '<p class="text-success font-weight-bold">No active process found</p>';
 }
?>