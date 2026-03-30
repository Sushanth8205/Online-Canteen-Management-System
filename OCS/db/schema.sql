CREATE DATABASE IF NOT EXISTS canteen_db8;
USE canteen_db8;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  username VARCHAR(60) NOT NULL,
  phone VARCHAR(15) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role_of ENUM('customer','staff') NOT NULL DEFAULT 'customer',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_username (username)
);

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  username VARCHAR(60) NOT NULL,
  phone VARCHAR(15) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  admin_level ENUM('super_admin','admin') NOT NULL DEFAULT 'admin',
  permissions TEXT NULL, -- JSON string of specific permissions
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_admins_email (email),
  UNIQUE KEY uq_admins_username (username)
);

CREATE TABLE IF NOT EXISTS dishes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL,
  category VARCHAR(50) NOT NULL,
  image_url VARCHAR(255) NULL,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  preparation_time INT NULL, -- in minutes
  dietary_info ENUM('vegetarian','non-vegetarian','vegan','gluten-free') NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cart_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  dish_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (dish_id) REFERENCES dishes(id) ON DELETE CASCADE
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  payment_method VARCHAR(20) NULL,
  seat_number INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create order_items table
CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  dish_id INT NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (dish_id) REFERENCES dishes(id) ON DELETE CASCADE
);


-- Insert sample dishes
INSERT INTO dishes (name, description, price, category, image_url, preparation_time, dietary_info) VALUES
('Biryani', 'Fragrant rice dish with aromatic spices and tender meat', 120.00, 'rice', 'images/biryani.jpg', 25, 'non-vegetarian'),
('Paneer Butter Masala', 'Soft cottage cheese cubes in rich creamy tomato gravy', 150.00, 'curry', 'images/paneer-butter.jpg', 20, 'vegetarian'),
('Chicken Fried Rice', 'Stir-fried rice with chicken and vegetables', 110.00, 'rice', 'images/chicken-fried-rice.jpg', 15, 'non-vegetarian'),
('Veg Manchurian', 'Crispy vegetable balls in tangy Indo-Chinese gravy', 130.00, 'starter', 'images/veg-manchurian.jpg', 18, 'vegetarian'),
('Masala Dosa', 'Crispy rice crepe with spiced potato filling', 80.00, 'breakfast', 'images/masala-dosa.jpg', 15, 'vegetarian'),
('Chicken Noodles', 'Stir-fried noodles with chicken and vegetables', 100.00, 'noodles', 'images/chicken-noodles.jpg', 12, 'non-vegetarian'),
('Dal Makhani', 'Creamy black lentils cooked with butter and spices', 140.00, 'curry', 'images/dal-makhani.jpg', 22, 'vegetarian'),
('Chicken Tikka', 'Grilled chicken marinated in yogurt and spices', 160.00, 'starter', 'images/chicken-tikka.jpg', 20, 'non-vegetarian'),
('Veg Pulao', 'Fragrant rice cooked with mixed vegetables', 90.00, 'rice', 'images/veg-pulao.jpg', 15, 'vegetarian'),
('Egg Curry', 'Hard-boiled eggs in spicy onion-tomato gravy', 110.00, 'curry', 'images/egg-curry.jpg', 18, 'non-vegetarian'),
('Spring Rolls', 'Crispy vegetable rolls served with sweet chili sauce', 85.00, 'starter', 'images/spring-rolls.jpg', 12, 'vegetarian'),
('Samosa', 'Crispy pastry filled with spiced potatoes and peas', 40.00, 'starter', 'images/samosa.jpg', 10, 'vegetarian'),
('Butter Chicken', 'Tender chicken in rich buttery tomato gravy', 180.00, 'curry', 'images/butter-chicken.jpg', 25, 'non-vegetarian'),
('Mixed Veg', 'Seasonal vegetables stir-fried with Indian spices', 120.00, 'curry', 'images/mixed-veg.jpg', 15, 'vegetarian'),
('Fried Noodles', 'Stir-fried noodles with vegetables and soy sauce', 80.00, 'noodles', 'images/fried-noodles.jpg', 10, 'vegetarian'),
('Chole Bhature', 'Spicy chickpea curry with fluffy fried bread', 95.00, 'breakfast', 'images/chole-bhature.jpg', 20, 'vegetarian'),
('Fish Curry', 'Fresh fish cooked in aromatic coconut gravy', 200.00, 'curry', 'images/fish-curry.jpg', 25, 'non-vegetarian'),
('Palak Paneer', 'Cottage cheese cubes in creamy spinach gravy', 145.00, 'curry', 'images/palak-paneer.jpg', 20, 'vegetarian'),
('Hakka Noodles', 'Indo-Chinese style spicy noodles', 90.00, 'noodles', 'images/hakka-noodles.jpg', 12, 'vegetarian'),
('Idli Sambar', 'Steamed rice cakes with lentil soup', 60.00, 'breakfast', 'images/idli-sambar.jpg', 15, 'vegetarian'),
('Mutton Rogan Josh', 'Tender mutton in aromatic Kashmiri spices', 220.00, 'curry', 'images/mutton-rogan.jpg', 30, 'non-vegetarian');


