<?php

// Filter inputs
$titleId = filter_input(INPUT_POST,'titleId', FILTER_SANITIZE_NUMBER_INT);
$packageId = filter_input(INPUT_POST,'packageId', FILTER_SANITIZE_NUMBER_INT);
$vendorId = filter_input(INPUT_POST,'vendorId', FILTER_SANITIZE_NUMBER_INT);
$importType = filter_input(INPUT_POST,'importType', FILTER_SANITIZE_STRING);
$resourceStatus = filter_input(INPUT_POST,'status', FILTER_SANITIZE_STRING);
$organizationId = filter_input(INPUT_POST,'organizationID', FILTER_SANITIZE_NUMBER_INT);
$organizationOption = filter_input(INPUT_POST,'organizationOption', FILTER_SANITIZE_STRING);
$parentOrChild = filter_input(INPUT_POST,'parentOrChild', FILTER_SANITIZE_STRING);
$resourceFormatId = filter_input(INPUT_POST,'resourceFormatID', FILTER_SANITIZE_NUMBER_INT);
$acquisitionTypeId = filter_input(INPUT_POST,'acquisitionTypeID', FILTER_SANITIZE_NUMBER_INT);
$noteText = filter_input(INPUT_POST,'noteText', FILTER_SANITIZE_STRING);
$providerText = filter_input(INPUT_POST,'providerText', FILTER_SANITIZE_STRING);
$importTitles = !empty($_POST['importTitles']);
$importTitlesOption = filter_input(INPUT_POST,'importTitlesOption', FILTER_SANITIZE_STRING);
//determine status id for all the imports
$status = new Status();
$statusId = $status->getIDFromName($resourceStatus);


// provider
$organizationRole = new OrganizationRole();
$organizationRoleId = $organizationRole->getProviderID();

switch($organizationOption){
    case 'alias':
        //do something
        break;
    case 'parentOrChild':
        //do something
        break;
    case 'import':
        //do something
        break;
    default:
        break;
}

// cache for subjects so we don't have to keep pinging the DB to check if an org exists
$subjectCache = [];

// cache for resource types so we don't have to keep pinging the DB to check if an org exists
$resourceTypeCache = [];


$ebscoKb = EbscoKbService::getInstance();

// title import
if(!empty($titleId)){
    $title = $ebscoKb->getTitle($titleId);
    $newWorkflow = true;
    if(empty($title)){
        echo '<p>There was an issue importing this title</p>';
        exit;
    }
    $resource = importTitle($title);
    echo $resource->primaryKey;
    exit;
}


if(!empty($vendorId) && !empty($packageId)) {
    $kbResource = $ebscoKb->getPackage($vendorId, $packageId);

    // Do stuff with packages
    //$titles = $packagesTitles;
}



if(!$importTitles){
    // return $packageId for redirect
    exit;
}


for($i = 1; $i <= ceil($totalTitles / $count); $i++){

}

/* --------------------
    Functions
*/

function dd($item, $title){
    echo "<h1>$title</h1>";
    echo '<pre>';
    echo print_r($item);
    echo '</pre>';
}

function importTitle($title, $parent = null){

    global $loginID,
           $statusId,
           $newWorkflow,
           $acquisitionTypeId,
           $resourceFormatId,
           $providerText;

    $resource = new Resource();
    $existingResource = $resource->getResourceByEbscoKbId($title->titleId);
    // Search for a matching resource
    if ($existingResource){
        //get this resource
        $resource = $existingResource;
    } else {
        //set up new resource
        $resource->createLoginID = $loginID;
        $resource->createDate = date( 'Y-m-d' );
        $resource->updateLoginID = '';
        $resource->updateDate = '';
    }

    $resource->resourceTypeID = getResourceTypeId($title->pubType);
    $resource->resourceFormatID = $resourceFormatId;
    $resource->acquisitionTypeID = $acquisitionTypeId;
    $resource->titleText = $title->titleName;
    $resource->descriptionText = $title->description;
    $resource->isbnOrISSN;
    $resource->statusID	= $statusId;
    $resource->orderNumber = '';
    $resource->systemNumber = '';
    $resource->userLimitID = '';
    $resource->authenticationUserName = '';
    $resource->authenticationPassword = '';
    $resource->storageLocationID = '';
    $resource->registeredIPAddresses = '';
    $resource->providerText	= $providerText;
    $resource->coverageText = implode('; ', $title->coverageTextArray);
    $resource->ebscoKbID = $title->titleId;

    $urlsByCoverage = $title->sortUrlsByCoverage();
    $resource->resourceURL = empty($urlsByCoverage[0]) ? '' : $urlsByCoverage[0]['url'];
    $resource->resourceAltURL = empty($urlsByCoverage[1]) ? '' : $urlsByCoverage[1]['url'];

    $resource->save();
    $resource->setIsbnOrIssn($title->isxns);

    addProvider($resource);
    addNotes($resource);

    if ($newWorkflow){
        $resource->enterNewWorkflow();
    }
    return $resource;

}

function addProvider(Resource $resource){
    global $organizationId, $organizationRoleId;

    if ($organizationId && ($organizationRoleId)){
        $resource->removeResourceOrganizations();
        $resourceOrganizationLink = new ResourceOrganizationLink();
        $resourceOrganizationLink->resourceID = $resource->primaryKey;
        $resourceOrganizationLink->organizationID = $organizationId;
        $resourceOrganizationLink->organizationRoleID = $organizationRoleId;
        $resourceOrganizationLink->save();
    }
}

function addNotes(Resource $resource){

    global $loginID, $resourceStatus, $noteText, $providerText, $organizationId;
    //add notes
    if (($noteText) || (($providerText) && (!$organizationId))){
        //first, remove existing notes in case this was saved before
        $resource->removeResourceNotes();

        //this is just to figure out what the creator entered note type ID is
        $noteType = new NoteType();

        $resourceNote = new ResourceNote();
        $resourceNote->resourceNoteID = '';
        $resourceNote->updateLoginID = $loginID;
        $resourceNote->updateDate = date( 'Y-m-d' );
        $resourceNote->noteTypeID = $noteType->getInitialNoteTypeID();
        $resourceNote->tabName = 'Product';
        $resourceNote->resourceID = $resource->primaryKey;
        $resourceNote->noteText = $resourceStatus == 'progress'
            ? "Provider:  $providerText\n\n$noteText"
            : $noteText;
        $resourceNote->save();
    }
}

function addSubjects($resource, $subjects){
    // TODO
}

function getSubjectId($subject){

    // TODO, would be used by add subjects

    global $subjectCache;

    // Search for the cached key
    $cachedKey = array_search($subject, $subjectCache);
    if($cachedKey) {
        return $cachedKey;
    }

    // If it doesn't exist, create or get the subject id
    // TODO: should it be detailed or general?
    $detailedSubject = new DetailedSubject();
    $detailedSubjectId = $detailedSubject->getResourceTypeIDByName($subject);
    if(empty($detailedSubjectId)){
        // create a new resource type
        $detailedSubject->shortName = $subject;
        $detailedSubject->save();
        $detailedSubjectId = $detailedSubject->primaryKey;
    }
    // add the key and name to the cache
    $resourceTypeCache[$detailedSubjectId] = $subject;
    return $detailedSubjectId;
}

function getResourceTypeId($typeName){

    global $resourceTypeCache;

    // Search for the cached key
    $cachedKey = array_search($typeName, $resourceTypeCache);
    if($cachedKey) {
        return $cachedKey;
    }

    // If it doesn't exist, create or get the resource type id
    $resourceType = new ResourceType();
    $resourceTypeId = $resourceType->getResourceTypeIDByName($typeName);
    if(empty($resourceTypeId)){
        // create a new resource type
        $resourceType->shortName = $typeName;
        $resourceType->save();
        $resourceTypeId = $resourceType->primaryKey;
    }
    // add the key and name to the cache
    $resourceTypeCache[$resourceTypeId] = $typeName;
    return $resourceTypeId;
}



