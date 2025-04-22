<?php
namespace Opencart\Catalog\Controller\Payment;

class OnSpot extends \Opencart\System\Engine\Controller {
    public function index(): string {
        $this->load->language('payment/on_spot');
        return $this->load->view('payment/on_spot');
    }

    public function confirm(): void {
        if ($this->session->data['payment_method']['code'] === 'on_spot') {
            $this->load->model('checkout/order');
            $this->model_checkout_order->addHistory(
                $this->session->data['order_id'],
                1,
                'Customer will pay on spot.'
            );
        }
    }
}
