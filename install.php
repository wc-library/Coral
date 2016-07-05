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
const INSTALLATION_VERSION = "2.0.0";
const INSTALLATION_VERSIONS = ["2.0.0"];
const INSTALLATION_IN_PROGRESS = true;

/**
 *  All the requests that come from the template page post { "installing":true }
 *  So if it's not set, we need to draw the template for the first time.
 */
if (!isset($_POST["installing"]))
{
	require "install/templates/install_page_template.php";
	draw_install_page_template();
	exit();
}

require_once "install/test_if_installed.php";
if (!continue_installing())
{
	session_unset();
	exit(1);
	//We shouldn't ever get here
}

require "install/installer.php";
$installer = new Installer();
$requirements = $installer->getCheckListUids();

require_once "install/test_results_yielder.php";
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
	yield_test_results_and_exit($failingPostInstallationTest->yield, $completed_tests, 97/100);


$return = new stdClass();
$return->redirect_home = true;
yield_test_results_and_exit($return, $completed_tests, 100/100);
