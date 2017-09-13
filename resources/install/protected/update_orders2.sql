INSERT INTO ResourceAcquisition (resourceID,
orderNumber,
systemNumber,
acquisitionTypeID,
subscriptionAlertEnabledInd,
authenticationTypeID,
authenticationUserName,
authenticationPassword,
accessMethodID,
storageLocationID,
userLimitID,
coverageText,
bibSourceURL,
catalogingTypeID,
catalogingStatusID,
numberRecordsAvailable,
numberRecordsLoaded,
recordSetIdentifier,
hasOclcHoldings,
workflowRestartDate,
workflowRestartLoginID,subscriptionStartDate, subscriptionEndDate) SELECT Resource.resourceID,
Resource.orderNumber,
Resource.systemNumber,
Resource.acquisitionTypeID,
Resource.subscriptionAlertEnabledInd,
Resource.authenticationTypeID,
Resource.authenticationUserName,
Resource.authenticationPassword,
Resource.accessMethodID,
Resource.storageLocationID,
Resource.userLimitID,
Resource.coverageText,
Resource.bibSourceURL,
Resource.catalogingTypeID,
Resource.catalogingStatusID,
Resource.numberRecordsAvailable,
Resource.numberRecordsLoaded,
Resource.recordSetIdentifier,
Resource.hasOclcHoldings,
Resource.workflowRestartDate,
Resource.workflowRestartLoginID,NOW(),NOW() FROM Resource;
UPDATE ResourcePurchaseSiteLink LEFT JOIN ResourceAcquisition ON ResourcePurchaseSiteLink.resourceAcquisitionID = ResourceAcquisition.resourceID SET ResourcePurchaseSiteLink.resourceAcquisitionID = ResourceAcquisition.resourceAcquisitionID;
UPDATE ResourcePayment LEFT JOIN ResourceAcquisition ON ResourcePayment.resourceAcquisitionID = ResourceAcquisition.resourceID SET ResourcePayment.resourceAcquisitionID = ResourceAcquisition.resourceAcquisitionID;
UPDATE ResourceAdministeringSiteLink LEFT JOIN ResourceAcquisition ON ResourceAdministeringSiteLink.resourceAcquisitionID = ResourceAcquisition.resourceID SET ResourceAdministeringSiteLink.resourceAcquisitionID = ResourceAcquisition.resourceAcquisitionID;
UPDATE ResourceAuthorizedSiteLink LEFT JOIN ResourceAcquisition ON ResourceAuthorizedSiteLink.resourceAcquisitionID = ResourceAcquisition.resourceID SET ResourceAuthorizedSiteLink.resourceAcquisitionID = ResourceAcquisition.resourceAcquisitionID;
UPDATE Attachment LEFT JOIN ResourceAcquisition ON Attachment.resourceAcquisitionID = ResourceAcquisition.resourceID SET Attachment.resourceAcquisitionID = ResourceAcquisition.resourceAcquisitionID;
UPDATE Contact LEFT JOIN ResourceAcquisition ON Contact.resourceAcquisitionID = ResourceAcquisition.resourceID SET Contact.resourceAcquisitionID = ResourceAcquisition.resourceAcquisitionID;
UPDATE ResourceLicenseLink LEFT JOIN ResourceAcquisition ON ResourceLicenseLink.resourceAcquisitionID = ResourceAcquisition.resourceID SET ResourceLicenseLink.resourceAcquisitionID = ResourceAcquisition.resourceAcquisitionID;
UPDATE ResourceLicenseStatus LEFT JOIN ResourceAcquisition ON ResourceLicenseStatus.resourceAcquisitionID = ResourceAcquisition.resourceID SET ResourceLicenseStatus.resourceAcquisitionID = ResourceAcquisition.resourceAcquisitionID;
UPDATE ResourceStep LEFT JOIN ResourceAcquisition ON ResourceStep.resourceAcquisitionID = ResourceAcquisition.resourceID SET ResourceStep.resourceAcquisitionID = ResourceAcquisition.resourceAcquisitionID;
UPDATE Downtime LEFT JOIN ResourceAcquisition ON Downtime.entityID = ResourceAcquisition.resourceID SET Downtime.resourceAcquisitionID = ResourceAcquisition.resourceAcquisitionID;
UPDATE IssueRelationship LEFT JOIN ResourceAcquisition ON IssueRelationship.entityID = ResourceAcquisition.resourceID SET IssueRelationship.resourceAcquisitionID = ResourceAcquisition.resourceAcquisitionID;
UPDATE ResourceNote LEFT JOIN ResourceAcquisition ON ResourceNote.entityID = ResourceAcquisition.resourceID SET ResourceNote.entityID = ResourceAcquisition.resourceAcquisitionID WHERE UPPER(tabName) = 'CATALOGING';
UPDATE ResourceNote LEFT JOIN ResourceAcquisition ON ResourceNote.entityID = ResourceAcquisition.resourceID SET ResourceNote.entityID = ResourceAcquisition.resourceAcquisitionID WHERE UPPER(tabName) = 'ACCESS';
UPDATE ResourceNote LEFT JOIN ResourceAcquisition ON ResourceNote.entityID = ResourceAcquisition.resourceID SET ResourceNote.entityID = ResourceAcquisition.resourceAcquisitionID WHERE UPPER(tabName) = 'ACQUISITIONS';
