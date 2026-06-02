-- MySQL schema for CRUD Web API
CREATE TABLE IF NOT EXISTS sv1112_db.products (
    product_id INT NOT NULL AUTO_INCREMENT,
    
    product_name VARCHAR(50) NOT NULL,
    
    price DECIMAL(20,2) DEFAULT 0,
    
    quantity INT NOT NULL DEFAULT 0,
    
    amount DECIMAL(20,2)
    GENERATED ALWAYS AS (price * quantity) STORED,
    
    description TEXT DEFAULT NULL,
    
    image VARCHAR(100) DEFAULT NULL,
    
    rating DECIMAL(10,2) DEFAULT 0,
    
    create_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    category_id INT DEFAULT NULL,
    
    PRIMARY KEY (product_id)
);

-- Sample data
INSERT INTO sv1112_db.products (product_name, price, quantity, description, image, rating, category_id) VALUES
('Apple Juice', 12.99, 50, 'Fresh pressed apple juice', 'apple-juice.jpg', 4.5, 1),
('Orange Juice', 10.99, 45, 'Natural orange juice', 'orange-juice.jpg', 4.3, 1),
('Banana', 5.99, 100, 'Fresh organic bananas', 'banana.jpg', 4.8, 2),
('Milk', 3.99, 60, 'Whole milk 1 liter', 'milk.jpg', 4.2, 3),
('Bread', 2.99, 80, 'Whole wheat bread', 'bread.jpg', 4.6, 4),
('Cheese', 8.99, 30, 'Aged cheddar cheese', 'cheese.jpg', 4.7, 3),
('Yogurt', 4.49, 40, 'Greek yogurt plain', 'yogurt.jpg', 4.4, 3),
('Almonds', 15.99, 25, 'Raw roasted almonds', 'almonds.jpg', 4.9, 5),
('Apples', 7.99, 120, 'Red delicious apples', 'apples.jpg', 4.5, 2),
('Avocado', 3.49, 35, 'Ripe avocados', 'avocado.jpg', 4.6, 2);