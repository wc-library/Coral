<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('Ensure that I can create/delete an organization and see it in the list');

// Organization creation
$I->amOnPage("/organizations/");
$I->click("New Organization");
$I->fillField("#organizationName", "Test Organization");
$I->click(".submit-button");
// We are redirected to the test organization's details page
$I->waitForText("Created:", 5); // Ensure that we are on details page

// Find resource in list and go to its page
$I->amOnPage("/organizations/");
$I->waitForPageToBeReady();
// Since test_database has several organizations in it already, we'll need to search to get Test Organization in the list
$I->fillField("#searchOrganizationName", "Test Organization");
$I->click("go!"); // run search
$I->waitForPageToBeReady();
$I->click("Test Organization");

// Delete organization
$I->willAcceptTheNextConfirmBox();
$I->click("remove resource"); // button title/name
$I->waitForText("Organization successfully deleted.", 5); // Ensure that the list has loaded by Ajax.
$I->dontSee("Test Organization");
