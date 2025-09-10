<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sheger Automatic Laundry Management System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration settings for the Sheger Automatic Laundry
    | Management System including VAT, penalties, and other business rules.
    |
    */

    // Tax and Pricing Configuration
    'vat_percentage' => env('SHEBAR_VAT_PERCENTAGE', 15.00),
    'penalty_daily_rate' => env('SHEBAR_PENALTY_DAILY_RATE', 10.00),
    
    // Order Configuration
    'default_order_status' => 'received',
    'auto_generate_order_id' => true,
    'order_id_prefix' => 'ORD',
    'order_id_format' => 'Ymd',
    'order_id_suffix_length' => 3,
    'vip_order_id_prefix' => 'VIP',

    // Customer Configuration
    'auto_generate_customer_code' => env('SHEBAR_AUTO_GENERATE_CUSTOMER_CODE', true),
    
    // Inventory Configuration
    'low_stock_threshold' => env('SHEBAR_LOW_STOCK_THRESHOLD', 10),
    'enable_stock_alerts' => env('SHEBAR_ENABLE_STOCK_ALERTS', true),
    
    // Notification Configuration
    'enable_notifications' => env('SHEBAR_ENABLE_NOTIFICATIONS', true),
    'pickup_reminder_days' => env('SHEBAR_PICKUP_REMINDER_DAYS', 1),
    'pickup_reminder_time' => env('SHEBAR_PICKUP_REMINDER_TIME', '09:00'),
    
    // File Upload Configuration
    'max_file_size' => env('SHEBAR_MAX_FILE_SIZE', 2048), // KB
    'allowed_file_types' => ['jpg', 'jpeg', 'png', 'pdf'],
    
    // Pagination Configuration
    'default_pagination' => env('SHEBAR_DEFAULT_PAGINATION', 15),
    
    // Business Hours (for appointment scheduling)
    'business_hours' => [
        'monday' => ['09:00', '18:00'],
        'tuesday' => ['09:00', '18:00'],
        'wednesday' => ['09:00', '18:00'],
        'thursday' => ['09:00', '18:00'],
        'friday' => ['09:00', '18:00'],
        'saturday' => ['09:00', '16:00'],
        'sunday' => ['closed'],
    ],
    
    // Order Status Workflow
    'order_status_workflow' => [
        'received' => ['processing'],
        'processing' => ['washing'],
        'washing' => ['drying_steaming'],
        'drying_steaming' => ['ironing'],
        'ironing' => ['packaging'],
        'packaging' => ['ready_for_pickup'],
        'ready_for_pickup' => ['delivered'],
        'delivered' => [],
        'cancelled' => [],
    ],
    
    // Service Status Workflow
    'service_status_workflow' => [
        // Allow operators to start work directly from pending without forcing full allocation first
        'pending' => ['assigned', 'in_progress'],
        'assigned' => ['in_progress'],
        'in_progress' => ['completed', 'on_hold'],
        'on_hold' => ['in_progress', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ],
]; 