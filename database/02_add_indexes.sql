-- SQL Migration: Add optimized index keys to cities and reviews

ALTER TABLE `cities` ADD INDEX `idx_cities_name` (`name`);
ALTER TABLE `reviews` ADD INDEX `idx_reviews_created` (`created_at`);
