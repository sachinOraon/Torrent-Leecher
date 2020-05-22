<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title>Torrent Leecher</title>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.11.2/css/all.css">
  <!-- Bootstrap core CSS -->
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <!-- Material Design Bootstrap -->
  <link href="css/mdb.min.css" rel="stylesheet">
  <!-- Your custom styles (optional) -->
  <link href="css/style.min.css" rel="stylesheet">
  <style type="text/css">
    @media (min-width: 800px) and (max-width: 850px) {
      .navbar:not(.top-nav-collapse) {
        background: #1C2331 !important;
      }
    }
    @font-face {
      font-family: 'tale_of_hawksregular';
      src: url('font/tale/tale_of_hawks-webfont.woff2') format('woff2'),
           url('font/tale/tale_of_hawks-webfont.woff') format('woff');
      font-weight: bold;
      font-style: normal;
    }
  </style>
</head>

<body>
<?php
 session_start();
 /* take the url and hand it to the leecher script */
 if(isset($_POST['torrent_url']))
 {
  $url="'".trim($_POST['torrent_url'])."'";
  $logfile='/var/www/html/torrent/files/_log/'.time().'.txt';
  $cmd='python3 leecher.py '.$url.'  "'.$logfile.'"  2>/dev/null >/dev/null &';
  shell_exec($cmd);
  $_SESSION['flag']=true;
 }
 /* default password for files deletion */
 $pass='qwerty';
 /* purge files */
 if(isset($_POST['purge']))
 {
   if($_POST['purge'] == $pass)
   {
    shell_exec('find /var/www/html/torrent/files -maxdepth 2 -type d,f -user www-data -exec rm -rf {} \;');
    $_SESSION['delflag']=true;
   }
   else $_SESSION['wrngpass']=true;
 }
 /* get the list of files */
 if(isset($_POST['listFiles']))
 {
   if($_POST['listFiles'] == $pass)
   {
     $_SESSION['listFiles']=true;
     $files=array_slice(scandir('/var/www/html/torrent/files/'), 2);
   }
   else $_SESSION['wrngpass']=true;
 }
 /* delete the selected files */
 if(!empty($_POST['filePool']))
 {
  foreach($_POST['filePool'] as $delMe)
  {
    shell_exec('rm -rf "files/'.$delMe.'"');
  }
  $_SESSION['delflag']=true;
 }
?>
<!-- The Modal -->
<div class="modal fade" id="myModal">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <h4 class="modal-title text-info">Information</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        Your request has been submitted successfully. Kindly wait till your file is downloaded. You can find the downloaded files via File Browser menu.
      </div>

      <!-- Modal footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>
<!-- The Modal -->
<div class="modal fade" id="PurgeModal">
  <div class="modal-dialog">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <h4 class="modal-title">Purge Files</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
       <div class="alert alert-danger">
        Purging file will remove all the downloaded files and logs from the server. Use this to free up space.
       </div>
       <form method='POST' action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>'>
        <div class="form-group">
         <label for="pwd">Password:</label>
         <input type="password" class="form-control" placeholder="Enter password" id="pwd" name='purge' required>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
       </form>
      </div>
    </div>
  </div>
</div>
<!-- The Modal -->
<div class="modal fade" id="PassMsg">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <h4 class="modal-title">Information</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
      <?php
       if(isset($_SESSION['delflag']) && $_SESSION['delflag'] == true)
        echo '<h6 class="text-success font-weight-bold">Files Deleted Successfully</h6>';
       if(isset($_SESSION['wrngpass']) && $_SESSION['wrngpass'] == true)
        echo '<h6 class="text-danger font-weight-bold">Wrong Password Entered</h6>';
      ?>
      </div>

      <!-- Modal footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>

<!-- The Modal -->
<div class="modal fade" id="freeUpForm">
  <div class="modal-dialog">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <h4 class="modal-title">Delete Files</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
       <div class="alert alert-info">
        The next page will bring up a list of user files stored on the server and provides an option to select and delete unused files.
       </div>
       <form method='POST' action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>'>
        <div class="form-group">
         <label for="pwd">Password:</label>
         <input type="password" class="form-control" placeholder="Enter password" id="pwdDf" name='listFiles' required>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
       </form>
      </div>
    </div>
  </div>
</div>

<!-- The Modal -->
<div class="modal fade" id="showFiles">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <h4 class="modal-title">Files Available</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        <form method='POST' action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>'>
         <?php
         $num_files=0;
         for($i=0; $i<count($files); $i++)
         {
         	if($files[$i] != '_h5ai' && $files[$i] != '_log')
         	{
         		echo '<div class="custom-control custom-checkbox">';
         		echo '<input type="checkbox" class="custom-control-input" id="check'.$i.'" name="filePool[]" value="'.$files[$i].'">';
         		echo '<label class="custom-control-label"'.'for="check'.$i.'">'.$files[$i].'</label></div>';
         		$num_files++;
         	}
         }
         if($num_files)
         	echo '<hr><button type="submit" class="btn btn-danger">Delete</button>';
         else echo '<p class="text-danger font-weight-bold">NO files available</p>';
         ?>
        </form>
      </div>
    </div>
  </div>
</div>

  <!-- Navbar -->
  <nav class="navbar fixed-top navbar-expand-lg navbar-dark scrolling-navbar">
    <div class="container">

      <!-- Brand -->
      <a class="navbar-brand waves-effect" href="http://www.github.com/sachinOraon" target="_blank">
        <i class="fab fa-github fa-lg"></i>&nbsp;<strong>sachinOraon</strong>
      </a>
      <!-- Collapse -->
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
        aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Links -->
      <div class="collapse navbar-collapse" id="navbarSupportedContent">

        <!-- Left -->
        <ul class="navbar-nav mr-auto">
          <li class="nav-item active">
            <a class="nav-link" href="/torrent"><i class="fas fa-redo-alt"></i> Reload</a><span class="sr-only">(current)</span>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="files" target="_blank"><i class="fas fa-film"></i> File Browser</a>
          </li>
          <li class="nav-item">
            <a class="nav-link storage_info" href="#"><i class="fas fa-hdd"></i> Storage Info</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#" data-toggle="modal" data-target="#PurgeModal" data-backdrop="static"><i class="fas fa-skull-crossbones"></i> Purge Files</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#" data-toggle="modal" data-target="#freeUpForm" data-backdrop="static"><i class="fas fa-trash-alt"></i> Delete Files</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="https://unblockit.me/" target="_blank"><i class="far fa-grin-stars"></i> Torrent Sites</a>
          </li>
        </ul>

      </div>

    </div>
  </nav>
  <!-- Navbar -->

  <!-- Full Page Intro -->
  <div class="view" style="background-image: url('https://www.hdwallpapers.in/download/experiment_3-1280x720.jpg'); background-repeat: no-repeat; background-size: cover;">

    <!-- Mask & flexbox options-->
    <div class="mask rgba-black-light d-flex justify-content-center align-items-center">

      <!-- Content -->
      <div class="text-center white-text wow fadeIn col-md-5">
        <div class="row">
            <div class="col-md-6"><h1 class="mb-4 display-2" style="font-family: 'tale_of_hawksregular'; text-shadow: 0 0 10px #007cff, 0 0 12px #00c3ff, 0 0 14px #6800ff, 0 0 16px #f00, 0 0 18px #f00;">Torrent</h1></div>
            <div class="col-md-6"><h1 class="mb-4 display-2" style="font-family: 'tale_of_hawksregular'; text-shadow: 0 0 10px #007cff, 0 0 12px #00c3ff, 0 0 14px #6800ff, 0 0 16px #f00, 0 0 18px #f00;">Leecher</h1></div>
        </div>
            <div id="typed-strings-body">
	            <p>You can paste .torrent file url or magnet link.</p>
	            <p>Just sit and relax, I will download it for you.</p>
	            <p>It's better than any other leeching service.</p>
	            <p>NO limitation in file size.</p>
            </div>
            <h6 class="font-weight-bold" style="text-shadow: 0 0 10px #f44336, 0 0 12px #00c3ff, 0 0 14px #6800ff, 0 0 16px #f00, 0 0 18px #f00;"><span id="typed-body"></span></h6><br><br>
			<form method='POST' action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>' id='submitUrl'>
			  <div class="form-group">
				<div class="input-group mx-auto">
				  <div class="input-group-prepend">
				    <span class="input-group-text" id="inputGroup-sizing-default"><i class="fas fa-magnet"></i></span>
				  </div>
				  <input type="text" class="form-control" name='torrent_url' aria-label="torrent url" aria-describedby="inputGroup-sizing-default" placeholder="Paste .torrent file url or magnet link" required>
				</div>
			   </div>
			  <button type="button" class="btn btn-outline-white btn-lg submitBtn">Download <i class="fas fa-arrow-circle-down fa-lg"></i></button>
			</form>
      </div>
      <!--/.Content -->

    </div>
    <!--/.Mask & flexbox options-->

  </div>
  <!--/.Full Page Intro -->

  <!--Footer-->
  <footer class="page-footer text-center font-small wow fadeIn">

    <!--Call to action-->
    <div class="pt-1">
    	<small class="text-monospace">Disclaimer : We believe in a right to privacy, not piracy. While this site is targeted towards torrents, we do not endorse or condone any inappropriate use of torrents and copyright protected materials. This site was created for educational purpose only demonstrating the use of libtorrent python library. Kindly follow the rules and guidelines of downloading or spreading torrents as per your government regulations.</small>
    </div>
    <!--/.Call to action-->

    <!--Copyright-->
    <div class="footer-copyright py-3">
      © 2020 Copyright:
      <a href="https://www.github.com/sachinOraon" target="_blank"> @sachinOraon</a>
    </div>
    <!--/.Copyright-->

  </footer>
  <!--/.Footer-->

  <!-- SCRIPTS -->
  <!-- JQuery -->
  <script type="text/javascript" src="js/jquery-3.4.1.min.js"></script>
  <!-- Bootstrap tooltips -->
  <script type="text/javascript" src="js/popper.min.js"></script>
  <!-- Bootstrap core JavaScript -->
  <script type="text/javascript" src="js/bootstrap.min.js"></script>
  <!-- MDB core JavaScript -->
  <script type="text/javascript" src="js/mdb.min.js"></script>
  <!-- Typed.js plugin -->
  <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.11"></script>
  <!-- Initializations -->
  <script type="text/javascript">
  $(document).ready(function(){
    // Animations initialization
    new WOW().init();
    // Typed.js
    var typed = new Typed('#typed-body', {
        stringsElement: '#typed-strings-body',
    	typeSpeed: 40,
	    loop: true,
	    loopCount: Infinity,
        shuffle: false,
        backSpeed: 20
  	});
  	// Storage info popover
  	$('.storage_info').popover({
  		html: true,
  		title: '<h5>Partition info</h5>',
  		trigger: "hover",
  		placement: "bottom",
        <?php
         $str=shell_exec('df -H --sync --output=size,used,avail,pcent --type=ext4');
         $str=trim(preg_replace('/\s+/',' ', $str));
         $arr=explode(" ", $str);
         $info='';
         for($i=0, $j=4; $i<count($arr)/2; $i++, $j++)
            $info .= $arr[$i].' : '.$arr[$j].'<br>';
        ?>
        content: '<?php echo '<p class="text-monospace font-weight-bold">'.$info.'</p>'; ?>'
  	});
    // Avoid taking empty data
    $('.submitBtn').on('click', function(){
        var url=$('input[name="torrent_url"]').val().trim();
        if(url === ""){
            // display alert tooltip
            $('input[name="torrent_url"]').tooltip({
                trigger: "click",
                html: true,
                title: '<h6 class="font-weight-bold">Empty URL given :(</h6>',
                placement: "top"
            });
            $('input[name="torrent_url"]').tooltip('show');
            setTimeout(() => { $('input[name="torrent_url"]').tooltip('dispose'); }, 1000);
        }
        else $('#submitUrl').submit();
    });
  });
 </script>
<?php
 /* display the request submission dialog */
 if(isset($_SESSION['flag'])){
  echo '<script type="text/javascript">$("#myModal").modal({backdrop: "static"});</script>';
 }
 /* display the wrong password dialog */
 if(isset($_SESSION['delflag']) || isset($_SESSION['wrngpass']))
  echo '<script type="text/javascript">$("#PassMsg").modal({backdrop: "static"});</script>';
 /* display the list of files dialog */
 if(isset($_SESSION['listFiles']))
  echo '<script type="text/javascript">$("#showFiles").modal({backdrop: "static"});</script>';
 session_unset();
 session_destroy();
?>
</body>

</html>
