-- Add parts_cost column to order_costs table
-- This column tracks the cost of spare parts used in orders

ALTER TABLE order_costs
ADD COLUMN parts_cost DECIMAL(10,2) DEFAULT 0 AFTER service_cost;

-- Update existing records to set parts_cost to 0 if NULL
UPDATE order_costs SET parts_cost = 0 WHERE parts_cost IS NULL;
