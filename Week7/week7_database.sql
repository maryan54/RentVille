/CREATE DATABASE IF NOT EXISTS rentville;
USE rentville;

CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    role       ENUM('superadmin','landlord','tenant') DEFAULT 'tenant',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tenants (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NOT NULL,
    name        VARCHAR(100) NOT NULL,
    phone       VARCHAR(20)  NOT NULL,
    email       VARCHAR(150) DEFAULT '',
    property    VARCHAR(100) NOT NULL,
    rent_amount DECIMAL(10,2) NOT NULL,
    lease_start DATE         NOT NULL,
    lease_end   DATE,
    status      VARCHAR(20)  DEFAULT 'Active',
    notes       TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Default superadmin account
-- Email: admin@rentville.com | Password: Admin@1234
INSERT INTO users (name, email, password, role) VALUES (
    'Super Admin',
    'admin@rentville.com',
    '$2b$10$6AkXH7Lyeu9cEwgil7JLquE8UDR6cbKGA4oIEKRSjWxA3dfM2xCNu',
    'superadmin'
);
