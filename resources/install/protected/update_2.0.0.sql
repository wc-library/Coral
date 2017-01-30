ALTER TABLE `ResourcePayment`
	ADD COLUMN `includeStats` boolean default NULL;

DROP TABLE IF EXISTS `Fund`;
CREATE TABLE `Fund` (
  `fundID` int(11) NOT NULL auto_increment,
  `fundCode` varchar(20) default NULL,
  `shortName` varchar(200) default NULL,
  `archived` boolean default NULL,
  PRIMARY KEY (`fundID`),
  UNIQUE `fundCode` (`fundCode`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `ImportConfig`;
CREATE TABLE `ImportConfig` (
  `importConfigID` int(11) NOT NULL auto_increment,
  `shortName` varchar(200) default NULL,
  `configuration` varchar(1000) default NULL,
  PRIMARY KEY (`importConfigID`)
  ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=UTF8;

DROP TABLE IF EXISTS `OrgNameMapping`;
CREATE TABLE `OrgNameMapping` (
  `orgNameMappingID` int(11) NOT NULL auto_increment,
  `importConfigID` int(11) NOT NULL,
  `orgNameImported` varchar(200) default NULL,
  `orgNameMapped` varchar(200) default NULL,
  PRIMARY KEY (`orgNameMappingID`),
  KEY (`importConfigID`)
  ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=UTF8;

INSERT INTO `Fund` (shortName) SELECT DISTINCT `fundName` FROM `ResourcePayment`;
UPDATE `Fund` SET fundCode = fundID;

ALTER TABLE `ResourcePayment` ADD COLUMN `fundID` int(10) AFTER `resourceID`;

UPDATE `ResourcePayment`
INNER JOIN `Fund`
    ON `ResourcePayment`.fundName = `Fund`.shortName
SET `ResourcePayment`.fundID = `Fund`.fundID;

ALTER TABLE `ResourcePayment`
 ADD INDEX `Index_fundID`(`fundID`),
 DROP INDEX `Index_All`,
 ADD INDEX `Index_All`(`resourceID`, `fundID`, `year`, `costDetailsID`, `invoiceNum`);
 

ALTER TABLE `ResourcePayment` DROP COLUMN `fundName`;

ALTER TABLE  `ResourceNote` MODIFY `updateDate` timestamp NOT NULL default CURRENT_TIMESTAMP;
