<?php
	include_once 'directory.php';
	$pageTitle=_('Dashboard');
	include 'templates/header.php';

    function getResourceTypesAsDropdown($currentID = null) {
        $display = array();
        $resourceType = new ResourceType();
        echo '<select name="resourceTypeID" id="resourceTypeID">';
        echo "<option value=''>All</option>";
        foreach($resourceType->getAllResourceType() as $display) {
            if ($display['resourceTypeID'] == $current) {
                echo "<option value='" . $display['resourceTypeID'] . "' selected>" . $display['shortName'] . "</option>";
            } else {
                echo "<option value='" . $display['resourceTypeID'] . "'>" . $display['shortName'] . "</option>";
            }
        }
        echo '</select>';
    }

    function getAcquisitionTypesAsDropdown($currentID = null) {
        $display = array();
        $acquisitionType = new AcquisitionType();
        echo '<select name="acquisitionTypeID" id="acquisitionTypeID">';
        echo "<option value=''>All</option>";
        foreach($acquisitionType->allAsArray() as $display) {
            if ($display['acquisitionTypeID'] == $current) {
                echo "<option value='" . $display['acquisitionTypeID'] . "' selected>" . $display['shortName'] . "</option>";
            } else {
                echo "<option value='" . $display['acquisitionTypeID'] . "'>" . $display['shortName'] . "</option>";
            }
        }
        echo '</select>';
    }

    function getOrderTypesAsDropdown($currentID = null) {
        $display = array();
        $orderType = new OrderType();
        echo '<select name="orderTypeID" id="orderTypeID">';
        echo "<option value=''>All</option>";
        foreach($orderType->getAllOrderType() as $display) {
            if ($display['orderTypeID'] == $current) {
                echo "<option value='" . $display['orderTypeID'] . "' selected>" . $display['shortName'] . "</option>";
            } else {
                echo "<option value='" . $display['orderTypeID'] . "'>" . $display['shortName'] . "</option>";
            }
        }
        echo '</select>';
    }
    
    function getSubjectsAsDropdown($currentID = null) {
        $generalSubject = new GeneralSubject();
        $generalSubjectArray = $generalSubject->allAsArray();

        $detailedSubject = new DetailedSubject();
        $detailedSubjectArray = $detailedSubject->allAsArray();

        echo '<select name="subjectID" id="subjectID">';
        echo "<option value=''>All</option>";
        foreach($generalSubjectArray as $ug) {
            $generalSubject = new GeneralSubject(new NamedArguments(array('primaryKey' => $ug['generalSubjectID'])));
            echo "<option value='" . $ug['generalSubjectID'] . "'>" . $ug['shortName'] . "</option>";
            foreach ($generalSubject->getDetailedSubjects() as $ds){
                echo "<option value='d" . $ds->detailedSubjectID . "'> -- " . $ds->shortName . "</option>";
            }
        }
        echo '</select>';
    }

?>
<script type="text/javascript" src="js/dashboard.js"></script>
<div id="dashboardPage"><h1><?php echo _("Dashboard");?></h1>
<label for="resourceTypeID">Resource type:</label>
<?php getResourceTypesAsDropdown(); ?><br />
<label for="subjectID">Subject:</label>
<?php getSubjectsAsDropdown(); ?><br />
<label for="acquisitionTypeID">Acquisition type:</label>
<?php getAcquisitionTypesAsDropdown(); ?><br />
<label for="year">Year: </label><input type="text" id="year" size="4" value="<?php echo date('Y');?>" />
<label for="orderTypeID">Order Type:</label>
<?php getOrderTypesAsDropdown(); ?>
<input type="submit" id="submitDashboard" />
<br /><br />
<div id="dashboardTable" />
