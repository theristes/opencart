<?php
namespace Opencart\Catalog\Controller\Extension\Opencart\Payment;

class OnSpot extends \Opencart\System\Engine\Controller {
    public function index(array $settings = []): string {
        $this->load->language('extension/opencart/payment/on_spot');
        return $this->load->view('extension/opencart/payment/on_spot');
    }

    public function confirm(array $route = []): void {
        if ($this->session->data['payment_method']['code'] === 'on_spot') {
            $this->load->language('extension/opencart/payment/on_spot');
            $this->load->model('checkout/order');

            $this->model_checkout_order->addHistory(
                $this->session->data['order_id'],
                $this->config->get('payment_on_spot_order_status_id'),
                $this->language->get('text_description'),
                true
            );
        }
    }
}
