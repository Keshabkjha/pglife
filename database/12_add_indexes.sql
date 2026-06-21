-- Database indexing audit - adds missing indexes for frequently queried columns

-- Bookings: lookups by user_id + property_id are common (cancel, check duplicate)
-- Already has: PRIMARY KEY, KEY user_id, KEY property_id, UNIQUE(user_id, property_id)

-- Messages: conversation queries filter by sender_id, receiver_id, property_id
CREATE INDEX idx_messages_sender_property ON messages(sender_id, property_id);
CREATE INDEX idx_messages_receiver_property ON messages(receiver_id, property_id);
CREATE INDEX idx_messages_unread ON messages(receiver_id, is_read);

-- Reviews: lookups by property_id + user_id (duplicate check, property page)
CREATE INDEX idx_reviews_property_user ON reviews(property_id, user_id);

-- Interested users: toggle checks by user_id + property_id
-- Already has: KEY user_id, KEY property_id (covers most queries)

-- Maintenance tickets: lookups by property_id + user_id
CREATE INDEX idx_tickets_property_user ON maintenance_tickets(property_id, user_id);

-- Payments: lookups by booking_id (dashboard queries)
-- Already has: fk_payments_bookings on booking_id

-- Room types: lookups by property_id + is_active
-- Already has: idx_property_available (property_id, is_active)

-- Properties: lookups by city_id and owner_id
CREATE INDEX idx_properties_owner ON properties(owner_id);
CREATE INDEX idx_properties_city_rent ON properties(city_id, rent);
