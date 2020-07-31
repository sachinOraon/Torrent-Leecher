<?php
 session_start();
 $pass='qwerty';
 if($_REQUEST['getProcess'] == true){
    $info = shell_exec('ps -u www-data > files/_log/.psraw && ps -o pid,etime,%mem,%cpu,cmd | head -n1 > files/_log/.psinfo && for line in $(grep -n goLeecher files/_log/.psraw | cut -d":" -f1); do ps -o pid,etime,%mem,%cpu,cmd -u www-data | head -n${line} | tail -n1 >> files/_log/.psinfo; done && cat files/_log/.psinfo');
    $count = shell_exec('ps -u www-data | grep -c "goLeecher"');
    if($count > 0)
        echo '<pre>'.$info.'</pre>';
    else echo '<span class="text-success text-monospace font-weight-bold">No active process found</span>';
 }
 if($_REQUEST['getStorage'] == true){
    $str=shell_exec('df -H --sync --output=size,used,avail,pcent --type=ext4');
    $str=trim(preg_replace('/\s+/',' ', $str));
    $arr=explode(" ", $str);
    $info='';
    for($i=0, $j=4; $i<count($arr)/2; $i++, $j++)
    {
        if($i != 2)
            $info .= $arr[$i].' &nbsp;: '.$arr[$j].'<br>';
        else $info .= $arr[$i].' : '.$arr[$j].'<br>';
    }
    echo '<span class="text-monospace font-weight-bold">'.$info.'</span>';
 }
 if(isset($_POST['getLog'])){
    $out=shell_exec('cat '.$_REQUEST['getLog']);
    echo '<pre style="overflow: hidden; text-overflow: ellipsis;">'.$out.'</pre>';
 }
 if(isset($_POST['getFileName'])){
    $found=shell_exec('grep -c "Name   : " '.$_REQUEST['getFileName']);
    $failed=shell_exec('grep -c "Process Terminated" '.$_REQUEST['getFileName']);
    if($found == 1){
        $cmd='grep -oP "(?<=Name   : ).*" '.$_REQUEST['getFileName'].' | tr -d "\n"';
        $fname=shell_exec($cmd);
        $linecnt=shell_exec('wc -l '.$_REQUEST['getFileName'].' | cut -d" " -f1 | tr -d "\n"');
        if($linecnt > 8)
          $pcent=shell_exec('head -n9 '.$_REQUEST['getFileName'].' | tail -n1 | tr -d "\n[*] "');
        else $pcent='0%';
        $response->fname=$fname;
        $response->status=$pcent;
    }
    else if($failed == 1){
      $response->fname='Failed to download';
      $response->status='&#128683;';
    }
    else{
      $response->fname='Getting file info';
      $response->status='<div class="spinner-border text-success spinner-border-sm"></div>';
    }
    echo json_encode($response);
 }
 if(isset($_POST['getDlPcent'])){
  $pcent=shell_exec('head -n9 '.$_REQUEST['getDlPcent'].' | tail -n1 | tr -d "\n[*] "');
  echo '<kbd>'.$pcent.'</kbd>';
 }
 if(isset($_POST['torrent_url']))
 {
  $url="'".$_POST['torrent_url']."'";
  $logfile='files/_log/'.time().'.txt';
  $cmd='./goLeecher '.$url.'  "'.$logfile.'"  2>/dev/null >/dev/null &';
  shell_exec($cmd);
  $_SESSION['req_lst'][]=$logfile;
  $response->logfile=$logfile;
  $response->index=count($_SESSION['req_lst']);
  echo json_encode($response);
 }
 if(isset($_POST['purgePass']))
 {
   if($_REQUEST['purgePass'] == $pass)
   {
    shell_exec('find files -maxdepth 2 -type d,f -user www-data -exec rm -rf {} \;');
    while(count($_SESSION['req_lst']))
      array_pop($_SESSION['req_lst']);
    echo 'done';
   }
   else echo 'wrongPass';
 }
 if(isset($_POST['delPass']))
 {
   if($_REQUEST['delPass'] == $pass)
   {
    $files=scandir('files');
    echo json_encode($files);
   }
   else
   {
    $err->msg='wrongPass';
    echo json_encode($err);
   }
 }
 if(isset($_POST['filelist'])){
  $delMe=json_decode($_REQUEST['filelist']);
  foreach($delMe as $file)
    shell_exec('rm -rf "files/'.$file.'"');
  echo 'done';
 }
 if(isset($_POST['processId'])){
  shell_exec('pkill --signal TERM -f '.$_REQUEST['processId']);
  if($_REQUEST['file'] != 'NA')
    shell_exec('rm -rf "files/'.$_REQUEST['file'].'"');
  $index=array_search($_REQUEST['processId'], $_SESSION['req_lst']);
  unset($_SESSION['req_lst'][$index]);
  $response->msg='done';
  $response->count=count($_SESSION['req_lst']);
  echo json_encode($response);
 }
 if(isset($_POST['pkillpass'])){
  if($_REQUEST['pkillpass'] == $pass){
    $pid=explode('#', shell_exec('pgrep -u www-data -d "#" "goLeecher" | tr -d "\n"'));
    $pname=explode('^', rtrim(shell_exec('pgrep -u www-data -a "goLeecher" | cut -d" " -f2- | tr -t "\n" "^"'), '^'));
    echo json_encode(array_combine($pid, $pname));
  }
  else
  {
    $err->msg='wrongPass';
    echo json_encode($err);
  }
 }
 if(isset($_POST['killpid'])){
  exec('kill -9 '.$_REQUEST['killpid'], $output, $return);
  if($return)
    echo 'fail';
  else echo 'done';
 }
?>
