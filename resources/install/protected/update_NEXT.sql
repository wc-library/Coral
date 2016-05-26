DROP TABLE IF EXISTS `AuthorizedSite`;
CREATE TABLE  `AuthorizedSite` (
  `authorizedSiteID` int(11) NOT NULL auto_increment,
  `shortName` varchar(45) default NULL,
  PRIMARY KEY  (`authorizedSiteID`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `Resource`;
CREATE TABLE  `Resource` (
  `resourceID` int(11) NOT NULL auto_increment,
  `createDate` date default NULL,
  `createLoginID` varchar(45) default NULL,
  `updateDate` date default NULL,
  `updateLoginID` varchar(45) default NULL,
  `archiveDate` date default NULL,
  `archiveLoginID` varchar(45) default NULL,
  `workflowRestartDate` date default NULL,
  `workflowRestartLoginID` varchar(45) default NULL,
  `titleText` varchar(200) default NULL,
  `descriptionText` text,
  `statusID` int(11) default NULL,
  `resourceTypeID` int(11) default NULL,
  `resourceFormatID` int(11) default NULL,
  `orderNumber` varchar(45) default NULL,
  `systemNumber` varchar(45) default NULL,
  `currentStartDate` date default NULL,
  `currentEndDate` date default NULL,
  `subscriptionAlertEnabledInd` int(10) unsigned default NULL,
  `userLimitID` int(11) default NULL,
  `resourceURL` varchar(2000) default NULL,
  `authenticationUserName` varchar(200) default NULL,
  `authenticationPassword` varchar(200) default NULL,
  `storageLocationID` int(11) default NULL,
  `registeredIPAddresses` varchar(200) default NULL,
  `acquisitionTypeID` int(10) unsigned default NULL,
  `authenticationTypeID` int(10) unsigned default NULL,
  `accessMethodID` int(10) unsigned default NULL,
  `providerText` varchar(200) default NULL,
  `recordSetIdentifier` VARCHAR( 45 ) DEFAULT NULL ,
  `hasOclcHoldings` varchar( 10 ) DEFAULT NULL ,
  `numberRecordsAvailable` VARCHAR( 45 ) DEFAULT NULL ,
  `numberRecordsLoaded` VARCHAR( 45 ) DEFAULT NULL ,
  `bibSourceURL` VARCHAR( 2000 ) DEFAULT NULL ,
  `catalogingTypeID` int(11) DEFAULT NULL,
  `catalogingStatusID` int(11) DEFAULT NULL,
  `coverageText` VARCHAR(1000) NULL DEFAULT NULL,
  `resourceAltURL` VARCHAR(2000) NULL DEFAULT NULL,
  PRIMARY KEY  (`resourceID`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `ResourcePayment`;
CREATE TABLE  `ResourcePayment` (
  `resourcePaymentID` int(11) NOT NULL auto_increment,
  `resourceID` int(10) unsigned NOT NULL,
  `fundName` varchar(200) default NULL,
  `selectorLoginID` varchar(45) default NULL,
  `paymentAmount` int(10) unsigned default NULL,
  `orderTypeID` int(10) unsigned default NULL,
  `currencyCode` varchar(3) NOT NULL,
  `year` varchar(20) default NULL,
  `subscriptionStartDate` date default NULL,
  `subscriptionEndDate` date default NULL,
  `costDetailsID` int(11) default NULL,
  `costNote` text,
  `invoiceNum` varchar(20),
  PRIMARY KEY  (`resourcePaymentID`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `ResourceType`;
CREATE TABLE  `ResourceType` (
  `resourceTypeID` int(11) NOT NULL auto_increment,
  `shortName` varchar(200) default NULL,
  `includeStats` boolean default NULL,
  PRIMARY KEY  (`resourceTypeID`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

ALTER TABLE `Resource`
ADD INDEX `catalogingTypeID` ( `catalogingTypeID` ),
ADD INDEX `catalogingStatusID` ( `catalogingStatusID` );