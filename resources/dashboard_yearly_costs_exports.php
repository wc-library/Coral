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
    $groupBy = $_POST['groupBy'];
    $csv = $_POST['csv'];

    function escape_csv($value) {
      // replace \n with \r\n
      $value = preg_replace("/(?<!\r)\n/", "\r\n", $value);
      // escape quotes
      $value = str_replace('"', '""', $value);
      return '"'.$value.'"';
    }

    function array_to_csv_row($array) {
      $escaped_array = array_map("escape_csv", $array);
      return implode(",",$escaped_array)."\r\n";
    }

    $replace = array("/", "-");
    $excelfile = "dashboard_export_" . str_replace( $replace, "_", format_date( date( 'Y-m-d' ) ) ).".csv";

    header("Pragma: public");
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=\"" . $excelfile . "\"");

    $filters = array();
    if ($orderTypeID) {
        $orderType = new OrderType(new NamedArguments(array('primaryKey' => $orderTypeID)));
        $filters[] = _("Order Type") . ": " . $orderType->shortName;
    }
    if ($costDetailsID) {
        $costDetails = new CostDetails(new NamedArguments(array('primaryKey' => $costDetailsID)));
        $filters[] = _("Cost Details") . ": " . $costDetails->shortName;
    }

    echo "Dashboard Yearly Costs Export " . date('Y-m-d') . "\r\n";
    echo "Filters on payments: ";
    if (count($filters) > 0) {
        echo join(" / ", $filters) . "\r\n";
    } else {
        echo "none\r\n";
    }

    $costDetails = new CostDetails();
    $costDetailsArray = $costDetails->allAsArray();

    $columnHeaders = array(
      _("Record ID"),
      _("Name"),
      _("Resource Type"),
      _("Subject"),
      _("Acquisition Type"),
    );
    for ($i = $startYear; $i <= $endYear; $i++) {
        foreach ($costDetailsArray as $costDetail) {
            $columnHeaders[] = $costDetail['shortName'] . " / $i";
        }
    }
    echo array_to_csv_row($columnHeaders);

    $dashboard = new Dashboard();
    $query = $dashboard->getQueryYearlyCosts($resourceTypeID, $startYear, $endYear, $acquisitionTypeID, $orderTypeID, $subjectID, $costDetailsID, $groupBy);
    $results = $dashboard->getResults($query);
    $count = sizeof($results);
    $currentCount = 1;
    foreach ($results as $result) {
        $subject = $result['generalSubject'] && $result['detailedSubject'] ? 
            $result['generalSubject'] . " / " . $result['detailedSubject'] : 
            $result['generalSubject'] . $result['detailedSubject'];

        if ($result['resourceID'] != null) {
            $dashboardValues = array(
                $result['resourceID'],
                $result['titleText'],
                $result['resourceType'],
                $subject,
                $result['acquisitionType'],
            );
        } else {
            $dashboardValues = array($currentCount == $count ? _('Total') : _("Sub-Total"), '', '', '', '');
        }
        for ($i = $startYear; $i <= $endYear; $i++) {
            foreach ($costDetailsArray as $costDetail) {
                $dashboardValues[] =  integer_to_cost($result[$costDetail['shortName'] . " / $i"]);
            }
        }

        echo array_to_csv_row($dashboardValues);
        $currentCount++;
    }
?>
