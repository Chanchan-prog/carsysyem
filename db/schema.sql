-- Car Loan System schema

-- Users
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  role ENUM('admin','customer') NOT NULL DEFAULT 'customer',
  email VARCHAR(150) UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cars (catalog)
CREATE TABLE IF NOT EXISTS cars (
  id INT AUTO_INCREMENT PRIMARY KEY,
  model VARCHAR(120) NOT NULL,
  plate_no VARCHAR(20) UNIQUE,
  price DECIMAL(12,2) NOT NULL,
  is_active TINYINT(1) DEFAULT 1
);

-- Loan applications
CREATE TABLE IF NOT EXISTS loans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  car_id INT NOT NULL,
  car_price DECIMAL(12,2) NOT NULL,
  down_payment DECIMAL(12,2) NOT NULL,
  principal DECIMAL(12,2) NOT NULL,
  annual_interest_rate DECIMAL(5,2) NOT NULL,
  term_months INT NOT NULL,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  admin_note VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  approved_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (car_id) REFERENCES cars(id)
);

-- EMI schedule
CREATE TABLE IF NOT EXISTS emis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  loan_id INT NOT NULL,
  installment_no INT NOT NULL,
  due_date DATE NOT NULL,
  principal_component DECIMAL(12,2) NOT NULL,
  interest_component DECIMAL(12,2) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  status ENUM('due','paid','late') DEFAULT 'due',
  paid_at DATETIME NULL,
  FOREIGN KEY (loan_id) REFERENCES loans(id)
);

-- Payments (receipts)
CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  emi_id INT NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  method VARCHAR(50) DEFAULT 'manual',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (emi_id) REFERENCES emis(id)
);

-- Seed admin and sample cars
INSERT INTO users (username, password_hash, full_name, role, email)
SELECT 'admin', SHA2('admin123', 256), 'Administrator', 'admin', 'admin@example.com'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='admin');

INSERT INTO cars (model, price)
SELECT 'Sedan S', 15000.00 WHERE NOT EXISTS (SELECT 1 FROM cars);
INSERT INTO cars (model, price) VALUES ('SUV X', 24000.00), ('Hatchback H', 12000.00);


