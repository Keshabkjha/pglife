-- Migration 10: Smart Room Type Availability Management
-- Adds room_types table for per-property room inventory tracking

CREATE TABLE IF NOT EXISTS room_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    room_type ENUM('single', 'double', 'triple', 'dormitory', 'private') NOT NULL DEFAULT 'single',
    label VARCHAR(100) NOT NULL COMMENT 'Custom display label e.g. AC Single, Non-AC Double',
    price_per_month DECIMAL(10,2) NOT NULL,
    total_beds INT NOT NULL DEFAULT 1 COMMENT 'Total beds/rooms of this type',
    occupied_beds INT NOT NULL DEFAULT 0 COMMENT 'Currently occupied beds',
    amenities TEXT NULL COMMENT 'Comma-separated amenities specific to room type',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    INDEX idx_property_available (property_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed some test room types for the owner's property (Pune Elite Co-living, id=20)
INSERT INTO room_types (property_id, room_type, label, price_per_month, total_beds, occupied_beds, amenities) VALUES
(20, 'single', 'AC Single Room', 8500.00, 5, 2, 'AC,WiFi,Attached Bathroom'),
(20, 'double', 'Non-AC Double Sharing', 6000.00, 8, 6, 'WiFi,Common Bathroom'),
(20, 'triple', 'Triple Sharing (Budget)', 4500.00, 6, 3, 'WiFi,Fan');
