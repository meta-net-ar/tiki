UPDATE `tiki_bruteforce_attempts` target
JOIN (
    SELECT MAX(`id`) keep_id, `operation`, `properties_hash`, SUM(`attempt_count`) attempt_count, MAX(`attempt_time`) attempt_time
    FROM `tiki_bruteforce_attempts`
    GROUP BY `operation`, `properties_hash`
    HAVING COUNT(*) > 1
) grouped ON target.`id` = grouped.keep_id
SET target.`attempt_count` = grouped.attempt_count,
    target.`attempt_time` = grouped.attempt_time;

DELETE duplicate_rows
FROM `tiki_bruteforce_attempts` duplicate_rows
JOIN (
    SELECT keep_id, `operation`, `properties_hash`
    FROM (
        SELECT MAX(`id`) keep_id, `operation`, `properties_hash`
        FROM `tiki_bruteforce_attempts`
        GROUP BY `operation`, `properties_hash`
        HAVING COUNT(*) > 1
    ) grouped_rows
) grouped ON duplicate_rows.`operation` = grouped.`operation`
    AND duplicate_rows.`properties_hash` = grouped.`properties_hash`
    AND duplicate_rows.`id` <> grouped.keep_id;

ALTER TABLE `tiki_bruteforce_attempts` DROP INDEX `idx_operation_hash`;
ALTER TABLE `tiki_bruteforce_attempts` ADD UNIQUE KEY `idx_operation_hash` (`operation`, `properties_hash`);
