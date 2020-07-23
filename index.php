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
    .modal-open .navbar-expand-lg {
        padding-right: 16px !important;
    }
  </style>
</head>

<body>
<?php
 session_start();
 /* create session array to store submitted requests */
 if(!isset($_SESSION['req_lst']))
    $_SESSION['req_lst']=array();
?>
<!-- The Modal -->
<div class="modal fade" id="myModal">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <h4 class="modal-title">Information</h4>
        <button type="button" class="close text-danger font-weight-bold" data-dismiss="modal">&times;</button>
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
<div class="modal fade" id="processLst">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <h4 class="modal-title">Process Info</h4>
        <button type="button" class="close text-danger font-weight-bold" data-dismiss="modal">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body processinfo"><div class="spinner-grow text-info"></div></div>

      <!-- Modal footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>
<!-- The Modal -->
<div class="modal fade" id="storageInfo">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <h4 class="modal-title">Storage Info</h4>
        <button type="button" class="close text-danger font-weight-bold" data-dismiss="modal">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body storage-info"><div class="spinner-grow text-info"></div></div>

      <!-- Modal footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>
<!-- The Modal -->
<div class="modal fade" id="logModal">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <h4 class="modal-title">View Log</h4>
        <button type="button" class="close text-danger font-weight-bold" data-dismiss="modal">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body logfile"><div class="spinner-grow text-success"></div></div>

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
        <button type="button" class="close text-danger font-weight-bold" data-dismiss="modal">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
       <div class="alert alert-danger">
        Purging file will remove all the downloaded files and logs from the server. Use this to free up space in one go.
       </div>
       <form method='POST' action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>' id='purgeForm'>
        <div class="form-group">
         <label for="pwd">Password:</label>
         <input type="password" class="form-control" placeholder="Enter password" id="pwd" name='purge' required>
        </div>
        <button type="button" class="btn btn-primary">Submit</button>
       </form>
      </div>
    </div>
  </div>
</div>
<!-- The Modal -->
<div class="modal fade" id="successModal">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <h4 class="modal-title">Information</h4>
        <button type="button" class="close text-danger font-weight-bold" data-dismiss="modal">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body"><h6 class="text-success font-weight-bold">Files Deleted Successfully</h6></div>

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
        <button type="button" class="close text-danger font-weight-bold" data-dismiss="modal">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
       <div class="alert alert-info">
        The next page will bring up a list of user files stored on the server and provides an option to select and delete unused files.
       </div>
       <form method='POST' action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>' id="delFileForm">
        <div class="form-group">
         <label for="pwd">Password:</label>
         <input type="password" class="form-control" placeholder="Enter password" id="pwdDf" name='listFiles' required>
        </div>
        <button type="button" class="btn btn-primary">Submit</button>
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
        <button type="button" class="close text-danger font-weight-bold" data-dismiss="modal">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body filelist"></div>

      <!-- Modal footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-danger d-none delFileBtn">Delete</button>
      </div>

    </div>
  </div>
</div>

  <!-- Navbar -->
  <nav class="navbar fixed-top navbar-expand-lg navbar-dark scrolling-navbar">
    <div class="container">

      <!-- Brand -->
      <a class="navbar-brand waves-effect" href="http://www.github.com/sachinOraon/Torrent-Leecher" target="_blank">
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
          <li class="nav-item">
            <a class="nav-link" href="index.php"><i class="fas fa-redo-alt"></i> Reload</a><span class="sr-only">(current)</span>
          </li>
          <li class="nav-item">
            <a class="nav-link pbtn" href="#"><i class="fas fa-server"></i> Process Info</a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown"><i class="fas fa-list-ul"></i> Status</a>
            <div class="dropdown-menu">
                <?php
                if(count($_SESSION['req_lst'])){
                    $i=1;
                    foreach($_SESSION['req_lst'] as $file){
                        echo '<a class="dropdown-item font-weight-bold text-monospace viewLogModal" href="#" data-logfile="'.$file.'">'.$i.'. <span class="fname">[ Getting file info ]</span></a>';
                        $i++;
                    }
                }
                else echo '<span class="dropdown-item default-item font-weight-bold text-monospace">No url submitted</span>';
                ?>
            </div>
          </li>
          <li class="nav-item">
            <a class="nav-link storage_info" href="#"><i class="fas fa-hdd"></i> Storage Info</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="files" target="_blank"><i class="fas fa-film"></i> File Browser</a>
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
  <div id="particles-js" class="view" style="background-image: url('https://www.hdwallpapers.in/download/experiment_3-1280x720.jpg'); background-repeat: no-repeat; background-size: cover;">

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
  <!-- Particles.js plugin -->
  <script type="text/javascript" src="js/particles.js"></script>
  <script type="text/javascript" src="js/app.js"></script>
  <!-- Initializations -->
  <script type="text/javascript">
  $(document).ready(function(){
    // Typed.js
    var typed = new Typed('#typed-body', {
      stringsElement: '#typed-strings-body',
      typeSpeed: 40,
      loop: true,
      loopCount: Infinity,
      shuffle: false,
      backSpeed: 20
    });
    // Storage info modal
    $('.storage_info').on('click', function(){
        $('#storageInfo').modal('show');
    });
    $('#storageInfo').on('show.bs.modal', function(){
        // ajax call to fetch storage info
        $('.storage-info').load('getInfo.php', {"getStorage": true} );
    });
    $('#storageInfo').on('hidden.bs.modal', function(){
        $('.storage-info').html('<div class="spinner-grow text-info"></div>');
    });
    // Download button click event
    $('.submitBtn').on('click', function(){
        var url=$('input[name="torrent_url"]').val().trim();
        // Avoid taking empty data
        if(url === ""){
            // display alert tooltip
            $('input[name="torrent_url"]').tooltip({
                trigger: "click",
                html: true,
                title: '<h6 class="font-weight-bold">Empty URL given</h6>',
                placement: "top"
            });
            $('input[name="torrent_url"]').tooltip('show');
            setTimeout(() => { $('input[name="torrent_url"]').tooltip('dispose'); }, 2000);
        }
        else
        {
          // send the request to server
          $.ajax({
            url: 'getInfo.php',
            type: 'POST',
            data: {torrent_url: $('input[name="torrent_url"]').val().trim()},
            dataType: 'json',
            success: function(response){
                $("#myModal").modal('show');
                document.getElementById('submitUrl').reset();
                if($('.default-item').length)
                    $('.default-item').remove();
                $('.dropdown-menu').append('<a class="dropdown-item font-weight-bold text-monospace" href="#" data-logfile="'+response.logfile+'">'+response.index+'. <span class="fname">[ Getting file info ]</span></a>');
                $('.dropdown-menu a:last').on('click', function(){
                    logFile=$(this).data('logfile');
                    $('#logModal').modal('show');
                });
            },
            error: function(xhr, status, error){
                var errorMessage = xhr.status + ': ' + xhr.statusText;
                window.alert('Error - ' + errorMessage);
            }
          });
        }
    });
    // Purge files function
    $('#purgeForm button').on('click', function(){
        $.ajax({
            url: 'getInfo.php',
            type: 'POST',
            data: {purgePass: $('#pwd').val()},
            success: function(response)
            {
                if(response == 'done')
                {
                    $('#PurgeModal').modal('hide');
                    $('#PurgeModal').on('hidden.bs.modal', function(){$('#successModal').modal('show'); $('#PurgeModal').off('hidden.bs.modal');});
                    $('.dropdown-menu > a').remove();
                    if(!$('.default-item').length)
                        $('.dropdown-menu').append('<span class="dropdown-item default-item font-weight-bold text-monospace">No Log available</span>');
                }
                else if(response == 'wrongPass')
                {
                    document.getElementById('purgeForm').reset();
                    $('#pwd').tooltip({
                        trigger: "click",
                        html: true,
                        title: '<h6 class="font-weight-bold">Incorrect password</h6>',
                        placement: "top"
                    });
                    $('#pwd').tooltip('show');
                    setTimeout(() => { $('#pwd').tooltip('dispose'); }, 2000);
                }
            },
            error: function(xhr, status, error)
            {
                var errorMessage = xhr.status + ': ' + xhr.statusText;
                window.alert('Error - ' + errorMessage);
            }
        });
    });
    // Delete files function
    $('#delFileForm button').on('click', function(){
        $.ajax({
            url: 'getInfo.php',
            type: 'POST',
            data: {delPass: $('#pwdDf').val()},
            dataType: 'json',
            success: function(response)
            {
                if(response.msg == 'wrongPass')
                {
                    document.getElementById('delFileForm').reset();
                    $('#pwdDf').tooltip({
                        trigger: "click",
                        html: true,
                        title: '<h6 class="font-weight-bold">Incorrect password</h6>',
                        placement: "top"
                    });
                    $('#pwdDf').tooltip('show');
                    setTimeout(() => { $('#pwdDf').tooltip('dispose'); }, 2000);
                }
                else
                {
                    $('#freeUpForm').modal('hide');
                    var idx=1;
                    for(var i in response){
                        if(response[i] == '_h5ai' || response[i] == '_log' || response[i] == '.' || response[i] == '..')
                            continue;
                        else $('.filelist').append('<div class="custom-control custom-checkbox">'+'<input type="checkbox" class="custom-control-input" value="'+response[i]+'" id="file'+idx+'"><label class="custom-control-label" for="file'+idx+'">'+response[i]+'</label></div>');
                        idx++;
                    }
                    if(idx == 1){
                        if(!$('.filelist > p').length) $('.filelist').append('<p class="text-danger font-weight-bold">NO files available</p>');
                        $('.delFileBtn').addClass('d-none');
                    }
                    else
                    {
                        $('.delFileBtn').removeClass('d-none');
                        $('.filelist > p').remove();
                    }
                    $('#freeUpForm').on('hidden.bs.modal', function(){$('#showFiles').modal({show: true, backdrop: 'static'}); $('#freeUpForm').off('hidden.bs.modal')});
                    $('#showFiles').on('hidden.bs.modal', function(){ $('.filelist > div').remove(); $('#showFiles').off('hidden.bs.modal')});
                }
            },
            error: function(xhr, status, error)
            {
                var errorMessage = xhr.status + ': ' + xhr.statusText;
                window.alert('Error - ' + errorMessage);
            }
        });
    });
    $('.delFileBtn').on('click', function(){
        var delMe=[];
        $('.filelist input').each(function(){
            if($(this).prop('checked')){
                delMe.push($(this).val());
            }
        });
        if(delMe.length)
        {
            $.ajax({
                url: 'getInfo.php',
                type: 'POST',
                data: {filelist: JSON.stringify(delMe)},
                success: function(response)
                {
                    if(response == 'done'){
                        $('#showFiles').modal('hide');
                        $('#showFiles').on('hidden.bs.modal', function(){$('#successModal').modal('show'); $('#showFiles').off('hidden.bs.modal')});
                    }
                },
                error: function(xhr, status, error)
                {
                    var errorMessage = xhr.status + ': ' + xhr.statusText;
                    window.alert('Error - ' + errorMessage);
                }
            });
        }
        else
        {
            $('.delFileBtn').tooltip({
                trigger: "click",
                html: true,
                title: '<h6 class="font-weight-bold">No file selected</h6>',
                placement: "left"
            });
            $('.delFileBtn').tooltip('show');
            setTimeout(() => { $('.delFileBtn').tooltip('dispose'); }, 2000);
        }
    });
    // Display processLst modal and fetch running process info
    $('.pbtn').on('click', function(){
        $('#processLst').modal('show');
    });
    $('#processLst').on('shown.bs.modal', function(){
        fetchProcess();
        refreshProcess = setInterval(fetchProcess, 5000);
    });
    $('#processLst').on('hide.bs.modal', function(){
        clearInterval(refreshProcess);
    });
    function fetchProcess(){
        $('.processinfo').load('getInfo.php', {"getProcess": true} );
    }
    $('#processLst').on('hidden.bs.modal', function(){
        $('.processinfo').html('<div class="spinner-grow text-info"></div>');
    });
    // Display log modal
    var logFile;
    $('.viewLogModal').on('click', function(){
        $('#logModal').modal('show');
        logFile=$(this).data('logfile');
    });
    $('#logModal').on('shown.bs.modal', function(){
        fetchLog();
        if($('.logfile').html().search('Completed') > 0 || $('.logfile').html().search('Process Terminated') > 0)
            clearInterval(refreshLog);
        else refreshLog = setInterval(fetchLog, 1000);
    });
    function fetchLog(){
        $('.logfile').load('getInfo.php', {'getLog': logFile});
        if($('.logfile').html().search('Completed') > 0 || $('.logfile').html().search('Process Terminated') > 0)
            clearInterval(refreshLog);
    }
    $('#logModal').on('hide.bs.modal', function(){
        clearInterval(refreshLog);
        $('.logfile pre').remove();
    });
    $('#logModal').on('hidden.bs.modal', function(){
        $('.logfile').html('<div class="spinner-grow text-success"></div>');
    });
    // Get file name info for dropdown list
    $(".dropdown-toggle").dropdown();
    $('.dropdown > a').on('click', function(){
        $('.dropdown-menu > a').each(function(){
            var logfile=$(this).data('logfile');
            var fname=$(this).find('.fname').html();
            if(fname.search('Getting file') >= 0)
                $(this).find('.fname').load('getInfo.php', {'getFileName': logfile});
        });
    });
  });
 </script>

</body>

</html>
