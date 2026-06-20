-- SQL Migration: Add user_id Foreign Key to reviews table

ALTER TABLE `reviews` ADD `user_id` INT NULL AFTER `property_id`;

-- Map legacy reviews to a default existing user (ID 1)
UPDATE `reviews` SET `user_id` = 1;

-- Enforce NOT NULL and create Foreign Key constraint
ALTER TABLE `reviews` MODIFY `user_id` INT NOT NULL;
ALTER TABLE `reviews` ADD CONSTRAINT `fk_reviews_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
