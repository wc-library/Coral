<?php

    include_once 'directory.php';


    $startYear = $_POST['startYear'];
    if (!$startYear) $startYear = date('Y');

    $endYear = $_POST['endYear'];
    if (!$endYear) $endYear = date('Y');

    $resourceTypeID = $_POST['resourceTypeID'];
    $acquisitionTypeID = $_POST['acquisitionTypeID'];
    $orderTypeID = $_POST['orderTypeID'];
    $subjectID = $_POST['subjectID'];
    $costDetailsID = $_POST['costDetailsID'];
    $csv = $_POST['csv'];

    $dashboard = new Dashboard();
    $query = $dashboard->getQueryYearlyCosts($resourceTypeID, $startYear, $endYear, $acquisitionTypeID, $orderTypeID, $subjectID, $costDetailsID);
    $results = $dashboard->getResults($query);
    $costDetails = new CostDetails();
    $costDetailsArray = $costDetails->allAsArray();

    echo "<table id='dashboard_table' class='dataTable' style='width:840px'>";
    echo "<thead><tr>";
    echo "<th>" . _("Name") . "</th>";
    echo "<th>" . _("Resource Type") . "</th>";
    echo "<th>" . _("Subject") . "</th>";
    echo "<th>" . _("Acquisition Type") . "</th>";
    for ($i = $startYear; $i <= $endYear; $i++) {
        foreach ($costDetailsArray as $costDetail) {
            echo "<th>" . $costDetail['shortName'] . " / $i</th>";
        }
    }
    echo "</tr></thead>";
    echo "<tbody>";
    foreach ($results as $result) {
        if ($result['resourceID'] != null) {
            echo "<tr>";
            echo '<td><a href="resource.php?resourceID=' . $result['resourceID'] . '">' . $result['titleText'] . "</a></td>";
            echo "<td>" . $result['resourceType'] . "</td>";
            $subject = $result['generalSubject'] && $result['detailedSubject'] ? 
                $result['generalSubject'] . " / " . $result['detailedSubject'] : 
                $result['generalSubject'] . $result['detailedSubject'];
            echo "<td>" . $subject . "</td>";
            echo "<td>" . $result['acquisitionType'] . "</td>";
            for ($i = $startYear; $i <= $endYear; $i++) {
                foreach ($costDetailsArray as $costDetail) {
                    echo "<td>" . integer_to_cost($result[$costDetail['shortName'] . " / $i"]) . "</td>";
                }
            }
            echo "</tr>";
        } else {
            echo "<tfoot><tr><td colspan='4'>" . _("Total") . "</td>";
            for ($i = $startYear; $i <= $endYear; $i++) {
                foreach ($costDetailsArray as $costDetail) {
                    echo "<td>" . integer_to_cost($result[$costDetail['shortName'] . " / $i"]) . "</td>";
                }
            }
            echo "</tr></tfoot>";
        }
    }
    echo "</tbody>";
    echo "</table>";

?>
