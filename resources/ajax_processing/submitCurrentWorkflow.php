<?php

		$resourceID = $_POST['resourceID'];

        $resource = new Resource(new NamedArguments(array('primaryKey' => $resourceID)));

		try {

			$stepNameArray = array();
			$stepNameArray = explode(':::',$_POST['stepNames']);
			$userGroupArray = array();
			$userGroupArray = explode(':::',$_POST['userGroups']);
			$priorStepArray = array();
			$priorStepArray = explode(':::',$_POST['priorSteps']);
			$stepIDArray = array();
			$stepIDPriorArray = array();
			$oldStepIDArray = array();
            $oldStepIDArray = explode(':::',$_POST['stepIDs']);
			$actionArray = array();
            $actionArray = explode(':::',$_POST['actions']);

			foreach ($stepNameArray as $key => $value){
				if (trim($value)){

                    if ($actionArray[$key] == "delete") {
                        if ($oldStepIDArray[$key] != -1 ) {
                            $oldStep = new ResourceStep(new NamedArguments(array('primaryKey' => $oldStepIDArray[$key])));
                            $oldStep->delete();
                        }
                        continue;
                    } 

					$step = new ResourceStep();

                    // Getting previous information if available
                    if ($oldStepIDArray[$key] != -1) {
                        $oldStep = new ResourceStep(new NamedArguments(array('primaryKey' => $oldStepIDArray[$key])));
                        $step->stepStartDate = $oldStep->stepStartDate;
                        $step->stepEndDate = $oldStep->stepEndDate;
                        $step->displayOrderSequence = $oldStep->displayOrderSequence;
                        $step->stepID = $oldStep->stepID;
                        $step->priorStepID = $oldStep->priorStepID;
                        $oldStep->delete();
                        unset($oldStep);
                    }
                    
					$step->resourceID = $resourceID;
					$step->stepName = trim($value);
					$step->userGroupID = $userGroupArray[$key];
                    if ($step->priorStepID == null) {
                        $step->priorStepID = $priorStepArray[$key];
                    }

					try {
						$step->save();
						$stepID = $step->primaryKey;
					} catch (Exception $e) {
						echo $e->getMessage();
					}
                }
			}


		} catch (Exception $e) {
			echo $e->getMessage();
		}
?>
