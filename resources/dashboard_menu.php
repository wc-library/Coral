<?php
	include_once 'directory.php';
	$pageTitle=_('Dashboards');
	include 'templates/header.php';

?>
<h1><?php echo _("Dashboards"); ?></h1>
<a href="dashboard.php"><img src="../images/icon-usage.png" /><br /><?php echo _("Statistics"); ?></a><br /><br />
<a href="dashboard_yearly_costs.php"><img src="../images/icon-usage.png" /><br /><?php echo _("Yearly costs"); ?></a><br />
