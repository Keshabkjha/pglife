-- Chat enhancements: delivered tracking, soft-delete, indexing
-- Run after 09_add_chat_table.sql

-- Add delivered_at column for message delivery tracking
ALTER TABLE messages ADD COLUMN delivered_at DATETIME NULL DEFAULT NULL AFTER is_read;

-- Add soft-delete columns
ALTER TABLE messages ADD COLUMN deleted_by_sender TINYINT NOT NULL DEFAULT 0 AFTER delivered_at;
ALTER TABLE messages ADD COLUMN deleted_by_receiver TINYINT NOT NULL DEFAULT 0 AFTER deleted_by_sender;

-- Add composite index for fast incremental polling
CREATE INDEX idx_messages_conversation ON messages (property_id, sender_id, receiver_id, id);
