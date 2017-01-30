<?php
function register_have_read_write_access_to_config_provider()
{
	return [
		"uid" => "have_read_write_access_to_config",
		"translatable_title" => _("Config File Access"),
		"bundle" => function($version = 0) {
			return [
				"dependencies_array" => ["meets_system_requirements", "modules_to_use"],
				"function" => function($shared_module_info) {
					$return = new stdClass();
					$return->success = false;
					$return->yield = new stdClass();
					$return->yield->messages = [];

					require_once "install/templates/try_again_template.php";
					$return->yield->body = try_again_template();

					require_once("common/Config.php");

					$return->yield->title = _('Trying to Read & Write Configuration Files');

					$return->success = true;

					$config_files = [];
					foreach ($shared_module_info["modules_to_use"]["useModule"] as $key => $value) {
						if ($value &&
							!empty($shared_module_info["dependencies"][$key]) &&
							in_array("have_read_write_access_to_config", $shared_module_info["dependencies"][$key]))
						{
							if (isset($shared_module_info[$key]["config_file"]))
							{
								$cfgFile = $shared_module_info[$key]["config_file"];
								$cfgFile["key"] = $key;
								$config_files[] = $cfgFile;
							}
							else
							{
								$return->yield->messages[] = _("One of your modules is not configured correctly. Although it requires r/w access to a config file, it does not provide a config file path. Offending module: ") . "<b>$key</b>";
								$return->success = false;
							}
						}
					}
					if (!$return->success)
						return $return;

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
						if (!file_exists($cfg["path"]))
						{
							try
							{
								$try_create = @file_put_contents($cfg["path"], "", FILE_APPEND);
							}
							catch (Exception $e)
							{ }
							if (!$try_create)
							{
								try {
									@chmod(dirname($cfg["path"]), 0777);
									@file_put_contents($cfg["path"], "", FILE_APPEND);
									@chmod(dirname($cfg["path"]), 0755);
								} catch (Exception $e) {}
							}
						}

						$writable_test = file_exists($cfg["path"]) ? $cfg["path"] : dirname($cfg["path"]);
						switch ($testFileAccess($writable_test)) {
							case $fileACCESS["NOT_WRITABLE"]:
								// TODO: fix the sprintf with multiple strings here:
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

					if ($shared_module_info["isInPostInstallationMode"]())
					{
						// if we're actually in post-installation then make test successful
						// (doesn't matter any more whether we could read config files)
						$return->success = true;
					}

					if ($return->success)
					{
						$shared_module_info["setSharedModuleInfo"](
							"provided",
							"write_config_file",
							function($path, $settingsObject) use ($shared_module_info) {
								if ($shared_module_info["isInPostInstallationMode"]())
									return; //If we're in post-installation mode we don't want to change files

								$file = fopen($path, 'w');
								foreach ($settingsObject as $key => $value) {
									$dataToWrite[] = "[$key]";
									foreach ($value as $k => $v) {
										//TODO: test with other problem variables - I think slashes are also going to cause mayhem...
										// (just added to addcslashes but somehow we ended up with a lot of slashes!!!!!!!!!!!!!!!!!!!!!!)
										//slash out double quotes only for ini
										// $escaped_value = addcslashes($v, '"\\{}|&~![()^"');
										$escaped_value = addslashes($v);
										$dataToWrite[] = "$k = \"$escaped_value\"";
									}
									$dataToWrite[] = "";
								}
								fwrite($file, implode("\r\n",$dataToWrite));
								fclose($file);
							}
						);
						$shared_module_info["registerInstallationTest"]([
							"uid" => "check_config_files_not_writable",
							"translatable_title" => "Check Config Files Are Protected",
							"post_installation" => true,
							"hide_from_completion_list" => true,
							"bundle" => function($version = 0) use ($config_files, $testFileAccess, $fileACCESS) {
								return [
									"function" => function($shared_module_info) use ($config_files, $testFileAccess, $fileACCESS) {
										$return = new stdClass();
										$return->yield = new stdClass();
										$return->success = true;
										$return->yield->messages = [];
										$return->yield->title = _("Checking Config Files Are Protected");

										foreach ($config_files as $cfg) {
											//check the config file's parent directory
											switch ($testFileAccess(dirname($cfg["path"])))
											{
												case $fileACCESS["FULL_ACCESS"]:
													if (decoct(fileperms(dirname($cfg["path"])) & 0777) == "755")
														break;
													if (@chmod(dirname($cfg["path"]), 0755))
														break;
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
													if (decoct(fileperms(dirname($cfg["path"])) & 0777) == "644")
														break;
													if (@chmod($cfg["path"], 0644))
														break;
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
										if (!$return->success)
										{
											require_once "install/templates/try_again_template.php";
											$return->yield->body = try_again_template();
										}
										return $return;
									}
								];
							}
						]);
					}
					else
					{
						$return->yield->messages[] = "<b>" . _("Be sure to reset permissions to any files you change.") . "</b>";
					}
					return $return;
				}
			];
		}
	];
}
