<?php
include_once '../../directory.php';

$obj = new Resource();

$query = "SELECT resourceID from Resource";
$results = $obj->db->processQuery($query, 'assoc');

$fields = array('resourceID', 'orderNumber', 'systemNumber', 'acquisitionTypeID', 'subscriptionAlertEnabledInd', 'authenticationTypeID', 'authenticationUserName', 'authenticationPassword', 'accessMethodID', 'storageLocationID', 'userLimitID', 'coverageText', 'bibSourceURL', 'catalogingTypeID', 'catalogingStatusID', 'numberRecordsAvailable', 'numberRecordsLoaded', 'recordSetIdentifier', 'hasOclcHoldings', 'workflowRestartDate', 'workflowRestartLoginID');

$tables = array('ResourcePurchaseSiteLink', 'ResourcePayment', 'ResourceAdministeringSiteLink', 'ResourceAuthorizedSiteLink', 'Attachment', 'Contact', 'ResourceLicenseLink', 'ResourceLicenseStatus', 'ResourceStep'); 

function prefix($n) {
    return "Resource." . $n;
}

$entityTables = array('Downtime', 'IssueRelationship');

    echo "INSERT INTO ResourceAcquisition (" . join($fields, ",\n") . ",subscriptionStartDate, subscriptionEndDate) SELECT " . join(array_map("prefix", $fields), ",\n") . ",NOW(),NOW() FROM Resource;\n";

foreach ($tables as $table) {
        $query = "UPDATE $table LEFT JOIN ResourceAcquisition ON $table.resourceAcquisitionID = ResourceAcquisition.resourceID SET $table.resourceAcquisitionID = ResourceAcquisition.resourceAcquisitionID;\n";
        echo $query;
}

foreach ($entityTables as $table) {
        $query = "UPDATE $table LEFT JOIN ResourceAcquisition ON $table.entityID = ResourceAcquisition.resourceID SET $table.resourceAcquisitionID = ResourceAcquisition.resourceAcquisitionID;\n";
        echo $query;
}

$tabNames = array('CATALOGING', 'ACCESS', 'ACQUISITIONS');
foreach ($tabNames as $tabName) {
    $query = "UPDATE ResourceNote LEFT JOIN ResourceAcquisition ON ResourceNote.entityID = ResourceAcquisition.resourceID SET ResourceNote.entityID = ResourceAcquisition.resourceAcquisitionID WHERE UPPER(tabName) = '$tabName';\n";
    echo $query;
}

die();
?>
