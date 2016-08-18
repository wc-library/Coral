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
if (dirname($_SERVER["SCRIPT_FILENAME"]) !== dirname(__DIR__) || basename($_SERVER["SCRIPT_FILENAME"]) !== basename(__FILE__))
{
	// Calculating $location allows the root to be something other than / (e.g. /Coral/)
	$trim_from_left = function ($str_to_trim, $trim) { return preg_replace('/^' . preg_quote($trim, '/') . '/', '', $str_to_trim); };
	$location = $trim_from_left(dirname(__DIR__), $_SERVER["DOCUMENT_ROOT"]);
	header("Location: " . $location);
	exit();
}

/**
 * INSTALLATION_VERSIONS is an array of all version strings that can be upgraded from
 * INSTALLATION_VERSION is the current version string (which should be the last element in the INSTALLATION_VERSIONS array)
 *
 * NOTE: It is assumed that version strings can be understood by php's version_compare function
 */
const INSTALLATION_VERSION = "2.0.0";
const INSTALLATION_VERSIONS = ["2.0.0"];

// TODO: if /index.php is calling this all the time, these lines make no sense
// 			(we shouldn't set these constants for every page).
const INSTALLATION_IN_PROGRESS = true;


function is_installed()
{
	require_once("common/Config.php");
	try {
		$return = Config::getInstallationVersion();
	} catch (Exception $e) {
		$return = false;
	}
	return $return;
}

function run_loop($version)
{
	require_once "installer.php";
	switch ($version) {
		case Installer::VERSION_STRING_INSTALL:
			$requirement_filter = Installer::REQUIRED_FOR_INSTALL;
			break;
		case Installer::VERSION_STRING_MODIFY:
			$requirement_filter = Installer::REQUIRED_FOR_MODIFY;
			break;
		default:
			$requirement_filter = Installer::REQUIRED_FOR_UPGRADE;
			break;
	}
	$installer = new Installer($version);
	$requirements = $installer->getRequiredProviders($requirement_filter);
	foreach ($requirements as $i => $requirement) {
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
		yield_test_results_and_exit($failingPostInstallationTest->yield, $completed_tests, 97/100);

	// Success!
	$return = new stdClass();
	$return->show_completion = true;
	session_unset();
	yield_test_results_and_exit($return, $completed_tests, 100/100);
}

function do_install()
{
	require_once "test_if_installed.php";
	if (!continue_installing())
	{
		session_unset();
		$return = new stdClass();
		$return->redirect_home = true;
		yield_test_results_and_exit($return, [], 1);
	}
	run_loop(Installer::VERSION_STRING_INSTALL);
}

function do_upgrade($version)
{
	// Need to figure out a modular way of handling this:
	/**
	 * Maybe we need to consider installation modes:
	 * 		upgrade
	 * 		modify
	 * 		install
	 * 	 along with the flag "post_mode" => for post-installation/modification/upgrade scripts to run
	 *
	 * Maybe upgrader should return an installer array with dependencies and
	 * everything depending on the version we give it and maybe we should
	 * have an installer that upgraders can depend on that will process sql
	 * files and update conf files but how do we get it to run after them?
	 * that implies we have functional "required" flag but they only work
	 * for the installer...
	 *
	 * So new plan:
	 * 	We check the required_for var which will tell us whether needed for
	 * 	upgrade, modify or install. Installers with required_for set are
	 * 	basically doing all the heavy lifting (fancy type stuff that
	 * 	modules_to_use_helper does). When we are installing, we look for
	 * 	inarray(required_for, install)...
	 *
	 * It depends on everything needed for that thing...
	 *
	 */

	$current_version_index = array_search($version, INSTALLATION_VERSIONS);
	for ($version_to_install_index = $current_version_index + 1; $version_to_install_index < count(INSTALLATION_VERSIONS); $version_to_install_index++)
	{
		run_loop(INSTALLATION_VERSIONS[$version_to_install_index]);
	}
}

$version = is_installed();
if ($version !== INSTALLATION_VERSION || (isset($_SESSION["installer_post_installation"]) && $_SESSION["installer_post_installation"]))
{
	if (!isset($_POST["installing"]))
	{
		require_once "templates/install_page_template.php";
		draw_install_page_template();
		exit();
	}

	require_once "test_results_yielder.php";
	if (!$version || (isset($_SESSION["installer_post_installation"]) && $_SESSION["installer_post_installation"]))
	{
		do_install();
		exit();
	}
	else
	{
		$return = new stdClass();
		$return->messages = [];
		if (array_slice(INSTALLATION_VERSIONS, -1)[0] !== INSTALLATION_VERSION)
		{
			// The instllation constants are not correctly set up
			$return->messages[] = "<b>" . _("An error has occurred:") . "</b><br />" . _("Sorry but the installer has been incorrectly configured. Please contact the developer.");
			$return->messages[] = _("Version of Installer does not match the last installation version in INSTALLATION_VERSIONS.");
			yield_test_results_and_exit($return, [], 0);
		}
		elseif (!in_array($version, INSTALLATION_VERSIONS))
		{
			$return->messages[] = "<b>" . _("An error has occurred:") . "</b><br />" . _("Sorry but the installer has been incorrectly configured. Please contact the developer.");
			$return->messages[] = _("The version currently installed is not a recognised version.");
			yield_test_results_and_exit($return, [], 0);
		}
		do_upgrade($version);
		exit();
	}
}


// TAKEN FROM test_if_installed.php -> needs to be handled in do_upgrade()

// elseif (version_compare(INSTALLATION_VERSION, $old_version) > 0)
// {
// 	// This installer installs a newer version
// 	$instruction = _("This installer installs a newer version of CORAL than the one currently installed. This is <b>highly discouraged</b> and will probably result in the loss of data. Instead you should try to upgrade.");
// 	$option_buttons = $allowed_options(["take_me_home", "try_upgrade", "install_anyway"]);
// }
// else if (version_compare(INSTALLATION_VERSION, $old_version) === 0)
// {
// 	// Already installed and current version
// 	$instruction = _("You already have the current version installed. Are you looking for the home page?");
// 	$option_buttons = $allowed_options(["take_me_home"]);
// }
// else if (version_compare(INSTALLATION_VERSION, $old_version) < 0)
// {
// 	// Apparently the already installed version is newer than this installer
// 	$yield->messages[] = _("<b>Warning:</b> A problem exists in your CORAL installation.");
// 	$yield->messages[] = _("<b>Warning:</b> The CORAL version already installed is newer than this software version. You should notify your administrator or the developer.");
// 	$instruction = _("The installed version of CORAL is newer than the newest version this installer can install.");
// 	$option_buttons = $allowed_options(["take_me_home"]);
// }
