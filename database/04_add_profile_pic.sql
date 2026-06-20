-- SQL Migration: Add profile_pic column to users table

ALTER TABLE `users` ADD `profile_pic` VARCHAR(255) NULL AFTER `role`;
