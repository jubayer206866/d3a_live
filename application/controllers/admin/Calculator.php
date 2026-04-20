<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Calculator extends AdminController
{
    /**
     * Codeigniter Instance
     * Expenses detailed report filters use $ci
     * @var object
     */
    private $ci;

    /** DDP calculator option names and default values (SETTINGS sheet) */
    private static $dpp_option_defaults = [
        'dpp_paymode_b_surcharge'              => '1.02',
        'dpp_discount_value_density_threshold' => '1000',
        'dpp_discount_fee_per_cbm_threshold'   => '1000',
        'dpp_discount_rate'                    => '0.05',
        'dpp_kg_per_cbm'                       => '500',
        'dpp_others_tiering_threshold'          => '800',
        'dpp_floor_percent_product'            => '45',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase/purchase_model');
    }

    /* No access on this url */
    public function index()
    {
        redirect(admin_url());
    }

    /** LCL calculator option names and default values (matches LCL_Calculator_Final_RoundTotalTo10) */
    private static $lcl_option_defaults = [
        'lcl_kg_per_cbm'               => '500',
        'lcl_crate_fee_per_unit'       => '50',
        'lcl_fixed_service_fee'         => '300',
        'lcl_fixed_operational_cost'   => '6000',
        'lcl_container_capacity'       => '68',
    ];

    private function ensure_lcl_options()
    {
        foreach (self::$lcl_option_defaults as $name => $value) {
            if (!option_exists($name)) {
                add_option($name, $value);
            }
        }
    }

    private function get_lcl_setting($name)
    {
        $default = isset(self::$lcl_option_defaults[$name]) ? self::$lcl_option_defaults[$name] : '';
        return get_option($name) !== false && get_option($name) !== '' ? get_option($name) : $default;
    }

    public function lcl()
    {
        $this->ensure_lcl_options();
        $data['title'] = _l('lcl_pricing_calculator');
        $data['settings'] = [
            'kg_per_cbm'                 => (float) $this->get_lcl_setting('lcl_kg_per_cbm'),
            'crate_fee_per_unit'         => (float) $this->get_lcl_setting('lcl_crate_fee_per_unit'),
            'fixed_service_fee'          => (float) $this->get_lcl_setting('lcl_fixed_service_fee'),
            'fixed_operational_cost'     => (float) $this->get_lcl_setting('lcl_fixed_operational_cost'),
            'container_capacity'         => (float) $this->get_lcl_setting('lcl_container_capacity'),
        ];
        $this->load->view('admin/calculator/lcl_calculator', $data);
    }

    public function cbm()
    {
        $this->lcl();
    }

    /**
     * Ensure DDP settings options exist with defaults
     */
    private function ensure_dpp_options()
    {
        foreach (self::$dpp_option_defaults as $name => $value) {
            if (!option_exists($name)) {
                add_option($name, $value);
            }
        }
    }

    /**
     * Get DDP setting value with default
     */
    private function get_dpp_setting($name)
    {
        $default = isset(self::$dpp_option_defaults[$name]) ? self::$dpp_option_defaults[$name] : '';
        return get_option($name) !== false && get_option($name) !== '' ? get_option($name) : $default;
    }

    public function dpp()
    {
        $this->ensure_dpp_options();

        $data['title'] = _l('calculator_dpp');
        $data['settings'] = [
            'paymode_b_surcharge'              => (float) $this->get_dpp_setting('dpp_paymode_b_surcharge'),
            'discount_value_density_threshold' => (float) $this->get_dpp_setting('dpp_discount_value_density_threshold'),
            'discount_fee_per_cbm_threshold'   => (float) $this->get_dpp_setting('dpp_discount_fee_per_cbm_threshold'),
            'discount_rate'                    => (float) $this->get_dpp_setting('dpp_discount_rate'),
            'kg_per_cbm'                       => (float) $this->get_dpp_setting('dpp_kg_per_cbm'),
            'others_tiering_threshold'         => (float) $this->get_dpp_setting('dpp_others_tiering_threshold'),
            'floor_percent_product'            => (float) $this->get_dpp_setting('dpp_floor_percent_product'),
        ];

        $this->db->select('id, category, floor_percent_of_pv, floor_euro_per_cbm, customs_duty, vat_integre_percent');
        $this->db->from(db_prefix() . 'categories');
        $rows = $this->db->get()->result_array();
        // Normalize null/empty customs_duty and vat_integre_percent so DDP calculator always gets a number
        foreach ($rows as &$row) {
            $row['customs_duty'] = $row['customs_duty'] !== null && $row['customs_duty'] !== '' ? (float) $row['customs_duty'] : 0;
            $row['vat_integre_percent'] = $row['vat_integre_percent'] !== null && $row['vat_integre_percent'] !== '' ? (float) $row['vat_integre_percent'] : 0;
        }
        $data['categories'] = $rows;

        $this->db->select('id, material, excise_type, excise_value');
        $this->db->from(db_prefix() . 'materials');
        $data['materials'] = $this->db->get()->result_array();

        $this->db->select('id, min_cbm, max_cbm, rate');
        $this->db->from(db_prefix() . 'ladder_rates');
        $this->db->order_by('min_cbm', 'asc');
        $data['ladder_rates'] = $this->db->get()->result_array();

        $this->load->view('admin/calculator/dpp_calculator', $data);
    }

    public function ddpo()
    {
        $this->load->view('admin/calculator/dpp_calculator');
    }
}
