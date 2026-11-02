-- Starparts Motor WhatsApp AI - Phase 2
-- Import this SQL using phpMyAdmin against the same database as the website.
-- No existing website tables are altered.

CREATE TABLE IF NOT EXISTS `whatsapp_webhook_events` (
  `event_key` char(64) NOT NULL,
  `sender` varchar(64) NOT NULL DEFAULT '',
  `message_hash` char(64) NOT NULL,
  `status` enum('processing','sent','ignored','failed') NOT NULL DEFAULT 'processing',
  `status_detail` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`event_key`),
  KEY `idx_whatsapp_events_created_at` (`created_at`),
  KEY `idx_whatsapp_events_sender` (`sender`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `whatsapp_conversation_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sender` varchar(64) NOT NULL,
  `role` enum('user','assistant') NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_whatsapp_conversation_sender_created` (`sender`,`created_at`),
  KEY `idx_whatsapp_conversation_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
