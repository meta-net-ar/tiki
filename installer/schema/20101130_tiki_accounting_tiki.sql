CREATE TABLE IF NOT EXISTS `tiki_acct_account` (
  `accountBookId` INT(10) UNSIGNED NOT NULL,
  `accountId` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `accountName` VARCHAR(255) NOT NULL,
  `accountNotes` text NOT NULL,
  `accountBudget` DOUBLE NOT NULL DEFAULT '0',
  `accountLocked` INT(1) NOT NULL DEFAULT '0',
  `accountTax` INT(11) NOT NULL DEFAULT '0',
  `accountUserId` INT(8) NOT NULL DEFAULT '0',
  PRIMARY KEY (`accountBookId`,`accountId`),
  KEY `accountTax` (`accountTax`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `tiki_acct_bankaccount` (
  `bankBookId` INT(10) UNSIGNED NOT NULL,
  `bankAccountId` INT(10) UNSIGNED NOT NULL,
  `externalNumber` INT(10) NOT NULL,
  `bankCountry` VARCHAR(2) NOT NULL,
  `bankCode` VARCHAR(11) NOT NULL,
  `bankIBAN` VARCHAR(63) NOT NULL,
  `bankBIC` VARCHAR(63) NOT NULL,
  `bankDelimeter` VARCHAR(15) NOT NULL DEFAULT ';',
  `bankDecPoint` VARCHAR(1) NOT NULL DEFAULT ',',
  `bankThousand` VARCHAR(1) NOT NULL DEFAULT '.',
  `bankHasHeader` TINYINT(1) NOT NULL DEFAULT '1',
  `fieldNameAccount` VARCHAR(63) NOT NULL,
  `fieldNameBookingDate` VARCHAR(63) NOT NULL,
  `formatBookingDate` VARCHAR(31) NOT NULL,
  `fieldNameValueDate` VARCHAR(63) NOT NULL,
  `formatValueDate` VARCHAR(31) NOT NULL,
  `fieldNameBookingText` VARCHAR(63) NOT NULL,
  `fieldNameReason` VARCHAR(63) NOT NULL,
  `fieldNameCounterpartName` VARCHAR(63) NOT NULL,
  `fieldNameCounterpartAccount` VARCHAR(63) NOT NULL,
  `fieldNameCounterpartBankCode` VARCHAR(63) NOT NULL,
  `fieldNameAmount` VARCHAR(63) NOT NULL,
  `amountType` INT(10) UNSIGNED NOT NULL,
  `fieldNameAmountSign` VARCHAR(63) NOT NULL,
  `SignPositive` VARCHAR(7) NOT NULL,
  `SignNegative` VARCHAR(7) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`bankBookId`,`bankAccountId`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `tiki_acct_book` (
  `bookId` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `bookName` VARCHAR(255) NOT NULL,
  `bookClosed` enum('y','n') NOT NULL DEFAULT 'n',
  `bookStartDate` DATE NULL,
  `bookEndDate` DATE NULL,
  `bookCurrency` VARCHAR(3) NOT NULL DEFAULT 'EUR',
  `bookCurrencyPos` INT(11) NOT NULL,
  `bookDecimals` INT(11) NOT NULL DEFAULT '2',
  `bookDecPoint` VARCHAR(1) NOT NULL DEFAULT ',',
  `bookThousand` VARCHAR(1) NOT NULL DEFAULT '.',
  `exportSeparator` VARCHAR(4) NOT NULL DEFAULT ';',
  `exportEOL` VARCHAR(4) NOT NULL DEFAULT 'LF',
  `exportQuote` VARCHAR(4) NOT NULL DEFAULT '"',
  `bookAutoTax` enum('y','n') NOT NULL DEFAULT 'y',
  PRIMARY KEY (`bookId`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `tiki_acct_item` (
  `itemJournalId` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `itemAccountId` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `itemType` INT(1) NOT NULL DEFAULT '-1',
  `itemAmount` DOUBLE NOT NULL DEFAULT '0',
  `itemText` VARCHAR(255) NOT NULL DEFAULT '',
  `itemTs` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`itemJournalId`,`itemAccountId`,`itemType`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `tiki_acct_stackitem` (
  `stackItemStackId` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `stackItemAccountId` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `stackItemType` INT(1) NOT NULL DEFAULT '-1',
  `stackItemAmount` DOUBLE NOT NULL DEFAULT '0',
  `stackItemText` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`stackItemStackId`,`stackItemAccountId`,`stackItemType`)
) ENGINE=MyISAM;

INSERT IGNORE INTO `users_permissions` (`permName` , `permDesc` , `level` , `type` , `admin` , `feature_check` ) VALUES 
( 'tiki_p_acct_create_book', 'Can create/close a book', 'admin', 'accounting', 'y', 'feature_accounting'),
( 'tiki_p_acct_manage_accounts', 'Can create/edit/lock accounts', 'admin', 'accounting', 'y', 'feature_accounting' ),
( 'tiki_p_acct_book', 'Create a new transaction', 'editor', 'accounting', 'n', 'feature_accounting'),
( 'tiki_p_acct_view', 'Permission to view the journal', 'registered', 'accounting', 'n', 'feature_accounting' ),
( 'tiki_p_acct_book_stack', 'Can book into the stack, where statements can be changed', 'editor', 'accounting', 'n', 'feature_accounting'),
( 'tiki_p_acct_book_import', 'Can import statements from external accounts', 'editor', 'accounting', 'n', 'feature_accounting' ),
( 'tiki_p_acct_manage_template', 'Can manage templates for recurring transactions', 'editor', 'accounting', 'n', 'feature_accounting');
