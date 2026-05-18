<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
$db = Database::getInstance()->getConnection();
echo "<pre>";

function run($db,$sql,$label){echo ($db->query($sql)?"OK":"ERR ({$db->error})").": $label\n";}
function addCol($db,$table,$col,$def){$r=$db->query("SHOW COLUMNS FROM `$table` LIKE '$col'");if($r&&$r->num_rows>0){echo "EXISTS: $table.$col\n";return;}run($db,"ALTER TABLE `$table` ADD COLUMN `$col` $def","$table.$col");}

run($db,"CREATE TABLE IF NOT EXISTS purchase_requisition_items (id INT AUTO_INCREMENT PRIMARY KEY,requisition_id INT NOT NULL,item_name VARCHAR(150) NOT NULL,quantity DECIMAL(12,2) NOT NULL,unit VARCHAR(50),estimated_price DECIMAL(12,2) DEFAULT 0,total_price DECIMAL(12,2) DEFAULT 0,FOREIGN KEY (requisition_id) REFERENCES purchase_requisitions(id) ON DELETE CASCADE)","purchase_requisition_items");
run($db,"CREATE TABLE IF NOT EXISTS delivery_receipts (id INT AUTO_INCREMENT PRIMARY KEY,delivery_id INT NOT NULL,dr_number VARCHAR(100) NOT NULL UNIQUE,received_by INT NOT NULL,received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,condition_notes TEXT,signature_name VARCHAR(150),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY (delivery_id) REFERENCES deliveries(id) ON DELETE CASCADE,FOREIGN KEY (received_by) REFERENCES users(id))","delivery_receipts");
run($db,"CREATE TABLE IF NOT EXISTS delivery_items (id INT AUTO_INCREMENT PRIMARY KEY,delivery_id INT NOT NULL,product_id INT NOT NULL,quantity DECIMAL(12,2) NOT NULL,unit VARCHAR(50),notes TEXT,FOREIGN KEY (delivery_id) REFERENCES deliveries(id) ON DELETE CASCADE,FOREIGN KEY (product_id) REFERENCES products(id))","delivery_items");
run($db,"CREATE TABLE IF NOT EXISTS farmer_ledger (id INT AUTO_INCREMENT PRIMARY KEY,farmer_id INT NOT NULL,type ENUM('credit','debit') NOT NULL,category ENUM('sale','withdrawal','adjustment','advance','other') NOT NULL,reference_type VARCHAR(100),reference_id INT,amount DECIMAL(12,2) NOT NULL,running_balance DECIMAL(12,2) NOT NULL,description VARCHAR(255),transaction_date DATE NOT NULL,recorded_by INT NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY (farmer_id) REFERENCES farmers(id),FOREIGN KEY (recorded_by) REFERENCES users(id))","farmer_ledger");
run($db,"CREATE TABLE IF NOT EXISTS farmer_withdrawals (id INT AUTO_INCREMENT PRIMARY KEY,farmer_id INT NOT NULL,amount DECIMAL(12,2) NOT NULL,reason TEXT,status ENUM('pending','approved','rejected','released') DEFAULT 'pending',requested_by INT NOT NULL,approved_by INT NULL,approved_at TIMESTAMP NULL,released_at TIMESTAMP NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY (farmer_id) REFERENCES farmers(id),FOREIGN KEY (requested_by) REFERENCES users(id),FOREIGN KEY (approved_by) REFERENCES users(id))","farmer_withdrawals");
run($db,"CREATE TABLE IF NOT EXISTS notifications (id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,type VARCHAR(50) NOT NULL,title VARCHAR(255) NOT NULL,message TEXT NOT NULL,link VARCHAR(500),is_read TINYINT(1) DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)","notifications");
run($db,"CREATE TABLE IF NOT EXISTS qa_inspections (id INT AUTO_INCREMENT PRIMARY KEY,reference_type ENUM('return','batch','seed','delivery','purchase_order') NOT NULL,reference_id INT NOT NULL,product_id INT NOT NULL,warehouse_id INT NULL,inspected_by INT NOT NULL,inspection_date DATE NOT NULL,result ENUM('passed','failed','conditional') DEFAULT 'passed',moisture_pct DECIMAL(5,2) DEFAULT 0,foreign_matter DECIMAL(5,2) DEFAULT 0,germination_pct DECIMAL(5,2) DEFAULT 0,sample_qty DECIMAL(12,2) DEFAULT 0,approved_qty DECIMAL(12,2) DEFAULT 0,rejected_qty DECIMAL(12,2) DEFAULT 0,remarks TEXT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY (product_id) REFERENCES products(id),FOREIGN KEY (inspected_by) REFERENCES users(id))","qa_inspections");
run($db,"CREATE TABLE IF NOT EXISTS stock_release_requests (id INT AUTO_INCREMENT PRIMARY KEY,product_id INT NOT NULL,warehouse_id INT NOT NULL,quantity DECIMAL(12,2) NOT NULL,purpose VARCHAR(255),requested_by INT NOT NULL,status ENUM('pending','approved','rejected','released') DEFAULT 'pending',released_at TIMESTAMP NULL,notes TEXT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY (product_id) REFERENCES products(id),FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),FOREIGN KEY (requested_by) REFERENCES users(id))","stock_release_requests");
run($db,"CREATE TABLE IF NOT EXISTS processing_stage_logs (id INT AUTO_INCREMENT PRIMARY KEY,batch_id INT NOT NULL,stage ENUM('drying','sorting','bagging','milling','shelling') NOT NULL,stage_order INT NOT NULL DEFAULT 1,input_qty DECIMAL(12,2) NOT NULL,output_qty DECIMAL(12,2) DEFAULT 0,waste_qty DECIMAL(12,2) DEFAULT 0,started_at DATETIME NULL,completed_at DATETIME NULL,status ENUM('pending','in_progress','completed','skipped') DEFAULT 'pending',notes TEXT,recorded_by INT NULL,FOREIGN KEY (batch_id) REFERENCES processing_batches(id) ON DELETE CASCADE,FOREIGN KEY (recorded_by) REFERENCES users(id))","processing_stage_logs");
run($db,"CREATE TABLE IF NOT EXISTS batch_costs (id INT AUTO_INCREMENT PRIMARY KEY,batch_id INT NOT NULL,cost_type ENUM('labor','material','overhead','utility','other') NOT NULL,description VARCHAR(255),amount DECIMAL(12,2) NOT NULL,recorded_by INT,recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY (batch_id) REFERENCES processing_batches(id) ON DELETE CASCADE,FOREIGN KEY (recorded_by) REFERENCES users(id))","batch_costs");
run($db,"CREATE TABLE IF NOT EXISTS production_schedules (id INT AUTO_INCREMENT PRIMARY KEY,production_record_id INT NOT NULL,activity VARCHAR(150) NOT NULL,scheduled_date DATE NOT NULL,completed_date DATE NULL,status ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',assigned_to INT NULL,notes TEXT,FOREIGN KEY (production_record_id) REFERENCES production_records(id) ON DELETE CASCADE,FOREIGN KEY (assigned_to) REFERENCES employees(id))","production_schedules");
run($db,"CREATE TABLE IF NOT EXISTS production_inputs (id INT AUTO_INCREMENT PRIMARY KEY,production_record_id INT NOT NULL,input_type ENUM('fertilizer','pesticide','seed','labor','other') NOT NULL,name VARCHAR(150) NOT NULL,quantity DECIMAL(12,2),unit VARCHAR(50),unit_cost DECIMAL(12,2) DEFAULT 0,total_cost DECIMAL(12,2) DEFAULT 0,applied_date DATE,notes TEXT,FOREIGN KEY (production_record_id) REFERENCES production_records(id) ON DELETE CASCADE)","production_inputs");
run($db,"CREATE TABLE IF NOT EXISTS journal_entries (id INT AUTO_INCREMENT PRIMARY KEY,entry_date DATE NOT NULL,reference VARCHAR(100),description VARCHAR(255) NOT NULL,debit_account VARCHAR(100) NOT NULL,credit_account VARCHAR(100) NOT NULL,amount DECIMAL(12,2) NOT NULL,source_type VARCHAR(100),source_id INT,created_by INT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY (created_by) REFERENCES users(id))","journal_entries");

addCol($db,'purchase_requisitions','notes','TEXT');
addCol($db,'purchase_requisitions','approved_by','INT NULL');
addCol($db,'purchase_requisitions','approved_at','TIMESTAMP NULL');
addCol($db,'farmers','balance','DECIMAL(12,2) DEFAULT 0.00');
addCol($db,'farmers','total_credits','DECIMAL(12,2) DEFAULT 0.00');
addCol($db,'farmers','total_debits','DECIMAL(12,2) DEFAULT 0.00');
addCol($db,'processing_batches','input_warehouse_id','INT NULL');
addCol($db,'processing_batches','output_warehouse_id','INT NULL');
addCol($db,'processing_batches','created_by','INT NULL');

// Payroll itemized deductions
addCol($db,'payroll','sss_pct','DECIMAL(5,2) DEFAULT 0');
addCol($db,'payroll','sss_amount','DECIMAL(12,2) DEFAULT 0');
addCol($db,'payroll','pagibig_pct','DECIMAL(5,2) DEFAULT 0');
addCol($db,'payroll','pagibig_amount','DECIMAL(12,2) DEFAULT 0');
addCol($db,'payroll','philhealth_pct','DECIMAL(5,2) DEFAULT 0');
addCol($db,'payroll','philhealth_amount','DECIMAL(12,2) DEFAULT 0');
addCol($db,'payroll','other_deductions','DECIMAL(12,2) DEFAULT 0');
addCol($db,'payroll','other_deductions_note','VARCHAR(255) DEFAULT NULL');

// Payroll itemized bonuses
addCol($db,'payroll','rest_day_pct','DECIMAL(5,2) DEFAULT 0');
addCol($db,'payroll','rest_day_amount','DECIMAL(12,2) DEFAULT 0');
addCol($db,'payroll','special_holiday_pct','DECIMAL(5,2) DEFAULT 0');
addCol($db,'payroll','special_holiday_amount','DECIMAL(12,2) DEFAULT 0');
addCol($db,'payroll','regular_holiday_pct','DECIMAL(5,2) DEFAULT 0');
addCol($db,'payroll','regular_holiday_amount','DECIMAL(12,2) DEFAULT 0');
addCol($db,'payroll','other_bonuses','DECIMAL(12,2) DEFAULT 0');
addCol($db,'payroll','other_bonuses_note','VARCHAR(255) DEFAULT NULL');

run($db,"ALTER TABLE processing_batches MODIFY COLUMN process_type ENUM('drying','sorting','bagging','milling','shelling') NOT NULL","MODIFY processing_batches.process_type");
run($db,"ALTER TABLE sales_orders MODIFY COLUMN status ENUM('pending','approved','processing','delivered','cancelled','rejected') DEFAULT 'pending'","MODIFY sales_orders.status");

// Rebuild approval chains: single step — GM/Manager approves directly
run($db,"DELETE FROM approval_chains","Clear approval_chains");
$modules = ['sales','purchasing','inventory','hr','finance','logistics','production','processing','qa','prs','withdrawal','stock_release'];
foreach ($modules as $mod) {
    $db->query("INSERT IGNORE INTO approval_chains (module,step_order,approver_role,label,is_gm_step) VALUES ('$mod',1,'gm','General Manager',1)");
}
echo "OK: approval_chains rebuilt (single-step)\n";

// Add file_content column for DB-stored uploads
$check = $db->query("SHOW COLUMNS FROM `routed_documents` LIKE 'file_content'");
if ($check && $check->num_rows === 0) {
    echo $db->query("ALTER TABLE routed_documents ADD COLUMN file_content LONGBLOB NULL")
        ? "OK: routed_documents.file_content\n" : "ERR: {$db->error}\n";
} else {
    echo "EXISTS: routed_documents.file_content\n";
}
echo "\nAll done. DELETE this file!\n</pre>";

// Employee documents table
run($db,"CREATE TABLE IF NOT EXISTS employee_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    doc_type ENUM('sss','pagibig','philhealth','application_letter','resume','contract','other') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(100),
    file_size INT DEFAULT 0,
    file_content LONGBLOB NOT NULL,
    uploaded_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
)","employee_documents");
