<?php
		$resourceID = $_GET['resourceID'];
		$resource = new Resource(new NamedArguments(array('primaryKey' => $resourceID)));

		try {
			$resource->removeResource();
			echo _("Resource successfully deleted.");
		} catch (Exception $e) {
            http_response_code(404);
            echo _("Resource not found. Error: ".$e->getMessage());
		}
?>
