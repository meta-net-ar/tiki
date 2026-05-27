#sylvieg
ALTER TABLE `tiki_actionlog` ADD KEY `lastModif`(`lastModif`);
ALTER TABLE `tiki_actionlog` ADD KEY `object`(`object`(100), `objectType`, `action`(100));
ALTER TABLE `tiki_wiki_attachments` ADD KEY  `page` (`page`);