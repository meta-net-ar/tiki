ALTER TABLE `tiki_object_scores`
  ADD KEY `recipient_latest` (`recipientObjectType`(32), `recipientObjectId`(191), `id`),
  ADD KEY `recipient_rule` (`recipientObjectType`(32), `recipientObjectId`(128), `ruleId`(80)),
  ADD KEY `trigger_event` (`triggerObjectType`(32), `triggerObjectId`(64), `triggerUser`(64), `triggerEvent`(64), `id`),
  ADD KEY `trigger_rule` (`triggerObjectType`(24), `triggerObjectId`(64), `ruleId`(48), `recipientObjectType`(24), `recipientObjectId`(64), `reversalOf`, `id`);
