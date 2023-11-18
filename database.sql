-- database.sql

CREATE DATABASE bipe;
USE bipe;

CREATE TABLE IF NOT EXISTS tasks (
  id INT NOT NULL AUTO_INCREMENT,
  category_id INT,
  client_id INT,
  code CHAR(4),
  name VARCHAR(50),
  rate DECIMAL(5, 2),
  active TINYINT(1) DEFAULT 1,
  readonly TINYINT(1) DEFAULT 0,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS quotations (
  id INT NOT NULL AUTO_INCREMENT,
  task_id INT NOT NULL,
  amount DECIMAL(8, 2) NOT NULL,
  description VARCHAR(50),
  nature ENUM('i', 'e'), -- income/expense
  day DATE NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS invoices (
  id INT NOT NULL AUTO_INCREMENT,
  task_id INT NOT NULL,
  amount DECIMAL(8, 2) NOT NULL,
  description VARCHAR(50),
  nature ENUM('i', 'e'),
  day DATE NOT NULL,
  sent TINYINT(1) DEFAULT 0,
  settled TINYINT(1) DEFAULT 0,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS categories (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS clients (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL,
  address VARCHAR(50),
  city VARCHAR(30),
  postcode VARCHAR(10),
  email VARCHAR(50),
  phone VARCHAR(18),
  vat_code VARCHAR(12),
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS time_log (
  id INT NOT NULL AUTO_INCREMENT,
  user_id VARCHAR(20) NOT NULL,
  task_id INT NOT NULL,
  day DATE NOT NULL,
  duration DECIMAL(5, 2),
  saved TINYINT(1) DEFAULT 0,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS holidays (
  id INT NOT NULL AUTO_INCREMENT,
  month TINYINT(2) NOT NULL,
  day TINYINT(2) NOT NULL,
  description VARCHAR(50),
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS expenses_reclaim (
  id INT NOT NULL AUTO_INCREMENT,
  user_id VARCHAR(20) NOT NULL,
  task_id INT NOT NULL,
  amount DECIMAL(5, 2) NOT NULL,
  description VARCHAR(50),
  nature ENUM('m', 'r'), -- mileage/receipt
  day DATE NOT NULL,
  paid_back TINYINT(1) DEFAULT 0,
  PRIMARY KEY (id)
);

INSERT INTO tasks (id, code, category_id, client_id, name, rate, active, 
  readonly) VALUES
  (1, null, null, null, null, null, 1, 1), -- weekend/nothing
  (2, null, null, null, null, null, 1, 1), -- holiday
  (3, null, null, null, null, null, 1, 1), -- off sick
  (4, null, null, null, null, null, 1, 1), -- leave
  (5, null, null, null, null, null, 1, 1), -- off
  (6, null, null, null, null, null, 1, 1), -- unpaid
  (7, null, null, null, null, null, 0, 1), -- reserved
  (8, null, null, null, null, null, 0, 1), -- reserved
  (9, null, null, null, null, null, 0, 1), -- reserved
  (10, null ,null, null, null, null, 0, 1), -- reserved
  (11, 2201, 1, 1, 'bloc de pisos a arenys', 30, 1, 0),
  (12, 2202, null, null, 'cèdula habitabilitat major 73', 20, 0, 0),
  (13, 2203, 4, 2, 'ampliació nau industrial polígon sud', 30, 1, 0),
  (14, null, 3, null, 'visitant obra del jaume', null, 1, 0);

INSERT INTO quotations (task_id, amount, description, nature, day) VALUES
  (11, 120000.00, 'honoraris H2201', 'i', '2022-01-01'),
  (11, 16000.00, 'honoraris addicionals H2203', 'i', '2022-04-01'),
  (12, 120.00, null, 'i', '2023-01-15'),
  (13, 3000.00, 'pressupost P2207', 'i', '2022-12-24'),
  (13, 175.00, 'pressupost lloguer grua', 'e', '2023-01-11');

INSERT INTO invoices (task_id, amount, description, settled, nature, day)
  VALUES
  (11, 40000.00, 'provisió de fons H2201: factura F2207', 1, 'i',
    '2022-01-02'),
  (12, 120.00, 'factura F2215', 0, 'i', '2023-01-29'),
  (13, 750.00, 'provisió de fons P2207: factura F2212', 1, 'i', '2022-12-27'),
  (13, 1125.00, 'factura 1/2 de P2207: F2213', 0, 'i', '2023-01-29'),
  (13, 200.00, 'lloguer grua', 0, 'e', '2023-02-15');

INSERT INTO clients (name, address, city, postcode, vat_code, email) VALUES
  ('Desenvolupaments Pons', '11 Ronda Pedrolo',
    'Palma', 'PA1 3FH', '112358', null),
  ('Mulberry Trench S. Ltda.', '1180 Av. de la independència', 'Barcelona',
    'B15 AZ1', '132134', 'info@multrench.com');

INSERT INTO time_log (user_id, task_id, day, duration, saved) VALUES
  ('jordi.bs', 2, '2021-08-01', 8, 1),
  ('jordi.bs', 2, '2021-08-02', 8, 1),
  ('jordi.bs', 2, '2021-08-03', 4, 1),
  ('jordi.bs', 2, '2022-11-01', 8, 1),
  ('jordi.bs', 2, '2022-11-02', 8, 1),
  ('jordi.bs', 2, '2022-11-03', 8, 1),
  ('jordi.bs', 3, '2022-11-04', 8, 1),
  ('jordi.bs', 3, '2022-11-05', 4, 1),
  ('jordi.bs', 14, '2023-07-26', 8, 1),
  ('jordi.bs', 14, '2023-07-27', 8, 1),
  ('jordi.bs', 11, '2023-07-28', 8, 1),
  ('jordi.bs', 11, '2023-07-29', 8, 1),
  ('jordi.bs', 11, '2023-07-30', 8, 1),
  ('jordi.bs', 13, '2023-07-31', 8, 1),
  ('jordi.bs', 13, '2023-08-01', 8, 1),
  ('jordi.bs', 13, '2023-08-02', 4.5, 1),
  ('jordi.bs', 13, '2023-08-03', 4.75, 1),
  ('jordi.bs', 13, '2023-08-04', 8, 1),
  ('jordi.bs', 13, '2023-08-05', 8, 1),
  ('jordi.bs', 13, '2023-08-23', 8, 1),
  ('jordi.bs', 13, '2023-08-24', 8, 1),
  ('jordi.bs', 13, '2023-08-25', 8, 1),
  ('jordi.bs', 4, '2023-08-26', null, 1),
  ('jordi.bs', 4, '2023-08-27', null, 1),
  ('jordi.bs', 4, '2023-08-28', null, 1);

INSERT INTO expenses_reclaim (user_id, task_id, amount, description, nature,
  day, paid_back)
  VALUES
  ('jordi.bs', 11, 17.86, 'viatge a Lleida km', 'm', '2023-03-01', 1),
  ('jordi.bs', 11, 12.50, 'dinar a Mataró', 'r', '2023-03-01', 1),
  ('jordi.bs', 13, 1.71, 'transport eines al polígon', 'm', '2023-03-15', 1),
  ('jordi.bs', 13, 12.00, 'caixes Eudald', 'r', '2023-06-19', 1),
  ('jordi.bs', 13, 72.00, 'tubs de silicona', 'r', '2023-06-19', 0),
  ('jordi.bs', 13, 19.00, 'certificacions Barcelona', 'm', '2023-06-20', 0),
  ('jordi.bs', 11, 13.90, 'sopar a Mataró', 'r', '2023-06-25', 0),
  ('jordi.bs', 11, 19.00, 'tornada cotxe', 'm', '2023-06-25', 0);
  
INSERT INTO categories (id, name) VALUES
  (1, 'obra nova'),
  (2, 'gran rehabilitació'),
  (3, 'rehabilitació'),
  (4, 'ampliació'),
  (5, 'reforç estructural'),
  (6, 'reforma'),
  (7, 'interiorisme'),
  (8, 'urbanisme'),
  (9, 'cee'),
  (10, 'cèdula'),
  (11, 'cee + cèdula'),
  (12, 'informes');

INSERT INTO holidays (month, day, description) VALUES
  (1, 1, 'Cap d\'any'),
  (1, 6, 'Reis'),
  (5, 1, 'Dia del treballador'),
  (6, 24, 'Dia dels Països Catalans'),
  (8, 15, 'Santa Maria'),
  (9, 11, 'Diada nacional'),
  (11, 1, 'Vigília dia dels Morts'),
  (12, 8, 'Dogma de Maria'),
  (12, 25, 'Nadal'),
  (12, 26, 'Boxing day');
