ALTER TABLE `tiki_file_galleries` ADD `image_max_dimension` INT( 8 ) NOT NULL DEFAULT '1600';
ALTER TABLE `tiki_file_galleries` DROP `image_max_size_x`;
ALTER TABLE `tiki_file_galleries` DROP `image_max_size_y`;