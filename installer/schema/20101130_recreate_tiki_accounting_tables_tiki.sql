CREATE TABLE IF NOT EXISTS `tiki_acct_journal` (
  `journalBookId` INT(10) UNSIGNED NOT NULL,
  `journalId` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `journalDate` DATE NULL,
  `journalDescription` VARCHAR(255) NOT NULL,
  `journalCancelled` INT(1) NOT NULL DEFAULT '0',
  `journalTs` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`journalId`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `tiki_acct_stack` (
  `stackBookId` INT(10) UNSIGNED NOT NULL,
  `stackId` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `stackDate` DATE NULL,
  `stackDescription` VARCHAR(255) NOT NULL,
  `stackTs` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`stackId`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `tiki_acct_statement` (
  `statementBookId` INT(10) UNSIGNED NOT NULL,
  `statementAccountId` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `statementId` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `statementBookingDate` DATE NULL,
  `statementValueDate` DATE NULL,
  `statementBookingText` VARCHAR(255) NOT NULL,
  `statementReason` VARCHAR(255) NOT NULL,
  `statementCounterpart` VARCHAR(63) NOT NULL,
  `statementCounterpartAccount` VARCHAR(63) NOT NULL,
  `statementCounterpartBankCode` VARCHAR(63) NOT NULL,
  `statementAmount` DOUBLE NOT NULL,
  `statementJournalId` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `statementStackId` INT(11) NOT NULL,
  PRIMARY KEY (`statementId`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `tiki_acct_tax` (
  `taxBookId` INT(10) UNSIGNED NOT NULL,
  `taxId` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `taxText` VARCHAR(63) NOT NULL,
  `taxAmount` DOUBLE NOT NULL DEFAULT '0',
  `taxIsFix` enum('y','n') NOT NULL DEFAULT 'n',
  PRIMARY KEY (`taxId`)
) ENGINE=MyISAM;
