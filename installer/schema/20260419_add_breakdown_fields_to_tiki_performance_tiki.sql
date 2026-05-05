ALTER TABLE `tiki_performance`
    ADD COLUMN `backend_time` int(12) DEFAULT NULL AFTER `time_taken`,
    ADD COLUMN `frontend_time` int(12) DEFAULT NULL AFTER `backend_time`;
