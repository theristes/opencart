<?php
namespace Opencart\Catalog\Controller\Extension\Opencart\Payment;

/**
 * Class pix
 *
 * @package Opencart\Catalog\Controller\Extension\Opencart\Payment
 */
class pix extends \Opencart\System\Engine\Controller {
    /**
     * Payment index
     *
     * @return string
     */
    public function index(): string {
        $data['payable'] = $this->config->get('payment_pix_payable');
        return $this->load->view('extension/opencart/payment/pix', $data);
    }

    /**
     * Confirm payment
     *
     * @return void
     */
    public function confirm(): void {
        $json = [];
    
        if (!isset($this->session->data['order_id'])) {
            $json['redirect'] = $this->url->link('checkout/failure', 'language=' . $this->config->get('config_language'), true);
        }
    
        // Payment method check
        if (!isset($this->session->data['payment_method']) || $this->session->data['payment_method']['code'] !== 'pix.pix') {
            $json['error'] = "Invalid payment method selected.";
        }
    
        // If no errors so far
        if (empty($json)) {
            $this->load->model('checkout/order');
            $this->load->model('account/customer');
            $this->load->model('account/address');
    
            $payable = $this->config->get('payment_pix_payable') ?? '';
            $address_text = $this->config->get('config_address') ?? '';
    
            $comment = $payable . "\n\n" . $address_text . "\n\n";
    
            $this->model_checkout_order->addHistory(
                $this->session->data['order_id'],
                $this->config->get('payment_pix_order_status_id'),
                $comment,
                true
            );
    
            // --- Recover customer ---
            $customer_info = [];
            if ($this->customer->isLogged()) {
                $customer_info = $this->model_account_customer->getCustomer($this->customer->getId());
            }
    
            // --- Recover address ---
            $address_info = [];
            if (!empty($customer_info['customer_id'])) {
                $address_info = $this->model_account_address->getAddresses($customer_info['customer_id']);
            }
    
            // --- Build items ---
            $items = [];
            foreach ($this->cart->getProducts() as $product) {
                $items[] = [
                    'name'        => $product['name'],
                    'description' => $product['model'],
                    'quantity'    => $product['quantity'],
                    'value'       => number_format($product['price'], 2, '.', '')
                ];
            }

            $json['error'] = json_encode($address_info);
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;


            // --- Build payload ---
            $payload = [
                'billingTypes'   => ['PIX'],
                'chargeTypes'    => ['DETACHED'],
                'minutesToExpire'=> 60,
                'callback'       => [
                    'cancelUrl'  => $this->url->link('checkout/failure'),
                    'expiredUrl' => $this->url->link('checkout/failure'),
                    'successUrl' => $this->url->link(
                        'checkout/success',
                        'orderId=' . (int)$this->session->data['order_id'] . '&language=' . $this->config->get('config_language'),
                        true
                    )
                ],
                'items' => $items,
                'customerData' => [
                    'name'   => $customer_info['firstname'] . ' ' . $customer_info['lastname'],
                    'email'  => $customer_info['email'],
                    'phone'  => $customer_info['telephone'] ?? '',
                    'cpfCnpj'=> $customer_info['cpf'] ?? '',
                    "address"=> $address_info['address_1'] ?? '',
                    "addressNumber"=> '',
                    "complement"=> $address_info['address_2'] ?? '',
                    "postalCode"=> $address_info['postcode'] ?? '',
                    "province"=> $address_info['zone_code'] ?? '',
                    "city"=> $address_info['city'] ?? ''
                ],
            ];
    
            // --- Call Asaas API ---
            $token    = $this->config->get('config_asaas_token');
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
            if ($result === false) {
                $json['error'] = 'Payment API request failed: ' . curl_error($ch);
            }
            curl_close($ch);
    
            if (empty($json['error'])) {
                $response = json_decode($result, true);
    
                if (!empty($response['link'])) {
                    $json['redirect'] = $response['link'];
                } else {
                    if (!empty($response['errors'][0]['description'])) {
                        $json['error'] = $response['errors'][0]['description'];
                    } else {
                        $json['error'] = 'Unexpected response from payment API.';
                    }
                }
            }
        }
    
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
}