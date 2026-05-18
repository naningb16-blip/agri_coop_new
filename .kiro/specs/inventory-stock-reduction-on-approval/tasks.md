# Implementation Plan: Inventory Stock Reduction on Approval

## Overview

This implementation plan enhances the existing approval workflow system to automatically reduce inventory stock when the General Manager approves stock release requests. The work builds on the existing `ApprovalModel::_syncApproval()` method's stock_release case, adding proper validation, atomic transactions, error handling, and department tracking.

The implementation focuses on:
- Database schema migration for department tracking
- Transaction-wrapped approval processing with rollback capability
- Stock availability validation at approval time (not just creation time)
- Idempotent stock reduction (preventing duplicate movements)
- Complete audit trail with department information
- Comprehensive error handling and user notifications

## Tasks

- [x] 1. Create database migration for requesting_department column
  - Create migration file `database/stock_release_department_migration.sql`
  - Add `requesting_department VARCHAR(50) NULL` column to `stock_release_requests` table
  - Position column after `purpose` column
  - Test migration runs successfully without errors
  - _Requirements: 1.4, 5.1_

- [ ] 2. Enhance ApprovalModel with transaction handling
  - [x] 2.1 Wrap actOnStep() method in database transaction
    - Add `START TRANSACTION` at the beginning of actOnStep()
    - Add `COMMIT` on successful completion
    - Add `ROLLBACK` in catch block with error logging
    - Return error response with exception message on failure
    - Ensure all existing approval flows work within transaction
    - _Requirements: 6.1, 6.3, 10.1, 10.2, 10.3_
  
  - [ ] 2.2 Write property test for transaction rollback
    - **Property 25: Rollback on Stock Reduction Failure**
    - **Validates: Requirements 10.1**
    - Test that approval status remains unchanged when stock reduction fails
    - Generate scenarios with insufficient stock discovered during transaction
    - Verify no partial updates occur (approval_steps, approval_requests unchanged)
  
  - [ ] 2.3 Write property test for approval failure rollback
    - **Property 26: Rollback on Approval Failure**
    - **Validates: Requirements 10.2**
    - Test that stock movements are rolled back if approval update fails
    - Simulate database errors during approval status update
    - Verify inventory quantity remains unchanged after rollback

- [ ] 3. Enhance _syncApproval() stock_release case with validation and atomic operations
  - [x] 3.1 Add stock availability validation at approval time
    - Fetch stock release request data from database
    - Query current inventory with `SELECT ... FOR UPDATE` to lock row
    - Compare available quantity with requested quantity
    - Throw exception with detailed error message if insufficient stock
    - Include product name, requested amount, and available amount in error
    - _Requirements: 3.3, 8.1, 8.2, 8.3, 8.4_
  
  - [x] 3.2 Add idempotency check for stock movements
    - Query stock_movements table for existing movement with reference_type='stock_release' and matching reference_id
    - Return early (skip processing) if movement already exists
    - Prevents duplicate stock reductions on repeated approval attempts
    - _Requirements: 9.4_
  
  - [x] 3.3 Enhance stock movement creation with department information
    - Build notes string including GM approval message and purpose
    - Append requesting_department to notes if present
    - Call InventoryModel::addMovement() with complete data including reference_type and reference_id
    - Update stock_release_requests status to 'released' with released_at timestamp
    - _Requirements: 3.1, 3.2, 3.4, 3.5, 4.1, 4.2, 4.3, 4.5_
  
  - [ ] 3.4 Write property test for stock availability validation
    - **Property 19: Stock Availability Validation at Approval Time**
    - **Validates: Requirements 8.1, 8.2**
    - Generate random stock levels and request quantities
    - Test approval succeeds when available >= requested
    - Test approval fails with error when available < requested
    - Verify error message contains product name and quantities
  
  - [ ] 3.5 Write property test for real-time stock validation
    - **Property 20: Real-Time Stock Validation**
    - **Validates: Requirements 8.3**
    - Create request when stock is S1
    - Change stock to S2 before approval
    - Verify approval validation uses S2 (current value)
    - Test with S2 > S1 (should succeed) and S2 < S1 (should fail)
  
  - [ ] 3.6 Write property test for idempotency
    - **Property 24: Idempotency**
    - **Validates: Requirements 9.4**
    - Generate approved stock release requests
    - Process approval multiple times
    - Verify exactly one stock_movements record exists
    - Verify inventory quantity reduced only once

- [ ] 4. Checkpoint - Ensure approval flow works with validation
  - Run manual test: create stock release request and approve with sufficient stock
  - Run manual test: approve request with insufficient stock (should fail with error)
  - Verify transaction rollback works correctly on failure
  - Verify stock movements created correctly with department info
  - Ask user if questions arise

- [ ] 5. Create or enhance InventoryController stock release request creation
  - [x] 5.1 Add createReleaseRequest() method to InventoryController
    - Accept POST data: product_id, warehouse_id, quantity, purpose, requesting_department
    - Validate all required fields are present and valid
    - Validate quantity is positive number
    - Query current stock availability (soft check at creation time)
    - Return error if insufficient stock at creation time
    - Insert record into stock_release_requests table with all fields including requesting_department
    - Create approval request via ApprovalModel::createRequest()
    - Return success JSON response
    - _Requirements: 1.1, 1.2, 1.3, 1.4_
  
  - [ ] 5.2 Write property test for stock release request creation
    - **Property 1: Stock Release Request Data Persistence**
    - **Validates: Requirements 1.1**
    - Generate random valid request data (product, warehouse, quantity, department, purpose)
    - Create stock release request
    - Query database and verify all fields match input values
  
  - [ ] 5.3 Write property test for stock availability validation at creation
    - **Property 2: Stock Availability Validation at Creation**
    - **Validates: Requirements 1.2**
    - Generate random stock scenarios (available stock vs requested quantity)
    - Test request accepted when available >= requested
    - Test request rejected when available < requested
  
  - [ ] 5.4 Write property test for approval request creation
    - **Property 3: Approval Request Creation on Stock Release**
    - **Validates: Requirements 1.3**
    - Create stock release request
    - Query approval_requests table
    - Verify approval request exists with status='pending', reference_type='stock_release', matching reference_id
  
  - [ ] 5.5 Write property test for department information preservation
    - **Property 4: Department Information Preservation**
    - **Validates: Requirements 1.4, 3.5, 4.3, 5.1, 5.4**
    - Generate requests with different departments
    - Create and approve requests
    - Verify department preserved in stock_release_requests table
    - Verify department appears in stock_movements.notes field
    - Verify department available in query results

- [ ] 6. Enhance notification system for stock release approvals
  - [x] 6.1 Verify _notifyRequester() handles stock release notifications
    - Review existing _notifyRequester() implementation in ApprovalModel
    - Verify it includes request title, approval status, and link
    - Verify it handles both approval and rejection cases
    - Add remarks to rejection notification message
    - _Requirements: 7.1, 7.2, 7.3, 7.4_
  
  - [ ] 6.2 Write property test for approval notifications
    - **Property 16: Approval Notification**
    - **Validates: Requirements 7.1**
    - Generate and approve stock release requests
    - Query notifications table for requesting user
    - Verify notification exists with type indicating approval
  
  - [ ] 6.3 Write property test for rejection notifications
    - **Property 17: Rejection Notification**
    - **Validates: Requirements 7.2**
    - Generate and reject stock release requests with remarks
    - Query notifications table for requesting user
    - Verify notification exists with type indicating rejection
    - Verify message contains rejection remarks
  
  - [ ] 6.4 Write property test for notification content
    - **Property 18: Notification Content Completeness**
    - **Validates: Requirements 7.3, 7.4**
    - Generate approved/rejected requests
    - Query notification records
    - Verify message contains title, product name, quantity, status
    - Verify link field contains valid URL to request detail page

- [ ] 7. Add query methods for department-filtered stock releases
  - [x] 7.1 Enhance InventoryModel::getReleaseRequests() to support department filtering
    - Add optional department filter parameter
    - Add WHERE clause for requesting_department when filter provided
    - Ensure department information included in SELECT results
    - Test query returns correct filtered results
    - _Requirements: 5.2, 5.3, 5.4_
  
  - [ ] 7.2 Write property test for department filtering
    - **Property 13: Department Filtering**
    - **Validates: Requirements 5.2**
    - Generate requests for multiple departments (Logistics, Production, Processing)
    - Query with department filter
    - Verify results contain only requests for specified department
    - Verify no requests from other departments returned
  
  - [ ] 7.3 Write property test for query data availability
    - **Property 14: Query Data Availability**
    - **Validates: Requirements 5.3**
    - Generate stock release requests
    - Query release requests
    - Verify approval status available in results (from approval_requests join)
    - Verify released_at timestamp available in results

- [ ] 8. Add comprehensive unit tests for core functionality
  - [ ] 8.1 Write unit tests for stock movement creation
    - Test movement record contains all required fields (product_id, warehouse_id, quantity, created_at)
    - Test reference_type='stock_release' and reference_id matches request
    - Test notes field includes department information
    - Test movement is immutable (cannot be updated after creation)
    - _Requirements: 3.4, 4.1, 4.2, 4.3_
  
  - [ ] 8.2 Write unit tests for inventory quantity updates
    - Test inventory quantity reduced by approved amount
    - Test GREATEST(0, quantity - amount) prevents negative values
    - Test ON DUPLICATE KEY UPDATE works for existing inventory records
    - Test INSERT works for new product/warehouse combinations
    - _Requirements: 3.2, 3.3, 6.2_
  
  - [ ] 8.3 Write unit tests for approval status synchronization
    - Test approval_requests.status updated to 'approved' on GM approval
    - Test stock_release_requests.status updated to 'released' on GM approval
    - Test both updates occur in same transaction
    - Test approval_requests.status updated to 'rejected' on GM rejection
    - Test stock_release_requests.status updated to 'rejected' on GM rejection
    - _Requirements: 2.1, 2.2, 2.3, 2.4_
  
  - [ ] 8.4 Write unit tests for audit trail
    - Test approval_steps records actioned_by user ID and actioned_at timestamp
    - Test approval_audit_log entry created for each action
    - Test audit log includes step_order, actor_id, action, and remarks
    - _Requirements: 2.5, 4.5_
  
  - [ ] 8.5 Write unit tests for error scenarios
    - Test approval fails with error when stock release request not found
    - Test approval fails with error when insufficient stock at approval time
    - Test error message format includes product name and quantities
    - Test transaction rollback on database errors
    - Test no partial updates on failure
    - _Requirements: 8.4, 10.1, 10.2, 10.3_

- [ ] 9. Checkpoint - Ensure all tests pass
  - Run all unit tests and verify they pass
  - Run property-based tests (if implemented) and verify they pass
  - Fix any failing tests
  - Ensure code coverage is adequate for new code
  - Ask user if questions arise

- [ ] 10. Integration testing and final validation
  - [ ] 10.1 Test complete approval workflow end-to-end
    - Create stock release request via InventoryController
    - Verify approval request created with correct reference
    - Approve as GM via ApprovalController
    - Verify stock reduced correctly
    - Verify stock movement created with department info
    - Verify notification sent to requester
    - Query stock movements and verify audit trail complete
    - _Requirements: All_
  
  - [ ] 10.2 Test multi-department scenarios
    - Create requests for Logistics, Production, and Processing departments
    - Approve all requests
    - Query by department and verify filtering works
    - Verify each department's movements tracked separately
    - _Requirements: 5.1, 5.2, 5.3, 5.4_
  
  - [ ] 10.3 Test concurrent approval handling
    - Create multiple stock release requests for same product/warehouse
    - Simulate concurrent approval attempts (if possible in test environment)
    - Verify each approval processes atomically
    - Verify no race conditions or negative inventory
    - Verify SELECT ... FOR UPDATE prevents concurrent issues
    - _Requirements: 6.1, 6.3_
  
  - [ ] 10.4 Test failure and rollback scenarios
    - Create request with sufficient stock
    - Reduce stock externally to create insufficient condition
    - Attempt approval and verify it fails with appropriate error
    - Verify transaction rolled back (approval status unchanged)
    - Verify no stock movement created
    - Verify inventory quantity unchanged
    - _Requirements: 10.1, 10.2_
  
  - [ ] 10.5 Test rejection workflow
    - Create stock release request
    - Reject as GM with remarks
    - Verify both approval_requests and stock_release_requests status updated to 'rejected'
    - Verify no stock movement created
    - Verify notification sent with rejection reason
    - _Requirements: 2.3, 2.4, 7.2, 9.2_
  
  - [ ] 10.6 Test multi-step approval chain
    - Configure approval chain with multiple steps before GM
    - Create stock release request
    - Approve at intermediate steps
    - Verify stock NOT reduced until GM approval
    - Approve at GM step
    - Verify stock reduced only after GM approval
    - _Requirements: 9.1, 9.2, 9.3_

- [ ] 11. Final checkpoint - Production readiness verification
  - Review all code changes for security issues
  - Verify all database queries use parameterized statements
  - Verify all user inputs are validated and sanitized
  - Verify error messages don't expose sensitive information
  - Verify logging is adequate for debugging production issues
  - Run migration on test database and verify schema changes
  - Document any configuration changes needed
  - Ask user if ready for deployment

## Notes

- Tasks marked with `*` are optional testing tasks and can be skipped for faster MVP delivery
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation and allow for user feedback
- Property tests validate universal correctness properties across randomized inputs
- Unit tests validate specific examples and edge cases
- Integration tests verify complete end-to-end workflows
- The implementation builds on existing code in ApprovalModel and InventoryModel
- Transaction handling ensures atomicity and prevents partial updates
- Idempotency checks prevent duplicate stock movements
- Department tracking enables audit trail and departmental reporting
