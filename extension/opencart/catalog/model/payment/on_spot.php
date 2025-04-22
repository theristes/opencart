<?php
namespace Opencart\Catalog\Model\Payment;

class OnSpot extends \Opencart\System\Engine\Model {
    public function getMethods(array $address = []): array {
        return [
            'code'       => 'on_spot',
            'title'      => $this->language->get('text_title'),
            'terms'      => '',
            'sort_order' => (int)$this->config->get('payment_on_spot_sort_order'),
        ];
    }
}
