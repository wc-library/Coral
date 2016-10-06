<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('Import usage statistics files');

// Import usage statistics
$I->amOnPage('/usage/');
$I->click("File Import");
// Attempt to upload without importing a file
$I->click('#submitFile');
// We expect to get an error message and remain on the same page
$I->see(' Please select a file.');
// Attempt to import a non-txt file
$I->attachFile('input[type="file"]', 'bad_import.md');
$I->click('#submitFile');
// We expect an error here
$I->see('Incorrect File format, must be .txt!');

// TODO: import correctly-formatted test file
