ALTER TABLE `tiki_files`
  ADD COLUMN `objectType` varchar(200) DEFAULT NULL,
  ADD COLUMN `objectId` int(11) DEFAULT NULL;