<?php
	include_once 'directory.php';
	$pageTitle=_('Dashboard');
	include 'templates/header.php';

    function getResourceTypesAsDropdown($currentID = null) {
        $display = array();
        $resourceType = new ResourceType();
        echo '<select name="resourceTypeID" id="resourceTypeID" style="width:150px;">';
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
        echo '<select name="acquisitionTypeID" id="acquisitionTypeID" style="width:150px;">';
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
        echo '<select name="orderTypeID" id="orderTypeID" style="width:150px;">';
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

        echo '<select name="subjectID" id="subjectID" style="width:150px;">';
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

    function getCostDetailsAsDropdown($currentID = null) {
        $display = array();
        $costDetails = new CostDetails();
        echo '<select name="costDetailsID" id="costDetailsID" style="width:150px;">';
        echo "<option value=''>All</option>";
        foreach($costDetails->allAsArray() as $display) {
            if ($display['costDetailsID'] == $current) {
                echo "<option value='" . $display['costDetailsID'] . "' selected>" . $display['shortName'] . "</option>";
            } else {
                echo "<option value='" . $display['costDetailsID'] . "'>" . $display['shortName'] . "</option>";
            }
        }
        echo '</select>';

    }

?>
<script type="text/javascript" src="js/dashboard.js"></script>
<div id="dashboardPage"><h1><?php echo _("Dashboard");?></h1>
<div style='text-align:left;'>
<table class="headerTable" style="background-image:url('images/header.gif');background-repeat:no-repeat;">
<tr style='vertical-align:top;'>
<td style="width:155px;padding-right:10px;">
<table class='noBorder' id='title-search'>
	<tr><td style='text-align:left;width:75px;' align='left'>

	<table class='borderedFormTable' style="width:150px">

	<tr>
	<td class='searchRow'>
    <form action="dashboard_export.php" method="POST">
    <label for="resourceTypeID"><b><?php echo _("Resource type"); ?>:</b></label><br />
    <?php getResourceTypesAsDropdown(); ?><br />
    </td>
	</tr>
	<tr>
	<td class='searchRow'>
    <label for="subjectID"><b><?php echo _("Subject"); ?>:</b></label><br />
    <?php getSubjectsAsDropdown(); ?><br />
    </td>
	</tr>
	<tr>
	<td class='searchRow'>
    <label for="acquisitionTypeID"><b><?php echo _("Acquisition type"); ?>:</b></label><br />
    <?php getAcquisitionTypesAsDropdown(); ?><br />
    </td>
	</tr>
	<tr>
	<td class='searchRow'>
    <label for="orderTypeID"><b><?php echo _("Order Type"); ?>:</b></label><br />
    <?php getOrderTypesAsDropdown(); ?>
    </td>
	</tr>
	<tr>
	<td class='searchRow'>
    <label for="costDetailsID"><b><?php echo _("Cost Details"); ?>:</b></label><br />
    <?php getCostDetailsAsDropdown(); ?>
    </td>
	</tr>
	<tr>
	<td class='searchRow'>
    <label for="startYear"><b><?php echo _("Year (start)"); ?>:</b></label><br /><input type="text" name="startYear" id="startYear" size="4" value="<?php echo date('Y');?>" /><br />
    <label for="endYear"><b><?php echo _("Year (end)"); ?>:</b></label><br /><input type="text" name="endYear" id="endYear" size="4" value="<?php echo date('Y');?>" />
    </td>
	</tr>
	<tr>
	<td class='searchRow'>
    <input type="button" id="submitDashboard" value="<?php echo _("Display"); ?>" />
    <input type="hidden" name="csv" value="1" />
    <input type="submit" id="getDashboardCSV" value="<?php echo _("Export"); ?>" />
    </form>
    </td></tr>
    </table>
    </div>
 </td>
<td>
<div id="dashboardTable" />
</td></tr>
</table>
</div>

