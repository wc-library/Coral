<?php

    include_once 'directory.php';


    $year = $_POST['year'];
    if (!$year) $year = date('Y');
    $resourceTypeID = $_POST['resourceTypeID'];
    $acquisitionTypeID = $_POST['acquisitionTypeID'];
    $orderTypeID = $_POST['orderTypeID'];
    $subjectID = $_POST['subjectID'];
    $costDetailsID = $_POST['costDetailsID'];
    $groupBy = $_POST['groupBy'];

    $dashboard = new Dashboard();
    $query = $dashboard->getQuery($resourceTypeID, $year, $acquisitionTypeID, $orderTypeID, $subjectID, $costDetailsID, $groupBy);
    $results = $dashboard->getResults($query);
    $total = 0;

    echo "<table id='dashboard_table' class='dataTable' style='width:840px'>";
    echo "<thead><tr>";
    echo "<th>" . _("Resource ID") . "</th>";
    echo "<th>" . _("Name") . "</th>";
    echo "<th>" . _("Resource Type") . "</th>";
    echo "<th>" . _("Subject") . "</th>";
    echo "<th>" . _("Acquisition Type") . "</th>";
//    echo "<th>" . _("Order Type") . "</th>";
//    echo "<th>" . _("Cost Details") . "</th>";
    echo "<th>" . _("Payment amount") . "</th>";
    echo "</tr></thead>";
    echo "<tbody>";
    foreach ($results as $result) {
        $total += $result['paymentAmount'];
        echo "<tr>";
        echo "<td>" . $result['resourceID'] . "</td>";
        echo "<td>" . $result['titleText'] . "</td>";
        echo "<td>" . $result['resourceType'] . "</td>";
        $subject = $result['generalSubject'] && $result['detailedSubject'] ? 
            $result['generalSubject'] . " / " . $result['detailedSubject'] : 
            $result['generalSubject'] . $result['detailedSubject'];
        echo "<td>" . $subject . "</td>";
        echo "<td>" . $result['acquisitionType'] . "</td>";
//        echo "<td>" . $result['orderType'] . "</td>";
//        echo "<td>" . $result['costDetails'] . "</td>";
        echo "<td>" . integer_to_cost($result['paymentAmount']) . "</td>";
        echo "</tr>";
    }
    echo "<tfoot><tr><td colspan='5'>" . _("Total") . "</td><td>" . integer_to_cost($total) . "</td></tr></tfoot>";
    echo "</tbody>";
    echo "</table>";

?>
