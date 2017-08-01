<?php
    include_once 'directory.php';
    $pageTitle=_('Dashboard');
    include 'templates/header.php';
    $dashboard = new Dashboard();

?>
<script type="text/javascript" src="js/dashboard.js"></script>
<div id="dashboardPage"><h1><?php echo _("Dashboard: yearly costs");?></h1>
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
    <fieldset>
    <legend>Filter on resources:</legend>
    <label for="resourceTypeID"><b><?php echo _("Resource type"); ?>:</b></label><br />
    <?php $dashboard->getResourceTypesAsDropdown(); ?><br />
    </td>
    </tr>
    <tr>
    <td class='searchRow'>
    <label for="subjectID"><b><?php echo _("Subject"); ?>:</b></label><br />
    <?php $dashboard->getSubjectsAsDropdown(); ?><br />
    </td>
    </tr>
    <tr>
    <td class='searchRow'>
    <label for="acquisitionTypeID"><b><?php echo _("Acquisition type"); ?>:</b></label><br />
    <?php $dashboard->getAcquisitionTypesAsDropdown(); ?><br />
    </td>
    </tr>
    </fieldset>
    <tr>
    <td class='searchRow'>
    <fieldset>
    <legend>Filter on payments:</legend>
    <label for="orderTypeID"><b><?php echo _("Order Type"); ?>:</b></label><br />
    <?php $dashboard->getOrderTypesAsDropdown(); ?>
    </td>
    </tr>
    <tr>
    <td class='searchRow'>
    <label for="costDetailsID"><b><?php echo _("Cost Details"); ?>:</b></label><br />
    <?php $dashboard->getCostDetailsAsDropdown(); ?>
    </td>
    </tr>
    <tr>
    <td class='searchRow'>
    <label for="startYear"><b><?php echo _("Year (start)"); ?>:</b></label><br /><input type="text" name="startYear" id="startYear" size="4" value="<?php echo (date('Y') - 1); ?>" /><br />
    <label for="endYear"><b><?php echo _("Year (end)"); ?>:</b></label><br /><input type="text" name="endYear" id="endYear" size="4" value="<?php echo date('Y');?>" />
    </td>
    </tr>
    </fieldset>
    <tr>
    <td class='searchRow'>
    <input type="button" id="submitDashboardYearlyCosts" value="<?php echo _("Display"); ?>" />
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

