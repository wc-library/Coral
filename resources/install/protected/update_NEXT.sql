ALTER TABLE 'ResourcePayment'
	ADD `includeStats` boolean default NULL;

ALTER TABLE `Resource`
ADD INDEX `catalogingTypeID` ( `catalogingTypeID` ),
ADD INDEX `catalogingStatusID` ( `catalogingStatusID` );
