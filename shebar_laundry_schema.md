# Shebar Laundry Management System Database Schema

## Prepared for Customer Evaluation

**Prepared by**: Samuel Aberra  
**Version**: 2.0  
**Date**: August 4, 2025

---

## Table of Contents

-   [Shebar Laundry Management System Database Schema](#shebar-laundry-management-system-database-schema)
    -   [Prepared for Customer Evaluation](#prepared-for-customer-evaluation)
    -   [Table of Contents](#table-of-contents)
    -   [Introduction](#introduction)
    -   [Database Schema](#database-schema)
        -   [users](#users)
        -   [roles](#roles)
        -   [permissions](#permissions)
        -   [role_has_permissions](#role_has_permissions)
        -   [model_has_roles](#model_has_roles)
        -   [model_has_permissions](#model_has_permissions)
        -   [customers](#customers)
        -   [units](#units)
        -   [cloth_items](#cloth_items)
        -   [services](#services)
        -   [urgency_tiers](#urgency_tiers)
        -   [pricing_tiers](#pricing_tiers)
        -   [orders](#orders)
        -   [order_items](#order_items)
        -   [order_item_services](#order_item_services)
        -   [stores](#stores)
        -   [inventory_items](#inventory_items)
    -   [inventory_stock](#inventory_stock)
    -   [purchases](#purchases)
    -   [purchase_items](#purchase_items)
        -   [stock_transfers](#stock_transfers)
        -   [stock_transfer_items](#stock_transfer_items)
        -   [stock_usage](#stock_usage)
    -   [payments](#payments)
    -   [penalty_tiers](#penalty_tiers)
    -   [order_item_penalties](#order_item_penalties)
        -   [notifications](#notifications)
        -   [system_settings](#system_settings)
        -   [activity_logs](#activity_logs)
    -   [Explanation of Changes](#explanation-of-changes)

---

## Introduction

This document presents the updated database schema for the Shebar Laundry Management System, a comprehensive solution for managing laundry operations. The schema supports customer registration, order processing with flexible pricing (normal and urgent), inventory tracking with main and sub-stores, role-based access control, and detailed status tracking for orders and services. It is optimized for Laravel 12, ensuring scalability, maintainability, and alignment with industry standards.

---

## Database Schema

### users

| Column     | Type             | Constraints/Description                            |
| ---------- | ---------------- | -------------------------------------------------- |
| id         | bigint, unsigned | Auto-incrementing primary key                      |
| name       | string(255)      | Not null                                           |
| email      | string(255)      | Not null, unique                                   |
| password   | string(255)      | Not null                                           |
| phone      | string(20)       | Not null                                           |
| created_at | timestamp        | Default current timestamp                          |
| updated_at | timestamp        | Default current timestamp, updates on modification |

### roles

| Column     | Type             | Constraints/Description                            |
| ---------- | ---------------- | -------------------------------------------------- |
| id         | bigint, unsigned | Auto-incrementing primary key                      |
| name       | string(255)      | Not null, unique (e.g., Admin, Manager, Receptionist, Operator)       |
| guard_name | string(255)      | Not null, for Spatie Laravel Permission            |
| created_at | timestamp        | Default current timestamp                          |
| updated_at | timestamp        | Default current timestamp, updates on modification |

### permissions

| Column     | Type             | Constraints/Description                                 |
| ---------- | ---------------- | ------------------------------------------------------- |
| id         | bigint, unsigned | Auto-incrementing primary key                           |
| name       | string(255)      | Not null, unique (e.g., create_order, manage_inventory) |
| guard_name | string(255)      | Not null, for Spatie Laravel Permission                 |
| created_at | timestamp        | Default current timestamp                               |
| updated_at | timestamp        | Default current timestamp, updates on modification      |

### role_has_permissions

| Column        | Type                     | Constraints/Description                                    |
| ------------- | ------------------------ | ---------------------------------------------------------- |
| permission_id | bigint, unsigned         | Not null, foreign key to permissions.id, on delete cascade |
| role_id       | bigint, unsigned         | Not null, foreign key to roles.id, on delete cascade       |
| unique        | [permission_id, role_id] | Composite unique constraint                                |

### model_has_roles

| Column     | Type                            | Constraints/Description                              |
| ---------- | ------------------------------- | ---------------------------------------------------- |
| role_id    | bigint, unsigned                | Not null, foreign key to roles.id, on delete cascade |
| model_type | string(255)                     | Not null, polymorphic (e.g., `App\\Models\\User`)    |
| model_id   | bigint, unsigned                | Not null                                             |
| unique     | [role_id, model_type, model_id] | Composite unique constraint                          |

### model_has_permissions

| Column        | Type                                  | Constraints/Description                                    |
| ------------- | ------------------------------------- | ---------------------------------------------------------- |
| permission_id | bigint, unsigned                      | Not null, foreign key to permissions.id, on delete cascade |
| model_type    | string(255)                           | Not null, polymorphic (e.g., `App\\Models\\User`)          |
| model_id      | bigint, unsigned                      | Not null                                                   |
| unique        | [permission_id, model_type, model_id] | Composite unique constraint                                |

### customers

| Column     | Type             | Constraints/Description                            |
| ---------- | ---------------- | -------------------------------------------------- |
| id         | bigint, unsigned | Auto-incrementing primary key                      |
| name       | string(255)      | Not null                                           |
| phone      | string(20)       | Not null, indexed                                  |
| address    | text             | Nullable                                           |
| created_at | timestamp        | Default current timestamp                          |
| updated_at | timestamp        | Default current timestamp, updates on modification |

### units

| Column            | Type             | Constraints/Description                                            |
| ----------------- | ---------------- | ------------------------------------------------------------------ |
| id                | bigint, unsigned | Auto-incrementing primary key                                      |
| name              | string(50)       | Not null, unique (e.g., pcs, kg, liters, grams)                    |
| parent_unit_id    | bigint, unsigned | Nullable, foreign key to units.id, on delete restrict              |
| conversion_factor | decimal(10,4)    | Nullable, factor to convert to parent (e.g., 1000 for grams to kg) |
| created_at        | timestamp        | Default current timestamp                                          |
| updated_at        | timestamp        | Default current timestamp, updates on modification                 |

### cloth_items

| Column      | Type             | Constraints/Description                               |
| ----------- | ---------------- | ----------------------------------------------------- |
| id          | bigint, unsigned | Auto-incrementing primary key                         |
| name        | string(255)      | Not null, unique (e.g., T-shirt, Shirt, Blanket)      |
| unit_id     | bigint, unsigned | Not null, foreign key to units.id, on delete restrict |
| description | text             | Nullable                                              |
| created_at  | timestamp        | Default current timestamp                             |
| updated_at  | timestamp        | Default current timestamp, updates on modification    |

### services

| Column      | Type             | Constraints/Description                                    |
| ----------- | ---------------- | ---------------------------------------------------------- |
| id          | bigint, unsigned | Auto-incrementing primary key                              |
| name        | string(255)      | Not null, unique (e.g., Dry Washing, Wet Washing, Ironing) |
| description | text             | Nullable                                                   |
| created_at  | timestamp        | Default current timestamp                                  |
| updated_at  | timestamp        | Default current timestamp, updates on modification         |

### urgency_tiers

| Column        | Type             | Constraints/Description                                |
| ------------- | ---------------- | ------------------------------------------------------ |
| id            | bigint, unsigned | Auto-incrementing primary key                          |
| label         | string(255)      | Not null (e.g., "3 Days +50%", "2 Days +75%")          |
| duration_days | integer          | Not null, days for urgent processing (e.g., 2, 3)      |
| multiplier    | decimal(5,2)     | Not null, multiplier for pricing (e.g., 1.50 for +50%) |
| created_at    | timestamp        | Default current timestamp                              |
| updated_at    | timestamp        | Default current timestamp, updates on modification     |

### pricing_tiers

| Column        | Type                        | Constraints/Description                                     |
| ------------- | --------------------------- | ----------------------------------------------------------- |
| id            | bigint, unsigned            | Auto-incrementing primary key                               |
| cloth_item_id | bigint, unsigned            | Not null, foreign key to cloth_items.id, on delete restrict |
| service_id    | bigint, unsigned            | Not null, foreign key to services.id, on delete restrict    |
| price         | decimal(10,2)               | Not null, base price for normal service                     |
| created_at    | timestamp                   | Default current timestamp                                   |
| updated_at    | timestamp                   | Default current timestamp, updates on modification          |
| unique        | [cloth_item_id, service_id] | Composite unique constraint                                 |

### orders

| Column             | Type                                                                                                                               | Constraints/Description                                                 |
| ------------------ | ---------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------- |
| id                 | bigint, unsigned                                                                                                                   | Auto-incrementing primary key                                           |
| order_id           | string(50)                                                                                                                         | Not null, unique, auto-generated ticket number (e.g., ORD-20250804-001) |
| customer_id        | bigint, unsigned                                                                                                                   | Not null, foreign key to customers.id, on delete restrict               |
| created_by         | bigint, unsigned                                                                                                                   | Not null, foreign key to users.id, on delete restrict                   |
| total_cost         | decimal(10,2)                                                                                                                      | Not null, calculated from order items                                   |
| discount           | decimal(10,2)                                                                                                                      | Default 0.00                                                            |
| vat_percentage     | decimal(5,2)                                                                                                                       | Not null, from system_settings                                          |
| appointment_date   | datetime                                                                                                                           | Nullable, scheduled appointment date/time                               |
| pickup_date        | datetime                                                                                                                           | Nullable, scheduled pickup date/time                                    |
| penalty_amount     | decimal(10,2)                                                                                                                      | Default 0.00, for late pickup                                           |
| penalty_daily_rate | decimal(10,2)                                                                                                                      | Nullable, from system_settings                                          |
| status             | enum('received', 'processing', 'washing', 'drying_steaming', 'ironing', 'packaging', 'ready_for_pickup', 'delivered', 'cancelled') | Not null                                                                |
| remarks            | text                                                                                                                               | Nullable, general order notes                                           |
| created_at         | timestamp                                                                                                                          | Default current timestamp                                               |
| updated_at         | timestamp                                                                                                                          | Default current timestamp, updates on modification                      |
| indexes            | [order_id, status, customer_id, created_by]                                                                                        | Indexed columns                                                         |

### order_items

| Column        | Type             | Constraints/Description                                     |
| ------------- | ---------------- | ----------------------------------------------------------- |
| id            | bigint, unsigned | Auto-incrementing primary key                               |
| order_id      | bigint, unsigned | Not null, foreign key to orders.id, on delete cascade       |
| cloth_item_id | bigint, unsigned | Not null, foreign key to cloth_items.id, on delete restrict |
| unit_id       | bigint, unsigned | Not null, foreign key to units.id, on delete restrict       |
| quantity      | decimal(10,2)    | Not null, supports pcs, kg, liters                          |
| remarks       | text             | Nullable (e.g., Color fading, missing buttons)              |
| created_at    | timestamp        | Default current timestamp                                   |
| updated_at    | timestamp        | Default current timestamp, updates on modification          |

### order_item_services

| Column          | Type                                                                            | Constraints/Description                                               |
| --------------- | ------------------------------------------------------------------------------- | --------------------------------------------------------------------- |
| id              | bigint, unsigned                                                                | Auto-incrementing primary key                                         |
| order_item_id   | bigint, unsigned                                                                | Not null, foreign key to order_items.id, on delete cascade            |
| service_id      | bigint, unsigned                                                                | Not null, foreign key to services.id, on delete restrict              |
| employee_id     | bigint, unsigned                                                                | Nullable, foreign key to users.id, on delete set null                 |
| urgency_tier_id | bigint, unsigned                                                                | Nullable, foreign key to urgency_tiers.id, on delete restrict         |
| quantity        | decimal(10,2)                                                                   | Not null, quantity of item for this service (e.g., 4 T-shirts)        |
| price_applied   | decimal(10,2)                                                                   | Not null, snapshot of price at order time (base + urgency multiplier) |
| status          | enum('pending', 'assigned', 'in_progress', 'completed', 'on_hold', 'cancelled') | Not null                                                              |
| created_at      | timestamp                                                                       | Default current timestamp                                             |
| updated_at      | timestamp                                                                       | Default current timestamp, updates on modification                    |
| index           | [order_item_id, service_id, employee_id]                                        | Indexed columns                                                       |

### stores

| Column      | Type                | Constraints/Description                                  |
| ----------- | ------------------- | -------------------------------------------------------- |
| id          | bigint, unsigned    | Auto-incrementing primary key                            |
| name        | string(255)         | Not null, unique (e.g., Main, Washing Room, Drying Room) |
| type        | enum('main', 'sub') | Not null                                                 |
| description | text                | Nullable                                                 |
| created_at  | timestamp           | Default current timestamp                                |
| updated_at  | timestamp           | Default current timestamp, updates on modification       |

### inventory_items

| Column        | Type             | Constraints/Description                               |
| ------------- | ---------------- | ----------------------------------------------------- |
| id            | bigint, unsigned | Auto-incrementing primary key                         |
| name          | string(255)      | Not null, unique (e.g., Detergent, Plastic Wrap)      |
| unit_id       | bigint, unsigned | Not null, foreign key to units.id, on delete restrict |
| minimum_stock | decimal(10,2)    | Not null, for low stock alerts                        |
| created_at    | timestamp        | Default current timestamp                             |
| updated_at    | timestamp        | Default current timestamp, updates on modification    |

### inventory_stock

| Column            | Type                          | Constraints/Description                                         |
| ----------------- | ----------------------------- | --------------------------------------------------------------- |
| id                | bigint, unsigned              | Auto-incrementing primary key                                   |
| inventory_item_id | bigint, unsigned              | Not null, foreign key to inventory_items.id, on delete restrict |
| store_id          | bigint, unsigned              | Not null, foreign key to stores.id, on delete restrict          |
| quantity          | decimal(10,2)                 | Not null                                                        |
| created_at        | timestamp                     | Default current timestamp                                       |
| updated_at        | timestamp                     | Default current timestamp, updates on modification              |
| unique            | [inventory_item_id, store_id] | Composite unique constraint                                     |

### purchases

| Column           | Type             | Constraints/Description                               |
| ---------------- | ---------------- | ----------------------------------------------------- |
| id               | bigint, unsigned | Auto-incrementing primary key                         |
| supplier_name    | string(255)      | Not null                                              |
| supplier_phone   | string(20)       | Nullable                                              |
| supplier_address | text             | Nullable                                              |
| purchase_date    | date             | Not null                                              |
| total_price      | decimal(10,2)    | Not null                                              |
| created_by       | bigint, unsigned | Not null, foreign key to users.id, on delete restrict |
| created_at       | timestamp        | Default current timestamp                             |
| updated_at       | timestamp        | Default current timestamp, updates on modification    |

### purchase_items

| Column            | Type             | Constraints/Description                                         |
| ----------------- | ---------------- | --------------------------------------------------------------- |
| id                | bigint, unsigned | Auto-incrementing primary key                                   |
| purchase_id       | bigint, unsigned | Not null, foreign key to purchases.id, on delete cascade        |
| inventory_item_id | bigint, unsigned | Not null, foreign key to inventory_items.id, on delete restrict |
| unit_id           | bigint, unsigned | Not null, foreign key to units.id, on delete restrict           |
| quantity          | decimal(10,2)    | Not null                                                        |
| unit_price        | decimal(10,2)    | Not null                                                        |
| total_price       | decimal(10,2)    | Not null                                                        |
| to_store_id       | bigint, unsigned | Not null, foreign key to stores.id, on delete restrict          |
| created_at        | timestamp        | Default current timestamp                                       |
| updated_at        | timestamp        | Default current timestamp, updates on modification              |

### stock_transfers

| Column         | Type             | Constraints/Description                                |
| -------------- | ---------------- | ------------------------------------------------------ |
| id             | bigint, unsigned | Auto-incrementing primary key                          |
| from_store_id  | bigint, unsigned | Not null, foreign key to stores.id, on delete restrict |
| to_store_id    | bigint, unsigned | Not null, foreign key to stores.id, on delete restrict |
| transferred_at | datetime         | Not null                                               |
| created_by     | bigint, unsigned | Not null, foreign key to users.id, on delete restrict  |
| created_at     | timestamp        | Default current timestamp                              |
| updated_at     | timestamp        | Default current timestamp, updates on modification     |

### stock_transfer_items

| Column            | Type             | Constraints/Description                                         |
| ----------------- | ---------------- | --------------------------------------------------------------- |
| id                | bigint, unsigned | Auto-incrementing primary key                                   |
| stock_transfer_id | bigint, unsigned | Not null, foreign key to stock_transfers.id, on delete cascade  |
| inventory_item_id | bigint, unsigned | Not null, foreign key to inventory_items.id, on delete restrict |
| unit_id           | bigint, unsigned | Not null, foreign key to units.id, on delete restrict           |
| quantity          | decimal(10,2)    | Not null                                                        |
| created_at        | timestamp        | Default current timestamp                                       |
| updated_at        | timestamp        | Default current timestamp, updates on modification              |

### stock_usage

| Column            | Type                                                       | Constraints/Description                                         |
| ----------------- | ---------------------------------------------------------- | --------------------------------------------------------------- |
| id                | bigint, unsigned                                           | Auto-incrementing primary key                                   |
| inventory_item_id | bigint, unsigned                                           | Not null, foreign key to inventory_items.id, on delete restrict |
| store_id          | bigint, unsigned                                           | Not null, foreign key to stores.id, on delete restrict          |
| unit_id           | bigint, unsigned                                           | Not null, foreign key to units.id, on delete restrict           |
| quantity_used     | decimal(10,2)                                              | Not null                                                        |
| operation_type    | enum('washing', 'drying', 'ironing', 'packaging', 'other') | Not null                                                        |
| usage_date        | datetime                                                   | Not null                                                        |
| created_by        | bigint, unsigned                                           | Not null, foreign key to users.id, on delete restrict           |
| created_at        | timestamp                                                  | Default current timestamp                                       |
| updated_at        | timestamp                                                  | Default current timestamp, updates on modification              |

### payments

| Column      | Type                                      | Constraints/Description                                                    |
| ----------- | ----------------------------------------- | -------------------------------------------------------------------------- |
| id          | bigint, unsigned                           | Auto-incrementing primary key                                              |
| order_id    | bigint, unsigned                           | Not null, foreign key to orders.id, on delete cascade                      |
| amount      | decimal(10,2)                              | Not null                                                                   |
| method      | enum('cash','card','mobile','bank','other')| Not null                                                                   |
| reference   | string(255)                                | Nullable                                                                   |
| status      | enum('pending','completed','refunded','void') | Not null, default 'completed'                                           |
| paid_at     | datetime                                   | Nullable                                                                   |
| received_by | bigint, unsigned                           | Nullable, foreign key to users.id, on delete set null                      |
| created_at  | timestamp                                  | Default current timestamp                                                  |
| updated_at  | timestamp                                  | Default current timestamp, updates on modification                         |

### penalty_tiers

| Column        | Type             | Constraints/Description                                                 |
| ------------- | ---------------- | ----------------------------------------------------------------------- |
| id            | bigint, unsigned | Auto-incrementing primary key                                           |
| cloth_item_id | bigint, unsigned | Not null, foreign key to cloth_items.id, on delete restrict             |
| daily_rate    | decimal(10,2)    | Not null, penalty per day for this cloth item                           |
| grace_days    | integer          | Default 0, days after pickup_date before penalty starts                 |
| active        | boolean          | Default true                                                            |
| created_at    | timestamp        | Default current timestamp                                               |
| updated_at    | timestamp        | Default current timestamp, updates on modification                      |
| index         | [cloth_item_id]  | Indexed columns                                                         |

### order_item_penalties

| Column               | Type             | Constraints/Description                                                                 |
| -------------------- | ---------------- | --------------------------------------------------------------------------------------- |
| id                   | bigint, unsigned | Auto-incrementing primary key                                                           |
| order_item_id        | bigint, unsigned | Not null, foreign key to order_items.id, on delete cascade                              |
| days_late            | integer          | Not null, computed as max(0, delivered_at - pickup_date - grace_days)                  |
| rate_applied         | decimal(10,2)    | Not null, snapshot from penalty_tiers.daily_rate or custom                              |
| amount               | decimal(10,2)    | Not null, = days_late * rate_applied * quantity (unit-aware as needed)                 |
| waiver_status        | enum('none','pending','approved','rejected') | Default 'none'                                                      |
| waiver_requested_by  | bigint, unsigned | Nullable, foreign key to users.id (receptionist), on delete set null                    |
| waiver_request_note  | text             | Nullable                                                                                |
| waiver_decided_by    | bigint, unsigned | Nullable, foreign key to users.id (admin), on delete set null                           |
| waiver_decided_at    | datetime         | Nullable                                                                                |
| created_at           | timestamp        | Default current timestamp                                                               |
| updated_at           | timestamp        | Default current timestamp, updates on modification                                      |

### notifications

| Column     | Type                                                 | Constraints/Description                               |
| ---------- | ---------------------------------------------------- | ----------------------------------------------------- |
| id         | bigint, unsigned                                     | Auto-incrementing primary key                         |
| user_id    | bigint, unsigned                                     | Nullable, foreign key to users.id, on delete set null |
| type       | enum('low_stock', 'order_status', 'pickup_reminder', 'assignment', 'service_status') | Not null             |
| message    | text                                                 | Not null                                              |
| is_read    | boolean                                              | Default false                                         |
| created_at | timestamp                                            | Default current timestamp                             |
| updated_at | timestamp                                            | Default current timestamp, updates on modification    |
| index      | [user_id, type]                                      | Indexed columns                                       |

### system_settings

| Column     | Type             | Constraints/Description                                     |
| ---------- | ---------------- | ----------------------------------------------------------- |
| id         | bigint, unsigned | Auto-incrementing primary key                               |
| key        | string(255)      | Not null, unique (e.g., vat_percentage, penalty_daily_rate) |
| value      | text             | Not null                                                    |
| created_at | timestamp        | Default current timestamp                                   |
| updated_at | timestamp        | Default current timestamp, updates on modification          |

### activity_logs

| Column       | Type             | Constraints/Description                                |
| ------------ | ---------------- | ------------------------------------------------------ |
| id           | bigint, unsigned | Auto-incrementing primary key                          |
| user_id      | bigint, unsigned | Not null, foreign key to users.id, on delete restrict  |
| action       | string(255)      | Not null (e.g., created_order, updated_service_status) |
| subject_type | string(255)      | Nullable, polymorphic (e.g., `App\\Models\\Order`)     |
| subject_id   | bigint, unsigned | Nullable                                               |
| changes      | json             | Nullable, store changes for auditing                   |
| created_at   | timestamp        | Default current timestamp                              |

---

## Explanation of Changes

1. **Order Item Services with Quantity**:

    - Added `quantity` to `order_item_services` to handle cases where a specific service applies to a subset of the order item's quantity (e.g., 4 out of 10 T-shirts for wet washing). This ensures flexibility when different services apply to different quantities within the same order item.

2. **Urgency as a Separate Table**:

    - Created `urgency_tiers` to define urgency levels (e.g., 3 days +50%, 2 days +75%) with `label`, `duration_days`, and `multiplier`. This replaces the `is_urgent` boolean and `pricing_tier_id` in `order_item_services`.
    - Linked `urgency_tier_id` to `order_item_services` to allow per-service urgency settings, enabling scenarios where only specific items/services are urgent.

3. **Employee Assignment**:

    - Added `employee_id` to `order_item_services` to assign specific employees to each service (e.g., washing by Employee A, ironing by Employee B). This supports tracking and accountability for service execution.

4. **Unit Parent-Child Relationship**:

    - Added `parent_unit_id` and `conversion_factor` to `units` to support hierarchical units (e.g., kilogram as parent to gram with a conversion factor of 1000). This allows dynamic unit conversions for cloth items and inventory items.

5. **Track Order Creator**:

    - Added `created_by` to `orders`, `purchases`, `stock_transfers`, and `stock_usage` to track the user responsible for creating each record, enhancing auditability.

6. **Order and Service Statuses**:

    - Expanded `orders.status` to include: `received`, `processing`, `washing`, `drying_steaming`, `ironing`, `packaging`, `ready_for_pickup`, `delivered`, `cancelled`.
    - Expanded `order_item_services.status` to include: `pending`, `assigned`, `in_progress`, `completed`, `on_hold`, `cancelled`. This provides granular tracking for each service within an order.

7. **Robustness Enhancements**:
    - Ensured all tables have appropriate indexes for performance (e.g., `order_id`, `status`, `customer_id` in `orders`).
    - Used `decimal(10,2)` for quantities and prices to support precise measurements (e.g., kg, liters).
    - Maintained strict foreign key constraints with `on delete restrict` or `cascade` to ensure data integrity.
    - Added `created_by` to key tables for better auditing and traceability.

8. **Inventory Transaction Integrity**:
    - Added `to_store_id` to `purchase_items` to record the destination store of received stock per line item, aligning with multi-store operations.
    - Reaffirmed that stock changes must only occur via `purchase_items`, `stock_transfer_items`, and `stock_usage` rows within transactions. Consider DB-level CHECK constraints to prevent negative stock.

9. **Payments**:
    - Introduced a `payments` table to record order payments, enabling notifications on payment events and robust financial reporting.

10. **Penalties (Late Pickup)**:
    - Introduced `penalty_tiers` (per cloth item daily rate and grace days) and `order_item_penalties` (per-order-item computed penalties with waiver workflow fields). This supports optional penalties, receptionist remark/waiver request, and admin approval to waive.

11. **Operator Acceptance Metadata**:
    - For acceptance/rejection semantics, store them via `order_item_services.status` transitions (assigned -> in_progress as acceptance). For richer auditing, add fields (via migration) such as `acknowledged_at`, `rejected_at`, and `reject_reason` on `order_item_services`.

This schema provides a robust foundation for the Shebar Laundry Management System, supporting all required functionalities while ensuring flexibility, scalability, and maintainability.
