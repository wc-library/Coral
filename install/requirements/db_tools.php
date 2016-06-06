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
					foreach ($sql_file_array as $sql_file)
					{
						if (isset($_SESSION[$muid]["sql_files"][$sql_file]) &&
							$_SESSION[$muid]["sql_files"][$sql_file])
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
				function($db, $module_shared, $column_denoting_existence, $module_title) {
					$return = new stdClass();
					$return->yield = new stdClass();
					$return->yield->messages = [];
					$return->yield->title = sprintf(_("DB Check for %s"), $module_title);
					if ($module_shared["db_feedback"] == DBAccess::DB_ALREADY_EXISTED)
					{
						try
						{
							$query = "SELECT count(*) count FROM `information_schema`.`TABLES` WHERE `table_schema`=`{$module_shared['db_name']}` AND `table_name`=`$column_denoting_existence`";
							$result = $db->processQuery($query);
							// TODO: offer to do this (drop tables)
							if ($result->numRows() > 0)
							{
								$return->success = false;
								$return->yield->messages[] = sprintf(_('The tables for %s already exist. If you intend to upgrade, please run upgrade.php instead. If you would like to perform a fresh install you will need to manually drop all of the tables in this schema first.'), $module_title);
								require_once "install/templates/try_again_template.php";
								$return->yield->body = try_again_template();
								return $return;
							}
						}
						catch (Exception $e)
						{
							//TODO: we need to handle other possible reasons for this exception
							//TODO: this should be handled much better! if the table already existed we need to figure out more about it...
							// SOLUTION: we're going to ask if the user meant to do an update and then redirect or just use the existing db.
							// "wow, hang on - you already have tables in a database for %s"
							$return->yield->messages[] = var_export($e, 1);
							//TODO: This may indicate a halfway done installation at some point
							$return->success = false;
							$return->yield->messages[] = _("Please verify your database user has access to select from the information_schema MySQL metadata database.");
							require_once "install/templates/try_again_template.php";
							$return->yield->body = try_again_template();
							return $return;
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
