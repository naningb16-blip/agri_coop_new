﻿<?php
require_once __DIR__ . '/../../core/Model.php';

class ApprovalModel extends Model {
    protected string $table = 'approval_requests';

    // &#8369;8369;”€ Chain &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function getChain(string $module): array {
        return $this->db->fetchAll(
            "SELECT * FROM approval_chains WHERE module = ? ORDER BY step_order",
            [$module], 's'
        );
    }

    // &#8369;8369;”€ Create a new request with all its steps &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    public function createRequest(array $data, int $requestedBy): int {
        $chain = $this->getChain($data['module']);
        if (empty($chain)) return 0;

        $reqId = $this->db->insert(
            "INSERT INTO approval_requests
                (module, reference_type, reference_id, title, description, current_step, requested_by)
             VALUES (?,?,?,?,?,1,?)",
            [$data['module'], $data['reference_type'], $data['reference_id'],
             $data['title'], $data['description'] ?? '', $requestedBy],
            'ssissi'
        );

        foreach ($chain as $step) {
            $this->db->insert(
                "INSERT INTO approval_steps (request_id, step_order, approver_role, label) VALUES (?,?,?,?)",
                [$reqId, $step['step_order'], $step['approver_role'], $step['label']],
                'iiss'
            );
        }

        $this->_audit($reqId, 1, $requestedBy, 'submitted', 'Request submitted.');
        return $reqId;
    }

    // &#8369;8369;”€ Act on a step (approve / reject) &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function actOnStep(int $requestId, int $actorId, string $action, string $remarks = ''): array {
            try {
                // Begin transaction
                $this->db->query("START TRANSACTION");

                $req = $this->findById($requestId);
                if (!$req) {
                    $this->db->query("ROLLBACK");
                    return ['success' => false, 'message' => 'Request not found.'];
                }
                if ($req['status'] !== 'pending') {
                    $this->db->query("ROLLBACK");
                    return ['success' => false, 'message' => 'Request already finalised.'];
                }

                $step = $this->db->fetchOne(
                    "SELECT * FROM approval_steps WHERE request_id=? AND step_order=? AND status='pending'",
                    [$requestId, $req['current_step']], 'ii'
                );
                if (!$step) {
                    $this->db->query("ROLLBACK");
                    return ['success' => false, 'message' => 'No active step found.'];
                }

                // Verify actor has the right role
                $actor = $this->db->fetchOne(
                    "SELECT u.*, r.name as role FROM users u JOIN roles r ON u.role_id=r.id WHERE u.id=?",
                    [$actorId], 'i'
                );
                $actorRole = $actor['role'] ?? '';
                // Admin, gm, and manager can all approve (they are the same role functionally)
                $canAct = in_array($actorRole, ['admin', 'gm', 'manager'])
                    || $actorRole === $step['approver_role'];
                if (!$actor || !$canAct) {
                    $this->db->query("ROLLBACK");
                    return ['success' => false, 'message' => 'You are not authorised to act on this step.'];
                }

                // Update the step
                $this->db->query(
                    "UPDATE approval_steps SET status=?, actioned_by=?, remarks=?, actioned_at=NOW()
                     WHERE request_id=? AND step_order=?",
                    [$action, $actorId, $remarks, $requestId, $req['current_step']],
                    'siisi'
                );
                $this->_audit($requestId, $req['current_step'], $actorId, $action, $remarks);

                if ($action === 'rejected') {
                    $this->db->query("UPDATE approval_requests SET status='rejected', updated_at=NOW() WHERE id=?", [$requestId], 'i');
                    // Sync rejection to source record
                    $this->_syncRejection($req['reference_type'], $req['reference_id']);
                    // Notify the requester
                    $this->_notifyRequester($req, 'rejected', $remarks);
                    $this->db->query("COMMIT");
                    return ['success' => true, 'message' => 'Request rejected.'];
                }

                // Move to next step
                $nextStep = $this->db->fetchOne(
                    "SELECT * FROM approval_steps WHERE request_id=? AND step_order>? ORDER BY step_order LIMIT 1",
                    [$requestId, $req['current_step']], 'ii'
                );

                if ($nextStep) {
                    $this->db->query(
                        "UPDATE approval_requests SET current_step=?, updated_at=NOW() WHERE id=?",
                        [$nextStep['step_order'], $requestId], 'ii'
                    );
                    $this->db->query("COMMIT");
                    return ['success' => true, 'message' => "Approved. Forwarded to: {$nextStep['label']}."];
                }

                // All steps done — fully approved
                $this->db->query("UPDATE approval_requests SET status='approved', updated_at=NOW() WHERE id=?", [$requestId], 'i');

                // Sync status back to the source record (may throw exception)
                $this->_syncApproval($req['reference_type'], $req['reference_id'], 'approved', $actorId);

                // Notify the requester
                $this->_notifyRequester($req, 'approved', $remarks);

                // Commit transaction
                $this->db->query("COMMIT");

                return ['success' => true, 'message' => 'Request fully approved.'];

            } catch (Exception $e) {
                // Rollback on any error
                $this->db->query("ROLLBACK");

                // Log error
                error_log("Approval transaction failed: " . $e->getMessage());

                return [
                    'success' => false, 
                    'message' => 'Approval failed: ' . $e->getMessage()
                ];
            }
        }


    private function _syncApproval(string $refType, int $refId, string $status, int $actorId): void {
        switch ($refType) {
            case 'processing_batch':
                // When approved, batch can start processing (no status change needed, just approved flag)
                // The batch status is managed by stage progression, not approval
                break;
            case 'delivery':
                // Update delivery status to in_transit when approved
                $this->db->query(
                    "UPDATE deliveries SET status='in_transit' WHERE id=?",
                    [$refId], 'i'
                );
                break;
            case 'expense':
                $this->db->query(
                    "UPDATE expenses SET status='approved', approved_by=? WHERE id=?",
                    [$actorId, $refId], 'ii'
                );
                // Create journal entry
                $exp = $this->db->fetchOne("SELECT * FROM expenses WHERE id=?", [$refId], 'i');
                if ($exp) {
                    $this->db->insert(
                        "INSERT INTO journal_entries (entry_date, reference, description, debit_account, credit_account, amount, source_type, source_id, created_by)
                         VALUES (?,?,?,?,?,?,?,?,?)",
                        [$exp['expense_date'], 'EXP-'.$refId,
                         'Expense approved: '.($exp['category']??'').' - '.($exp['description']??''),
                         $exp['category'] ?? 'Operating Expense', 'Cash',
                         $exp['amount'], 'expense', $refId, $actorId],
                        'sssssdsii'
                    );
                }
                break;
            case 'sales_order':
                $this->db->query("UPDATE sales_orders SET status='approved', approved_by=? WHERE id=?", [$actorId, $refId], 'ii');
                break;
            case 'purchase_order':
                $this->db->query("UPDATE purchase_orders SET status='approved', approved_by=? WHERE id=?", [$actorId, $refId], 'ii');
                // Auto-add PO items to inventory on GM approval
                $items = $this->db->fetchAll(
                    "SELECT * FROM purchase_order_items WHERE po_id=?", [$refId], 'i'
                );
                // Use first warehouse as default
                $defaultWarehouse = $this->db->fetchOne("SELECT id FROM warehouses ORDER BY id LIMIT 1");
                $warehouseId = $defaultWarehouse ? (int)$defaultWarehouse['id'] : null;
                if ($warehouseId && !empty($items)) {
                    require_once __DIR__ . '/InventoryModel.php';
                    $inv = new InventoryModel();
                    foreach ($items as $item) {
                        if (empty($item['item_name']) || $item['quantity'] <= 0) continue;
                        // Find or create product by name
                        $product = $this->db->fetchOne(
                            "SELECT id FROM products WHERE name=?", [$item['item_name']], 's'
                        );
                        if ($product) {
                            $productId = (int)$product['id'];
                        } else {
                            $productId = (int)$this->db->insert(
                                "INSERT INTO products (name, unit, reorder_level) VALUES (?,?,0)",
                                [$item['item_name'], $item['unit'] ?? 'unit'], 'ss'
                            );
                        }
                        $inv->addMovement([
                            'product_id'     => $productId,
                            'warehouse_id'   => $warehouseId,
                            'type'           => 'in',
                            'quantity'       => (float)$item['quantity'],
                            'reference_type' => 'purchase_order',
                            'reference_id'   => $refId,
                            'notes'          => 'Auto stock-in from approved PO #' . $refId,
                        ]);
                    }
                }
                break;
            case 'stock_release':
                // 1. Fetch stock release request
                $req = $this->db->fetchOne(
                    "SELECT * FROM stock_release_requests WHERE id=?", 
                    [$refId], 'i'
                );
                
                if (!$req) {
                    throw new Exception("Stock release request not found: $refId");
                }
                
                // 2. Validate stock availability at approval time with row lock
                $currentStock = $this->db->fetchOne(
                    "SELECT i.quantity, p.name as product_name 
                     FROM inventory i 
                     JOIN products p ON i.product_id = p.id
                     WHERE i.product_id=? AND i.warehouse_id=? FOR UPDATE",
                    [$req['product_id'], $req['warehouse_id']], 'ii'
                );
                
                $available = (float)($currentStock['quantity'] ?? 0);
                $requested = (float)$req['quantity'];
                
                if ($available < $requested) {
                    $productName = $currentStock['product_name'] ?? 'Unknown Product';
                    throw new Exception(
                        "Insufficient stock for {$productName}. " .
                        "Requested: $requested, Available: $available"
                    );
                }
                
                // 3. Check for existing movement (idempotency)
                $existing = $this->db->fetchOne(
                    "SELECT id FROM stock_movements 
                     WHERE reference_type='stock_release' AND reference_id=?",
                    [$refId], 'i'
                );
                
                if ($existing) {
                    // Already processed, skip
                    return;
                }
                
                // 4. Create stock movement with department information
                require_once __DIR__ . '/InventoryModel.php';
                $inv = new InventoryModel();
                
                $notes = 'GM approved release: ' . $req['purpose'];
                if (!empty($req['requesting_department'])) {
                    $notes .= ' | Dept: ' . $req['requesting_department'];
                }
                
                $inv->addMovement([
                    'product_id'     => $req['product_id'],
                    'warehouse_id'   => $req['warehouse_id'],
                    'type'           => 'out',
                    'quantity'       => $requested,
                    'reference_type' => 'stock_release',
                    'reference_id'   => $refId,
                    'notes'          => $notes,
                ]);
                
                // 5. Update stock release request status
                $this->db->query(
                    "UPDATE stock_release_requests 
                     SET status='released', released_at=NOW() 
                     WHERE id=?",
                    [$refId], 'i'
                );
                
                break;
            case 'purchase_requisition':
                $this->db->query("UPDATE purchase_requisitions SET status='approved', approved_by=? WHERE id=?", [$actorId, $refId], 'ii');
                break;
            case 'employee':
                $this->db->query("UPDATE employees SET status='active' WHERE id=?", [$refId], 'i');
                break;
            case 'withdrawal':
                // Update withdrawal status to approved
                $this->db->query(
                    "UPDATE farmer_withdrawals SET status='approved', approved_by=?, approved_at=NOW() WHERE id=?",
                    [$actorId, $refId], 'ii'
                );
                
                // Get withdrawal details
                $withdrawal = $this->db->fetchOne(
                    "SELECT fw.*, f.full_name as farmer_name 
                     FROM farmer_withdrawals fw 
                     JOIN farmers f ON fw.farmer_id = f.id 
                     WHERE fw.id=?",
                    [$refId], 'i'
                );
                
                if ($withdrawal) {
                    // Check balance one more time
                    $balance = $this->db->fetchOne(
                        "SELECT COALESCE(SUM(CASE WHEN type='credit' THEN amount ELSE -amount END),0) AS bal
                         FROM farmer_ledger WHERE farmer_id=?",
                        [$withdrawal['farmer_id']], 'i'
                    );
                    
                    $currentBalance = (float)($balance['bal'] ?? 0);
                    $withdrawalAmount = (float)$withdrawal['amount'];
                    
                    if ($currentBalance < $withdrawalAmount) {
                        throw new Exception("Insufficient balance for withdrawal. Current: ₱" . number_format($currentBalance, 2));
                    }
                    
                    // Calculate new balance
                    $newBalance = $currentBalance - $withdrawalAmount;
                    
                    // Create ledger entry (debit)
                    $this->db->insert(
                        "INSERT INTO farmer_ledger
                            (farmer_id, type, category, reference_type, reference_id, amount, running_balance, description, transaction_date, recorded_by)
                         VALUES (?,?,?,?,?,?,?,?,?,?)",
                        [
                            $withdrawal['farmer_id'],
                            'debit',
                            'withdrawal',
                            'withdrawal',
                            $refId,
                            $withdrawalAmount,
                            $newBalance,
                            "Withdrawal approved by GM: " . ($withdrawal['reason'] ?? 'No reason provided'),
                            date('Y-m-d'),
                            $actorId
                        ],
                        'isssiddssi'
                    );
                    
                    // Update withdrawal status to released
                    $this->db->query(
                        "UPDATE farmer_withdrawals SET status='released', released_at=NOW() WHERE id=?",
                        [$refId], 'i'
                    );
                }
                break;
            case 'stock_return':
                // Get stock return details
                $return = $this->db->fetchOne(
                    "SELECT * FROM stock_returns WHERE id=?",
                    [$refId], 'i'
                );
                
                if (!$return) {
                    throw new Exception("Stock return not found: $refId");
                }
                
                if ($return['status'] !== 'pending') {
                    // Already processed, skip
                    return;
                }
                
                // Determine action based on condition type
                // good = restock, damaged/defective = dispose
                $action = ($return['condition_type'] === 'good') ? 'restock' : 'dispose';
                
                if ($action === 'restock') {
                    // Add stock back to inventory
                    require_once __DIR__ . '/InventoryModel.php';
                    $inv = new InventoryModel();
                    
                    $inv->addMovement([
                        'product_id'     => $return['product_id'],
                        'warehouse_id'   => $return['warehouse_id'],
                        'type'           => 'return',
                        'quantity'       => $return['quantity'],
                        'reference_type' => 'stock_return',
                        'reference_id'   => $refId,
                        'notes'          => 'GM approved return (restocked): ' . ($return['reason'] ?? ''),
                    ]);
                    
                    $status = 'restocked';
                } else {
                    // Mark as disposed (no inventory movement)
                    $status = 'disposed';
                }
                
                // Update stock return status
                $this->db->query(
                    "UPDATE stock_returns SET status=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?",
                    [$status, $actorId, $refId], 'sii'
                );
                
                break;
        }
    }

    // &#8369;8369;”€ Queries &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;

    public function getAll(array $filters = []): array {
        $where = '1=1';
        $params = [];
        $types  = '';

        if (!empty($filters['status'])) {
            $where .= ' AND ar.status = ?';
            $params[] = $filters['status'];
            $types   .= 's';
        }
        if (!empty($filters['module'])) {
            $where .= ' AND ar.module = ?';
            $params[] = $filters['module'];
            $types   .= 's';
        }
        if (!empty($filters['requested_by'])) {
            $where .= ' AND ar.requested_by = ?';
            $params[] = $filters['requested_by'];
            $types   .= 'i';
        }

        return $this->db->fetchAll(
            "SELECT ar.*, u.full_name as requester_name, u2.full_name as current_approver_name,
                    acs.label as current_step_label
             FROM approval_requests ar
             JOIN users u ON ar.requested_by = u.id
             LEFT JOIN approval_steps acs ON acs.request_id = ar.id AND acs.step_order = ar.current_step
             LEFT JOIN users u2 ON u2.id = acs.actioned_by
             WHERE $where ORDER BY ar.created_at DESC",
            $params, $types
        );
    }

    public function getPendingForRole(string $role): array {
        // GM and manager are the same — both see all pending requests
        if (in_array($role, ['admin', 'gm', 'manager'])) {
            return $this->db->fetchAll(
                "SELECT ar.*, u.full_name as requester_name, acs.label as current_step_label
                 FROM approval_requests ar
                 JOIN users u ON ar.requested_by = u.id
                 JOIN approval_steps acs ON acs.request_id = ar.id
                      AND acs.step_order = ar.current_step
                      AND acs.status = 'pending'
                 WHERE ar.status = 'pending'
                 ORDER BY ar.created_at ASC"
            );
        }

        return $this->db->fetchAll(
            "SELECT ar.*, u.full_name as requester_name, acs.label as current_step_label
             FROM approval_requests ar
             JOIN users u ON ar.requested_by = u.id
             JOIN approval_steps acs ON acs.request_id = ar.id
                  AND acs.step_order = ar.current_step
                  AND acs.status = 'pending'
                  AND acs.approver_role = ?
             WHERE ar.status = 'pending'
             ORDER BY ar.created_at ASC",
            [$role], 's'
        );
    }

    public function getSteps(int $requestId): array {
        return $this->db->fetchAll(
            "SELECT s.*, u.full_name as actor_name
             FROM approval_steps s
             LEFT JOIN users u ON s.actioned_by = u.id
             WHERE s.request_id = ? ORDER BY s.step_order",
            [$requestId], 'i'
        );
    }

    public function getAuditLog(int $requestId): array {
        return $this->db->fetchAll(
            "SELECT al.*, u.full_name as actor_name
             FROM approval_audit_log al
             JOIN users u ON al.actor_id = u.id
             WHERE al.request_id = ? ORDER BY al.created_at ASC",
            [$requestId], 'i'
        );
    }

    public function getFullAuditLog(int $limit = 200): array {
        return $this->db->fetchAll(
            "SELECT al.*, u.full_name as actor_name, ar.title, ar.module, ar.reference_type
             FROM approval_audit_log al
             JOIN users u ON al.actor_id = u.id
             JOIN approval_requests ar ON al.request_id = ar.id
             ORDER BY al.created_at DESC LIMIT $limit"
        );
    }

    // &#8369;8369;”€ Internal &#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€&#8369;8369;”€

    private function _syncRejection(string $refType, int $refId): void {
        switch ($refType) {
            case 'processing_batch':
                $this->db->query("UPDATE processing_batches SET status='cancelled' WHERE id=?", [$refId], 'i');
                $this->db->query("UPDATE processing_stage_logs SET status='skipped' WHERE batch_id=? AND status='pending'", [$refId], 'i');
                break;
            case 'delivery':
                $this->db->query("UPDATE deliveries SET status='failed' WHERE id=?", [$refId], 'i');
                break;
            case 'stock_release':
                $this->db->query("UPDATE stock_release_requests SET status='rejected' WHERE id=?", [$refId], 'i');
                break;
            case 'expense':
                $this->db->query("UPDATE expenses SET status='rejected' WHERE id=?", [$refId], 'i');
                break;
            case 'sales_order':
                $this->db->query("UPDATE sales_orders SET status='rejected' WHERE id=?", [$refId], 'i');
                break;
            case 'purchase_order':
                $this->db->query("UPDATE purchase_orders SET status='cancelled' WHERE id=?", [$refId], 'i');
                break;
            case 'purchase_requisition':
                $this->db->query("UPDATE purchase_requisitions SET status='rejected' WHERE id=?", [$refId], 'i');
                break;
            case 'employee':
                $this->db->query("UPDATE employees SET status='inactive' WHERE id=?", [$refId], 'i');
                break;
            case 'stock_return':
                $this->db->query("UPDATE stock_returns SET status='rejected', reviewed_at=NOW() WHERE id=?", [$refId], 'i');
                break;
            case 'withdrawal':
                $this->db->query("UPDATE farmer_withdrawals SET status='rejected' WHERE id=?", [$refId], 'i');
                break;
        }
    }

    private function _notifyRequester(array $req, string $action, string $remarks): void {
        require_once __DIR__ . '/../../core/NotificationHelper.php';
        $notif = new NotificationHelper();
        $isApproved = $action === 'approved';
        $title = $isApproved
            ? "✅ Request Approved: {$req['title']}"
            : "❌ Request Rejected: {$req['title']}";
        $message = $isApproved
            ? "Your request \"{$req['title']}\" has been approved by the GM."
            : "Your request \"{$req['title']}\" has been rejected by the GM." . ($remarks ? " Reason: $remarks" : '');
        $link = defined('BASE_URL') ? BASE_URL . '/approvals/detail?id=' . $req['id'] : '/approvals/detail?id=' . $req['id'];
        $type = $isApproved ? 'request_approved' : 'request_rejected';
        $notif->notifyUser((int)$req['requested_by'], $type, $title, $message, $link);
    }

    private function _audit(int $requestId, int $step, int $actorId, string $action, string $remarks): void {
        $this->db->insert(
            "INSERT INTO approval_audit_log (request_id, step_order, actor_id, action, remarks) VALUES (?,?,?,?,?)",
            [$requestId, $step, $actorId, $action, $remarks],
            'iiiss'
        );
    }
}

