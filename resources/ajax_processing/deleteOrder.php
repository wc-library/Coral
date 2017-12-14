<?php
		$resourceAcquisitionID = $_GET['resourceAcquisitionID'];
		$resourceAcquisition = new ResourceAcquisition(new NamedArguments(array('primaryKey' => $resourceAcquisitionID)));

		try {
			$resourceAcquisition->removeResourceAcquisition();
			echo _("Order successfully deleted.");
		} catch (Exception $e) {
			echo $e->getMessage();
		}
?>
