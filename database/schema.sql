-- ============================================================
-- Agricultural Cooperative ERP - Database Schema
-- ============================================================

-- Aiven uses defaultdb, skip CREATE DATABASE

-- ============================================================
-- RBAC: Roles & Users
-- ============================================================
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(150),
    status ENUM('active','inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(100) NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT
);

CREATE TABLE role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id)
);

-- ============================================================
-- Approval Workflow
-- ============================================================
CREATE TABLE approval_workflows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(100) NOT NULL,
    reference_id INT NOT NULL,
    reference_type VARCHAR(100) NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    requested_by INT NOT NULL,
    reviewed_by INT NULL,
    remarks TEXT,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
);

-- ============================================================
-- HR Module
-- ============================================================
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    department_id INT,
    employee_code VARCHAR(50) UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    position VARCHAR(100),
    hire_date DATE,
    salary DECIMAL(12,2) DEFAULT 0,
    status ENUM('active','inactive','terminated') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    time_in TIME,
    time_out TIME,
    status ENUM('present','absent','late','half_day','leave') DEFAULT 'present',
    remarks TEXT,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

CREATE TABLE payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    basic_pay DECIMAL(12,2),
    deductions DECIMAL(12,2) DEFAULT 0,
    bonuses DECIMAL(12,2) DEFAULT 0,
    net_pay DECIMAL(12,2),
    status ENUM('pending','approved','paid') DEFAULT 'pending',
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- ============================================================
-- Suppliers & Purchasing
-- ============================================================
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(50),
    email VARCHAR(150),
    address TEXT,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE purchase_requisitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requested_by INT NOT NULL,
    department_id INT,
    description TEXT,
    status ENUM('pending','approved','rejected','ordered') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

CREATE TABLE purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requisition_id INT NULL,
    supplier_id INT NOT NULL,
    po_number VARCHAR(100) UNIQUE,
    order_date DATE,
    expected_delivery DATE,
    total_amount DECIMAL(12,2) DEFAULT 0,
    status ENUM('pending','approved','delivered','cancelled') DEFAULT 'pending',
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requisition_id) REFERENCES purchase_requisitions(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

CREATE TABLE purchase_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    item_name VARCHAR(150),
    quantity DECIMAL(12,2),
    unit VARCHAR(50),
    unit_price DECIMAL(12,2),
    total_price DECIMAL(12,2),
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id)
);

-- ============================================================
-- Warehouse & Inventory
-- ============================================================
CREATE TABLE warehouses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location TEXT,
    capacity DECIMAL(12,2)
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100),
    unit VARCHAR(50),
    description TEXT,
    reorder_level DECIMAL(12,2) DEFAULT 0
);

CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    quantity DECIMAL(12,2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
);

CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    type ENUM('in','out','return','adjustment') NOT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    reference_type VARCHAR(100),
    reference_id INT,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ============================================================
-- Processing Management
-- ============================================================
CREATE TABLE processing_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_number VARCHAR(100) UNIQUE,
    product_id INT NOT NULL,
    process_type ENUM('drying','sorting','bagging','milling') NOT NULL,
    input_quantity DECIMAL(12,2),
    output_quantity DECIMAL(12,2),
    waste_quantity DECIMAL(12,2) DEFAULT 0,
    start_date DATE,
    end_date DATE,
    status ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',
    assigned_to INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (assigned_to) REFERENCES employees(id)
);

-- ============================================================
-- Quality Assurance
-- ============================================================
CREATE TABLE quality_inspections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_type VARCHAR(100) NOT NULL,
    reference_id INT NOT NULL,
    inspected_by INT NOT NULL,
    inspection_date DATE,
    result ENUM('passed','failed','conditional') DEFAULT 'passed',
    moisture_content DECIMAL(5,2),
    foreign_matter DECIMAL(5,2),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inspected_by) REFERENCES users(id)
);

CREATE TABLE returned_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_type ENUM('sale','purchase') NOT NULL,
    reference_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity DECIMAL(12,2),
    reason TEXT,
    status ENUM('pending','approved','restocked','disposed') DEFAULT 'pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ============================================================
-- Production Tracking
-- ============================================================
CREATE TABLE farms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150),
    location TEXT,
    area_hectares DECIMAL(10,2),
    owner_id INT,
    FOREIGN KEY (owner_id) REFERENCES employees(id)
);

CREATE TABLE production_cycles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farm_id INT NOT NULL,
    product_id INT NOT NULL,
    season VARCHAR(50),
    planting_date DATE,
    expected_harvest DATE,
    actual_harvest DATE,
    planted_area DECIMAL(10,2),
    expected_yield DECIMAL(12,2),
    actual_yield DECIMAL(12,2),
    status ENUM('planned','planted','growing','harvested','completed') DEFAULT 'planned',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ============================================================
-- Logistics
-- ============================================================
CREATE TABLE deliveries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_type ENUM('sale','purchase') NOT NULL,
    reference_id INT NOT NULL,
    driver_name VARCHAR(150),
    vehicle_plate VARCHAR(50),
    origin TEXT,
    destination TEXT,
    dispatch_date DATETIME,
    delivery_date DATETIME,
    status ENUM('pending','in_transit','delivered','failed') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Sales & Customers
-- ============================================================
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(50),
    email VARCHAR(150),
    address TEXT,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sales_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    so_number VARCHAR(100) UNIQUE,
    order_date DATE,
    delivery_date DATE,
    total_amount DECIMAL(12,2) DEFAULT 0,
    status ENUM('pending','approved','processing','delivered','cancelled') DEFAULT 'pending',
    approved_by INT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE sales_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    so_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity DECIMAL(12,2),
    unit_price DECIMAL(12,2),
    total_price DECIMAL(12,2),
    FOREIGN KEY (so_id) REFERENCES sales_orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ============================================================
-- Finance
-- ============================================================
CREATE TABLE receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_type ENUM('sale','purchase','payroll','other') NOT NULL,
    reference_id INT,
    amount DECIMAL(12,2) NOT NULL,
    payment_method ENUM('cash','bank_transfer','check') DEFAULT 'cash',
    receipt_date DATE,
    received_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (received_by) REFERENCES users(id)
);

CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100),
    description TEXT,
    amount DECIMAL(12,2),
    expense_date DATE,
    approved_by INT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ============================================================
-- Seed Data
-- ============================================================
INSERT INTO roles (name, description) VALUES
('admin', 'Full system access'),
('manager', 'Module management and approvals'),
('staff', 'Data entry and view access');

INSERT INTO users (role_id, username, email, password, full_name) VALUES
(1, 'admin', 'admin@agricoop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin'),
(2, 'manager1', 'manager@agricoop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Farm Manager'),
(3, 'staff1', 'staff@agricoop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Field Staff');
-- Default password: password
