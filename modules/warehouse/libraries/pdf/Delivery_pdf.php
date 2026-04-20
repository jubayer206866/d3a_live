<?php
defined('BASEPATH') or exit('No direct script access allowed');
include_once APPPATH . 'libraries/pdf/App_pdf.php';

class Delivery_pdf extends App_pdf {
    protected $delivery;

    public function __construct($delivery) {
        $delivery = hooks()->apply_filters('request_html_pdf_data', $delivery);
        $GLOBALS['delivery_pdf'] = $delivery;

        parent::__construct();

        $this->delivery = mb_convert_encoding($delivery, 'UTF-8', 'auto');
        $this->delivery = $this->fix_editor_html($this->delivery);

        $this->setFontSubsetting(true);
        $this->SetFont('stsongstdlight', '', 12, '', true);

        $this->SetTitle('Delivery');
    }

    public function prepare() {
        $this->set_view_vars('delivery', $this->delivery);
        return $this->build();
    }

    protected function type() {
        return 'delivery';
    }

    protected function file_path() {
        $customPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/my_requestpdf.php';
        $actualPath = APP_MODULES_PATH . '/warehouse/views/manage_goods_delivery/deliverypdf.php';
        if (file_exists($customPath)) {
            $actualPath = $customPath;
        }
        return $actualPath;
    }
}