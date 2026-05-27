#Add needed columns
ALTER TABLE tiki_score CHANGE event event VARCHAR(255); #increase varchar length
ALTER TABLE tiki_score ADD COLUMN data TEXT;
ALTER TABLE tiki_score ADD COLUMN reversalEvent VARCHAR(255);
# Insert old values from tiki_score into new format
# Insert user stuff. Login, message, friend pts.
INSERT INTO tiki_score (event, data)
SELECT "tiki.user.login" AS event, concat('[{"ruleId":"User logs in","recipientType":"user","recipient":"user","score":"',score,'","validObjectIds":[""],"expiration":""}]') AS data FROM tiki_score WHERE event='login';
INSERT INTO tiki_score (event, data)
SELECT "tiki.user.view" AS event, concat('[{"ruleId":"See other user''s profile","recipientType":"user","recipient":"user","score":"',MAX(CASE WHEN event = 'profile_see' THEN score ELSE 0 end),'","validObjectIds":[""],"expiration":""},{"ruleId":"Have your profile seen","recipientType":"user","recipient":"object","score":"',MAX(CASE WHEN event = 'profile_is_seen' THEN score ELSE 0 end),'","validObjectIds":[""],"expiration":""}]') AS data FROM tiki_score LIMIT 1;
INSERT INTO tiki_score (event, data)
SELECT "tiki.user.friend" AS event, concat('[{"ruleId":"Make friends","recipientType":"user","recipient":"user","score":"',score,'","validObjectIds":[""],"expiration":""}]') AS data FROM tiki_score WHERE event='friend_new';
INSERT INTO tiki_score (event, data)
SELECT "tiki.user.message" AS event, concat('[{"ruleId":"Send message","recipientType":"user","recipient":"user","score":"',MAX(CASE WHEN event = 'message_send' THEN score ELSE 0 end),'","validObjectIds":[""],"expiration":""},{"ruleId":"Receive message","recipientType":"user","recipient":"object","score":"',MAX(CASE WHEN event = 'message_receive' THEN score ELSE 0 end),'","validObjectIds":[""],"expiration":""}]') AS data FROM tiki_score LIMIT 1;
# Articles
INSERT INTO tiki_score (event, data)
SELECT "tiki.article.create" AS event, concat('[{"ruleId":"Publish new article","recipientType":"user","recipient":"user","score":"',score,'","validObjectIds":[""],"expiration":""}]') AS data FROM tiki_score WHERE event='article_new';
INSERT INTO tiki_score (event, data)
SELECT "tiki.article.view" AS event, concat('[{"ruleId":"Read an article","recipientType":"user","recipient":"user","score":"',MAX(CASE WHEN event = 'article_read' THEN score ELSE 0 end),'","validObjectIds":[""],"expiration":""},{"ruleId":"Have your article read","recipientType":"user","recipient":"author","score":"',MAX(CASE WHEN event = 'article_is_read' THEN score ELSE 0 end),'","validObjectIds":[""],"expiration":""}]') AS data FROM tiki_score LIMIT 1;
# File Gallery
INSERT INTO tiki_score (event, data)
SELECT "tiki.filegallery.create" AS event, concat('[{"ruleId":"Create new file gallery","recipientType":"user","recipient":"user","score":"',score,'","validObjectIds":[""],"expiration":""}]') AS data FROM tiki_score WHERE event='fgallery_new';
INSERT INTO tiki_score (event, data)
SELECT "tiki.file.create" AS event, concat('[{"ruleId":"Upload new file to gallery","recipientType":"user","recipient":"user","score":"',score,'","validObjectIds":[""],"expiration":""}]') AS data FROM tiki_score WHERE event='fgallery_new_file';
INSERT INTO tiki_score (event, data)
SELECT "tiki.file.download" AS event, concat('[{"ruleId":"Download other user''s file","recipientType":"user","recipient":"user","score":"',MAX(CASE WHEN event = 'fgallery_download' THEN score ELSE 0 end),'","validObjectIds":[""],"expiration":""},{"ruleId":"Have your file downloaded","recipientType":"user","recipient":"owner","score":"',MAX(CASE WHEN event = 'fgallery_is_downloaded' THEN score ELSE 0 end),'","validObjectIds":[""],"expiration":""}]') AS data FROM tiki_score LIMIT 1;
# Image Gallery
INSERT INTO tiki_score (event, data)
SELECT "tiki.imagegallery.create" AS event, concat('[{"ruleId":"Create new image gallery","recipientType":"user","recipient":"user","score":"',score,'","validObjectIds":[""],"expiration":""}]') AS data FROM tiki_score WHERE event='igallery_new';
INSERT INTO tiki_score (event, data)
SELECT "tiki.image.create" AS event, concat('[{"ruleId":"Upload new image to gallery","recipientType":"user","recipient":"user","score":"',score,'","validObjectIds":[""],"expiration":""}]') AS data FROM tiki_score WHERE event='igallery_new_img';
INSERT INTO tiki_score (event, data)
SELECT "tiki.image.view" AS event, concat('[{"ruleId":"See other user''s image","recipientType":"user","recipient":"user","score":"',MAX(CASE WHEN event = 'igallery_see_img' THEN score ELSE 0 end),'","validObjectIds":[""],"expiration":""},{"ruleId":"Have your image seen","recipientType":"user","recipient":"owner","score":"',MAX(CASE WHEN event = 'igallery_img_seen' THEN score ELSE 0 end),'","validObjectIds":[""],"expiration":""}]') AS data FROM tiki_score LIMIT 1;
#Blogs
INSERT INTO tiki_score (event, data)
SELECT "tiki.blog.create" AS event, concat('[{"ruleId":"Create new blog","recipientType":"user","recipient":"user","score":"',score,'","validObjectIds":[""],"expiration":""}]') AS data FROM tiki_score WHERE event='blog_new';
INSERT INTO tiki_score (event, data)
SELECT "tiki.blogpost.create" AS event, concat('[{"ruleId":"Post in a blog","recipientType":"user","recipient":"user","score":"',score,'","validObjectIds":[""],"expiration":""}]') AS data FROM tiki_score WHERE event='blog_post';
INSERT INTO tiki_score (event, data)
SELECT "tiki.blog.view" AS event, concat('[{"ruleId":"Read other user''s blog","recipientType":"user","recipient":"user","score":"',MAX(CASE WHEN event = 'blog_read' THEN score ELSE 0 end),'","validObjectIds":[""],"expiration":""},{"ruleId":"Have your blog read","recipientType":"user","recipient":"author","score":"',MAX(CASE WHEN event = 'blog_is_read' THEN score ELSE 0 end),'","validObjectIds":[""],"expiration":""}]') AS data FROM tiki_score LIMIT 1;
# Wikis
INSERT INTO tiki_score (event, data)
SELECT "tiki.wiki.create" AS event, concat('[{"ruleId":"Create a wiki page","recipientType":"user","recipient":"user","score":"',score,'","validObjectIds":[""],"expiration":""}]') AS data FROM tiki_score WHERE event='wiki_new';
INSERT INTO tiki_score (event, data)
SELECT "tiki.wiki.update" AS event, concat('[{"ruleId":"Edit an existing wiki page","recipientType":"user","recipient":"user","score":"',score,'","validObjectIds":[""],"expiration":""}]') AS data FROM tiki_score WHERE event='wiki_edit';
INSERT INTO tiki_score (event, data)
SELECT "tiki.wiki.attachfile" AS event, concat('[{"ruleId":"Attach file to wiki page","recipientType":"user","recipient":"user","score":"',score,'","validObjectIds":[""],"expiration":""}]') AS data FROM tiki_score WHERE event='wiki_attach_file';
# Remove unnecessary fields
ALTER TABLE tiki_score DROP COLUMN score;
ALTER TABLE tiki_score DROP COLUMN expiration;
ALTER TABLE tiki_score DROP COLUMN validObjectIds;
#remove old scores from the tiki_score table
DELETE FROM tiki_score WHERE data IS NULL;

CREATE TABLE IF NOT EXISTS `tiki_object_scores` (
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  triggerObjectType VARCHAR(255) NOT NULL,
  triggerObjectId VARCHAR(255) NOT NULL,
  triggerUser VARCHAR(255) NOT NULL,
  triggerEvent VARCHAR(255) NOT NULL,
  ruleId VARCHAR(255) NOT NULL,
  recipientObjectType VARCHAR(255) NOT NULL,
  recipientObjectId VARCHAR(255) NOT NULL,
  pointsAssigned INT NOT NULL,
  pointsBalance INT NOT NULL,
  DATE INT NOT NULL,
  reversalOf INT UNSIGNED
) ENGINE=MyISAM;
# Import legacy score from users table
INSERT INTO tiki_object_scores (triggerObjectType, triggerObjectId, triggerUser, triggerEvent, ruleId, recipientObjectType, recipientObjectId, pointsAssigned, pointsBalance, DATE)
SELECT 'legacy_score', '0', login, 'tiki.legacy.score', 'Legacy Score', 'user', login, score, score, UNIX_TIMESTAMP() FROM users_users;
# Drop users score column, we now use pointsBalance from tiki_object_scores table
ALTER TABLE users_users DROP COLUMN score;
