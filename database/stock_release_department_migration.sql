-- Migration: Add requesting_department column to stock_release_requests table
-- Purpose: Track which department (Logistics, Production, Processing) is requesting stock
-- Date: 2026-04-28

ALTER TABLE stock_release_requests 
ADD COLUMN requesting_department VARCHAR(50) NULL 
AFTER purpose;
