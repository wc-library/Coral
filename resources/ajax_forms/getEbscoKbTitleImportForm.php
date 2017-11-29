<?php

$titleId = $_GET['titleId'];

if(empty($titleId)){
    echo '<p>No title ID provided</p>';
    exit;
}

$ebscoKb = EbscoKbService::getInstance();
$title = $ebscoKb->getTitle($titleId);

//get all acquisition types for output in drop down
$acquisitionTypeArray = array();
$acquisitionTypeObj = new AcquisitionType();
$acquisitionTypeArray = $acquisitionTypeObj->sortedArray();

//get all resource formats for output in drop down
$resourceFormatArray = array();
$resourceFormatObj = new ResourceFormat();
$resourceFormatArray = $resourceFormatObj->sortedArray();

// organizations
$resource = new Resource();
$orgArray = $resource->getOrganizationArray();
if (count($orgArray)>0){
    foreach ($orgArray as $org){
        $providerText = $org['organization'];
        $orgID = $org['organizationID'];
    }
}else{
    $providerText = $resource->providerText;
    $orgID = '';
}
?>
<div id="div_ebscoKbTitleImportForm">
    <h1><?php echo $title->titleName; ?></h1>
    <form id="ebscoKbTitleImportForm">
        <input type="hidden" name="organizationID" value="<?php echo $orgID; ?>" />
        <input type="hidden" name="importType" value="title" />
        <input type="hidden" name="titleId" value="<?php echo $title->titleId; ?>"/>
		<div class="formTitle" style="width:745px;">
            <span class="headerText"><?php  echo _('Import from EBSCO Kb'); ?></span>
        </div>
        <div class="smallDarkRedText" style="height:14px;margin:3px 0px 0px 0px;">&nbsp;* <?php echo _("required fields");?></div>

		<table class="noBorder">
		    <tr style="vertical-align:top;">
		        <td style="vertical-align:top; padding-right:35px;">
			        <span class="surroundBoxTitle">&nbsp;&nbsp;<b><?php echo _("Provider");?></b>&nbsp;&nbsp;</span>
                    <table class="surroundBox" style="width:350px;">
                        <tr>
                            <td>
				                <table class="noBorder" style="width:310px; margin:5px 15px;">
                                    <tr>
                                        <td>
                                            <p class="text-danger"><small>Note: Matching EbscoKb provider not found.</small></p>
                                            <label for="providerText"><?php echo _('Provider'); ?></label>
                                            <input type="text" id="providerText" style="width:220px;" class="changeInput" value="" />
                                            <span id="span_error_providerText" class="smallDarkRedText"></span>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

			        <span class="surroundBoxTitle">&nbsp;&nbsp;<label for="resourceFormatID"><b><?php echo _("Format");?></b></label>&nbsp;<span class="bigDarkRedText">*</span>&nbsp;&nbsp;</span>
                    <table class="surroundBox" style="width:350px;">
                        <tr>
                            <td style="padding: 10px;">
                                <span id="span_error_resourceFormatID" class="smallDarkRedText"></span>
                                <br />
                                <?php foreach ($resourceFormatArray as $resourceFormat): ?>
                                    <label for="resourceFormat<?php echo $resourceFormat["resourceFormatID"]; ?>" style="margin-right: 10px;">
                                        <input
                                            type="radio"
                                            name="resourceFormatID"
                                            id="resourceFormat<?php echo $resourceFormat["resourceFormatID"]; ?>"
                                            value="<?php echo $resourceFormat["resourceFormatID"]; ?>"
                                            <?php if (strtoupper($resourceFormat["shortName"]) == "ELECTRONIC"){ echo 'checked'; }?>/>
                                        <?php echo $resourceFormat['shortName']; ?>
                                    </label>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    </table>
                </td>
		        <td>
                    <span class="surroundBoxTitle">&nbsp;&nbsp;<b><?php echo _("Acquisition Type");?></b>&nbsp;<span class="bigDarkRedText">*</span>&nbsp;&nbsp;</span>
                    <table class="surroundBox" style="width:350px;">
                        <tr>
                            <td style="padding: 10px;">
                                <span id="span_error_acquisitionTypeID" class="smallDarkRedText"></span>
                                <br />
                                <?php foreach ($acquisitionTypeArray as $acquisitionType): ?>
                                    <label for="acquisitionType<?php echo $acquisitionType["acquisitionTypeID"]; ?>" style="margin-right: 10px;">
                                        <input type="radio"
                                               name="acquisitionTypeID"
                                               id="acquisitionType<?php echo $acquisitionType["acquisitionTypeID"]; ?>"
                                               value="<?php echo $acquisitionType["acquisitionTypeID"]; ?>"
                                            <?php if (strtoupper($acquisitionType["shortName"]) == "PAID"){ echo 'checked'; }?>/>
                                        <?php echo $acquisitionType["shortName"]; ?>
                                    </label>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    </table>

			        <span class="surroundBoxTitle">&nbsp;&nbsp;<label for="resourceFormatID"><b><?php echo _("Notes");?></b></label>&nbsp;&nbsp;</span>
                    <table class="surroundBox" style="width:350px;">
                        <tr>
                            <td>
                                <table class="noBorder smallPadding" style="width:320px; margin:7px 15px;">
                                    <tr>
                                        <td style="vertical-align:top;text-align:left;">
                                            <span class="smallGreyText"><?php echo _("Include any additional information");?></span>
                                            <br />
                                            <textarea rows="5" id="noteText" name="noteText" style="width:310px"></textarea>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
		        </td>
		    </tr>
		</table>
    </form>
</div>

<hr style="width:745px;margin:15px 0px 10px 0px;" />

<table class="noBorderTable" style="width:175px;">
    <tr>
        <td style="text-align:left">
            <button class="btn btn-primary" onclick="processEbscoKbImport('save','#ebscoKbTitleImportForm')">
                <?php echo _("save");?>
            </button>
        </td>
        <td style="text-align:left">
            <button class="btn btn-primary" style="margin-left: 8px;" onclick="processEbscoKbImport('progress','#ebscoKbTitleImportForm')">
                <?php echo _("submit");?>
            </button>
        </td>
        <td style="text-align:left">
            <button class="btn btn-primary" style="margin-left: 8px;" onclick="tb_remove()"><?php echo _("cancel");?></button>
        </td>
    </tr>
</table>
<script type="text/javascript" src="js/forms/importEbscoKbForm.js?random=<?php echo rand(); ?>"></script>

