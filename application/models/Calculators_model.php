<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Calculators_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function add_material($data)
{
    $insertData = [
        'material'       => $data['Material'],
        'excise_type'    => $data['Excise_type'],
        'excise_value'   => $data['Excise_value'],
        'vat_on_excise'  => $data['VAT_on_Excise'],
    ];

    if (!empty($data['id'])) {
        $this->db->where('id', $data['id']);
        $this->db->update(db_prefix() . 'materials', $insertData);
    } else {
        $this->db->insert(db_prefix() . 'materials', $insertData);
    }
}

    public function add_category($data)
  {
    $insertData = [
        'category'              => $data['Category'],
        'use_category_floor'    => $data['Use_Category_Floor'],
        'floor_percent_of_pv'   => $data['Floor_percent_of_pv'],
        'floor_euro_per_cbm'    => $data['Floor_euro_per_CBM'],
        'customs_duty'          => isset($data['Customs_duty']) ? $data['Customs_duty'] : null,
        'vat_integre_percent'   => isset($data['VAT_integre_percent']) ? $data['VAT_integre_percent'] : null,
        'excise_type'           => $data['Excise_type'],
        'excise_value'          => $data['Excise_value'],
    ];

    if (!empty($data['id'])) {
        $this->db->where('id', $data['id']);
        $this->db->update(db_prefix() . 'categories', $insertData);
    } else {
        $this->db->insert(db_prefix() . 'categories', $insertData);
    }
  }

    public function add_ladder_rate($data)
    {
        $insertData = [
            'min_cbm' => isset($data['Min_cbm']) ? $data['Min_cbm'] : 0,
            'max_cbm' => isset($data['Max_cbm']) ? $data['Max_cbm'] : 0,
            'rate'    => isset($data['Rate']) ? $data['Rate'] : 0,
        ];

        if (!empty($data['id'])) {
            $this->db->where('id', $data['id']);
            $this->db->update(db_prefix() . 'ladder_rates', $insertData);
        } else {
            $this->db->insert(db_prefix() . 'ladder_rates', $insertData);
        }
    }
}


