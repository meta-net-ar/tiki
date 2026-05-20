#gillesm 2008-10-27
#2008_10_26 GillesM
ALTER TABLE tiki_trackers ADD `groupforAlert` VARCHAR(255) DEFAULT NULL ;
ALTER TABLE tiki_trackers DROP `groupforAlert`;

