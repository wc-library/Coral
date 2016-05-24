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
 * Notes for install
 *
 * A. Create branch for install
 * B. Install order:
 * 		1. Core
 * 		2. Auth (don't know where this should be...)
 * 		3. Organizations - Coral recommends organizations be the first module installed (http://coral-erm.org/organizations/)
 * 		...
 * 		6. Resources - Resources is the most recent to be added + it interacts with organizations & licenses (http://coral-erm.org/resources/)
 * C. Make everything translatable
 *
 */


/*
 * Things to do:
 *
 * 1. Check in /index.php if CORAL is installed (and redirect here).
 * 2. Check in /install.php if CORAL is installed (and redirect to index - [with a message?]).
 * 3. Check if the admin dirs are writable and provide a script to set permissions correctly (and do the reverse when done)
 * 4. System Requirements:
 *    - PHP 5
 *    -
 * 5. Get root user details + host [we can guess this one pretty well though]
 * 6. Create a user with limited privileges
 * 7. Create databases for each of the modules (Q: do this whether they are wanted or not?)
 *
 */


 /**
 * @author j3frea+coral@gmail.com
 */


// TODO: grep for and remove "# code..."
// TODO: detect failed installation (possibly by asking) and handle dbs better...

const INSTALLATION_IN_PROGRESS = true;
require "install/test_if_installed.php";
/**
 *  All the requests that come from the template page post { "installing":true }
 *  So if it's not set, we need to draw the template for the first time.
 */
if (!isset($_POST["installing"]))
{
	// TODO: set session var and check it here
	// (indicates that installer has restarted - post var not set but session is)
	// ask if users wants the session cleared and install to start again
	require "install/templates/install_page_template.php";
	draw_install_page_template();
	exit();
}

require "install/test_results_yielder.php";

require "install/installer.php";
$installer = new Installer();
$requirements = $installer->getCheckListUids();
$completed_tests = [];

foreach ($requirements as $i => $requirement) {
	$testResult = $installer->runTestForResult($requirement);
	if (!$testResult->success)
	{
		$installer_messages = $installer->getMessages();
		$test_messages = isset($testResult->yield->messages) ? $testResult->yield->messages : [];
		$testResult->yield->messages = array_merge($installer_messages, $test_messages);
		yield_test_results($testResult->yield, $completed_tests, $i / (float) count($requirements));
	}
	if (isset($testResult->completionMessages))
		$completionMessages[ $requirement ] = $testResult->completionMessages;
	$completed_tests[] = $installer->getTitleFromUid($requirement);
}

yield_test_results($installer->successful_install(), $completed_tests, 100/100);
