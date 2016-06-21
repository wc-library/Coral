<?php
function register_have_read_write_access_to_config_requirement()
{
	return [
		"uid" => "have_read_write_access_to_config",
		"translatable_title" => _("Config File Access"),
		"dependencies_array" => ["meets_system_requirements", "have_default_db_user"],
		"required" => true,
		"installer" => function($shared_module_info) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->success = false;

			require_once "install/templates/try_again_template.php";
			$return->yield->body = try_again_template();

			require_once("common/Config.php");

			$return->yield->title = "<b>" . _('Current Test:') . "</b> " . _('Trying to write configuration file');

			$return->success = true;

			$modules_with_config_file_requirements = array_filter($shared_module_info, function($item){
				return is_array($item) && isset($item["config_file"]);
			});
			$config_files = array_map(function($key, $item) {
				$to_return = $item["config_file"];
				$to_return["key"] = $key;
				return $to_return;
			}, array_keys($modules_with_config_file_requirements), $modules_with_config_file_requirements);

			$fileACCESS = [
				"FULL_ACCESS" => 0, "NOT_READABLE" => 1, "NOT_WRITABLE" => 2, "DOESNT_EXIST" => 3
			];
			$testFileAccess = function($path) use ($fileACCESS) {
				if (!file_exists($path))
					return $fileACCESS["DOESNT_EXIST"];
				if (!is_readable($path))
					return $fileACCESS["NOT_READABLE"];
				if (!is_writable($path))
					return $fileACCESS["NOT_WRITABLE"];
				return $fileACCESS["FULL_ACCESS"];
			};

			// If file exists, see if it's writable - otherwise see if directory is writable (we can create it)
			array_unshift($config_files, [ "path" => Config::CONFIG_FILE_PATH, "key" => "core_configuration"]);
			foreach ($config_files as $cfg) {
				$writable_test = file_exists($cfg["path"]) ? $cfg["path"] : dirname($cfg["path"]);
				switch ($testFileAccess($writable_test)) {
					case $fileACCESS["NOT_WRITABLE"]:
						$return->yield->messages[] = sprintf( _("In order to proceed with the installation, we must be able to write to the '%s' configuration file at '<span class=\"highlight\">%s</span>'."), $cfg["key"], $cfg["path"] )
													.sprintf( "<br /><b>" . _("Try") . ":</b> <span class=\"highlight\">chmod 777 %s</span>", $writable_test );
						$return->success = false;
						break;
					case $fileACCESS["NOT_READABLE"]:
					case $fileACCESS["DOESNT_EXIST"]:
						$return->yield->messages[] = sprintf( _("In order to proceed with the installation, we must be able to read the '%s' configuration file at '<span class=\"highlight\">%s</span>'."), $cfg["key"], $cfg["path"] );
						$return->success = false;
						break;
				}
			}

			if ($return->success)
			{
				$shared_module_info["setSharedModuleInfo"](
					"provided",
					"write_config_file",
					function($path, $settingsObject){
						$file = fopen($path, 'w');
						foreach ($settingsObject as $key => $value) {
							$dataToWrite[] = "[$key]";
							foreach ($value as $k => $v) {
								//TODO: test with other problem variables - I think slashes are also going to cause mayhem...
								// (just added to addcslashes but somehow we ended up with a lot of slashes!!!!!!!!!!!!!!!!!!!!!!)
								//slash out double quotes only for ini
								$escaped_value = addcslashes($v, '"\\');
								$dataToWrite[] = "$k = \"$escaped_value\"";
							}
							$dataToWrite[] = "";
						}
						fwrite($file, implode("\r\n",$dataToWrite));
						fclose($file);
					}
				);
				$shared_module_info["registerPostInstallationTest"]([
					"uid" => "check_config_files_protected",
					"translatable_title" => sprintf(_("Check %s Has Access"), $default_db_username),
					"installer" => function($shared_module_info) use ($config_files, $testFileAccess, $fileACCESS) {
						$return = new stdClass();
						$return->yield = new stdClass();
						$return->success = true;
						$return->yield->messages = [];
						$return->yield->title = _("Check Config Files are Protected");

						foreach ($config_files as $cfg) {
							//check the config file's parent directory
							switch ($testFileAccess(dirname($cfg["path"])))
							{
								case $fileACCESS["FULL_ACCESS"]:
									$return->yield->messages[] = _("It is unsafe to leave your admin directories writable.")
																.sprintf( "<br /><b>" . _("Try") . ":</b> <span class=\"highlight\">chmod 755 %s</span>", dirname($cfg["path"]) );
									$return->success = false;
									continue 2;
								case $fileACCESS["NOT_READABLE"]:
									$return->yield->messages[] = _("CORAL will need to access your config files but it appears that some are not readable.")
																.sprintf( "<br /><b>" . _("Try") . ":</b> <span class=\"highlight\">chmod 755 %s</span>", dirname($cfg["path"]) );
									$return->success = false;
									continue 2;
								case $fileACCESS["DOESNT_EXIST"]:
									//weird that it was asked for and not created but whatever
									break;
								case $fileACCESS["NOT_WRITABLE"]:
									break;
							}
							//check the config file itself
							switch ($testFileAccess($cfg["path"])) {
								case $fileACCESS["FULL_ACCESS"]:
									$return->yield->messages[] = _("It is unsafe to leave your config files writable.")
																.sprintf( "<br /><b>" . _("Try") . ":</b> <span class=\"highlight\">chmod 644 %s</span>", $cfg["path"] );
									$return->success = false;
									continue 2;
								case $fileACCESS["NOT_READABLE"]:
									$return->yield->messages[] = _("CORAL will need to access your config files but it appears that some are not readable.")
																.sprintf( "<br /><b>" . _("Try") . ":</b> <span class=\"highlight\">chmod 644 %s</span>", $cfg["path"] );
									$return->success = false;
									continue 2;
								case $fileACCESS["DOESNT_EXIST"]:
									//weird that it was asked for and not created but whatever
									break;
								case $fileACCESS["NOT_WRITABLE"]:
									break;
							}
						}
						return $return;
					}
				]);
			}
			else
			{
				$return->yield->title = "<b>" . _('Current Test:') . "</b> " . _('Trying to read and write configuration files');
				$return->yield->messages[] = "<b>" . _("Be sure to reset permissions to any files you change.") . "</b>";
				//TODO: register post installation check to ensure that these have been reset.
			}
			return $return;
		}
	];
}
