<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$packageId = filter_input(INPUT_GET, 'packageId', FILTER_SANITIZE_STRING);
$vendorId = filter_input(INPUT_GET, 'vendorId', FILTER_SANITIZE_STRING);
$selected = filter_input(INPUT_GET, 'selected', FILTER_VALIDATE_BOOLEAN);

if(!isset($packageId) || !isset($vendorId)){
    echo '<p>Missing Package or Vendor ID</p>';
    exit;
}

$ebscoKb = EbscoKbService::getInstance();
$ebscoKb->setPackage($vendorId, $packageId, $selected);




