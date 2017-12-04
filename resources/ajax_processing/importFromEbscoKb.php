<?php

/* ------ Filter inputs --------- */
$titleId = filter_input(INPUT_POST,'titleId', FILTER_SANITIZE_NUMBER_INT);
$packageId = filter_input(INPUT_POST,'packageId', FILTER_SANITIZE_NUMBER_INT);
$vendorId = filter_input(INPUT_POST,'vendorId', FILTER_SANITIZE_NUMBER_INT);
$importType = filter_input(INPUT_POST,'importType', FILTER_SANITIZE_STRING);
$resourceStatus = filter_input(INPUT_POST,'status', FILTER_SANITIZE_STRING);
$organizationId = filter_input(INPUT_POST,'organizationId', FILTER_SANITIZE_NUMBER_INT);
$providerOption = filter_input(INPUT_POST,'providerOption', FILTER_SANITIZE_STRING);
$parentOrChild = filter_input(INPUT_POST,'parentOrChild', FILTER_SANITIZE_STRING);
$resourceFormatId = filter_input(INPUT_POST,'resourceFormatId', FILTER_SANITIZE_NUMBER_INT);
$acquisitionTypeId = filter_input(INPUT_POST,'acquisitionTypeId', FILTER_SANITIZE_NUMBER_INT);
$resourceTypeId = filter_input(INPUT_POST,'resourceTypeId', FILTER_SANITIZE_NUMBER_INT);
$noteText = filter_input(INPUT_POST,'noteText', FILTER_SANITIZE_STRING);
$providerText = trim(filter_input(INPUT_POST,'providerText', FILTER_SANITIZE_STRING));
$titleFilter = filter_input(INPUT_POST,'titleFilter', FILTER_SANITIZE_STRING);
$aliasTypeId = filter_input(INPUT_POST,'aliasTypeId', FILTER_SANITIZE_NUMBER_INT);
$workflowOption = filter_input(INPUT_POST, 'workflowOption', FILTER_SANITIZE_STRING);

// Is the org module in use
$config = new Configuration;
$orgModule = $config->settings->organizationsModule == 'Y' ? true : false;

//determine status id for all the imports
$status = new Status();
$statusId = $status->getIDFromName($resourceStatus);

// Set the organization role as provider
$organizationRole = new OrganizationRole();
$organizationRoleId = $organizationRole->getProviderID();

// cache for subjects so we don't have to keep pinging the DB to check if an org exists
$subjectCache = [];

// cache for resource types so we don't have to keep pinging the DB to check if an org exists
$resourceTypeCache = [];

// Setup the ebsco connection
$ebscoKb = EbscoKbService::getInstance();

/* ------ Check user input errors --------- */
$errors = [];
function create_error($target, $text){
    return ['target' => $target, 'text' => $text];
    //return ['target' => $target, 'text' => _($text)];
}
function send_errors($errors){
    header('Content-type: application/json');
    echo json_encode(['error' => $errors]);
    exit;
}

if(empty($resourceFormatId)) {
    $errors[] = create_error('resourceTypeId', 'No Resource Type selected');
}
if(empty($acquisitionTypeId)) {
    $errors[] = create_error('acquisitionTypeId', 'No Acquisition Type selected');
}
if(empty($importType)){
    $errors[] = create_error('general', 'No import type set');
}

if($importType == 'package'){
    // Is the package id set
    if(empty($packageId)) {
        $errors[] = create_error('general', 'No package ID found');
    }
    // Is the vendor id set
    if(empty($vendorId)) {
        $errors[] = create_error('general', 'No vendor ID found');
    }
    // can we access the package via Ebsco KB
    try {
        $package = $ebscoKb->getPackage($vendorId, $packageId);
    } catch (Exception $e) {
        $errors[] = create_error('general', $e->getMessage());
    }

    // Is the providerOption set
    if(empty($providerOption)){
        $errors[] = create_error('providerOption', 'Please select a provider import option');
    }
    // Is the organization ID set if adding an alias or parent/child relationship
    if(($providerOption == 'parentChild' || $providerOption == 'alias') && empty($organizationId)){
        $errors[] = create_error('organization', 'You must select an organization');
    }
    // alias & parent/child require the org module
    if(($providerOption == 'parentChild' || $providerOption == 'alias') && !$orgModule){
        $errors[] = create_error('organization', 'The organization module is not in use. You cannot import an alias or parent child relationship');
    }
    // If the provider option is parentChild, is the option selected
    if($providerOption == 'parentChild' && empty($parentOrChild)){
        $errors[] = create_error('parentOrChild', 'You must select either a parent or child relationship');
    }
    // Is the title filter set
    if(empty($titleFilter)){
        $errors[] = create_error('titleFilter', 'You must select which set of titles to import');
    }
    // Is the workflow option set
    if($titleFilter != 'none' && empty($workflowOption)){
        $errors[] = create_error('workflowOption', 'You must select if you want to start a workflow for all titles or only the package');
    }
}

if($importType == 'title'){
    // Is the title id set
    if(empty($titleId)) {
        $errors[] = create_error('general', 'No title ID found');
    }
    // can we access the package via Ebsco KB
    try {
        $title = $ebscoKb->getTitle($titleId);
    } catch (Exception $e) {
        $errors[] = create_error('general', $e->getMessage());
    }
}

// Send errors to be rendered
if(!empty($errors)){
    send_errors($errors);
}

/* ------ Setup organization --------- */
switch($providerOption){
    case 'alias':
        //if the org module is installed get the org model from org namespace
        require_once __DIR__.'/../../organizations/admin/classes/domain/Alias.php';
        $organization = new \Organizations\Domain\Alias();
        $alias = new Alias();
        $alias->aliasTypeID = $aliasTypeId;
        $alias->organizationID = $organizationId;
        $alias->name = $package->vendorName;
        try {
            $alias->save();
        } catch (Exception $e) {
            send_errors(create_error('general', $e->getMessage()));
        }
        break;
    case 'parentOrChild':
        require_once __DIR__.'/../../organizations/admin/classes/domain/OrganizationHierarchy.php';
        if($parentOrChild == 'child'){
            // create the child record to attached it to the provided organization ID
            $organization = createOrUpdateOrganization($package->vendorName, $package->vendorId);
            $organization->removeParentOrganizations();
            $organizationHierarchy = new \Organizations\Domain\OrganizationHierarchy;
            $organizationHierarchy->organizationID = $organization->primaryKey;
            $organizationHierarchy->parentOrganizationID = $organizationId;
            try {
                $organizationHierarchy->save();
            } catch (Exception $e) {
                send_errors(create_error('general', $e->getMessage()));
            }
            // set the organizationId for import purposes as the created/updated organization
            $organizationId = $organization->primaryKey;
        }
        if($parentOrChild == 'parent'){
            // create the parent record to attached it to the provided organization ID
            $organization = createOrUpdateOrganization($package->vendorName, $package->vendorId);
            $organizationHierarchy = new \Organizations\Domain\OrganizationHierarchy;
            $organizationHierarchy->organizationID = $organizationId;
            $organizationHierarchy->parentOrganizationID = $organization->primaryKey;
            try {
                $organizationHierarchy->save();
            } catch (Exception $e) {
                send_errors(create_error('general', $e->getMessage()));
            }
            // set the organizationId for import purposes as the created/updated organization
            $organizationId = $organization->primaryKey;
        }
        break;
    case 'import':
        $organization = createOrUpdateOrganization($package->vendorName, $package->vendorId);
        // set the organizationId for import purposes as the created/updated organization
        $organizationId = $organization->primaryKey;
        break;
    default:
        break;
}

/* ------ Title import --------- */
if($importType == 'title'){
    $title = $ebscoKb->getTitle($titleId);
    $newWorkflow = true;
    $resource = importTitle($title);
    echo $resource->primaryKey;
    exit;
}

/* ------ Package import --------- */
if($importType == 'package' && isset($package)) {

    $resource = importPackage($package);
    $count = EbscoKbService::$defaultSearchParameters['count'];
    switch($titleFilter){
        case 'all':
            $totalTitles = $package->selectedCount;
            $selection = 0;
            break;
        case 'selected':
            $totalTitles = $package->titleCount;
            $selection = 1;
            break;
        default:
            echo $resource->primaryKey;
            exit;
    }

    $newWorkflow = $workflowOption == 'all' ? true : false;
    for($i = 1; $i <= ceil($totalTitles / $count); $i++){
        $ebscoKb->createQuery([
            'vendorId' => $package->vendorId,
            'packageId' => $package->packageId,
            'count' => $count,
            'selection' => $selection,
            'type' => 'titles'
        ]);
        $ebscoKb->execute();
        $packageTitles = $ebscoKb->results();
        send_errors([create_error('general',$packageTitles)]);
        foreach($packageTitles as $title){
            $title = $ebscoKb->getTitle($title->titleId);
            importTitle($title, $resource->primaryKey);
        }
    }
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

function importPackage($package){

    global $loginID,
           $statusId,
           $acquisitionTypeId,
           $resourceFormatId,
           $resourceTypeId,
           $providerText;

    $resource = new Resource();
    $existingResource = $resource->getResourceByEbscoKbId($package->packageId);
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

    if($resourceTypeId == '-1'){
        $resource->resourceTypeID = getResourceTypeId($package->contentType);
    } else {
        $resource->resourceTypeID = $resourceTypeId;
    }
    $resource->resourceFormatID = $resourceFormatId;
    $resource->acquisitionTypeID = $acquisitionTypeId;
    $resource->titleText = $package->packageName;
    $resource->statusID	= $statusId;
    $resource->orderNumber = '';
    $resource->systemNumber = '';
    $resource->userLimitID = '';
    $resource->authenticationUserName = '';
    $resource->authenticationPassword = '';
    $resource->storageLocationID = '';
    $resource->registeredIPAddresses = '';
    $resource->providerText	= $providerText;
    $resource->ebscoKbID = $package->packageId;
    try {
        $resource->save();
    } catch (Exception $e) {
        send_errors(create_error('general', $e->getMessage()));
    }

    addProvider($resource);
    addNotes($resource);

    $resource->enterNewWorkflow();
    return $resource;

}

function importTitle($title, $parentId = null){

    global $loginID,
           $statusId,
           $newWorkflow,
           $acquisitionTypeId,
           $resourceFormatId,
           $providerText;

    $resource = new Resource();
    $existingResource = $resource->getResourceByEbscoKbId($title->titleId);
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

    try {
        $resource->save();
    } catch (Exception $e) {
        send_errors(create_error('general', $e->getMessage()));
    }
    $resource->setIsbnOrIssn($title->isxns);

    addProvider($resource);
    addNotes($resource);

    if(!empty($parentId)){
        $resourceRelationship = new ResourceRelationship();
        $resourceRelationship->resourceID = $resource->primaryKey;
        $resourceRelationship->relatedResourceID = $parentId;
        $resourceRelationship->relationshipTypeID = '1';  //hardcoded because we're only allowing parent relationships
        try {
            $resourceRelationship->save();
        } catch (Exception $e) {
            send_errors(create_error('general', $e->getMessage()));
        }
    }

    if ($newWorkflow){
        $resource->enterNewWorkflow();
    }
    return $resource;

}

function addProvider(Resource $resource){
    global $organizationId, $organizationRoleId;

    if ($organizationId && $organizationRoleId){

        // create an original list of organzational links
        $linkedOrganizations = array_map(function($org){
            return ['organizationId' => $org['organizationID'], 'organizationRoleId' => $org['organizationRoleID']];
        }, $resource->getOrganizationArray());
        $linkedOrganizations[] = ['organizationId' => $organizationId, 'organizationRoleId' => $organizationRoleId];
        $linkedOrganizations = array_map("unserialize", array_unique(array_map("serialize", $linkedOrganizations)));
        // Remove old links
        $resource->removeResourceOrganizations();
        foreach($linkedOrganizations as $link){
            $resourceOrganizationLink = new ResourceOrganizationLink();
            $resourceOrganizationLink->resourceID = $resource->primaryKey;
            $resourceOrganizationLink->organizationID = $link['organizationId'];
            $resourceOrganizationLink->organizationRoleID = $link['organizationRoleId'];
            try {
                $resourceOrganizationLink->save();
            } catch (Exception $e) {
                send_errors(create_error('general', $e->getMessage()));
            }
        }
    }
}

function addNotes(Resource $resource){

    global $loginID, $resourceStatus, $noteText, $providerText, $organizationId;
    //add notes
    if (($noteText) || (($providerText) && (!$organizationId))){

        $noteText = $resourceStatus == 'progress' ? "Provider:  $providerText\n\n$noteText" : $noteText;
        //first, remove existing notes in case this was saved before
        $existingNotes = $resource->getNotes();

        // If the note text doesn't already exist, add it
        if(!in_array($noteText, array_map(function($note){
            return $note['noteText'];
        }, $existingNotes))) {

            //this is just to figure out what the creator entered note type ID is
            $noteType = new NoteType();

            $resourceNote = new ResourceNote();
            $resourceNote->resourceNoteID = '';
            $resourceNote->updateLoginID = $loginID;
            $resourceNote->updateDate = date( 'Y-m-d' );
            $resourceNote->noteTypeID = $noteType->getInitialNoteTypeID();
            $resourceNote->tabName = 'Product';
            $resourceNote->resourceID = $resource->primaryKey;
            $resourceNote->noteText = $noteText;
            try {
                $resourceNote->save();
            } catch (Exception $e) {
                send_errors(create_error('general', $e->getMessage()));
            }
        }

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
        try {
            $resourceType->save();
        } catch (Exception $e) {
            send_errors(create_error('general', $e->getMessage()));
        }
        $resourceTypeId = $resourceType->primaryKey;
    }
    // add the key and name to the cache
    $resourceTypeCache[$resourceTypeId] = $typeName;
    return $resourceTypeId;
}

function createOrUpdateOrganization($organizationName, $ebscoKbId){
    global $loginID, $orgModule;
    //if the org module is installed get the org model from org namespace
    if ($orgModule) {
        require_once __DIR__.'/../../organizations/admin/classes/domain/Organization.php';
        $organization = new \Organizations\Domain\Organization;
    } else {
        $organization = new Organization;
    }
    $existingOrg = $organization->getOrganizationByEbscoKbId($ebscoKbId);

    // Search for a matching resource
    if ($existingOrg){
        //get this resource
        $organization = $existingOrg;
    } else {
        $existingOrg = $organization->getOrganizationIDByName($organizationName);
        if($existingOrg){
            $organization = new Organization(new NamedArguments(array('primaryKey' => $existingOrg)));
        } else {
            //set up new resource
            $organization->createLoginID 		= $loginID;
            $organization->createDate			= date( 'Y-m-d H:i:s' );
            $organization->updateLoginID 		= '';
            $organization->updateDate			= '';
        }
    }
    $organization->ebscoKbID = $ebscoKbId;
    $organization->name = $organizationName;
    try {
        $organization->save();
    } catch (Exception $e) {
        send_errors(create_error('general', $e->getMessage()));
    }

    return $organization;
}

