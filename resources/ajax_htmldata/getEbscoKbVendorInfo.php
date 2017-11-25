<?php

if(empty($_POST['vendorId'])){
    echo '<div style="margin-bottom: 2em;"><i>No vendor ID provided</i></div>';
    exit;
}


$vendorId = $_POST['vendorId'];

$ebscoKb = new EbscoKbService();
$ebscoKb->getVendor($vendorId);
$vendor = $ebscoKb->execute();
$ebscoKb->getVendor($vendorId, true);
$packages = $ebscoKb->execute();

if(!empty($vendor->Errors)){
    echo '<div style="margin-bottom: 2em;"><i>Sorry, there was an error with retrieving the vendor.</i></div>';
    echo '<ul>';
    foreach($vendor->Errors as $error){
        echo "<li>$error->Message</li>";
    }
    exit;
}

if(!empty($packages->Errors)){
    echo '<div style="margin-bottom: 2em;"><i>Sorry, there was an error with retrieving the vendor packages.</i></div>';
    echo '<ul>';
    foreach($packages->Errors as $error){
        echo "<li>$error->Message</li>";
    }
    exit;
}

$totalRecords = $data->totalResults;
switch($params['type']){
    case 'titles':
        $items = $data->titles;
        break;
    case 'vendors':
        $items = $data->vendors;
        break;
    case 'packages':
        $items = $data->packagesList;
        break;
    default:
        $items = [];
}

if(empty($data->totalResults) || empty($items)){
    echo '<div style="margin-bottom: 2em;"><i>No results found.</i></div>';
    exit;
}



// Pagination vars
$numPages = ceil($totalRecords / $recordsPerPage);
$maxDisplay = 25;
$pagination = [];
$halfMax = floor($maxDisplay/2);
$i = $page + $halfMax > $numPages ? $page - ($maxDisplay - ($numPages - $page + 1)) : $page - floor($maxDisplay/2);
while(count($pagination) <= $maxDisplay){
    if ($i > $numPages){
        break;
    }
    if($i > 0){
        $pagination[] = $i;
    }
    $i++;
}

$fromCalc = $recordsPerPage * ($page - 1) + 1;
$toCalc = ($fromCalc - 1) + $recordsPerPage;
$toCalc = $toCalc > $totalRecords ? $totalRecords : $toCalc;

?>

<span style="float:left; font-weight:bold; width:650px;">
    Displaying <?php echo $fromCalc; ?> to <?php echo $toCalc; ?> of <?php echo $totalRecords; ?> results
</span>

<?php if ($totalRecords > $recordsPerPage): ?>
    <div style="vertical-align:bottom;text-align:left;clear:both;" class="pagination">
        <?php if($page == 1): ?>
            <span class="smallerText"><i class="fa fa-backward"></i></span>
        <?php else: ?>
            <a href="javascript:void(0);" data-page="<?php echo $page - 1; ?>" class="setPage smallLink" alt="previous page" title="previous page">
                <i class='fa fa-backward'></i>
            </a>
        <?php endif; ?>


        <?php foreach($pagination as $p): ?>
            <?php if ($p == $page): ?>
                <span class="smallerText"><?php echo $p; ?></span>
            <?php else: ?>
                <a href='javascript:void(0);' data-page="<?php echo $p; ?>" class="setPage smallLink"><?php echo $p; ?></a>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($page + 1 >= $numPages): ?>
            <span class="smallerText"><i class="fa fa-forward"></i></span>
        <?php else: ?>
            <a href="javascript:void(0);" data-page="<?php echo $page+1; ?>" class="setPage smallLink" alt="next page" title="next page">
                <i class='fa fa-forward'></i>
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div style="vertical-align:bottom;text-align:left;clear:both;"></div>
<?php endif; ?>

<?php

switch($params['type']){
    case 'titles':
        include_once __DIR__.'/../templates/ebscoKbTitleList.php';
        break;
    case 'vendors':
        include_once __DIR__.'/../templates/ebscoKbVendorList.php';
        break;
    case 'packages':
        include_once __DIR__.'/../templates/ebscoKbPackageList.php';
        break;
    case 'holdings':
        echo '<pre>';
        echo print_r($items);
        echo '</pre>';
        break;
}
?>