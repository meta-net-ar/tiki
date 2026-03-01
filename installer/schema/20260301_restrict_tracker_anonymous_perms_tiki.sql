-- Upgrade the permission level in the users_permissions dictionary
UPDATE `users_permissions` SET `level` = 'registered' WHERE `permName` IN (
    'tiki_p_list_trackers',
    'tiki_p_view_trackers',
    'tiki_p_tracker_view_comments',
    'tiki_p_comment_tracker_items',
    'tiki_p_tracker_view_ratings'
);

