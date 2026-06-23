ALTER TABLE `tiki_performance`
    ADD COLUMN `backend_time` int DEFAULT NULL AFTER `time_taken`,
    ADD COLUMN `frontend_time` int DEFAULT NULL AFTER `backend_time`;
