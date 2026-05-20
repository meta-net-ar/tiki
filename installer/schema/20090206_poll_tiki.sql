#sylvieg
ALTER TABLE tiki_polls DROP COLUMN anonym;
ALTER TABLE tiki_user_votings ADD COLUMN TIME INT(14) NOT NULL;
ALTER TABLE tiki_user_votings ADD COLUMN ip VARCHAR(15) DEFAULT NULL AFTER user;
ALTER TABLE tiki_user_votings ADD KEY ip (`ip`);
ALTER TABLE tiki_user_votings ADD KEY id (`id`);
INSERT INTO users_permissions (`permName`, `permDesc`, level, type) VALUES ('tiki_p_view_poll_voters', 'Can view poll voters', 'basic', 'polls');
ALTER TABLE tiki_polls ADD COLUMN `voteConsiderationSpan` INT(4) DEFAULT 0;