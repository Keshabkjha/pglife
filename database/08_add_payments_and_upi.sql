ALTER TABLE users ADD COLUMN upi_id VARCHAR(100) NULL COMMENT 'Owner UPI address for receiving payments';

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount INT NOT NULL,
    payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status TINYINT NOT NULL DEFAULT 0 COMMENT '0: Pending Verification, 1: Verified, 2: Rejected',
    utr_number VARCHAR(100) NULL,
    screenshot VARCHAR(255) NULL,
    CONSTRAINT fk_payments_bookings FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
