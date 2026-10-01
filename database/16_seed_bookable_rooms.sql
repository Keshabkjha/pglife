-- Default bookable inventory for listings that have no room types yet.
INSERT INTO room_types (property_id, room_type, label, price_per_month, total_beds, occupied_beds, amenities)
SELECT p.id, 'double', 'Double Sharing', p.rent, 6, 1, 'WiFi,Bed'
FROM properties p
LEFT JOIN (
    SELECT DISTINCT property_id FROM room_types
) has_rooms ON has_rooms.property_id = p.id
WHERE has_rooms.property_id IS NULL;

INSERT INTO room_types (property_id, room_type, label, price_per_month, total_beds, occupied_beds, amenities)
SELECT p.id, 'single', 'Single Room', ROUND(p.rent * 1.35), 3, 0, 'WiFi,AC'
FROM properties p
LEFT JOIN (
    SELECT DISTINCT property_id FROM room_types WHERE room_type = 'single'
) singles ON singles.property_id = p.id
WHERE singles.property_id IS NULL;

-- Demo owner account can manage the seeded catalogue.
UPDATE properties SET owner_id = 2 WHERE owner_id IS NULL;
