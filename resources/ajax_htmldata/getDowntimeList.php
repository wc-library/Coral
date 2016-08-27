<?php
$resourceID = $_GET['resourceID'];
$archivedFlag = (!empty($_GET['archived']) && $_GET['archived'] == 1) ? true:false;

$resource = new Resource(new NamedArguments(array('primaryKey' => $resourceID)));
$util = new Utility();


//shared html template for organization and resource downtimes
function generateDowntimeHTML($downtime,$associatedEntities=null) {

	$html = "
	<div class=\"downtime\">";
	
	$html .= "
	  	<dl>
	  		<dt>" . _("Type:") . "</dt> 
	  		<dd>{$downtime->shortName}</dd>

	  		<dt>" . _("Downtime Start:") . "</dt> 
	  		<dd>{$downtime->startDate}</dd>
	  		<dt>" . _("Downtime Resolved:") . "</dt> 
	  		<dd>";
	if ($downtime->endDate != null) {
		$html .= $downtime->endDate;
	} else {
		$html .= "<a class=\"thickbox\" href=\"ajax_forms.php?action=getResolveDowntimeForm&height=363&width=345&modal=true&downtimeID={$downtime->downtimeID}\">Resolve</a>";
	}
	$html .= '</dd>';

	if($downtime->subjectText) {
		$html .= "
	  		<dt>" . _("Linked issue:") . "</dt> 
	  		<dd>{$downtime->subjectText}</dd>";
	}

	if ($downtime->note) {
		$html .= "
	  		<dt>" . _("Note:") . "</dt> 
	  		<dd>{$downtime->note}</dd>";
	}

	$html .= "		
		</dl>
	</div>";	
	
	return $html;
}

//display any organization level downtimes for the resource
$organizationArray = $resource->getOrganizationArray();

if (count($organizationArray) > 0) {
	echo '<h3 class="text-center">' . _("Organizational") . '</h3>';

	$downtimedOrgs = array();
	foreach ($organizationArray as $orgData) {
		if (!in_array($orgData['organizationID'],$downtimedOrgs)) {
			$organization = new Organization(new NamedArguments(array('primaryKey' => $orgData['organizationID'])));

			$orgDowntimes = $organization->getDowntime($archivedFlag);

			if(count($orgDowntimes) > 0) {
				foreach ($orgDowntimes as $downtime) {
					echo generateDowntimeHTML($downtime);
				}
			} else {
				echo "<br><p>" . _("There are no organization level downtimes.") . "</p><br>";
			}

			$orgDowntimes = null;
			$downtimedOrgs[] = $orgData['organizationID'];
		}
	}
}

//display any resource level downtimes for the resource (shows any other resources associated with the downtime, too)
$resourceDowntimes = $resource->getDowntime($archivedFlag);
echo '<h3 class="text-center">' . _("Resources") . '</h3>';
if(count($resourceDowntimes) > 0) {
	foreach ($resourceDowntimes as $downtime) {
		echo generateDowntimeHTML($downtime);
	}
} else {
	echo "<br><p>" . _("There are no resource level downtimes.") . "</p><br>";
}
?>