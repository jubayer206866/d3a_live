<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Calculators extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Calculators_model'); 
    }
    public function materials()
    {
        $data['title'] = 'Materials';
        $this->load->view('admin/calculators/manage', $data);
    }

    public function categories()
    {
        $data['title'] = 'Categories';
        $this->load->view('admin/calculators/categories', $data);
    }

    public function ladder_rates()
    {
        $data['title'] = _l('ladder_rates');
        $this->load->view('admin/calculators/ladder_rates', $data);
    }
    public function get_materials()
    {
        $this->app->get_table_data('materials'); 
    }
   public function save_material()
    {
        $data = $this->input->post();

        $this->Calculators_model->add_material($data);

        if (!empty($data['id'])) {
            set_alert('success', _l('material_updated_successfully'));
        } else {
            set_alert('success', _l('material_added_successfully'));
        }

        redirect(admin_url('calculators/materials'));
    }


   public function get_categories()
   {
    $this->app->get_table_data('categories_table');
   }

   public function save_category()
   {
    $data = $this->input->post();
    $this->Calculators_model->add_category($data);
    if (!empty($data['id'])) {
        set_alert('success', _l('category_updated_successfully'));
    } else {
        set_alert('success', _l('category_added_successfully'));
    }
    redirect(admin_url('calculators/categories'));
   }
public function delete_material($id)
{
    $this->db->where('id', $id);
    $this->db->delete(db_prefix() . 'materials');
    set_alert('success', _l('material_deleted_successfully'));
    redirect(admin_url('calculators/materials'));
}
public function delete_category($id)
{
    $this->db->where('id', $id);
    $this->db->delete(db_prefix() . 'categories');
    set_alert('success', _l('category_deleted_successfully'));
    redirect(admin_url('calculators/categories'));
}
public function get_category($id)
{
    $category = $this->db
        ->where('id', $id)
        ->get(db_prefix().'categories')
        ->row();

    echo json_encode($category);
}
public function get_material($id)
{
    $material = $this->db
        ->where('id', $id)
        ->get(db_prefix() . 'materials')
        ->row();

    echo json_encode($material);
}

    public function get_ladder_rates()
    {
        $this->app->get_table_data('ladder_rates_table');
    }

    public function save_ladder_rate()
    {
        $data = $this->input->post();
        $this->Calculators_model->add_ladder_rate($data);
        if (!empty($data['id'])) {
            set_alert('success', _l('ladder_rate_updated_successfully'));
        } else {
            set_alert('success', _l('ladder_rate_added_successfully'));
        }
        redirect(admin_url('calculators/ladder_rates'));
    }

    public function delete_ladder_rate($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'ladder_rates');
        set_alert('success', _l('ladder_rate_deleted_successfully'));
        redirect(admin_url('calculators/ladder_rates'));
    }

    public function get_ladder_rate($id)
    {
        $row = $this->db
            ->where('id', $id)
            ->get(db_prefix() . 'ladder_rates')
            ->row();
        echo json_encode($row);
    }
}