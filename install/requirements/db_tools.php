<?php
function register_db_tools_requirement()
{
	return [
		"uid" => "db_tools",
		"translatable_title" => _("Database Tools"),
		"dependencies_array" => [ "have_database_access" ],
		"hide_from_completion_list" => true,
		"installer" => function($shared_module_info) {
			$return = new stdClass();
			$return->success = true;

			$processSql = function($db, $sql_file) {
				$ret = [
					"success" => true,
					"messages" => []
				];

				if (!file_exists($sql_file))
				{
					$ret["messages"][] = sprintf(_("Could not open sql file: %s.<br />If this file does not exist you must download new install files."), $sql_file);
					$ret["success"] = false;
				}
				else
				{
					// Run the file - checking for errors at each SQL execution
					$f = fopen($sql_file,"r");
					$sqlFile = fread($f,filesize($sql_file));
					$sqlArray = explode(";",$sqlFile);
					// Process the sql file by statements
					foreach ($sqlArray as $stmt)
					{
						if (strlen(trim($stmt))>3)
						{
							try
							{
								$db->processQuery($stmt);
							}
							catch (Exception $e)
							{
								$ret["messages"][] = $db->getError() . "<br />For statement: " . $stmt;
								$ret["success"] = false;
							}
						}
					}
				}
				return $ret;
			};
			$shared_module_info["setSharedModuleInfo"](
				"provided",
				"process_sql_files",
				function ($db, $sql_file_array, $muid) use ($processSql)
				{
					// If we are supposed to use tables,
					// we are not supposed to process sql files
					if (in_array($muid, $_SESSION["db_tools"]["use_tables"]))
						return ["success" => true];

					foreach ($sql_file_array as $sql_file)
					{
						if (isset($_SESSION["db_tools"]["sql_files"][$muid][$sql_file]) &&
							$_SESSION["db_tools"]["sql_files"][$muid][$sql_file])
							continue;

						$result = $processSql($db, $sql_file);
						if (!$result["success"])
						{
							return [ "success" => false, "messages" => $result["messages"] ];
						}
						else
						{
							$_SESSION[$muid]["sql_files"][$sql_file] = true;
						}
					}
					return [ "success" => true ];
				}
			);
			$shared_module_info["setSharedModuleInfo"](
				"provided",
				"check_db",
				function($muid, $db, $module_shared, $column_denoting_existence, $module_title) {
					$return = new stdClass();
					$return->yield = new stdClass();
					$return->yield->messages = [];
					$return->yield->title = sprintf(_("DB Check for %s"), $module_title);

					$option_button_namespace = "db_tools_check_db";
					//TODO: Fix upgrade path...
					$option_buttons = [
						[ "name" => "redirect_to_upgrade", "title" => "Whoops, I Want To Upgrade", "custom_javascript" => 'window.location.href="upgrade.php";' ],
						[ "name" => "use_tables", "title" => "Use Existing Tables" ],
						[ "name" => "drop_tables", "title" => "Delete Existing Tables" ],
						[ "name" => "check_again", "title" => "Check Again" ],
					];

					if ($module_shared["db_feedback"] == DBAccess::DB_ALREADY_EXISTED)
					{
						$doElse = true;
						if (isset($_POST[$option_button_namespace . "_option_button"]))
						{
							$doElse = false;
							switch ($_POST[$option_button_namespace . "_option_button"])
							{
								case "redirect_to_upgrade":
									header('Location: upgrade.php');
									exit;
								case "use_tables":
									// set this session variable so that other db_tools stuff doesn't mess up this data
									if (!in_array($muid, $_SESSION["db_tools"]["use_tables"]))
										$_SESSION["db_tools"]["use_tables"][] = $muid;
									$return->success = true;
									return $return;
								case "drop_tables":
									$doElse = true;
									break;
								case "check_again":
									break;
							}
							$doElse = true;
						}

						if ($doElse)
						{
							try
							{
								$query = "SELECT count(*) count FROM `information_schema`.`TABLES` WHERE `table_schema`=\"{$module_shared['db_name']}\" AND `table_name`=\"$column_denoting_existence\"";
								$result = $db->processQuery($query);
								if ($result->numRows() > 0)
								{
									// SOLUTION: we're going to ask if the user meant to do an update and then redirect or just use the existing db.
									$return->success = false;
									$instruction = sprintf(_('The tables for %s already exist. If you intend to upgrade, please run upgrade.php instead. If you would like to perform a fresh install you will need to delete all of the tables in this schema first. Alternatively, if your tables are prepopulated, you can continue the install and we will assume that they are set up correctly.'), $module_title);
									require_once "install/templates/option_buttons_template.php";
									$return->yield->body = option_buttons_template($instruction, $option_buttons, $option_button_namespace);
									return $return;
								}
							}
							catch (Exception $e)
							{
								//TODO: we could to handle other possible reasons for this exception
								$return->success = false;
								$return->yield->messages[] = _("Please verify your database user has access to select from the information_schema MySQL metadata database.");
								require_once "install/templates/try_again_template.php";
								$return->yield->body = try_again_template();
								return $return;
							}
						}
					}
					return false;
				}
			);
			$shared_module_info["setSharedModuleInfo"](
				"provided",
				"set_up_admin_in_db",
				function($db, $admin_login) {
					// $db is connected to the right db already
					//delete admin user if they exist, then set them back up with correct username
					$query = "SELECT privilegeID FROM Privilege WHERE shortName like '%admin%';";
					//we've just inserted this and there was no error - we assume selection will succeed.
					$result = $db->processQuery($query);
					$privilegeID = $result->fetchRow()[0];
					$query = "DELETE FROM User WHERE loginID = '$admin_login';";
					$db->processQuery($query);
					$query = "INSERT INTO User (loginID, privilegeID) values ('$admin_login', $privilegeID);";
					$db->processQuery($query);
				}
			);
			return $return;
		}
	];
}
