-- Create ChatApp DataBase if not exist

-- remove command in line 5 and 6 if u will be use MySQL but SQLite not remove command

-- CREATE DATABASE IF NOT EXISTS chat_app;
-- USE chat_app;

-- Create users table if not exist
CREATE TABLE IF NOT EXISTS users (
    -- if u use MySQL add AUTO_INCREMENT in id column after KEY, if u use SQLite don't add this keyword
    id INTEGER PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

    -- if u want add feture change user data in application do that {
        -- if u use MySQL so add this column
        -- update_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        
        -- if u use SQLite so add same column without ON UPDATE CURRENT_TIMESTAMP
        -- update_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        -- then update this column with PHP code after every change because SQLite does't have ON UPDATE CURRENT_TIMESTAMP
    --}

); -- if u use MySQL add ENGINE=InnoDB before ; and after ) LIKE ) ENGINE=InnoDB;

-- create messages table if not exist
CREATE TABLE IF NOT EXISTS messages (
    -- if u use MySQL add AUTO_INCREMENT in id column after KEY, if u use SQLite don't add this keyword
    id INTEGER PRIMARY KEY,
    sender_id INT NOT NULL,
    user_message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- if u want add feture change user data in application do that {
        -- if u use MySQL so add this column
        -- update_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        
        -- if u use SQLite so add same column without ON UPDATE CURRENT_TIMESTAMP
        -- update_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        -- then update this column with PHP code after every change because SQLite does't have ON UPDATE CURRENT_TIMESTAMP
    --}
    
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
); -- if u use MySQL add ENGINE=InnoDB before ; and after ) LIKE ) ENGINE=InnoDB;


-- Table Verification Code Users
CREATE TABLE IF NOT EXISTS verification_code (
    -- if u use MySQL add AUTO_INCREMENT in id column after KEY, if u use SQLite don't add this keyword
    id INTEGER PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(10) NOT NULL,
    expires_at INT NOT NULL
);