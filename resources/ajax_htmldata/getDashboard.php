<?php
    $year = $_POST['year'];
    if (!$year) $year = date('Y');
    $resourceTypeID = $_POST['resourceTypeID'];
    $acquisitionTypeID = $_POST['acquisitionTypeID'];
    $orderTypeID = $_POST['orderTypeID'];
    $subjectID = $_POST['subjectID'];

    echo "params: year $year resourceTypeID $resourceTypeID <br />";

    function getStats($resourceTypeID, $year, $acquisitionTypeID, $orderTypeID, $subjectID) {
        $resource = new Resource();
        $query = "SELECT R.resourceID, R.titleText, SUM(RP.paymentAmount) as paymentAmount
                     FROM Resource R 
                    LEFT JOIN ResourcePayment RP ON RP.resourceID = R.resourceID";

        if ($subjectID) {
            $query .= " LEFT JOIN ResourceSubject RS ON RS.resourceID = R.resourceID
                    LEFT JOIN GeneralDetailSubjectLink GDSL ON GDSL.generalDetailSubjectLinkID = RS.generalDetailSubjectLinkID";
        }
        $query .= " WHERE RP.year = $year";

        if ($resourceTypeID) $query .= " AND R.resourceTypeID = $resourceTypeID";
        if ($acquisitionTypeID) $query .= " AND R.acquisitionTypeID = $acquisitionTypeID";
        if ($orderTypeID) $query .= " AND RP.orderTypeID = $orderTypeID";
        if ($subjectID) {
            if (substr($subjectID, 0, 1) == "d") {
                $query .= " AND GDSL.detailedSubjectID = " . substr($subjectID, 1);
            } else {
                $query .= " AND GDSL.generalSubjectID = $subjectID";
            }
        }
        $query .= " GROUP BY resourceID";
        $result = $resource->db->processQuery($query, 'assoc');
        echo $query;
        if (isset($result['resourceID'])) { $result = [$result]; }
        return $result;
    }

//    $criteria = array("resourceType" => 1);
    $results = getStats($resourceTypeID, $year, $acquisitionTypeID, $orderTypeID, $subjectID);

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
