<?php

	include_once 'directory.php';
	include_once 'util.php';


    $year = $_POST['year'];
    if (!$year) $year = date('Y');
    $resourceTypeID = $_POST['resourceTypeID'];
    $acquisitionTypeID = $_POST['acquisitionTypeID'];
    $orderTypeID = $_POST['orderTypeID'];
    $subjectID = $_POST['subjectID'];
    $csv = $_POST['csv'];

    $dashboard = new Dashboard();
    $query = $dashboard->getQuery($resourceTypeID, $year, $acquisitionTypeID, $orderTypeID, $subjectID);
    $results = $dashboard->getResults($query);

	echo "<table id='dashboard_table' class='dataTable' style='width:840px'>";
	echo "<thead><tr>";
	echo "<th>" . _("Resource ID") . "</th><th>" . _("Name") . "</th><th>" . _("Payment amount") . "</th>";
	echo "</tr></thead>";
	echo "<tbody>";
	foreach ($results as $result) {
		echo "<tr>";
		echo "<td>" . $result['resourceID'] . "</td>";
		echo "<td>" . $result['titleText'] . "</td>";
		echo "<td>" . integer_to_cost($result['paymentAmount']) . "</td>";
		echo "</tr>";
	}
	echo "</tbody>";
	echo "</table>";

?>
