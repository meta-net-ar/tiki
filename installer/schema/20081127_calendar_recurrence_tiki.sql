#bbenamran
# Create recurrence table on calendar items model, adding rules of recurrence
CREATE TABLE IF NOT EXISTS tiki_calendar_recurrence (
  `recurrenceId` INT(14) NOT NULL AUTO_INCREMENT,
  `calendarId` INT(14) NOT NULL DEFAULT '0',
  start INT(4) NOT NULL DEFAULT '0',
  end INT(4) NOT NULL DEFAULT '2359',
  allday TINYINT(1) NOT NULL DEFAULT '0',
  `locationId` INT(14) DEFAULT NULL,
  `categoryId` INT(14) DEFAULT NULL,
  `nlId` INT(12) NOT NULL DEFAULT '0',
  priority enum('1','2','3','4','5','6','7','8','9') NOT NULL DEFAULT '1',
  status enum('0','1','2') NOT NULL DEFAULT '0',
  url VARCHAR(255) DEFAULT NULL,
  lang CHAR(16) NOT NULL DEFAULT 'en',
  name VARCHAR(255) NOT NULL DEFAULT '',
  description BLOB,
  weekly TINYINT(1) DEFAULT '0',
  weekday TINYINT(1),
  monthly TINYINT(1) DEFAULT '0',
  `dayOfMonth` INT(2),
  yearly TINYINT(1) DEFAULT '0',
  `dateOfYear` INT(4),
  `nbRecurrences` INT(8),
  `startPeriod` INT(14),
  `endPeriod` INT(14),
  user VARCHAR(200) DEFAULT '',
  created INT(14) NOT NULL DEFAULT '0',
  lastmodif INT(14) NOT NULL DEFAULT '0',
  PRIMARY KEY (`recurrenceId`),
  KEY `calendarId` (`calendarId`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

# Add reference to recurrence table in calendar items table
ALTER TABLE `tiki_calendar_items`
    ADD `recurrenceId` INT(14);
ALTER TABLE tiki_calendar_items
    ADD CONSTRAINT fk_calitems_recurrence
        FOREIGN KEY (`recurrenceId`)
        REFERENCES tiki_calendar_recurrence(`recurrenceId`)
    ON UPDATE CASCADE
    ON DELETE SET NULL;

# Has a recurrent event been manually changed ?
ALTER TABLE `tiki_calendar_items`
    ADD changed TINYINT(1) DEFAULT '0';