<?php
function register_db_tools_requirement()
{
	return [
		"uid" => "db_tools",
		"translatable_title" => _("Database Tools"),
		"dependencies_array" => [ "have_database_access" ],
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

			// $shared_module_info["setSharedModuleInfo"](
			// 	"provided",
			// 	"get_db_connection",
			// 	function($db_name) use ($dbconnection) {
			// 		$dbconnection->selectDB($db_name);
			// 		return $dbconnection;
			// 	}
			// );

			return $return;
		}
	];
}
