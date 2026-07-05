-- SalesCraft database schema
-- Fresh install:  mysql -u USER -p DBNAME < sql/schema.sql

CREATE TABLE IF NOT EXISTS submissions (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    client_name    VARCHAR(150) NOT NULL,
    client_company VARCHAR(150) DEFAULT NULL,
    client_email   VARCHAR(190) DEFAULT NULL,
    client_phone   VARCHAR(40)  DEFAULT NULL,
    total          INT NOT NULL,
    max_score      INT NOT NULL DEFAULT 200,
    percent        DECIMAL(5,2) NOT NULL,
    band           VARCHAR(40) NOT NULL,
    categories     TEXT NOT NULL,   -- JSON: [{"name":"Sales Strategy","score":14}, ...]
    answers        TEXT NOT NULL,   -- JSON: {"0-0":3,"0-1":5, ...}
    ip             VARCHAR(45) DEFAULT NULL,
    user_agent     VARCHAR(255) DEFAULT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at),
    INDEX idx_email (client_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Key/value store for admin-editable settings (brand, SMTP, captcha, password, ...).
CREATE TABLE IF NOT EXISTS settings (
    skey  VARCHAR(64) PRIMARY KEY,
    sval  TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Saved in-progress answers so a client can resume by email.
CREATE TABLE IF NOT EXISTS progress (
    email      VARCHAR(190) PRIMARY KEY,
    answers    TEXT NOT NULL,
    step       INT NOT NULL DEFAULT 0,
    meta       TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Upgrading an EXISTING install (already had the submissions table)? Run:
--   ALTER TABLE submissions ADD COLUMN client_phone VARCHAR(40) DEFAULT NULL AFTER client_email;
--   (then the CREATE TABLE settings above)
-- ---------------------------------------------------------------------------
