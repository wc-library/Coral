<?php
if (is_numeric($_POST['downtimeID'])) {
	$downtime = new Downtime(new NamedArguments(array('primaryKey' => $_POST['downtimeID'])));

	$downtime->endDate = ($_POST['endDate']) ?  date('Y-m-d H:i:s', strtotime($_POST['endDate']." ".$_POST['endTime']['hour'].":".$_POST['endTime']['minute'].$_POST['endTime']['meridian'])):null;

	$downtime->note = ($_POST['note']) ? $_POST['note']:null;

	$downtime->save();
}
?>