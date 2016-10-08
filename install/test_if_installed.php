<?php
function continue_installing()
{
	$rin = "installation_root";
	$namespace = [
		"root_installation" => $rin,
		"install_anyway" => $rin . "_do_install_anyway",
		"option_button" => $rin . "_option_button",
		"already_installed" => $rin . "_do_already_installed"
	];

	require_once "install/test_results_yielder.php";
	if (!isset($_SESSION[$namespace["install_anyway"]]) || (isset($_SESSION[$namespace["install_anyway"]]) && $_SESSION[$namespace["install_anyway"]] !== true))
	{
		$option_button_set = [
			[ "name" => "install_anyway", "title" => _("Install CORAL") ],
			[ "name" => "already_installed", "title" => _("CORAL Is Already Installed") ],
		];
		if ((isset($_POST[$namespace["option_button"]]) && $_POST[$namespace["option_button"]] == "already_installed") || (isset($_SESSION[$namespace["already_installed"]]) && $_SESSION[$namespace["already_installed"]]))
		{
			$_SESSION[$namespace["already_installed"]] = true;
			upgradeToUnifiedInstaller($namespace);
			return false; //i.e. do not continue installing
		}
		elseif (isset($_POST[$namespace["option_button"]]) && $_POST[$namespace["option_button"]] == "install_anyway")
		{
			$_SESSION[$namespace["install_anyway"]] = true;
		}
		else // ((isset($_POST[$namespace["option_button"]]) && $_POST[$namespace["option_button"]] !== "install_anyway") || !isset($_POST[$namespace["option_button"]]))
		{
			$possible_modules_with_conf_files = [ "auth", "licensing", "management", "organizations", "reports", "resources", "usage" ];
			$maybe_installed = array_reduce($possible_modules_with_conf_files, function($carry, $item) {
				return $carry || file_exists($item . "/admin/configuration.ini");
			});
			if ($maybe_installed)
			{
				$allowed_options = function($allowed_array) use ($option_button_set) {
					return array_filter($option_button_set, function($item) use ($allowed_array) {
						return in_array($item["name"], $allowed_array);
					});
				};

				$yield = new stdClass();
				$yield->messages = [];
				$yield->title = _("CORAL Pre-Installation Check");

				$instruction = "";
				$option_buttons = [];

				// Installed not with unified installer
				// OR not installed
				$yield->messages[] = _("We cannot tell whether or not CORAL is installed. Either it is not installed or it was installed using another installer.");
				$yield->messages[] = _("If CORAL is already installed you should <b>NOT</b> try to install.");
				$instruction = _("Please choose one of the options below:");
				$option_buttons = $allowed_options(["already_installed", "install_anyway"]);

				require_once "install/templates/option_buttons_template.php";
				$yield->body = option_buttons_template($instruction, $option_buttons, $namespace["root_installation"]);
				yield_test_results_and_exit($yield, [], 0);
			}
			// else falls through to return true (i.e. "continue installing")
		}
	}
	return true;
}

function upgradeToUnifiedInstaller($namespace)
{
	// These can be hard coded because they are all the modules that could exist pre-unified installer
	$fields = [
		[ "uid" => "ui_upgrade_auth", "title" => "Auth", "required" => false, "module_name" => "auth" ],
		[ "uid" => "ui_upgrade_licensing", "title" => "Licensing", "required" => false, "module_name" => "licensing" ],
		[ "uid" => "ui_upgrade_management", "title" => "Management", "required" => false, "module_name" => "management" ],
		[ "uid" => "ui_upgrade_organizations", "title" => "Organizations", "required" => false, "module_name" => "organizations" ],
		[ "uid" => "ui_upgrade_reports", "title" => "Reports", "required" => false, "module_name" => "reports" ],
		[ "uid" => "ui_upgrade_resources", "title" => "Resources", "required" => false, "module_name" => "resources" ],
		[ "uid" => "ui_upgrade_usage", "title" => "Usage", "required" => false, "module_name" => "usage" ],
	];
	if (isset($_POST["ui_upgrade_auth"]))
	{
		require_once "common/Config.php";
		$configFilePath = Config::CONFIG_FILE_PATH;

		$iniFile = file_exists($configFilePath) ? parse_ini_file($configFilePath, true) : [];
		global $INSTALLATION_VERSION;
		$iniFile["installation_details"] = ["version" => $INSTALLATION_VERSION];

		foreach ($fields as $field)
		{
			$iniFile[$field["module_name"]] = [
				"installed" => isset($_POST[$field["uid"]]) ? ($_POST[$field["uid"]] ? "Y" : "N") : "N",
				"enabled" => isset($_POST[$field["uid"]]) ? ($_POST[$field["uid"]] ? "Y" : "N") : "N"
			];
		}


		$file = @fopen($configFilePath, 'w');
		if ($file)
		{
			$dataToWrite = [];
			foreach ($iniFile as $key => $value)
			{
				$dataToWrite[] = "[$key]";
				foreach ($value as $k => $v)
				{
					$escaped_value = addslashes($v);
					$dataToWrite[] = "$k = \"$escaped_value\"";
				}
				$dataToWrite[] = "";
			}
			fwrite($file, implode("\r\n",$dataToWrite));
			fclose($file);
			$_SESSION[$namespace["already_installed"]] = false;
			return true;
		}
		else
		{
			$yield = new stdClass();
			$yield = new stdClass();
			$yield->messages[] = sprintf(_("In order to proceed with the installation, we must be able to write to the main configuration file at '<span class=\"highlight\">%s</span>'. Try:"), $configFilePath);
			if (!file_exists($configFilePath))
			{
				$yield->messages[] = sprintf("<span class=\"highlight\">touch %s</span>", $configFilePath);
			}
			$yield->messages[] = sprintf("<span class=\"highlight\">chmod 777 %s</span>", $configFilePath);
			require_once "install/templates/try_again_template.php";
			$yield->body = try_again_template();
			yield_test_results_and_exit($yield, [], 0);
		}
	}
	else
	{
		require_once "install/templates/modules_to_use_template.php";
		$yield = new stdClass();
		$yield->title = _("Select Installed Modules");
		$yield->messages = [];
		$yield->body = modules_to_use_template($fields, _("Please select the modules that you have installed."));
		yield_test_results_and_exit($yield, [], 0);
	}
}
