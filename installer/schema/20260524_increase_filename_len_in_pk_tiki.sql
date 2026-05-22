ALTER TABLE `tiki_secdb`
DROP PRIMARY KEY,
ADD PRIMARY KEY (`filename`(186), `tiki_version`(5));