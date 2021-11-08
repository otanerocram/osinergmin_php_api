CREATE TABLE `Osinergmin` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`event` INT(11) NOT NULL DEFAULT '0',
	`plate` VARCHAR(24) NOT NULL COLLATE 'utf8_general_ci',
	`speed` INT(10) NULL DEFAULT NULL,
	`latitude` DOUBLE NULL DEFAULT NULL,
	`longitude` DOUBLE NULL DEFAULT NULL,
	`gpsDate` INT(11) NULL DEFAULT NULL,
	`odometer` DOUBLE NULL DEFAULT NULL,
	`sent` INT(2) NULL DEFAULT NULL,
	`accountID` VARCHAR(32) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
	PRIMARY KEY (`id`, `plate`, `event`) USING BTREE
)
COLLATE='latin1_swedish_ci'
ENGINE=MyISAM
ROW_FORMAT=DYNAMIC
AUTO_INCREMENT=2079
;


IF (instr(@agente, 'OSI') > 0) THEN
    set @newLicensePlate	= (SELECT licensePlate FROM Device WHERE `accountID`=@newAccountID AND `deviceID`=@newDeviceID);
    INSERT INTO `Osinergmin` (`event`, `plate`, `speed`, `latitude`, `longitude`, `gpsDate`, `odometer`, `sent`, `accountID`) 
    VALUES (@newStatusCode, @newLicensePlate, format(@newSpeed,0), @newLatitude, @newLongitude, @newTimestamp, format(@newOdometerKM,0), 0, @newAccountID);
END IF;
