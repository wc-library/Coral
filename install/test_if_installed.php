<?php
$root_installation_namespace = "installation_root";
function continue_installing()
{
	global $root_installation_namespace;
	require_once "install/test_results_yielder.php";
	if (!isset($_SESSION[$root_installation_namespace . "_do_install_anyway"]) || (isset($_SESSION[$root_installation_namespace . "_do_install_anyway"]) && $_SESSION[$root_installation_namespace . "_do_install_anyway"] !== true))
	{
		require_once "common/Config.php";
		if (file_exists(Config::CONFIG_FILE_PATH) && is_readable(Config::CONFIG_FILE_PATH))
		{
			$common_config = parse_ini_file(Config::CONFIG_FILE_PATH, true);
			$old_version = isset($common_config["installation_details"]["version"]) ? $common_config["installation_details"]["version"] : false;
		}
		else
		{
			$old_version = false;
		}

		if ((isset($_POST[$root_installation_namespace . "_option_button"]) && $_POST[$root_installation_namespace . "_option_button"] == "already_installed") || (isset($_SESSION[$root_installation_namespace . "_do_already_installed"]) && $_SESSION[$root_installation_namespace . "_do_already_installed"]))
		{
			$_SESSION[$root_installation_namespace . "_do_already_installed"] = true;
			upgradeToUnifiedInstaller();
		}
		elseif (isset($_POST[$root_installation_namespace . "_option_button"]) && $_POST[$root_installation_namespace . "_option_button"] == "install_anyway")
		{
			$_SESSION[$root_installation_namespace . "_do_install_anyway"] = true;
		}
		else // ((isset($_POST[$root_installation_namespace . "_option_button"]) && $_POST[$root_installation_namespace . "_option_button"] !== "install_anyway") || !isset($_POST[$root_installation_namespace . "_option_button"]))
		{
			$option_button_set = [
				[ "name" => "take_me_home", "title" => _("Take Me Home"), "custom_javascript" => 'window.location.href="index.php";' ],
				//TODO: fix upgrade path
				[ "name" => "try_upgrade", "title" => _("Try To Upgrade"), "custom_javascript" => 'window.location.href="upgrade.php";' ],
				[ "name" => "install_anyway", "title" => _("Install CORAL") ],
				[ "name" => "already_installed", "title" => _("CORAL Is Already Installed") ],
			];
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

			if (!$old_version)
			{
				// Installed not with unified installer
				// OR not installed
				$yield->messages[] = _("We cannot tell whether or not CORAL is installed. Either it is not installed or it was installed using another installer.");
				$yield->messages[] = _("If CORAL is already installed you should <b>NOT</b> try to install.");
				$instruction = _("Please choose one of the options below:");
				$option_buttons = $allowed_options(["already_installed", "install_anyway"]);
			}
			elseif (version_compare(INSTALLATION_VERSION, $old_version) > 0)
			{
				// This installer installs a newer version
				$instruction = _("This installer installs a newer version of CORAL than the one currently installed. This is <b>highly discouraged</b> and will probably result in the loss of data. Instead you should try to upgrade.");
				$option_buttons = $allowed_options(["take_me_home", "try_upgrade", "install_anyway"]);
			}
			else if (version_compare(INSTALLATION_VERSION, $old_version) === 0)
			{
				// Already installed and current version
				$instruction = _("You already have the current version installed. Are you looking for the home page?");
				$option_buttons = $allowed_options(["take_me_home"]);
			}
			else if (version_compare(INSTALLATION_VERSION, $old_version) < 0)
			{
				// Apparently the already installed version is newer than this installer
				$instruction = _("The installed version of CORAL is newer than the version this installer would try to install. Are you looking for the home page or perhaps trying to upgrade?");
				$option_buttons = $allowed_options(["take_me_home", "try_upgrade"]);
			}
			require_once "install/templates/option_buttons_template.php";
			$yield->body = option_buttons_template($instruction, $option_buttons, $root_installation_namespace);
			yield_test_results_and_exit($yield, [], 0);
		}
	}
	return true;
}

function upgradeToUnifiedInstaller()
{
	global $root_installation_namespace;
	require_once "common/Config.php";
	$configFilePath = Config::CONFIG_FILE_PATH;

	$iniFile = file_exists($configFilePath) ? parse_ini_file($configFilePath, true) : [];
	$iniFile["installation_details"] = ["version" => INSTALLATION_VERSION];

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
		$_SESSION[$root_installation_namespace . "_do_already_installed"] = false;
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
