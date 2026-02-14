-- Add category field to gas_tanks table
ALTER TABLE gas_tanks
ADD COLUMN category ENUM('gas', 'accessories', 'stove') NOT NULL DEFAULT 'gas' AFTER name;
