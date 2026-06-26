-- Migration 15: Add room_type_id to bookings table
-- Enables tracking which room type is associated with a booking

ALTER TABLE bookings ADD COLUMN room_type_id INT NULL;
ALTER TABLE bookings ADD CONSTRAINT fk_bookings_room_type FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE SET NULL;
