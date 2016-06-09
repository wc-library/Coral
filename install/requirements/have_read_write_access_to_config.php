<?php
function register_have_read_write_access_to_config_requirement()
{
	return [
		"uid" => "have_read_write_access_to_config",
		"translatable_title" => _("Config File Access"),
		"dependencies_array" => ["meets_system_requirements"],
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


			// If file exists, see if it's writable - otherwise see if directory is writable (we can create it)
			array_unshift($config_files, [ "path" => Config::CONFIG_FILE_PATH, "key" => "core_configuration"]);
			foreach ($config_files as $cfg) {
				$file_exists = file_exists($cfg["path"]);
				$writable_test = $cfg["path"];
				$writable_test = $file_exists ? $cfg["path"] : dirname($cfg["path"]);

				if (is_writable($writable_test))
				{
					if (is_readable($cfg["path"]) || !$file_exists)
					{
						continue; // Success!
					}
					else
					{
						$return->yield->messages[] = sprintf( _("In order to proceed with the installation, we must be able to read the '%s' configuration file at '<span class=\"highlight\">%s</span>'."), $cfg["key"], $cfg["path"] );
						$return->success = false;
					}
					$return->yield->messages[] = sprintf( _("We can write to the '%s' configuration file at '<span class=\"highlight\">%s</span>' but we cannot read from it."), $cfg["key"], $cfg["path"] );
					$return->success = false;
				}
				else {
					$return->yield->messages[] = sprintf( _("In order to proceed with the installation, we must be able to write to the '%s' configuration file at '<span class=\"highlight\">%s</span>'."), $cfg["key"], $cfg["path"] )
												.sprintf( "<br /><b>" . _("Try") . ":</b> <span class=\"highlight\">chmod 777 %s</span>", $writable_test );
					$return->success = false;
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
								$dataToWrite[] = "$k=$v";
							}
							$dataToWrite[] = "";
						}
						fwrite($file, implode("\n",$dataToWrite));
						fclose($file);
					}
				);
			}
			else
			{
				$return->yield->title = "<b>" . _('Current Test:') . "</b> " . _('Trying to read and write configuration files');
				$return->yield->messages[] = "<b>" . _("Be sure to reset permissions to any files you change.") . "</b>";
			}
			return $return;
		}
	];
}
