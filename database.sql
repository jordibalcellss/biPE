-- database.sql

CREATE DATABASE bipe;
USE bipe;

CREATE TABLE IF NOT EXISTS tasks (
  id INT NOT NULL AUTO_INCREMENT,
  category_id INT,
  client_id INT,
  code CHAR(4),
  name VARCHAR(50),
  rate DECIMAL(5,2),
  active TINYINT(1) DEFAULT 1,
  readonly TINYINT(1) DEFAULT 0,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS quotations (
  id INT NOT NULL AUTO_INCREMENT,
  task_id INT NOT NULL,
  amount DECIMAL(8,2) NOT NULL,
  description VARCHAR(50),
  nature ENUM('i','e'), -- income/expense
  day DATE NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS invoices (
  id INT NOT NULL AUTO_INCREMENT,
  task_id INT NOT NULL,
  amount DECIMAL(8,2) NOT NULL,
  description VARCHAR(50),
  nature ENUM('i','e'),
  day DATE NOT NULL,
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
  duration DECIMAL(4,2),
  saved TINYINT(1) DEFAULT 0,
  PRIMARY KEY (id)
);

INSERT INTO tasks (id,code,category_id,client_id,name,rate,active,readonly) VALUES
  (1,null,null,null,null,null,1,1), -- weekend/nothing
  (2,null,null,null,null,null,1,1), -- holiday
  (3,null,null,null,null,null,1,1), -- off sick
  (4,null,null,null,null,null,1,1), -- leave
  (5,null,null,null,null,null,0,1), -- reserved
  (6,null,null,null,null,null,0,1), -- reserved
  (7,null,null,null,null,null,0,1), -- reserved
  (8,null,null,null,null,null,0,1), -- reserved
  (9,null,null,null,null,null,0,1), -- reserved
  (10,null,null,null,null,null,0,1), -- reserved
  (11,2201,1,1,'bloc de pisos a arenys',30,1,0),
  (12,2202,null,null,'cèdula habitabilitat major 73',20,0,0),
  (13,2203,4,2,'ampliació nau industrial polígon',30,1,0),
  (14,null,3,null,'visitant obra del jaume',null,1,0);

INSERT INTO quotations (task_id,amount,description,nature,day) VALUES
  (11,120000.00,'honoraris H2201','i','2022-01-01'),
  (11,16000.00,'honoraris addicionals H2203','i','2022-04-01'),
  (12,120.00,null,'i','2023-01-15'),
  (13,3000.00,'pressupost P2207','i','2022-12-24'),
  (13,175.00,'pressupost lloguer grua','e','2023-01-11');

INSERT INTO invoices (task_id,amount,description,settled,nature,day) VALUES
  (11,40000.00,'provisió de fons H2201: factura F2207',1,'i','2022-01-02'),
  (12,120.00,'factura F2215',0,'i','2023-01-29'),
  (13,750.00,'provisió de fons P2207: factura F2212',1,'i','2022-12-27'),
  (13,1125.00,'factura 1/2 de P2207: F2213',0,'i','2023-01-29'),
  (13,200.00,'lloguer grua',0,'e','2023-02-15');

INSERT INTO clients (name,address,city,postcode,vat_code,email) VALUES
  ('Desenvolupaments Pons','11 Ronda Pedrolo','Palma','PA1 3FH','112358',null),
  ('Mulberry Trench S. Ltda.','1180 Av. de la independència','Barcelona','B15 AZ1','132134','info@multrench.com');

INSERT INTO time_log (user_id,task_id,day,duration,saved) VALUES
  ('jordi.bs',2,'20210801',8,1),
  ('jordi.bs',2,'20210802',8,1),
  ('jordi.bs',2,'20210803',4,1),
  ('jordi.bs',2,'20221101',8,1),
  ('jordi.bs',2,'20221102',8,1),
  ('jordi.bs',2,'20221103',8,1),
  ('jordi.bs',3,'20221104',8,1),
  ('jordi.bs',3,'20221105',4,1),
  ('jordi.bs',13,'20221106',8,1),
  ('jordi.bs',13,'20221107',8,1),
  ('jordi.bs',13,'20221108',4.5,1),
  ('jordi.bs',13,'20221109',4.75,1),
  ('jordi.bs',13,'20221110',8,1),
  ('jordi.bs',13,'20221111',8,1),
  ('jordi.bs',13,'20221112',8,1),
  ('jordi.bs',13,'20221115',8,1),
  ('jordi.bs',13,'20221116',8,1),
  ('jordi.bs',4,'20221117',null,1),
  ('jordi.bs',4,'20221118',null,1),
  ('jordi.bs',4,'20221119',null,1);
  
INSERT INTO categories (id,name) VALUES
  (1,'obra nova'),
  (2,'gran rehabilitació'),
  (3,'rehabilitació'),
  (4,'ampliació'),
  (5,'reforç estructural'),
  (6,'reforma'),
  (7,'interiorisme'),
  (8,'urbanisme'),
  (9,'cee'),
  (10,'cèdula'),
  (11,'cee + cèdula'),
  (12,'informes');
