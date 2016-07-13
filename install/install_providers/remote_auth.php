<?php
function register_remote_auth_provider()
{
	$MODULE_VARS = [
		"uid" => "remote_auth",
		"translatable_title" => _("Remote Auth"),
		"hide_from_completion_list" => true
	];
	return array_merge( $MODULE_VARS,[
		"installer" => function($shared_module_info) use ($MODULE_VARS) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->success = false;
			$return->yield->messages = [];
			$return->yield->title = _("Installation Variable Set");

			$field = [
				"uid" => "remote_auth_variable",
				"title" => _("Remote Auth Variable"),
				"default_value" => ""
			];
			$buildFormYield = function() use ($field) {
				require_once "install/templates/text_field_template.php";
				$instruction = "";
				return text_field_template($field, $instruction);
			};
			$isValid = function($remoteAuthVariableName)
			{
				$messages = [];
				//make sure variable name has matched number of ', otherwise it will bomb the program
				if((substr_count($remoteAuthVariableName, "'") % 2)!==0){
					$messages[] = 'Make sure Remote Auth Variable Name has matched single quotes';
				}
				// Conf files strike me as pretty fragile. The code used to
				// replace double quotes with single quotes but that seems like
				// a bad approach, rather fail and make the user fix it.
				if(substr_count($remoteAuthVariableName, "\"") !==0){
					$messages[] = 'Please replace double quotes with single quotes because of limitations in our conf files.';
				}
				return count($messages) > 0 ? $messages : true;
			};


			if (isset($_POST["remote_auth_variable"]))
				$_SESSION["remote_auth_variable"] = $_POST["remote_auth_variable"];

			if (!isset($_SESSION["remote_auth_variable"]))
			{
				$return->yield->body = $buildFormYield();
			}
			elseif ($isValid($_SESSION["remote_auth_variable"]) !== true)
			{
				$return->yield->body = $buildFormYield();
				$return->yield->messages = array_merge($return->yield->messages, $isValid($_SESSION["remote_auth_variable"]));
			}
			else
			{
				$shared_module_info["setSharedModuleInfo"]( $MODULE_VARS["uid"], "remote_auth_variable", $_SESSION["remote_auth_variable"]);
				$return->success = true;
			}

			return $return;
		}
	]);
}
