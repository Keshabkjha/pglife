-- SQL Migration: Add Latitude and Longitude to Properties table

ALTER TABLE `properties` ADD COLUMN `latitude` DECIMAL(10, 8) DEFAULT NULL AFTER `rent`;
ALTER TABLE `properties` ADD COLUMN `longitude` DECIMAL(11, 8) DEFAULT NULL AFTER `latitude`;

-- Seed coordinates for default properties from js/property_detail.js
UPDATE `properties` SET `latitude` = 28.64300000, `longitude` = 77.21500000 WHERE `id` = 1;
UPDATE `properties` SET `latitude` = 28.64250000, `longitude` = 77.21200000 WHERE `id` = 2;
UPDATE `properties` SET `latitude` = 19.10300000, `longitude` = 72.82700000 WHERE `id` = 3;
UPDATE `properties` SET `latitude` = 19.23000000, `longitude` = 72.83400000 WHERE `id` = 4;
UPDATE `properties` SET `latitude` = 19.23100000, `longitude` = 72.85800000 WHERE `id` = 5;

-- Seed approximate coordinates for other default cities so maps render reasonably
-- Bangalore properties
UPDATE `properties` SET `latitude` = 12.97840000, `longitude` = 77.64080000 WHERE `id` = 21; -- Indiranagar
UPDATE `properties` SET `latitude` = 12.93040000, `longitude` = 77.62260000 WHERE `id` = 22; -- Koramangala
-- Hyderabad properties
UPDATE `properties` SET `latitude` = 17.44830000, `longitude` = 78.37410000 WHERE `id` = 23; -- Hitec City
UPDATE `properties` SET `latitude` = 17.41560000, `longitude` = 78.44180000 WHERE `id` = 24; -- Banjara Hills
-- Kolkata properties
UPDATE `properties` SET `latitude` = 22.57260000, `longitude` = 88.43310000 WHERE `id` = 6;  -- Salt Lake
UPDATE `properties` SET `latitude` = 22.50500000, `longitude` = 88.36190000 WHERE `id` = 7;  -- Prince Anwar Shah Rd
-- Chennai properties
UPDATE `properties` SET `latitude` = 12.96940000, `longitude` = 80.24530000 WHERE `id` = 8;  -- Thoraipakkam
UPDATE `properties` SET `latitude` = 13.00630000, `longitude` = 80.25750000 WHERE `id` = 9;  -- Adyar
-- Pune properties
UPDATE `properties` SET `latitude` = 18.57930000, `longitude` = 73.73830000 WHERE `id` = 10; -- Hinjewadi
UPDATE `properties` SET `latitude` = 18.56790000, `longitude` = 73.91430000 WHERE `id` = 11; -- Viman Nagar
UPDATE `properties` SET `latitude` = 18.54000000, `longitude` = 73.90000000 WHERE `id` = 20; -- Kalyani Nagar
-- Ahmedabad properties
UPDATE `properties` SET `latitude` = 23.02450000, `longitude` = 72.52730000 WHERE `id` = 12; -- Satellite
UPDATE `properties` SET `latitude` = 23.03730000, `longitude` = 72.53150000 WHERE `id` = 13; -- Vastrapur
-- Jaipur properties
UPDATE `properties` SET `latitude` = 26.85240000, `longitude` = 75.80550000 WHERE `id` = 14; -- Malviya Nagar
UPDATE `properties` SET `latitude` = 26.91570000, `longitude` = 75.80860000 WHERE `id` = 15; -- C-Scheme
-- Noida properties
UPDATE `properties` SET `latitude` = 28.62730000, `longitude` = 77.37250000 WHERE `id` = 16; -- Sector 62
UPDATE `properties` SET `latitude` = 28.58350000, `longitude` = 77.31420000 WHERE `id` = 17; -- Sector 15
-- Gurgaon properties
UPDATE `properties` SET `latitude` = 28.49000000, `longitude` = 77.09000000 WHERE `id` = 18; -- DLF Phase 3
UPDATE `properties` SET `latitude` = 28.46000000, `longitude` = 77.08000000 WHERE `id` = 19; -- Sushant Lok
