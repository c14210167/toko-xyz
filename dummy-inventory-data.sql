-- ============================================
-- DUMMY INVENTORY DATA - Computer Shop
-- Realistic spare parts and products for XYZ Service Center
-- ============================================

USE xyz_service;

-- ============================================
-- SPARE PARTS (Category ID: 1)
-- ============================================

INSERT INTO inventory_items (item_code, name, category_id, description, quantity, unit, unit_price, reorder_level) VALUES
-- Motherboards
('MB-001', 'ASUS ROG Strix B550-F Gaming', 1, 'AMD B550 ATX Gaming Motherboard', 5, 'pcs', 2850000, 2),
('MB-002', 'MSI B450M PRO-VDH MAX', 1, 'AMD B450 Micro-ATX Motherboard', 8, 'pcs', 1250000, 3),
('MB-003', 'Gigabyte H510M H', 1, 'Intel H510 Micro-ATX Motherboard', 6, 'pcs', 1150000, 3),
('MB-004', 'ASRock B660M Steel Legend', 1, 'Intel B660 Micro-ATX Motherboard', 4, 'pcs', 1950000, 2),

-- RAM Memory
('RAM-001', 'Corsair Vengeance RGB 16GB DDR4 3200MHz', 1, 'RGB Desktop RAM 16GB Kit (2x8GB)', 15, 'pcs', 1350000, 5),
('RAM-002', 'Kingston Fury Beast 8GB DDR4 2666MHz', 1, 'Desktop RAM 8GB Single', 20, 'pcs', 450000, 8),
('RAM-003', 'G.Skill Ripjaws V 32GB DDR4 3600MHz', 1, 'Desktop RAM 32GB Kit (2x16GB)', 8, 'pcs', 2850000, 3),
('RAM-004', 'Crucial 8GB DDR4 2400MHz SODIMM', 1, 'Laptop RAM 8GB', 25, 'pcs', 550000, 10),
('RAM-005', 'Samsung 16GB DDR4 3200MHz SODIMM', 1, 'Laptop RAM 16GB', 12, 'pcs', 1150000, 5),

-- Storage - SSD
('SSD-001', 'Samsung 970 EVO Plus 500GB NVMe', 1, 'M.2 NVMe SSD 500GB', 18, 'pcs', 1250000, 6),
('SSD-002', 'WD Blue SN570 1TB NVMe', 1, 'M.2 NVMe SSD 1TB', 15, 'pcs', 1850000, 5),
('SSD-003', 'Kingston A400 240GB SATA', 1, '2.5" SATA SSD 240GB', 22, 'pcs', 450000, 8),
('SSD-004', 'Crucial MX500 500GB SATA', 1, '2.5" SATA SSD 500GB', 16, 'pcs', 850000, 6),
('SSD-005', 'Samsung 980 PRO 1TB NVMe Gen4', 1, 'M.2 NVMe Gen4 SSD 1TB', 10, 'pcs', 2650000, 3),

-- Storage - HDD
('HDD-001', 'Seagate BarraCuda 1TB 7200RPM', 1, '3.5" Internal HDD 1TB', 12, 'pcs', 650000, 5),
('HDD-002', 'WD Blue 2TB 5400RPM', 1, '3.5" Internal HDD 2TB', 8, 'pcs', 850000, 4),
('HDD-003', 'Toshiba 500GB 2.5" 5400RPM', 1, '2.5" Laptop HDD 500GB', 15, 'pcs', 450000, 6),
('HDD-004', 'Seagate IronWolf 4TB NAS', 1, '3.5" NAS HDD 4TB', 5, 'pcs', 1950000, 2),

-- Graphics Cards
('GPU-001', 'NVIDIA GTX 1650 4GB', 1, 'Graphics Card GTX 1650 4GB GDDR6', 6, 'pcs', 2450000, 2),
('GPU-002', 'AMD RX 6600 8GB', 1, 'Graphics Card RX 6600 8GB GDDR6', 4, 'pcs', 3850000, 2),
('GPU-003', 'NVIDIA RTX 3060 12GB', 1, 'Graphics Card RTX 3060 12GB GDDR6', 3, 'pcs', 5850000, 1),

-- Power Supplies
('PSU-001', 'Corsair CV450 450W 80+ Bronze', 1, 'Power Supply 450W Non-Modular', 10, 'pcs', 650000, 4),
('PSU-002', 'Cooler Master MWE 550W 80+ Bronze', 1, 'Power Supply 550W Non-Modular', 8, 'pcs', 750000, 3),
('PSU-003', 'Seasonic Focus GX-650 650W 80+ Gold', 1, 'Power Supply 650W Full Modular', 6, 'pcs', 1450000, 2),
('PSU-004', 'ASUS ROG Strix 850W 80+ Gold', 1, 'Power Supply 850W Full Modular', 4, 'pcs', 2350000, 2),

-- Processors
('CPU-001', 'Intel Core i3-12100F', 1, 'Processor Intel 12th Gen 4C/8T', 8, 'pcs', 1450000, 3),
('CPU-002', 'AMD Ryzen 5 5600G', 1, 'Processor AMD with Integrated GPU', 6, 'pcs', 2150000, 2),
('CPU-003', 'Intel Core i5-12400F', 1, 'Processor Intel 12th Gen 6C/12T', 5, 'pcs', 2450000, 2),
('CPU-004', 'AMD Ryzen 7 5700X', 1, 'Processor AMD 8C/16T', 4, 'pcs', 3650000, 2),

-- Cooling
('COOL-001', 'Deepcool GAMMAXX 400', 1, 'CPU Cooler Tower Type', 12, 'pcs', 250000, 5),
('COOL-002', 'Noctua NH-U12S', 1, 'Premium CPU Cooler', 6, 'pcs', 950000, 3),
('COOL-003', 'Cooler Master Hyper 212', 1, 'CPU Cooler Tower Type', 10, 'pcs', 450000, 4),
('COOL-004', 'ID-COOLING SE-224-XT', 1, 'Budget CPU Cooler', 15, 'pcs', 280000, 6),

-- Laptop Screens
('LCD-001', 'Laptop LCD 14" HD 1366x768', 1, 'Replacement Laptop Screen 14 inch', 8, 'pcs', 650000, 3),
('LCD-002', 'Laptop LCD 15.6" FHD 1920x1080', 1, 'Replacement Laptop Screen 15.6 inch', 10, 'pcs', 850000, 4),
('LCD-003', 'Laptop LCD 13.3" FHD IPS', 1, 'Replacement Laptop Screen 13.3 inch', 5, 'pcs', 1250000, 2),

-- Laptop Batteries
('BAT-001', 'Battery Asus A455L Original', 1, 'Laptop Battery Asus A455L', 12, 'pcs', 450000, 5),
('BAT-002', 'Battery Lenovo ThinkPad E470 Original', 1, 'Laptop Battery Lenovo E470', 8, 'pcs', 650000, 4),
('BAT-003', 'Battery HP 240 G6 Original', 1, 'Laptop Battery HP 240 G6', 10, 'pcs', 550000, 4),
('BAT-004', 'Battery Dell Inspiron 14-3000 Original', 1, 'Laptop Battery Dell Inspiron', 6, 'pcs', 750000, 3);

-- ============================================
-- ACCESSORIES (Category ID: 2)
-- ============================================

INSERT INTO inventory_items (item_code, name, category_id, description, quantity, unit, unit_price, reorder_level) VALUES
-- Mouse
('MSE-001', 'Logitech M170 Wireless Mouse', 2, 'Wireless Mouse with USB Receiver', 25, 'pcs', 125000, 10),
('MSE-002', 'Logitech G102 Gaming Mouse', 2, 'RGB Gaming Mouse 8000 DPI', 18, 'pcs', 250000, 8),
('MSE-003', 'Razer DeathAdder Essential', 2, 'Gaming Mouse 6400 DPI', 12, 'pcs', 350000, 6),
('MSE-004', 'Fantech X9 Gaming Mouse', 2, 'Budget Gaming Mouse RGB', 30, 'pcs', 85000, 12),

-- Keyboard
('KEY-001', 'Logitech K120 Wired Keyboard', 2, 'Standard USB Keyboard', 20, 'pcs', 150000, 8),
('KEY-002', 'Rexus Legionare MX5 TKL', 2, 'Mechanical Gaming Keyboard Blue Switch', 15, 'pcs', 450000, 6),
('KEY-003', 'Logitech K380 Bluetooth Keyboard', 2, 'Multi-Device Bluetooth Keyboard', 12, 'pcs', 550000, 5),
('KEY-004', 'Fantech MK857 RGB Mechanical', 2, 'Full Size Mechanical Keyboard', 10, 'pcs', 650000, 4),

-- Webcam
('WEB-001', 'Logitech C270 HD Webcam', 2, 'HD 720p Webcam with Mic', 15, 'pcs', 450000, 6),
('WEB-002', 'Logitech C920 Pro HD Webcam', 2, 'Full HD 1080p Webcam', 8, 'pcs', 1250000, 3),
('WEB-003', 'Razer Kiyo Streaming Webcam', 2, '1080p Webcam with Ring Light', 5, 'pcs', 1850000, 2),

-- Headset
('HEAD-001', 'Logitech H111 Stereo Headset', 2, 'Wired Stereo Headset with Mic', 18, 'pcs', 150000, 8),
('HEAD-002', 'Rexus Vonix F26 Gaming Headset', 2, 'RGB Gaming Headset', 15, 'pcs', 250000, 6),
('HEAD-003', 'Logitech G331 Gaming Headset', 2, '50mm Driver Gaming Headset', 10, 'pcs', 550000, 4),

-- Cables
('CBL-001', 'HDMI Cable 1.5m', 2, 'HDMI 2.0 Cable High Speed', 40, 'pcs', 35000, 15),
('CBL-002', 'DisplayPort Cable 1.8m', 2, 'DP 1.4 Cable 4K Support', 25, 'pcs', 65000, 10),
('CBL-003', 'USB 3.0 Cable Type-A to Type-B', 2, 'USB 3.0 Printer Cable 1.5m', 30, 'pcs', 25000, 12),
('CBL-004', 'USB-C to USB-C Cable 1m', 2, 'USB-C PD Fast Charging Cable', 35, 'pcs', 45000, 15),
('CBL-005', 'SATA Data Cable 50cm', 2, 'SATA 3.0 6Gbps Cable', 50, 'pcs', 15000, 20),
('CBL-006', 'VGA Cable 1.5m', 2, 'VGA Male to Male Cable', 30, 'pcs', 25000, 12),

-- Adapters
('ADP-001', 'USB-C to HDMI Adapter', 2, 'Type-C to HDMI 4K Adapter', 20, 'pcs', 125000, 8),
('ADP-002', 'USB 3.0 Hub 4 Port', 2, '4-Port USB 3.0 Hub', 18, 'pcs', 85000, 8),
('ADP-003', 'DisplayPort to HDMI Adapter', 2, 'DP to HDMI 4K Adapter', 15, 'pcs', 75000, 6),
('ADP-004', 'USB Bluetooth 5.0 Dongle', 2, 'Bluetooth Adapter for PC', 25, 'pcs', 55000, 10),

-- External Storage
('EXT-001', 'Sandisk USB Flash Drive 32GB', 2, 'USB 3.0 Flash Drive 32GB', 40, 'pcs', 85000, 15),
('EXT-002', 'Kingston USB Flash Drive 64GB', 2, 'USB 3.2 Flash Drive 64GB', 30, 'pcs', 125000, 12),
('EXT-003', 'WD Elements 1TB External HDD', 2, 'Portable External HDD 1TB USB 3.0', 12, 'pcs', 850000, 5),
('EXT-004', 'Seagate Backup Plus 2TB', 2, 'Portable External HDD 2TB', 8, 'pcs', 1250000, 4),

-- Monitor
('MON-001', 'LG 22MK430H 22" IPS Monitor', 2, '22" Full HD IPS Monitor 75Hz', 6, 'pcs', 1450000, 2),
('MON-002', 'ASUS VP249HE 24" IPS Monitor', 2, '24" Full HD IPS Monitor', 8, 'pcs', 1650000, 3),
('MON-003', 'AOC 24G2 24" Gaming Monitor', 2, '24" 144Hz IPS Gaming Monitor', 5, 'pcs', 2350000, 2),

-- Laptop Accessories
('LAP-001', 'Laptop Cooling Pad RGB', 2, 'Laptop Cooler with 5 Fans', 15, 'pcs', 150000, 6),
('LAP-002', 'Laptop Stand Aluminum', 2, 'Adjustable Laptop Stand', 12, 'pcs', 185000, 5),
('LAP-003', 'Laptop Charger Universal 65W', 2, 'Universal Laptop Adapter 65W', 10, 'pcs', 250000, 5);

-- ============================================
-- TOOLS (Category ID: 3)
-- ============================================

INSERT INTO inventory_items (item_code, name, category_id, description, quantity, unit, unit_price, reorder_level) VALUES
('TOOL-001', 'Precision Screwdriver Set 32in1', 3, 'Multi-purpose Screwdriver Set for Electronics', 8, 'set', 125000, 3),
('TOOL-002', 'Anti-Static Wrist Strap', 3, 'ESD Protection Wrist Strap', 15, 'pcs', 35000, 6),
('TOOL-003', 'Thermal Paste Arctic MX-4', 3, 'High Performance Thermal Compound 4g', 25, 'pcs', 85000, 10),
('TOOL-004', 'Cable Tester RJ45 + RJ11', 3, 'Network Cable Tester', 5, 'pcs', 75000, 2),
('TOOL-005', 'Digital Multimeter', 3, 'Digital Multimeter for Electronics', 6, 'pcs', 250000, 2),
('TOOL-006', 'Solder Wire 60/40 100g', 3, 'Tin Lead Solder Wire', 10, 'pcs', 45000, 4),
('TOOL-007', 'Soldering Iron 60W', 3, 'Adjustable Temperature Soldering Iron', 8, 'pcs', 185000, 3),
('TOOL-008', 'Desoldering Pump', 3, 'Solder Sucker Tool', 12, 'pcs', 25000, 5),
('TOOL-009', 'Compressed Air Duster 400ml', 3, 'Air Duster for Cleaning Electronics', 30, 'pcs', 45000, 12),
('TOOL-010', 'SATA to USB 3.0 Adapter', 3, 'HDD/SSD to USB Adapter Cable', 12, 'pcs', 85000, 5),
('TOOL-011', 'Crimping Tool RJ45', 3, 'Network Cable Crimper', 5, 'pcs', 125000, 2),
('TOOL-012', 'Isopropyl Alcohol 99% 500ml', 3, 'Cleaning Alcohol for Electronics', 20, 'bottle', 35000, 8),
('TOOL-013', 'Plastic Spudger Set', 3, 'Prying Tools for Opening Devices', 10, 'set', 45000, 4),
('TOOL-014', 'Magnetic Parts Tray', 3, 'Magnetic Screw Holder Tray', 12, 'pcs', 35000, 5);

-- ============================================
-- CONSUMABLES (Category ID: 4)
-- ============================================

INSERT INTO inventory_items (item_code, name, category_id, description, quantity, unit, unit_price, reorder_level) VALUES
('CONS-001', 'Cable Ties 100pcs White', 4, 'Nylon Cable Ties 2.5mm x 100mm', 50, 'pack', 15000, 20),
('CONS-002', 'Cable Ties 100pcs Black', 4, 'Nylon Cable Ties 2.5mm x 100mm', 45, 'pack', 15000, 20),
('CONS-003', 'Double Sided Tape 3M', 4, 'Heavy Duty Double Sided Tape', 30, 'roll', 25000, 12),
('CONS-004', 'Velcro Cable Ties Reusable', 4, 'Velcro Cable Management Ties 50pcs', 25, 'pack', 35000, 10),
('CONS-005', 'Thermal Pad 1mm 100x100mm', 4, 'Thermal Pad for VRM/VRAM Cooling', 20, 'pcs', 45000, 8),
('CONS-006', 'Microfiber Cleaning Cloth', 4, 'Screen Cleaning Cloth 30x30cm', 40, 'pcs', 15000, 15),
('CONS-007', 'Cable Sleeve Braided 5m', 4, 'Cable Management Sleeve Black', 18, 'roll', 55000, 8),
('CONS-008', 'Label Sticker Paper A4', 4, 'Printable Label Sticker 20 sheets', 15, 'pack', 35000, 6),
('CONS-009', 'Anti-Static Bag 100pcs', 4, 'ESD Protection Bags for Components', 25, 'pack', 45000, 10),
('CONS-010', 'Insulation Tape Black', 4, 'Electrical Tape 18mm x 10m', 35, 'roll', 8000, 15),
('CONS-011', 'Plastic Ziplock Bag Mixed Size', 4, 'Storage Bags for Small Parts 100pcs', 30, 'pack', 25000, 12),
('CONS-012', 'Cotton Swab 100pcs', 4, 'Cotton Buds for Cleaning', 40, 'pack', 12000, 15),
('CONS-013', 'Silica Gel Packets 50g', 4, 'Moisture Absorber 10pcs', 25, 'pack', 25000, 10),
('CONS-014', 'Keyboard Cleaning Gel', 4, 'Keyboard Dust Cleaner Gel', 20, 'pcs', 35000, 8);

-- ============================================
-- VERIFICATION
-- ============================================

SELECT 'Dummy inventory data inserted successfully!' as Status;
SELECT category_name, COUNT(*) as item_count
FROM inventory_items i
JOIN inventory_categories c ON i.category_id = c.category_id
GROUP BY category_name;
