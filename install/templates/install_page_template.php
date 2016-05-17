<?php
function draw_install_page_template()
{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>CORAL - Centralized Online Resources Acquisitions and Licensing</title>
	<link rel="SHORTCUT ICON" href="images/favicon.ico" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/skeleton/2.0.4/skeleton.min.css">
	<script type="text/javascript" src="js/plugins/jquery-1.12.3.min.js"></script>
	<script type="text/javascript" src="js/install.js"></script>
	<style type="text/css">
		body {
			margin-top: 3vh !important;
		}
		.percentageComplete {
			position: fixed;
			display: block;
			top: 0;
			height: 1vh;
			left: 0;
			width: 0;
			background-color: #4682B4;
		}
	</style>
</head>

<body class="container">

	<div class="row main">
		<div class="two columns">&nbsp;</div>
		<div class="eight columns">
			<h1 class="content-head is-center heading">CORAL Installer</h1>
			<div class="current-test"><b>Current Test: </b><span class="current-test-title"></span></div>
			<div class="messages">
				<div class="message">
					Welcome to the CORAL Installer.
				</div>
			</div>
			<div class="mainbody">
				howzit
			</div>
		</div>
		<div class="two columns">&nbsp;</div>
	</div>
	<div class="percentageComplete"></div>
</body>
</html>
<?php
}
