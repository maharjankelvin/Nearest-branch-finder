-- Food Ordering System Database Schema
CREATE DATABASE food_ordering;
USE food_ordering;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'branch_moderator', 'user') NOT NULL DEFAULT 'user',
    branch_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Branches table
CREATE TABLE branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    phone VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Menu items table
CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(50),
    image_url VARCHAR(255),
    status ENUM('available', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    branch_id INT NOT NULL,
    delivery_address TEXT NOT NULL,
    delivery_latitude DECIMAL(10, 8),
    delivery_longitude DECIMAL(11, 8),
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@foodsystem.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample branches (Kathmandu, Nepal locations)
INSERT INTO branches (name, address, latitude, longitude, phone) VALUES 
('Swayambhu Branch', 'Swayambhunath Temple Area, Kathmandu 44600, Nepal', 27.7151, 85.2904, '+977-01-4270101'),
('Dharahara Branch', 'Sundhara, Dharahara Tower Area, Kathmandu 44600, Nepal', 27.7040, 85.3129, '+977-01-4270102'),
('Gongabu Branch', 'Gongabu Chowk, Ring Road, Kathmandu 44600, Nepal', 27.7345, 85.3234, '+977-01-4270103'),
('Gaushala Branch', 'Gaushala, Bagmati Bridge Area, Kathmandu 44600, Nepal', 27.6958, 85.3454, '+977-01-4270104');

-- Insert sample menu items
INSERT INTO menu_items (name, description, price, category) VALUES 
('Margherita Pizza', 'Classic pizza with tomato sauce, mozzarella, and basil', 12.99, 'Pizza'),
('Chicken Burger', 'Grilled chicken breast with lettuce, tomato, and mayo', 8.99, 'Burgers'),
('Caesar Salad', 'Fresh romaine lettuce with Caesar dressing and croutons', 7.99, 'Salads'),
('Pasta Carbonara', 'Creamy pasta with bacon and parmesan cheese', 11.99, 'Pasta'),
('Fish & Chips', 'Beer-battered fish with crispy fries', 13.99, 'Main Course');
