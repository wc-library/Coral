<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('perform actions and see result');

// Document creation
$I->amOnPage("/management/");
$I->click("New Document");
$I->fillField("#licenseShortName", "Test Document");
// Select category of document
$I->selectOption('form select[name=licenseConsortiumID]', 'CORAL Documentation');
// Upload file tests/_data/test_doc.txt
// TODO: file uploads not working
//$I->click("#upload_button");
$I->attachFile('input[type="file"]', 'test_doc.txt');
//$I->click("#upload_button");
$I->click(".submit-button");
$I->waitForText("Document Added Successfully.", 5);
$I->click("Continue");
// we are redirected to the test document's page
$I->waitForText("Only one active document is allowed.", 5); // ensure that we are on the test document's page

// Find document and go to its page
$I->amOnPage("/management/");
$I->waitForPageToBeReady();
$I->click("Test Document");

// Document deletion
$I->willAcceptTheNextConfirmBox();
$I->click("remove");
$I->waitForText("records per page", 5); // Ensure that list loads
$I->dontSee("Test Document");