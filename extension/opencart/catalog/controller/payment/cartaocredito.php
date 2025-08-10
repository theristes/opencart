<?php
namespace Opencart\Catalog\Controller\Extension\Opencart\Payment;

/**
 * Class cartaocredito Uplodaded
 *
 * @package Opencart\Catalog\Controller\Extension\Opencart\Payment
 */
class cartaocredito extends \Opencart\System\Engine\Controller {
    /**
     * Payment index
     *
     * @return string
     */
    public function index(): string {
        $data['payable'] = $this->config->get('payment_cartaocredito_payable');
        return $this->load->view('extension/opencart/payment/cartaocredito', $data);
    }

    /**
     * Confirm payment
     *
     * @return void
     */
    public function confirm(): void {
        
        $json = [];

        if (isset($this->session->data['order_id'])) {
            $this->load->model('checkout/order');

            $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

            if (!$order_info) {
                $json['redirect'] = $this->url->link('checkout/failure', 'language=' . $this->config->get('config_language'), true);
                unset($this->session->data['order_id']);
            }
        }

        // Payment method check
        if (!isset($this->session->data['payment_method']) || $this->session->data['payment_method']['code'] !== 'cartaocredito.cartaocredito') {
            $json['error'] = "Invalid payment method selected.";
        }

        // If no errors
        if (empty($json)) {
            $this->load->model('checkout/order');

            $payable = $this->config->get('payment_cartaocredito_payable') ?? '';
            $address = $this->config->get('config_address') ?? '';

            $comment = $payable . "\n\n" . $address . "\n\n";

            $this->model_checkout_order->addHistory(
                $this->session->data['order_id'],
                $this->config->get('payment_cartaocredito_order_status_id'),
                $comment,
                true
            );

            $items = [];
            foreach ($this->cart->getProducts() as $product) {

                $items[] = [
                    'name' => mb_strimwidth($product['name'], 0, 30, '...'),
                    'description' => mb_strimwidth($product['model'], 0, 150, '...'),
                    'quantity' => (int) $product['quantity'],
                    'value' => (float) $product['price']
                ];
            }

            $payload = [
                'billingTypes' => ['CREDIT_CARD'],
                'chargeTypes' => ['DETACHED'],
                'minutesToExpire' => 60,
                'callback' => [
                    'cancelUrl' => $this->url->link('checkout/failure'),
                    'expiredUrl' => $this->url->link('checkout/failure'),
                    'successUrl' => $this->url->link('checkout/success', 'orderId=' . $order_info['order_id'], 'language=' . $this->config->get('config_language'), true)
                ],
                'items' => $items
            ];

            $token = $this->config->get('config_asaas_token');
            $asas_url = $this->config->get('config_asaas_url');
            
            $ch = curl_init("$asas_url/checkouts");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "User-Agent: fmxshop/1.0",
                "access_token: $token"
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        
            $result = curl_exec($ch);
            curl_close($ch);
        
            $response = json_decode($result, true);
	        
			if (!empty($response['link'])) {
			    $json['redirect'] = $response['link'];
        	} else {
                if (isset($response['errors']) && is_array($response['errors']) && count($response['errors']) > 0) {
            	    $json['error'] = $response['errors'][0]['description'];
                }
        	}
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}