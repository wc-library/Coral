<?php
		if ($_GET['resourceID']){
			$resourceID = $_GET['resourceID'];
            $action = $_GET['actionOnWorkflow'];
            $workflowID = $_GET['workflow'];
            $deleteWorkflow = $_GET['deleteWorkflow'];
			$resource = new Resource(new NamedArguments(array('primaryKey' => $resourceID)));

			//log who set off the restart
			$resource->workflowRestartLoginID = $loginID;
			$resource->workflowRestartDate = date( 'Y-m-d' );

			try {
				$resource->save();
                if ($deleteWorkflow == "true") {
                    $resource->deleteWorkflow();
                } else {
                     $resource->completeWorkflow();
                     $resource->archiveWorkflow();
                }

                if ($workflowID == "completed") {
                    $resource->enterNewWorkflow();
                } else {
                    $resource->enterNewWorkflow($workflowID);
                }
			} catch (Exception $e) {
				echo $e->getMessage();
			}


		}
?>
