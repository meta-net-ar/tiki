-- 20160315_correct_blogpost_encoding_tiki.sql missed the simple case where wysiwyg is not set
-- in this case, wysiwyg_htmltowiki is irrelevant as averything is stored in tiki syntax and requires fixing, too
SET @CONVERT='n';
SET @wysiwyg='n';
SELECT (@wysiwyg:=value) FROM tiki_preferences WHERE `name`='feature_wysiwyg';
SELECT (@CONVERT:=value) FROM tiki_preferences WHERE `name`='wysiwyg_htmltowiki' ;
-- If wysiwyg_htmltowiki is set, don't fix as it has been done already in 20160315_correct_blogpost_encoding_tiki.sql
UPDATE `tiki_blog_posts` SET `data` = REPLACE(`data`, '&gt;', '>') WHERE @wysiwyg='n' AND @CONVERT='n';
UPDATE `tiki_blog_posts` SET `data` = REPLACE(`data`, '&lt;', '<') WHERE @wysiwyg='n' AND @CONVERT='n';
UPDATE `tiki_blog_posts` SET `data` = REPLACE(`data`, '&amp;', '&') WHERE @wysiwyg='n' AND @CONVERT='n';

