-- Create database
CREATE DATABASE IF NOT EXISTS slsu_system;
USE slsu_system;

-- ==========================
-- TABLE: submissions
-- ==========================
CREATE TABLE IF NOT EXISTS submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    iptype ENUM('PATENT','TRADEMARK','COPYRIGHT') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================
-- TABLE: submission_files
-- ==========================
CREATE TABLE IF NOT EXISTS submission_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE
);

-- ==========================
-- TABLE: admin_users
-- ==========================
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,  -- hashed password
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert example admin user (hashed using SHA2)
INSERT INTO admin_users (username, password) 
VALUES ('admin', SHA2('123456', 256));
