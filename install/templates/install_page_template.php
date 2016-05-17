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
		.redirection {
			display: none;
			font-size: 120%;
		}
		.redirection .title {
			font-weight: bold;
		}
		.countdown {
			background: url("images/spinner.gif");
			width: 48px;
			height: 48px;
			display: inline-block;
			text-align: center;
			vertical-align: middle;
			line-height: 48px;
			margin-left: 10px;
		}
		.completed_test_holder {
			list-style: none;
		}
		.completed_test:before {
			background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjwhLS0gQ3JlYXRlZCB3aXRoIElua3NjYXBlIChodHRwOi8vd3d3Lmlua3NjYXBlLm9yZy8pIC0tPgoKPHN2ZwogICB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iCiAgIHhtbG5zOmNjPSJodHRwOi8vY3JlYXRpdmVjb21tb25zLm9yZy9ucyMiCiAgIHhtbG5zOnJkZj0iaHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyIKICAgeG1sbnM6c3ZnPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIKICAgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIgogICB4bWxuczpzb2RpcG9kaT0iaHR0cDovL3NvZGlwb2RpLnNvdXJjZWZvcmdlLm5ldC9EVEQvc29kaXBvZGktMC5kdGQiCiAgIHhtbG5zOmlua3NjYXBlPSJodHRwOi8vd3d3Lmlua3NjYXBlLm9yZy9uYW1lc3BhY2VzL2lua3NjYXBlIgogICB3aWR0aD0iMjgiCiAgIGhlaWdodD0iMjgiCiAgIHZpZXdCb3g9IjAgMCAyNy45OTk5OTkgMjgiCiAgIGlkPSJzdmcyIgogICB2ZXJzaW9uPSIxLjEiCiAgIGlua3NjYXBlOnZlcnNpb249IjAuOTEgcjEzNzI1IgogICBzb2RpcG9kaTpkb2NuYW1lPSJjaGVja21hcmsuc3ZnIj4KICA8ZGVmcwogICAgIGlkPSJkZWZzNCIgLz4KICA8c29kaXBvZGk6bmFtZWR2aWV3CiAgICAgaWQ9ImJhc2UiCiAgICAgcGFnZWNvbG9yPSIjZmZmZmZmIgogICAgIGJvcmRlcmNvbG9yPSIjNjY2NjY2IgogICAgIGJvcmRlcm9wYWNpdHk9IjEuMCIKICAgICBpbmtzY2FwZTpwYWdlb3BhY2l0eT0iMC4wIgogICAgIGlua3NjYXBlOnBhZ2VzaGFkb3c9IjIiCiAgICAgaW5rc2NhcGU6em9vbT0iNy45MTk1OTU5IgogICAgIGlua3NjYXBlOmN4PSIxMi4xNDAzMzkiCiAgICAgaW5rc2NhcGU6Y3k9IjguMzkxMjQ2NyIKICAgICBpbmtzY2FwZTpkb2N1bWVudC11bml0cz0icHgiCiAgICAgaW5rc2NhcGU6Y3VycmVudC1sYXllcj0ibGF5ZXIxIgogICAgIHNob3dncmlkPSJmYWxzZSIKICAgICBpbmtzY2FwZTpzbmFwLXRvLWd1aWRlcz0iZmFsc2UiCiAgICAgdW5pdHM9InB4IgogICAgIGlua3NjYXBlOndpbmRvdy13aWR0aD0iMTM2NiIKICAgICBpbmtzY2FwZTp3aW5kb3ctaGVpZ2h0PSI3NjgiCiAgICAgaW5rc2NhcGU6d2luZG93LXg9IjAiCiAgICAgaW5rc2NhcGU6d2luZG93LXk9IjAiCiAgICAgaW5rc2NhcGU6d2luZG93LW1heGltaXplZD0iMSIgLz4KICA8bWV0YWRhdGEKICAgICBpZD0ibWV0YWRhdGE3Ij4KICAgIDxyZGY6UkRGPgogICAgICA8Y2M6V29yawogICAgICAgICByZGY6YWJvdXQ9IiI+CiAgICAgICAgPGRjOmZvcm1hdD5pbWFnZS9zdmcreG1sPC9kYzpmb3JtYXQ+CiAgICAgICAgPGRjOnR5cGUKICAgICAgICAgICByZGY6cmVzb3VyY2U9Imh0dHA6Ly9wdXJsLm9yZy9kYy9kY21pdHlwZS9TdGlsbEltYWdlIiAvPgogICAgICAgIDxkYzp0aXRsZT48L2RjOnRpdGxlPgogICAgICA8L2NjOldvcms+CiAgICA8L3JkZjpSREY+CiAgPC9tZXRhZGF0YT4KICA8ZwogICAgIGlua3NjYXBlOmxhYmVsPSJMYXllciAxIgogICAgIGlua3NjYXBlOmdyb3VwbW9kZT0ibGF5ZXIiCiAgICAgaWQ9ImxheWVyMSIKICAgICB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLC0xMDI0LjM2MjIpIj4KICAgIDxwYXRoCiAgICAgICBzdHlsZT0iZmlsbDojN2FjMTQyO2ZpbGwtb3BhY2l0eToxIgogICAgICAgZD0ibSA4LjM3NDc5MjEsMTA0My4yNDUzIGMgLTIuMTM4MjgsLTIuODEzNSAtMy45MTkwNiwtNS4yNDczIC0zLjk1NzI0LC01LjQwODQgLTAuMDg2NiwtMC4zNjU5IDIuODE2MDQsLTIuNjAwNyAzLjEzOTczLC0yLjQxNzIgMC4xMjAyNSwwLjA2OCAxLjEzNzI3LDEuMzMxIDIuMjYwMDEsMi44MDYyIDEuMTIyNzA5OSwxLjQ3NTIgMi4xNDMyMDk5LDIuNjgyMiAyLjI2Nzc1OTksMi42ODIyIDAuMTI0NTMsMCAxLjIwMjQ1LC0xLjU3NTIgMi4zOTUyOCwtMy41MDA1IDEuMTkyODIsLTEuOTI1MyAyLjk0MTE2LC00Ljc0NzggMy44ODUxOCwtNi4yNzIzIDAuOTc3MjYsLTEuNTc4MiAxLjgzNDE5LC0yLjc3MjMgMS45ODk5MiwtMi43NzMxIDAuMTUwNDUsLTdlLTQgMC45NTQ4NywwLjQxOSAxLjc4NzY0LDAuOTMyOSAxLjA3MTk2LDAuNjYxNCAxLjQ5MTQ1LDEuMDE5OSAxLjQzNjU0LDEuMjI3NCAtMC4wNDI3LDAuMTYxMiAtMi4xODg1NSwzLjY4OTIgLTQuNzY4NjQsNy44Mzk4IC0yLjU4MDEsNC4xNTA2IC00LjkzODUyLDcuOTU1NiAtNS4yNDA5NCw4LjQ1NTcgLTAuNjAwMSwwLjk5MjMgLTEuMDMwNTIsMS41NDY1IC0xLjE5OTIxLDEuNTQ0MiAtMC4wNTk2LC03ZS00IC0xLjg1Nzc0LC0yLjMwMzQgLTMuOTk2MDI5OSwtNS4xMTY5IHoiCiAgICAgICBpZD0icGF0aDQxNjAiCiAgICAgICBpbmtzY2FwZTpjb25uZWN0b3ItY3VydmF0dXJlPSIwIiAvPgogIDwvZz4KPC9zdmc+Cg==);
			content: "";
			width: 28px;
			height: 28px;
			display: inline-block;
			vertical-align: middle;
			margin-top: -5px;
			margin-right: 10px;
		}
	</style>
</head>

<body class="container">

	<div class="row main">
		<div class="two columns">&nbsp;</div>
		<div class="eight columns">
			<h1 class="content-head is-center heading"><center>
				CORAL Installer
			</center></h1>
			<div class="installation_stuff">
				<div class="section-title"></div>
				<div class="messages">
					<div class="message">
						Welcome to the CORAL Installer.
					</div>
				</div>
				<div class="mainbody">
				</div>
			</div>
			<div class="redirection">
				<div class="row">
					<div class="three columns">
						<svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"><circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/></svg>
					</div>
					<div class="nine columns">
						<div class="title">Congratulations</div>
						<p>
							Installation has been successfully completed.
						</p>
						<p>
							Redirecting Home: <span class="countdown"></span>
						</p>
						<ul class="completed_test_holder">
						</ul>
					</div>
				</div>
			</div>
		</div>
		<div class="two columns">&nbsp;</div>
	</div>
	<div class="percentageComplete"></div>
</body>
</html>
<?php
}
