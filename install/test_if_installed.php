<?php
function continue_installing()
{
	require_once "install/test_results_yielder.php";
	$root_installation_namespace = "installation_root";
	if (!isset($_SESSION[$root_installation_namespace . "_do_install_anyway"]) || (isset($_SESSION[$root_installation_namespace . "_do_install_anyway"]) && $_SESSION[$root_installation_namespace . "_do_install_anyway"] !== true))
	{
		require_once "common/Config.php";
		$common_config = parse_ini_file(Config::CONFIG_FILE_PATH, true);
		$old_version = isset($common_config["installation_details"]["version"]) ? $common_config["installation_details"]["version"] : false;

		if ((isset($_POST[$root_installation_namespace . "_option_button"]) && $_POST[$root_installation_namespace . "_option_button"] !== "install_anyway") || !isset($_POST[$root_installation_namespace . "_option_button"]))
		{
			$option_button_set = [
				[ "name" => "go_to_root", "title" => _("Take Me Home"), "custom_javascript" => 'window.location.href="index.php";' ],
				//TODO: fix upgrade path
				[ "name" => "try_upgrade", "title" => _("Try To Upgrade"), "custom_javascript" => 'window.location.href="upgrade.php";' ],
				[ "name" => "install_anyway", "title" => _("Install CORAL") ],
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
				$yield->messages[] = _("We cannot tell whether or not CORAL is not installed. Either it is not installed or it was installed using another installer.");
				$instruction = _("If CORAL is already installed you should <b>NOT</b> continue. Perhaps you meant to <span class='highlight'>Upgrade</span> or go to the <span class='highlight'>Home</span> page.<br />If you are sure that CORAL is not installed then feel free to install.");
				$option_buttons = $allowed_options(["go_to_root", "try_upgrade", "install_anyway"]);
			}
			elseif (version_compare(INSTALLATION_VERSION, $old_version) > 0)
			{
				// This installer installs a newer version
				$instruction = _("This installer installs a newer version of CORAL than the one currently installed. This is <b>highly discouraged</b> and will probably result in the loss of data. Instead you should try to upgrade.");
				$option_buttons = $allowed_options(["go_to_root", "try_upgrade", "install_anyway"]);
			}
			else if (version_compare(INSTALLATION_VERSION, $old_version) === 0)
			{
				// Already installed and current version
				$instruction = _("You already have the current version installed. Are you looking for the home page?");
				$option_buttons = $allowed_options(["go_to_root"]);
			}
			else if (version_compare(INSTALLATION_VERSION, $old_version) < 0)
			{
				// Apparently the already installed version is newer than this installer
				$instruction = _("The installed version of CORAL is newer than the version this installer would try to install. Are you looking for the home page or perhaps trying to upgrade?");
				$option_buttons = $allowed_options(["go_to_root", "try_upgrade"]);
			}
			require_once "install/templates/option_buttons_template.php";
			$yield->body = option_buttons_template($instruction, $option_buttons, $root_installation_namespace);
			yield_test_results_and_exit($yield, [], 0);
		}
		elseif (isset($_POST[$root_installation_namespace . "_option_button"]) && $_POST[$root_installation_namespace . "_option_button"] == "install_anyway")
		{
			$_SESSION[$root_installation_namespace . "_do_install_anyway"] = true;
		}
	}
	return true;
}
