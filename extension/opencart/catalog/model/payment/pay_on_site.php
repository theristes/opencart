<?php
namespace Opencart\Catalog\Model\Extension\Opencart\Payment;

class PayOnSite extends \Opencart\System\Engine\Model {
    public function getMethods($address, $total) {
        $this->load->language('extension/opencart/payment/pay_on_site');

        $method_data = [];

        if ($this->config->get('payment_pay_on_site_status')) {
            $method_data = [
                'code'       => 'pay_on_site',
                'title'      => $this->language->get('text_title'),
                'terms'      => '',
                'sort_order' => $this->config->get('payment_pay_on_site_sort_order')
            ];
        }

        return $method_data;
    }
}