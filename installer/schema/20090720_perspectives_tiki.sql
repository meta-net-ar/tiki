
CREATE TABLE IF NOT EXISTS tiki_perspectives (
    `perspectiveId` INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    PRIMARY KEY( `perspectiveId` )
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS tiki_perspective_preferences (
    `perspectiveId` INT NOT NULL,
    pref VARCHAR(40) NOT NULL,
    value text,
    PRIMARY KEY( `perspectiveId`, pref )
) ENGINE=MyISAM;

