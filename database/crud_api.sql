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