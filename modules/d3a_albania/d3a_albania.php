<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: D3A Albania
Description: D3A Albania module for managing invoices, payments, expenses, PO invoices, and PO payments
Version: 1.0.0
Requires at least: 2.3.*
*/

define('D3A_ALBANIA_MODULE_NAME', 'd3a_albania');

$CI = &get_instance();

hooks()->add_action('admin_init', 'd3a_albania_init_menu_items');
hooks()->add_action('admin_init', 'd3a_albania_register_staff_permissions');
hooks()->add_action('admin_init', 'd3a_albania_migrate_invoice_format', 5);
hooks()->add_filter('get_upload_path_by_type', 'd3a_albania_upload_path_by_type', 10, 2);
hooks()->add_filter('items_table_class', 'd3a_albania_items_table_class', 10, 5);
hooks()->add_filter('sales_item_amount', 'd3a_albania_sales_item_amount', 10, 3);

/**
 * One-time migration: update ALB invoice format from D3A-ALB to ALB-INV- with 7-digit padding
 */
function d3a_albania_migrate_invoice_format()
{
    if (get_option('alb_inv_format_migrated') == 1) {
        return;
    }
    $CI = &get_instance();
    $CI->db->where('option_name', 'alb_inv_prefix');
    $row = $CI->db->get(db_prefix() . 'purchase_option')->row();
    if ($row && $row->option_val == 'D3A-ALB') {
        $CI->db->where('option_name', 'alb_inv_prefix');
        $CI->db->update(db_prefix() . 'purchase_option', ['option_val' => 'ALB-INV-']);
    }
    $CI->db->where('option_name', 'alb_number_padding');
    $row = $CI->db->get(db_prefix() . 'purchase_option')->row();
    if ($row && $row->option_val == '6') {
        $CI->db->where('option_name', 'alb_number_padding');
        $CI->db->update(db_prefix() . 'purchase_option', ['option_val' => '7']);
    }
    add_option('alb_inv_format_migrated', 1);
}

/**
 * Register activation module hook
 */
register_activation_hook(D3A_ALBANIA_MODULE_NAME, 'd3a_albania_activation_hook');

function d3a_albania_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
 * Register language files, must be registered if the module is using languages
 */
register_language_files(D3A_ALBANIA_MODULE_NAME, [D3A_ALBANIA_MODULE_NAME]);

/**
 * Staff permissions (Roles / Staff member) — filter staff_permissions via register_staff_capabilities.
 * Enforce in controllers with staff_can('capability_key', 'd3a_albania'); admins bypass staff_can().
 *
 * Order matches the array order below (shown in Setup → Staff / Roles).
 */
function d3a_albania_register_staff_permissions()
{
    register_staff_capabilities('d3a_albania', [
        'capabilities' => [
            'view_al_invoice'              => 'View AL invoice',
            'preview_al_invoice'           => 'Preview AL invoice',
            'create_al_invoice'            => 'Create AL invoice',
            'edit_al_invoice'              => 'Edit AL invoice',
            'delete_al_invoice'            => 'Delete AL invoice',
            'add_al_payment'               => 'Add payment',
            'view_al_payment'              => 'View payment',
            'delete_al_payment'            => 'Delete payment',
            'view_al_expense'              => 'View expense',
            'create_al_expense'            => 'Create expense',
            'edit_al_expense'              => 'Edit expense',
            'delete_al_expense'            => 'Delete expense',
            'view_al_purchase_invoice'     => 'View AL purchase invoice',
            'add_al_purchase_invoice'      => 'Add AL purchase invoice',
            'edit_al_purchase_invoice'     => 'Edit AL purchase invoice',
            'delete_al_purchase_invoice'   => 'Delete AL purchase invoice',
            'add_al_purchase_payment'      => 'Add AL purchase invoice payment',
            'view_al_purchase_payment'     => 'View AL purchase invoice payment',
            'delete_al_purchase_payment'   => 'Delete AL purchase invoice payment',
            'view_al_settings'             => 'View settings',
        ],
    ], 'D3A Albania');
}

/**
 * Init D3A Albania module menu items in admin_init hook
 * Sidebar shows if the user is admin or has any section "View …" capability for a child item.
 *
 * @return null
 */
function d3a_albania_init_menu_items()
{
    $CI       = &get_instance();
    $is_admin = is_admin();

    $can = static function ($capability) use ($is_admin) {
        return $is_admin || staff_can($capability, 'd3a_albania');
    };

    $children = [];

    if ($can('view_al_invoice')) {
        $children[] = [
            'slug'     => 'alb-invoice',
            'name'     => 'AL Invoice',
            'href'     => admin_url('d3a_albania/invoices'),
            'position' => 1,
        ];
    }
    if ($can('view_al_payment')) {
        $children[] = [
            'slug'     => 'alb-payments',
            'name'     => 'AL Payments',
            'href'     => admin_url('d3a_albania/payments'),
            'position' => 2,
        ];
    }
    if ($can('view_al_expense')) {
        $children[] = [
            'slug'     => 'alb-expenses',
            'name'     => 'AL Expenses',
            'href'     => admin_url('d3a_albania/expenses'),
            'position' => 3,
        ];
    }
    if ($can('view_al_purchase_invoice')) {
        $children[] = [
            'slug'     => 'alb-po-invoice',
            'name'     => 'AL Purchase Invoice',
            'href'     => admin_url('d3a_albania/po_invoices'),
            'position' => 4,
        ];
    }
    if ($can('view_al_purchase_payment')) {
        $children[] = [
            'slug'     => 'alb-po-payments',
            'name'     => 'AL Purchase Payments',
            'href'     => admin_url('d3a_albania/po_payments'),
            'position' => 5,
        ];
    }
    if ($can('view_al_settings')) {
        $children[] = [
            'slug'     => 'alb-company-information',
            'name'     => 'Company Information',
            'href'     => admin_url('d3a_albania/company_information'),
            'position' => 6,
        ];
    }

    if (! $is_admin && count($children) === 0) {
        return;
    }

    $CI->app_menu->add_sidebar_menu_item('d3a-albania', [
        'name'     => 'D3A Albania',
        'icon'     => 'fa fa-flag',
        'position' => 35,
        'collapse' => true,
    ]);

    foreach ($children as $child) {
        $CI->app_menu->add_sidebar_children_item('d3a-albania', $child);
    }
}

function d3a_albania_items_table_class($class, $transaction, $type, $for, $admin_preview)
{
    if (strtolower($type) === 'alb_invoice') {
        require_once __DIR__ . '/libraries/Alb_items_table.php';
        return new Alb_items_table($transaction, $type, $for, $admin_preview);
    }
    return $class;
}

function d3a_albania_upload_path_by_type($path, $type)
{
    if ($type == 'alb_invoices') {
        return FCPATH . 'uploads/alb_invoices/';
    }
    if ($type == 'alb_company') {
        return FCPATH . 'uploads/alb_company/';
    }
    return $path;
}

/**
 * For ALB invoices: amount = rate (no qty multiplication)
 */
function d3a_albania_sales_item_amount($amount, $item, $type)
{
    if ($type === 'alb_invoices' || $type === 'alb_invoice') {
        return (float) ($item['rate'] ?? 0);
    }
    return $amount;
}

