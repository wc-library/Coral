<?php
/*
 * *************************************************************************************************************************
 * * CORAL Unified Installer v. 0.1.0
 * *
 * * Copyright (c) 2010 University of Notre Dame
 * *
 * * This file is part of CORAL.
 * *
 * * CORAL is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * *
 * * CORAL is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * *
 * * You should have received a copy of the GNU General Public License along with CORAL. If not, see <http://www.gnu.org/licenses/>.
 * *
 * *************************************************************************************************************************
 */

 /**
 * @author j3frea+coral@gmail.com
 */

// TODO: go through template.php and remove hard coded vars

session_start();
// INSTALLATION_VERSION should be a version number that version_compare will understand
const INSTALLATION_VERSION = "0.1.0";
const INSTALLATION_IN_PROGRESS = true;

/**
 *  All the requests that come from the template page post { "installing":true }
 *  So if it's not set, we need to draw the template for the first time.
 */
if (!isset($_POST["installing"]))
{
	// TODO: decide whether to clear the session when navigation to this page does not contain the POST var...
	// (which is always sent on installation internal requests)
	// session_start();
	// session_unset();
	require "install/templates/install_page_template.php";
	draw_install_page_template();
	exit();
}

$root_installation_namespace = "installation_root";
require "install/test_results_yielder.php";
if (!isset($_SESSION[$root_installation_namespace . "_do_install_anyway"]) || (isset($_SESSION[$root_installation_namespace . "_do_install_anyway"]) && $_SESSION[$root_installation_namespace . "_do_install_anyway"] !== true))
{
	require_once "common/Config.php";
	$common_config = parse_ini_file(Config::CONFIG_FILE_PATH, true);
	$old_version = isset($common_config["installation_details"]["version"]) ? $common_config["installation_details"]["version"] : false;
	if (!$old_version)
	{
		// Installed not with unified installer
		// OR not installed
	}
	elseif ((isset($_POST[$root_installation_namespace . "_option_button"]) && $_POST[$root_installation_namespace . "_option_button"] !== "install_anyway") || !isset($_POST[$root_installation_namespace . "_option_button"]))
	{
		$option_button_set = [
			[ "name" => "go_to_root", "title" => _("Take Me Home"), "custom_javascript" => 'window.location.href="index.php";' ],
			//TODO: fix upgrade path
			[ "name" => "try_upgrade", "title" => _("Try To Upgrade"), "custom_javascript" => 'window.location.href="upgrade.php";' ],
			[ "name" => "install_anyway", "title" => _("Install Anyway") ],
		];
		$allowed_options = function($allowed_array) use ($option_button_set) {
			return array_filter($option_button_set, function($item) use ($allowed_array) {
				return in_array($item["name"], $allowed_array);
			});
		};

		$yield = new stdClass();
		$yield->messages = [];
		$yield->title = _("CORAL Is Already Installed");

		$instruction = "";
		$option_buttons = [];

		if (version_compare(INSTALLATION_VERSION, $old_version) > 0)
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

require "install/installer.php";
$installer = new Installer();
$requirements = $installer->getCheckListUids();

foreach ($requirements as $i => $requirement) {
	if (!$installer->isRequired($requirement))
		continue;

	$testResult = $installer->runTestForResult($requirement);

	if (isset($testResult->skipped))
	{
		switch ($testResult->cause)
		{
			case Installer::CAUSE_ALREADY_EXISTED:
				continue 2; // break switch & continue foreach
				break;
			case Installer::CAUSE_DEPENDENCY_NOT_FOUND:
				$testResult->yield = new stdClass();
				$testResult->yield->messages = [ sprintf(_("Dependency for '%s' not found: %s"), $installer->getTitleFromUid($requirement), $testResult->missing_dependency) ];
				yield_test_results_and_exit($testResult->yield, $installer->getSuccessfullyCompletedTestTitles(), $installer->getApproxiamateCompletion());
				break;
		}
	}
	else if (!$testResult->success)
	{
		$installer_messages = $installer->getMessages();
		$test_messages = isset($testResult->yield->messages) ? $testResult->yield->messages : [];
		$testResult->yield->messages = array_merge($installer_messages, $test_messages);
		yield_test_results_and_exit($testResult->yield, $installer->getSuccessfullyCompletedTestTitles(), $installer->getApproxiamateCompletion());
	}
	else
	{
		if (isset($testResult->completionMessages))
			$completionMessages[ $requirement ] = $testResult->completionMessages;
	}
}

$installer->declareInstallationComplete();

$completed_tests = $installer->getSuccessfullyCompletedTestTitles();
while ($failingPostInstallationTest = $installer->postInstallationTest())
	yield_test_results_and_exit($failingPostInstallationTest->yield, $completed_tests, 95/100);

yield_test_results_and_exit($installer->successful_install(), $completed_tests, 100/100);
