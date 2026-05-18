<?php
ob_start();
session_start();

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Router.php';

$router = new Router();

// Auth
$router->get('/login',   'AuthController', 'loginPage');
$router->post('/login',  'AuthController', 'login');
$router->get('/logout',  'AuthController', 'logout');

// Dashboard
$router->get('/dashboard',       'DashboardController', 'index');
$router->get('/dashboard/stats', 'DashboardController', 'ajaxStats');
$router->get('/',                'DashboardController', 'index');

// Inventory
$router->get('/inventory',                    'InventoryController', 'index');
$router->post('/inventory/stock-in',          'InventoryController', 'stockIn');
$router->post('/inventory/stock-out',         'InventoryController', 'stockOut');
$router->get('/inventory/movements',          'InventoryController', 'movements');
$router->post('/inventory/request-release',   'InventoryController', 'requestRelease');
$router->post('/inventory/approve-release',   'InventoryController', 'approveRelease');
$router->post('/inventory/return',            'InventoryController', 'createReturn');
$router->post('/inventory/process-return',    'InventoryController', 'processReturn');
$router->post('/inventory/save-product',      'InventoryController', 'saveProduct');
$router->post('/inventory/delete-product',    'InventoryController', 'deleteProduct');
$router->post('/inventory/save-warehouse',    'InventoryController', 'saveWarehouse');
$router->post('/inventory/delete-warehouse',  'InventoryController', 'deleteWarehouse');

// Sales
$router->get('/sales',              'SalesController', 'index');
$router->post('/sales/create',      'SalesController', 'create');
$router->get('/sales/detail',       'SalesController', 'detail');
$router->post('/sales/approve',     'SalesController', 'approve');
$router->post('/sales/delete',      'SalesController', 'delete');
$router->post('/sales/status',      'SalesController', 'updateStatus');
$router->post('/sales/mark-paid',   'SalesController', 'markPaid');
$router->post('/sales/record-payment', 'SalesController', 'recordPayment');
$router->get('/sales/customers',    'SalesController', 'customers');
$router->post('/sales/customers',   'SalesController', 'saveCustomer');
$router->get('/sales/invoice-print', 'SalesController', 'invoicePrint');

// QA
$router->get('/qa',                 'QAController', 'index');
$router->post('/qa/create',         'QAController', 'create');
$router->get('/qa/detail',          'QAController', 'detail');

// Operational (Production + Processing combined)
$router->get('/operational',                    'OperationalController', 'index');
// Production routes
$router->post('/operational/create-production',     'OperationalController', 'createProduction');
$router->get('/operational/production-detail',      'OperationalController', 'productionDetail');
$router->get('/operational/production-print',       'OperationalController', 'productionPrint');
$router->post('/operational/production-status',     'OperationalController', 'updateProductionStatus');
$router->post('/operational/add-input',             'OperationalController', 'addInput');
$router->post('/operational/add-schedule',          'OperationalController', 'addSchedule');
$router->post('/operational/update-schedule',       'OperationalController', 'updateSchedule');
// Processing routes
$router->post('/operational/create-processing',     'OperationalController', 'createProcessing');
$router->get('/operational/processing-detail',      'OperationalController', 'processingDetail');
$router->get('/operational/processing-print',       'OperationalController', 'processingPrint');
$router->post('/operational/update-stage',          'OperationalController', 'updateStage');
$router->post('/operational/cancel-processing',     'OperationalController', 'cancelProcessing');
$router->post('/operational/delete-processing',     'OperationalController', 'deleteProcessing');
// Farmers
$router->post('/operational/save-farmer',           'OperationalController', 'saveFarmer');

// Legacy redirects (for backward compatibility)
$router->get('/production',                 'OperationalController', 'index');
$router->get('/processing',                 'OperationalController', 'index');

// Monitoring
$router->get('/monitoring',                    'MonitoringController', 'index');
$router->post('/monitoring/add-cost',          'MonitoringController', 'addBatchCost');
$router->get('/monitoring/batch-cost-details', 'MonitoringController', 'batchCostDetails');
$router->get('/monitoring/report',             'MonitoringController', 'costReport');

// Reports
$router->get('/reports',             'ReportsController', 'index');
$router->get('/reports/financial',   'ReportsController', 'financial');
$router->get('/reports/inventory',   'ReportsController', 'inventory');
$router->get('/reports/sales',       'ReportsController', 'sales');
$router->get('/reports/production',  'ReportsController', 'production');
$router->get('/reports/approvals',   'ReportsController', 'approvals');
$router->get('/reports/archive',     'ReportsController', 'archive');

// Finance
$router->get('/finance',                  'FinanceController', 'index');
$router->post('/finance/receipt',         'FinanceController', 'createReceipt');
$router->post('/finance/expense',         'FinanceController', 'createExpense');
$router->post('/finance/approve-expense', 'FinanceController', 'approveExpense');
$router->post('/finance/delete-expense',  'FinanceController', 'deleteExpense');
$router->post('/finance/approve-payroll',     'FinanceController', 'approvePayroll');
$router->post('/finance/mark-paid',           'FinanceController', 'markPayrollPaid');
$router->post('/finance/approve-all-payroll', 'FinanceController', 'approveAllPayroll');
$router->get('/finance/report',           'FinanceController', 'report');
$router->get('/finance/report-print',     'FinanceController', 'reportPrint');
$router->get('/finance/receipt-print',    'FinanceController', 'receiptPrint');
$router->get('/finance/inventory-value',  'FinanceController', 'inventoryValue');

// HR
$router->get('/hr',                    'HRController', 'index');
$router->post('/hr/save-employee',     'HRController', 'saveEmployee');
$router->get('/hr/attendance',         'HRController', 'attendance');
$router->post('/hr/attendance',        'HRController', 'saveAttendance');
$router->get('/hr/payroll',            'HRController', 'payroll');
$router->post('/hr/payroll/generate',  'HRController', 'generatePayroll');
$router->get('/hr/archive',            'HRController', 'archive');
$router->post('/hr/archive-employee',    'HRController', 'archiveEmployee');
$router->post('/hr/unarchive-employee',  'HRController', 'unarchiveEmployee');
$router->post('/hr/delete-employee',     'HRController', 'deleteEmployee');
$router->get('/hr/employee-docs',      'HRController', 'employeeDocs');
$router->post('/hr/upload-doc',        'HRController', 'uploadDoc');
$router->get('/hr/download-doc',       'HRController', 'downloadDoc');
$router->post('/hr/delete-doc',        'HRController', 'deleteDoc');

// Purchasing — PO
$router->get('/purchasing',                'PurchasingController', 'index');
$router->post('/purchasing/create',        'PurchasingController', 'createPO');
$router->post('/purchasing/edit',          'PurchasingController', 'editPO');
$router->post('/purchasing/delete-po',     'PurchasingController', 'deletePO');
$router->post('/purchasing/approve',       'PurchasingController', 'approvePO');
$router->post('/purchasing/status',        'PurchasingController', 'updatePOStatus');
$router->get('/purchasing/po-detail',      'PurchasingController', 'poDetail');
$router->get('/purchasing/invoice-print',  'PurchasingController', 'invoicePrint');
// Purchasing — PRS
$router->post('/purchasing/create-prs',    'PurchasingController', 'createPRS');
$router->post('/purchasing/edit-prs',      'PurchasingController', 'editPRS');
$router->post('/purchasing/delete-prs',    'PurchasingController', 'deletePRS');
$router->get('/purchasing/prs-detail',     'PurchasingController', 'prsDetail');
// Purchasing — Suppliers
$router->get('/purchasing/suppliers',      'PurchasingController', 'suppliers');
$router->post('/purchasing/suppliers',     'PurchasingController', 'saveSupplier');
$router->post('/purchasing/toggle-supplier','PurchasingController', 'toggleSupplier');
$router->post('/purchasing/delete-supplier','PurchasingController', 'deleteSupplier');
// Purchasing — Tracking
$router->get('/purchasing/tracking',       'PurchasingController', 'tracking');

// Logistics
$router->get('/logistics',                  'LogisticsController', 'index');
$router->post('/logistics/create',          'LogisticsController', 'create');
$router->post('/logistics/status',          'LogisticsController', 'updateStatus');
$router->get('/logistics/detail',           'LogisticsController', 'detail');
$router->post('/logistics/generate-receipt','LogisticsController', 'generateReceipt');
$router->get('/logistics/receipt-print',    'LogisticsController', 'printReceipt');
$router->post('/logistics/delete',          'LogisticsController', 'delete');

// Ledger
$router->get('/ledger',                      'LedgerController', 'index');
$router->get('/ledger/farmer',               'LedgerController', 'farmer');
$router->post('/ledger/entry',               'LedgerController', 'addEntry');
$router->post('/ledger/withdrawal',          'LedgerController', 'requestWithdrawal');
$router->post('/ledger/approve-withdrawal',  'LedgerController', 'approveWithdrawal');

// Notifications
$router->get('/notifications',           'NotificationController', 'fetch');
$router->post('/notifications/read-all', 'NotificationController', 'readAll');
$router->post('/notifications/read',     'NotificationController', 'read');

// Documents
$router->get('/documents',          'DocumentController', 'index');
$router->post('/documents/upload',  'DocumentController', 'upload');
$router->get('/documents/detail',   'DocumentController', 'detail');
$router->post('/documents/act',     'DocumentController', 'act');
$router->get('/documents/download', 'DocumentController', 'download');
$router->post('/documents/delete',  'DocumentController', 'delete');

// Board of Directors
$router->get('/bod', 'BODController', 'index');

// Users (admin only)
$router->get('/users',          'UserController', 'index');
$router->post('/users/save',    'UserController', 'save');
$router->post('/users/delete',  'UserController', 'delete');

// Approvals
$router->get('/approvals',         'ApprovalController', 'index');
$router->get('/approvals/detail',  'ApprovalController', 'detail');
$router->post('/approvals/act',     'ApprovalController', 'act');
$router->post('/approvals/comment', 'ApprovalController', 'comment');
$router->post('/approvals/submit', 'ApprovalController', 'submit');
$router->get('/approvals/audit',   'ApprovalController', 'audit');

// Reports
$router->get('/reports',             'ReportsController', 'index');
$router->get('/reports/financial',   'ReportsController', 'financial');
$router->get('/reports/inventory',   'ReportsController', 'inventory');
$router->get('/reports/sales',       'ReportsController', 'sales');
$router->get('/reports/production',  'ReportsController', 'production');
$router->get('/reports/approvals',   'ReportsController', 'approvals');
$router->get('/reports/archive',     'ReportsController', 'archive');

$router->dispatch();
