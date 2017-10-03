<?php

    include_once 'directory.php';
    include_once 'util.php';

    $year = $_POST['year'];
    if (!$year) $year = date('Y');
    $resourceTypeID = $_POST['resourceTypeID'];
    $acquisitionTypeID = $_POST['acquisitionTypeID'];
    $orderTypeID = $_POST['orderTypeID'];
    $subjectID = $_POST['subjectID'];
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

    $columnHeaders = array(
      _("Record ID"),
      _("Name"),
      _("Resource Type"),
      _("Subject"),
      _("Acquisition Type"),
      _("Payment amount"),
    );
    echo array_to_csv_row($columnHeaders);

    $dashboard = new Dashboard();
    $query = $dashboard->getQuery($resourceTypeID, $year, $acquisitionTypeID, $orderTypeID, $subjectID, $costDetailsID, $groupBy);
    $results = $dashboard->getResults($query);
    $count = sizeof($results);
    $i = 1;
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
                integer_to_cost($result['paymentAmount'])
            );
        } else {
            $dashboardValues = array($i == $count ? _('Total') : _("Sub-Total"), '', '', '', '', integer_to_cost($result['paymentAmount']));
        }
        echo array_to_csv_row($dashboardValues);
        $i++;
    }
?>
