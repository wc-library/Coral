<?php
require 'vendor/autoload.php';
$server = "http://coral.local/resources/api/";
$user = $_SERVER['REMOTE_USER'] ? $_SERVER['REMOTE_USER'] : 'API';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />
<link rel="stylesheet" href="pure-min.css">
</head>
<h1>Simple Resources module API client</h1>
<h2>Propose a resource</h2>
<?php
if ($_POST['submitProposeResourceForm']) {
    $fieldNames = array("user", "titleText", "descriptionText", "providerText", "resourceURL", "resourceAltURL", "noteText", "resourceTypeID", "resourceFormatID", "acquisitionTypeID", "administeringSiteID", "homeLocationNote", "licenseRequired", "existingLicense", "publicationYear", "edition", "holdLocation", "patronHold", "CMRanking", "subjectCoverage", "audience", "frequency", "access", "contributingFactors", "ripCode", "fund", "cost");
    $headers = array("Accept" => "application/json");
    $body = array();
    foreach ($fieldNames as $fieldName) {
        $body[$fieldName] = $_POST[$fieldName];
    }
    $response = Unirest\Request::post($server . "proposeResource/", $headers, $body);
    if ($response->body->resourceID) {
        echo "<p>The resource was correctly submitted (resource " . $response->body->resourceID . ")</p>";
    } else {
        echo "<p>The resource could not be submitted. (error: " . $response->body->error . ")</p>";
    }
    echo '<a href="index.php">Submit another resource</a>';
} else {
  // Checking if the API is up
  $response = Unirest\Request::get($server . "version/", $headers, $body);
  if ($response->code != 200) {
      if ($response->code == 403) {
        echo "<p>You are not authorized to use this service.</p>";
        echo $response->body;
      }
      if ($response->code == 500) {
        echo "<p>This service encountered an error.</p>";
      }
  } else {
?>
<form name="proposeResourceForm" action="index.php" method="POST" class="pure-form pure-form-aligned" style="margin:50px">
<fieldset>
<legend>Product</legend>
<div class="pure-control-group">
<label for="titleText">Title: </label><input name="titleText" type="text" /><br />
</div>
<div class="pure-control-group">
<label for="descriptionText">Description: </label><textarea name="descriptionText"></textarea><br />
</div>
<div class="pure-control-group">
<label for="providerText">Provider: </label><input name="providerText" type="text" /><br />
</div>
<div class="pure-control-group">
<label for="resourceURL">URL: </label><input name="resourceURL" type="text" /><br />
</div>
<div class="pure-control-group">
<label for="resourceAltURL">URL Alt: </label><input name="resourceAltURL" type="text" /><br />
</div>
<div class="pure-control-group">
<label for="publicationYear">Publication year or subscription start date: </label><input name="publicationYear" type="text" /><br />
</div>
<div class="pure-control-group">
<label for="edition">Edition: </label><input name="edition" type="text" /><br />
</div>
<div class="pure-control-group">
<label for="holdLocation">Hold location (patron pickup library for item held)</label><input name="holdLocation" type="text" /><br />
</div>
<div class="pure-control-group">
<label for="patronHold">Patron hold (patrons' name, email)</label><input name="patronHold" type="text" /><br />
</div>
<div class="pure-control-group">
<label for="ripCode">RIP code (serials)</label><input name="ripCode" type="text" /><br />
</div>
<div class="pure-control-group">
<label for="fund">Fund</label><input name="fund" type="text" /><br />
</div>
<div class="pure-control-group">
<label for="cost">Cost</label><input name="cost" type="text" /><br />
</div>
</fieldset>

<fieldset>
<legend>Format</legend>
<?php getResourceFormatsAsDropdown($server); ?>
</fieldset>

<fieldset>
<legend>Acquisition Type</legend>
<?php getAcquisitionTypesAsRadio($server); ?>
</fieldset>

<fieldset>
<legend>Resource Type</legend>
<?php getResourceTypesAsDropdown($server); ?>
</fieldset>

<fieldset>
<legend>Library</legend>
<?php getAdministeringSitesAsCheckBoxes($server); ?>
</fieldset>

<fieldset>
<legend>License required?</legend>
<input type="radio" name="licenseRequired" value="Yes" />Yes
<input type="radio" name="licenseRequired" value="No" />No
<input type="radio" name="licenseRequired" value="Don't know" checked="checked" />Don't know
</fieldset>

<fieldset>
<legend>Existing license?</legend>
<input type="radio" name="existingLicense" value="Yes" />Yes
<input type="radio" name="existingLicense" value="No" />No
<input type="radio" name="existingLicense" value="Don't know" checked="checked" />Don't know
</fieldset>



<fieldset>
<legend>Home Location</legend>
<select name="homeLocationNote">
<option value="Stacks">Stacks</option>
<option value="References">References</option>
<option value="Reserves">Reserves</option>
<option value="Online">Online</option>
<option value="Teach DVD">Teach DVD</option>
<option value="Circulating DVD">Circulating DVD</option>
<option value="Media (Branch)">Media (Branch)</option>
<option value="Other">Other (please specify it in Notes)</option>
</select>
</fieldset>

<fieldset>
<legend>Notes</legend>
<label for="noteText">Include any additional information</label>
<textarea name="noteText"></textarea><br />
</fieldset>

<h2>The following fields are for collection managers' decision use.</h2>
<fieldset>
<legend>CM ranking</legend>
<select name="CMRanking">
<option value="1">High</option>
<option value="2">Medium</option>
<option value="3">Low</option>
</select>

<div class="pure-control-group">
<label for="subjectCoverage">Subject coverage: </label><input name="subjectCoverage" type="text" /><br />
</div>

<div class="pure-control-group">
<label for="audience">Audience: </label><input name="audience" type="text" /><br />
</div>

<div class="pure-control-group">
<label for="frequency">Frequency and language: </label><input name="frequency" type="text" /><br />
</div>

<div class="pure-control-group">
<label for="access">Access via indexes: </label><input name="access" type="text" /><br />
</div>

<div class="pure-control-group">
<label for="contributingFactors">Contributing factors: </label><input name="contributingFactors" type="text" /><br />
</div>

</fieldset>

<input type="hidden" name="user" value="<?php echo $user; ?>">

<a href="javascript:window.print();">Print view</a> - 
<input type="submit" name="submitProposeResourceForm" />
</form>
<?php
}
}

function getResourceTypesAsDropdown($server) {
    $response = Unirest\Request::post($server . "getResourceTypes/", $headers, $body);
    echo '<select name="resourceTypeID">';
    foreach ($response->body as $resourceType) {
        echo ' <option value="' . $resourceType->resourceTypeID  . '">' . $resourceType->shortName . "</option>";
    }
    echo '</select>';
}

function getAcquisitionTypesAsRadio($server) {
    $response = Unirest\Request::post($server . "getAcquisitionTypes/", $headers, $body);
    foreach ($response->body as $resourceType) {
        echo ' <input type="radio" name="acquisitionTypeID" value="' . $resourceType->acquisitionTypeID . '">' . $resourceType->shortName;
    }
}

function getResourceFormatsAsDropdown($server) {
    $response = Unirest\Request::post($server . "getResourceFormats/", $headers, $body);
    echo '<select name="resourceFormatID">';
    foreach ($response->body as $resourceType) {
        echo ' <option value="' . $resourceType->resourceFormatID . '">' . $resourceType->shortName . "</option>";
    }
    echo '</select>';
}

function getAdministeringSitesAsCheckBoxes($server) {
    $response = Unirest\Request::post($server . "getAdministeringSites/", $headers, $body);
    foreach ($response->body as $resourceType) {
        echo ' <input type="checkbox" name="administeringSiteID[]" value="' . $resourceType->administeringSiteID . '">' . $resourceType->shortName;
    }
}



?>
</html>
