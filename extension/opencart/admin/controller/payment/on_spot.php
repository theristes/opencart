<?php
namespace Opencart\Admin\Controller\Extension\Opencart\Payment;

class OnSpot extends \Opencart\System\Engine\Controller {
    public function index(): string {
        $this->load->language('payment/on_spot');
        return $this->load->view('payment/on_spot');  // This should match the path and filename
    }
    
}
