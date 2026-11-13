CREATE DATABASE IF NOT EXISTS inventory_sales_db;
USE inventory_sales_db;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','cashier') NOT NULL
);
CREATE TABLE items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  category VARCHAR(50),
  price DECIMAL(10,2) NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  reorder_level INT NOT NULL DEFAULT 10
);
CREATE TABLE sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  sale_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE TABLE sale_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT NOT NULL,
  item_id INT NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (sale_id) REFERENCES sales(id),
  FOREIGN KEY (item_id) REFERENCES items(id)
);

INSERT INTO items (name, category, price, quantity, reorder_level) VALUES
('Rice 1kg', 'Grocery', 58.00, 120, 30),
('Cooking Oil 1L', 'Grocery', 95.00, 8, 15),
('Instant Noodles', 'Grocery', 14.00, 200, 50),
('Bottled Water 500ml', 'Drinks', 15.00, 12, 24),
('Notebook', 'School Supplies', 35.00, 60, 20),
('Ballpen (Blue)', 'School Supplies', 10.00, 5, 20);
