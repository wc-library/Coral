<?php
function register_have_default_db_user_requirement()
{
	$MODULE_VARS = [
		"uid" => "have_default_db_user",
		"translatable_title" => _("Default Database User Configured"),
		"dependencies_array" => ["have_database_access"],
		"hide_from_completion_list" => true,
		"required" => true
	];

	return array_merge( $MODULE_VARS, [
		"installer" => function($shared_module_info) use ($MODULE_VARS) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->success = false;
			$return->yield->title = _("Configure Default Database User");

			$generate_password = function($length)
			{
				$password = '';
				while (strlen($password) < $length)
					$password .= chr(rand(33, 126));
				return htmlspecialchars($password);
			};
			if (!isset($_SESSION[ $MODULE_VARS["uid"] ]["userdetails"]))
				$_SESSION[ $MODULE_VARS["uid"] ]["userdetails"] = [];
			if (!isset($_SESSION[ $MODULE_VARS["uid"] ]["userdetails"]["username"]))
				$_SESSION[ $MODULE_VARS["uid"] ]["userdetails"]["username"] = "";
			if (!isset($_SESSION[ $MODULE_VARS["uid"] ]["userdetails"]["password"]))
				$_SESSION[ $MODULE_VARS["uid"] ]["userdetails"]["password"] = "";

			$default_username = !empty($_SESSION[ $MODULE_VARS["uid"] ]["userdetails"]) ? $_SESSION[ $MODULE_VARS["uid"] ]["userdetails"]["username"] : "";
			$default_password = !empty($_SESSION[ $MODULE_VARS["uid"] ]["userdetails"]) ? $_SESSION[ $MODULE_VARS["uid"] ]["userdetails"]["password"] : "";
			$default_username = !empty($_POST["default_db_username"]) ? $_POST["default_db_username"] : $default_username;
			$default_password = !empty($_POST["default_db_password"]) ? $_POST["default_db_password"] : $default_password;

			require_once "common/DBService.php";
			$default_username = DBService::escapeString($default_username);
			$default_password = DBService::escapeString($default_password);

			$_SESSION[ $MODULE_VARS["uid"] ]["userdetails"]["username"] = $default_username;
			$_SESSION[ $MODULE_VARS["uid"] ]["userdetails"]["password"] = $default_password;

			if (empty($_SESSION[ $MODULE_VARS["uid"] ]["userdetails"]["username"]) || empty($_SESSION[ $MODULE_VARS["uid"] ]["userdetails"]["password"]))
			{
				$fields = [
					"username" => [
						"uid" => "default_db_username",
						"title" => "Regular Database Username",
						"default_value" => empty($default_username) ? "coral_regular_user" : $default_username
					],
					"password" => [
						"uid" => "default_db_password",
						"title" => "Regular Database Password",
						"default_value" => empty($default_password) ? $generate_password(12) : $default_password
					]
				];
				$instruction = _("During installation and updates Coral needs more privileges to the database than during regular use. "
					. "If Coral has the rights, it will automatically set up a user with appropriate privileges based on these details. "
					. "Otherwise you will need to grant SELECT, INSERT, UPDATE and DELETE to this user on all the coral databases used in this install."
				);

				require "install/templates/have_default_db_user_template.php";
				$return->yield->body = have_default_db_user_template($instruction, $fields);
				return $return;
			}
			else
			{
				$default_db_username = $_SESSION[ $MODULE_VARS["uid"] ]["userdetails"]["username"];
				$default_db_password = $_SESSION[ $MODULE_VARS["uid"] ]["userdetails"]["password"];
				$modules_with_database_requirements = array_filter($shared_module_info, function($item){
					return is_array($item) && isset($item["database"]);
				});
				foreach (array_keys($modules_with_database_requirements) as $mod)
				{
					$db_details = [
						"dbname" => $shared_module_info["provided"]["get_db_connection"]($dbname),
						"host" => Config::dbInfo('host'),
						"username" => $default_db_username,
						"password" => $default_db_password
					];
					$dbname = $shared_module_info[$mod]["db_name"];
					try
					{
						$db->processQuery("GRANT SELECT, INSERT, UPDATE, DELETE ON {$db_details["dbname"]}.* TO {$db_details["username"]}@{$db_details["host"]} IDENTIFIED BY '{$db_details["password"]}'");
					}
					catch (Exception $e)
					{
						$PARENT_MODULE_VARS = $MODULE_VARS;
						$shared_module_info["registerPostInstallationTest"]([
							"uid" => "check_user_has_access_to_{$db_details["dbname"]}",
							"translatable_title" => sprintf(_("Check %s Has Access"), $default_db_username),
							"installer" => function($shared_module_info) use ($db_details) {
								$return = new stdClass();
								$return->yield = new stdClass();
								$return->success = true;
								$return->yield->title = _("Check DB User Has Access");

								$db_conn = @new mysqli($db_details["host"], $db_details["username"], $db_details["password"], $db_details["dbname"]);
								if ($db_conn->connect_errno)
								{
									$return->yield->messages[] = sprintf(_('<b>DB Access:</b> User "%s" does not have access to database "%s" (you will need to manually grant permissions). Try:'), $db_details["username"], $db_details["dbname"])
									 . "<br />GRANT SELECT, INSERT, UPDATE, DELETE ON {$db_details["dbname"]}.* TO {$db_details["username"]}@{$db_details["host"]} IDENTIFIED BY '{$db_details["password"]}'";
									$return->success = false;
								}
								return $return;
							}
						]);
					}
				}
				$shared_module_info["setSharedModuleInfo"]($MODULE_VARS["uid"], "username", $default_db_username);
				$shared_module_info["setSharedModuleInfo"]($MODULE_VARS["uid"], "password", $default_db_password);
				$return->success = true;
			}
			return $return;
		}
	]);
}
