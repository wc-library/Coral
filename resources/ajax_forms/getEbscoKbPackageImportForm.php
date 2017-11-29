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
    <form id="ebscoKbTitleImportForm">
        <input type="hidden" id="organizationID" value="<?php echo $orgID; ?>" />
        <input type="hidden" id="importType" value="title" />
		<div class="formTitle" style="width:745px;">
            <span class="headerText"><?php  echo _('Import').' '.$title->titleName.' '._(' from EBSCO Kb'); ?></span></div>
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
                                            <label for="providerOptionImport">
                                                <input type="radio" name="providerOption" value="import" id="providerOptionImport" class="change-provider-option"> Import
                                            </label>
                                            <label for="providerOptionAlias">
                                                <input type="radio" name="providerOption" value="alias" id="providerOptionAlias" class="change-provider-option"> Alias
                                            </label>
                                            <label for="providerOptionParentChild">
                                                <input type="radio" name="providerOption" value="parentChild" id="providerOptionParentChild" class="change-provider-option"> Parent or Child
                                            </label>
                                            <label for="providerOptionOverride">
                                                <input type="radio" name="providerOption" value="override" id="providerOptionOverride" class="change-provider-option"> Override
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <div id="providerOptionImport-import">
                                            <p>Will import the following as a new provider if one with a matching name</p>
                                            <p><?php echo $title->vendorName; ?></p>
                                        </div>
                                        <div id="providerOptionAlias-alias">
                                            <p>Will add the following name as an alias to the selected provider</p>
                                            <p><?php echo $title->vendorName; ?></p>
                                            <input type="text" id="providerText" style="width:220px;" class="changeInput" value="" />
                                            <span id="span_error_providerText" class="smallDarkRedText"></span>
                                        </div>
                                        <div id="providerOptionAlias-parentChild">
                                            <p>Will import the following name as a parent or child of the selected provider</p>
                                            <p><?php echo $title->vendorName; ?></p>
                                            <label for="providerIsParent">
                                                <input type="radio" name="providerParentOrChild" value="parent" id="providerIsParent" > Parent
                                            </label>
                                            <label for="providerIsChild">
                                                <input type="radio" name="providerParentOrChild" value="child" id="providerIsChild" > Child
                                            </label>
                                        </div>
                                        <div id="providerOptionOverride-override">
                                            <p>Will not import any provider information from EBSCO Kb, using the selected provider instead</p>
                                        </div>
                                        <div id="selectProvider">
                                            <input type="text" id="providerText" style="width:220px;" class="changeInput" value="" />
                                            <span id="span_error_providerText" class="smallDarkRedText"></span>
                                        </div>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

			        <span class="surroundBoxTitle">&nbsp;&nbsp;<label for="resourceFormatID"><b><?php echo _("Format");?></b></label>&nbsp;<span class="bigDarkRedText">*</span>&nbsp;&nbsp;</span>
                    <table class="surroundBox" style="width:350px;">
                        <tr>
                            <td>
                                <span id="span_error_resourceFormatID" class="smallDarkRedText"></span>
                                <table class="noBorder" style="width:310px; margin:5px 15px;">
                                <?php foreach ($resourceFormatArray as $resourceFormat): ?>
                                    <label for="resourceFormat<?php echo $resourceFormat["resourceFormatID"]; ?>">
                                        <input
                                            type="radio"
                                            name="resourceFormatID"
                                            id="resourceFormat<?php echo $resourceFormat["resourceFormatID"]; ?>"
                                            value="<?php echo $resourceFormat["resourceFormatID"]; ?>"
                                            <?php if (strtoupper($resourceFormat["shortName"]) == "ELECTRONIC"){ echo 'checked'; }?>/>
                                    </label>
                                <?php endforeach; ?>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
		        <td>
                    <span class="surroundBoxTitle">&nbsp;&nbsp;<b><?php echo _("Acquisition Type");?></b>&nbsp;<span class="bigDarkRedText">*</span>&nbsp;&nbsp;</span>
                    <table class="surroundBox" style="width:350px;">
                        <tr>
                            <td>
                                <table class="noBorder smallPadding" style="width:310px; margin:5px 15px;">
                                    <?php foreach ($acquisitionTypeArray as $acquisitionType): ?>
                                        <label for="acquisitionType<?php echo $acquisitionType["acquisitionTypeID"]; ?>">
                                            <input type="radio"
                                                   name="acquisitionTypeID"
                                                   id="acquisitionType<?php echo $acquisitionType["acquisitionTypeID"]; ?>"
                                                   value="<?php echo $acquisitionType["acquisitionTypeID"]; ?>"
                                                <?php if (strtoupper($acquisitionType["shortName"]) == "PAID"){ echo 'checked'; }?>/>
                                            <?php echo $acquisitionType["shortName"]; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </table>
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

		<hr style="width:745px;margin:15px 0px 10px 0px;" />

		<table class="noBorderTable" style="width:175px;">
			<tr>
				<td style="text-align:left"><input type="button" value="<?php echo _("save");?>" id="save" class="submitResource save-button"></td>
				<td style="text-align:left"><input type="button" value="<?php echo _("submit");?>" id="progress" class="submitResource submit-button"></td>
				<td style="text-align:left"><input type="button" value="<?php echo _("cancel");?>" onclick="kill(); tb_remove()" class="cancel-button"></td>
			</tr>
		</table>

    </form>
</div>
<script type="text/javascript" src="js/forms/resourceNewForm.js?random=<?php echo rand(); ?>"></script>