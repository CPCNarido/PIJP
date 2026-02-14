-- Migration: Add delivery staff management features
-- Date: 2026-02-14

-- Create staff table for delivery riders
CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(160),
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add delivery address column to orders
ALTER TABLE orders ADD COLUMN delivery_address TEXT NULL;

-- Add staff_id column to orders
ALTER TABLE orders ADD COLUMN staff_id INT NULL;

-- Add foreign key constraint for staff_id
ALTER TABLE orders ADD CONSTRAINT fk_orders_staff_id FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL;

-- Update orders status ENUM to include 'in_delivery'
ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'approved', 'in_delivery', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending';

-- Update order_events status ENUM to include 'in_delivery'
ALTER TABLE order_events MODIFY COLUMN status ENUM('pending', 'approved', 'in_delivery', 'delivered', 'cancelled') NOT NULL;
