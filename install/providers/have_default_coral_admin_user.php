<?php
function register_have_default_coral_admin_user_provider()
{
	$MODULE_VARS = [
		"uid" => "have_default_coral_admin_user",
		"translatable_title" => _("Default Coral Admin User Configured"),
		"hide_from_completion_list" => true,
	];

	return array_merge( $MODULE_VARS, [
		"bundle" => function($version = 0) use ($MODULE_VARS) {
			return [
				"function" => function($shared_module_info) use ($MODULE_VARS) {
					$return = new stdClass();
					$return->yield = new stdClass();
					$return->yield->title = _("Have default user");
					$return->success = false;

					$default_user = "coral";
					if (!empty($_POST["loginID"]))
						$_SESSION[ $MODULE_VARS["uid"] ]["loginID"] = $_POST["loginID"];
					$default_user = !empty($_SESSION[ $MODULE_VARS["uid"] ]["loginID"]) ? $_SESSION[ $MODULE_VARS["uid"] ]["loginID"] : $default_user;
					if (empty($_SESSION[ $MODULE_VARS["uid"] ]["loginID"]))
					{
						$instruction = _("Since user privileges are driven through the web, we will need to set up the first admin account to administer other users. Please enter either your CORAL Authentication Login ID (username) or your externally authenticated Login ID below.");
						$field = [
							"type" => "text",
							"uid" => "loginID",
							"title" => _("Admin Login ID (Username)"),
							"default_value" => $default_user
						];

						require "install/templates/text_field_template.php";
						$return->yield->body = text_field_template($field, $instruction);
					}
					else
					{
						// Could run checks here for what type of username is acceptable
						$return->success = true;
						$shared_module_info["setSharedModuleInfo"]($MODULE_VARS["uid"], "default_user", $_SESSION[$MODULE_VARS["uid"]]["loginID"]);
					}

					return $return;
				}
			];
		}
	]);
}
