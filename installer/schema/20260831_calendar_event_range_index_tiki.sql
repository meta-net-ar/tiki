ALTER TABLE `tiki_calendar_items`
    ADD KEY `idx_calendarid_start_end` (`calendarId`, `start`, `end`);
