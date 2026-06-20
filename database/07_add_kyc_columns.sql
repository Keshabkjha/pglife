ALTER TABLE users ADD COLUMN is_verified TINYINT DEFAULT 0 COMMENT '0: Not Verified, 1: Pending, 2: Verified';
ALTER TABLE users ADD COLUMN kyc_document VARCHAR(255) NULL;
