-- Database Migration: Add primary_image column to properties table
ALTER TABLE properties ADD COLUMN primary_image VARCHAR(255) NULL AFTER longitude;
