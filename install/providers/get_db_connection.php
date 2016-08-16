<?php
function register_get_db_connection_provider()
{
	return [
		"uid" => "get_db_connection",
		"translatable_title" => _("Get DB Connection"),
		"hide_from_completion_list" => true,
		"bundle" => function($version = 0) {
			return [
				"function" => function($shared_module_info) {
					$return = new stdClass();
					$return->yield = new stdClass();
					$return->success = true;
					$return->yield->messages = [];
					$return->yield->title = _("Get DB Connection");

					$shared_module_info["setSharedModuleInfo"](
						"provided",
						"get_db_connection",
						function ($db_details){
							require_once("common/Config.php");
							require_once("common/DBService.php");

							$keys = ["host", "username", "password"];
							$db_details_to_restore = false;
							if ($db_details !== false && count(array_intersect($db_details,$keys)) == 3)
							{
								try {
									$db_details_to_restore = Config::getSettingsFor("database");
								} catch (Exception $e) {
									$db_details_to_restore = false;
								}
								Config::loadTemporaryDBSettings(array_intersect($db_details,$keys));
							}

							try
							{
								$dbconnection = new DBService(isset($db_details["name"]) ? $db_details["name"] : false);
								if ($db_details_to_restore)
									Config::loadTemporaryDBSettings($db_details_to_restore);
								return $dbconnection;
							}
							catch (Exception $e)
							{
								$return = [];
								switch ($e->getCode()) {
									case DBService::ERR_ACCESS_DENIED:
										$return[] = _("Unfortunately, although we could find the database, access was denied.");
										$return[] = _("Please review your settings.");
										break;

									case DBService::ERR_COULD_NOT_CONNECT:
										$return[] = _("Unfortunately we could not connect to the host.");
										$return[] = _("Please review your settings.");
										break;

									default:
										var_dump($shared_module_info["debug"]);
										echo "We haven't prepared for the following error (have_database_access.php #1):<br />\n<pre>";
										var_dump($e);
										echo "</pre>";
										throw $e;
										break;
								}
								if ($db_details_to_restore)
									Config::loadTemporaryDBSettings($db_details_to_restore);
								return $return;
							}
						}
					);

					return $return;
				}
			];
		}
	];
}
