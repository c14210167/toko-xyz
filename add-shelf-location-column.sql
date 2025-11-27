-- Add shelf_location column to inventory_items table
ALTER TABLE inventory_items
ADD COLUMN shelf_location VARCHAR(100) AFTER location_id;

-- Update some sample data (optional)
UPDATE inventory_items SET shelf_location = 'Rak A, baris 1' WHERE item_id = 1;
UPDATE inventory_items SET shelf_location = 'Rak A, baris 2' WHERE item_id = 2;
UPDATE inventory_items SET shelf_location = 'Rak B, baris 1' WHERE item_id = 3;
