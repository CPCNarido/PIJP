-- Migration: Add image_path to gas_tanks
-- Date: 2026-02-14

ALTER TABLE gas_tanks ADD COLUMN image_path VARCHAR(255) NULL;
