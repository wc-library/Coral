<?php

error_reporting(E_ERROR);
ini_set('display_errors', 1);

function pprint($string){
    echo '<pre>';
    echo print_r($string);
    echo '</pre>';
    exit;
}

$fName = 'usage/sushistore/ebsco_DB1_2018-01-01_2018-02-28.xml';


$serviceProvider = 'Ebsco';
$overwritePlatform = TRUE;
$xmlFileName = $fName;

//read layouts ini file to get the available layouts
$layoutsArray = parse_ini_file("usage/layouts.ini", true);
$layoutColumns = array();

// Create the xml object

if (!file_exists($xmlFileName)) {
    echo 'file does not exist';
    exit;
    // $this->logStatus("Failed trying to open XML File: " . $xmlFileName . ".  This could be due to not having write access to the /sushistore/ directory.");
    // $this->saveLogAndExit($reportLayout);
}

$string = file_get_contents($fName);
$clean_xml = str_ireplace(['s:','SOAP-ENV:','SOAP:'],'',$string);
$xml = simplexml_load_string($clean_xml);
//pprint($xml);


$layoutCode = "";
$txtOut = "";
$startDateArr = explode("-", '2018-01-01');
$endDateArr = explode("-", '2018-02-28');
$startYear = $startDateArr[0];
$startMonth = $startDateArr[1];
$endYear = $endDateArr[0];
$endMonth = $endDateArr[1];
$numMonths = 0;
if ($startMonth > $endMonth)
    $numMonths = (13 - ($startMonth - $endMonth));
else if ($endMonth > $startMonth)
    $numMonths = ($endMonth - $startMonth);
else
    $numMonths = 1;
$m = null; //month

//First - get report information
$report = $xml->Body->ReportResponse->Report->Report;
$reportTypeName = $report->attributes()->Name;
$version = $report->attributes()->Version;

$layoutCode = $reportTypeName;

if (($version == "3") || ($version =="4")){
    $version = "R" . $version;
}

if ($version != ''){
    $layoutCode .= "_" . $version;
} else {
    $layoutCode .= "_R" . $this->releaseNumber;
}

//At this point, determine the format of the report to port to csv from the layouts.ini file
$layoutKey = $layoutsArray['ReportTypes'][$layoutCode];
$layoutColumns = $layoutsArray[$layoutKey]['columns'];

if (count($layoutColumns) == 0 || $layoutCode == ''){
    echo 'report failed';
    //$this->logStatus("Failed determining layout:  Reached report items before establishing layout.  Please make sure this layout is set up in layouts.ini");
    //$this->saveLogAndExit($reportLayout);
}

foreach($report->Customer->ReportItems as $resource) {
    //reset variables
    /**
     * Each $reportArray is slightly different
     * JR1: Need aggregated count columns of ytd, ytdPDF, ytdHTML
     * BR1: Need aggregated count columns of ytd
     * DB1: Need separate rows based on activity type, but aggregated counts for those activity types
     */
    $identifierArray=array();
    $reportArray = array();
    $baseStats = array('ytd' => 0, 'ytdPDF' => 0, 'ytdHTML' => 0);
    $statRows = array();

    if ($overwritePlatform){
        $reportArray['platform'] = $serviceProvider;
    }else{
        $reportArray['platform'] = $resource->ItemPlatform[0];
    }

    $reportArray['publisher'] = $resource->ItemPublisher;
    $reportArray['title'] = $resource->ItemName;
    foreach($resource->ItemIdentifier as $identifier) {
        $idType = strtoupper($identifier->Type);
        $identifierArray[$idType] = $identifier->Value;
    }

    foreach($resource->ItemPerformance as $monthlyStat) {
        $date = new DateTime($monthlyStat->Period->Begin);
        $m = strtolower($date->format('M'));
        if ($reportTypeName == 'DB1') {
            foreach($monthlyStat->Instance as $metricStat) {
                $type = $metricStat->MetricType->__toString();
                if(empty($statRows[$type])){
                    $statRows[$type] = $baseStats;
                }
                $count = intval($metricStat->Count);
                $statRows[$type][$m] = $count;
                $statRows[$type]['activityType'] = $metricStat->MetricType;
                $statRows[$type]['ytd'] += $count;
            }
        } else {
            if (empty($statRows)) {
                $statRows[0] = $baseStats;
            }
            $monthlyTotal = 0;
            $pdfTotal = 0;
            $htmlTotal = 0;
            foreach ($monthlyStat->Instance as $metricStat) {
                $count = intval($metricStat->Count);
                if ($metricStat->MetricType == 'ft_total') {
                    $monthlyTotal = $count;
                }
                if (stripos($metricStat->MetricType, 'pdf')) {
                    $pdfTotal = $count;
                }
                if (stripos($metricStat->MetricType, 'html')) {
                    $htmlTotal = $count;
                }
            }
            $monthlyTotal = $monthlyTotal == 0 ? $pdfTotal + $htmlTotal : $monthlyTotal;
            $statRows[0][$m] = $monthlyTotal;
            $statRows[0]['ytd'] += $monthlyTotal;
            $statRows[0]['ytdPDF'] += $pdfTotal;
            $statRows[0]['ytdHTML'] += $htmlTotal;
        }
    }

    foreach($identifierArray as $key => $value){
        if (!(strrpos($key,'PRINT') === false) && !(strrpos($key,'ISSN') === false)){
            $reportArray['issn'] = $value;
        }else if (!(strrpos($key,'ONLINE') === false) && !(strrpos($key,'ISSN') === false)){
            $reportArray['eissn'] = $value;
        }else if (!(strpos($key,'PRINT') === false) && !(strpos($key,'ISBN') === false)){
            $reportArray['isbn'] = $value;
        }else if (!(strpos($key,'ONLINE') === false) && !(strpos($key,'ISBN') === false)){
            $reportArray['eisbn'] = $value;
        }else if (!(strpos($key,'DOI') === false)){
            $reportArray['doi'] = $value;
        }else if (!(strpos($key,'PROPRIETARY') === false)){
            $reportArray['pi']=$value;
        }
    }

    //Now look at the report's layoutcode's columns to order them properly
    foreach($statRows as $row) {
        $reportRow = array_merge($reportArray, $row);
        $finalArray=array();
        foreach($layoutColumns as $colName){
            if (isset($reportRow[$colName]))
                $finalArray[] = $reportRow[$colName];
            else
                $finalArray[] = null;
        }
        $txtOut .= implode($finalArray,"\t") . "\n";
    }
}

echo '<pre>';
echo $txtOut;
echo '</pre>';