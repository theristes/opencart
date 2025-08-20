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
    /**
     * Confirm payment
     *
     * @return void
     */
    public function confirm(): void {
        $json = [];

        if (!isset($this->session->data['order_id'])) {
            $json['redirect'] = $this->url->link('checkout/failure', 'language=' . $this->config->get('config_language'), true);
        } else {
            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

            if (!$order_info) {
                $json['redirect'] = $this->url->link('checkout/failure', 'language=' . $this->config->get('config_language'), true);
                unset($this->session->data['order_id']);
            }
        }

        // Payment method check
        if (!isset($this->session->data['payment_method']) || $this->session->data['payment_method']['code'] !== 'pix.pix') {
            $json['error'] = "Invalid payment method selected.";
        }

        // If no errors
        if (empty($json)) {
            $this->load->model('checkout/order');

            // --- 1. Prepare Customer & Address Data ---

            // Sanitize phone number to contain only digits
            $phone = preg_replace('/[^0-9]/', '', $order_info['telephone']);

            // Get CPF/CNPJ. OpenCart doesn't have a default field for this, so it's usually a custom field.
            // IMPORTANT: You may need to adjust this logic to match your store's custom fields.
            $cpfCnpj = '';
            if (!empty($order_info['payment_cpf'])) {
                $cpfCnpj = $order_info['payment_cpf'];
            } elseif (!empty($order_info['payment_cnpj'])) {
                $cpfCnpj = $order_info['payment_cnpj'];
            } elseif (isset($order_info['payment_custom_field'][2])) { // Common custom field ID for CPF
                $cpfCnpj = $order_info['payment_custom_field'][2];
            }
            $cpfCnpj = preg_replace('/[^0-9]/', '', $cpfCnpj);

            // Parse address into street and number, as required by the API
            $address = trim($order_info['payment_address_1']);
            $addressNumber = 'S/N'; // Default to 'S/N' (No Number)
            $addressStreet = $address;

            // This regex attempts to find the number at the end of the address string
            if (preg_match('/^(.*?),?\s+([0-9]+[a-zA-Z]*.*)$/', $address, $matches)) {
                $addressStreet = trim($matches[1]);
                $addressNumber = trim($matches[2]);
            }
            
            // The 'province' field is for the neighborhood ('bairro'). OpenCart's 'address_2' is often used for this.
            $province = $order_info['payment_address_2'] ?? '';
            $complement = $order_info['payment_address_2'] ?? '';
            
            // Sanitize postal code
            $postalCode = preg_replace('/[^0-9]/', '', $order_info['payment_postcode']);

            // Build the customer data object
            $customer_data = [
                'name' => $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'],
                'email' => $order_info['email'],
                'phone' => $phone,
                'cpfCnpj' => $cpfCnpj
            ];
            
            // --- 2. Build Items Array ---
            $items = [];
            foreach ($this->cart->getProducts() as $product) {
                $items[] = [
                    'name' => $product['name'],
                    'description' => $product['model'],
                    'quantity' => $product['quantity'],
                    // Ensure price is a float with 2 decimal places
                    'value' => round((float)$product['price'], 2) 
                ];
            }

            // --- 3. Construct the Full Payload ---
            $payload = [
                'billingTypes' => ['PIX'],
                'chargeTypes' => ['DETACHED'],
                'minutesToExpire' => 60,
                'customer' => $customer_data, // Customer object
                'postalCode' => $postalCode,
                'address' => $addressStreet,
                'addressNumber' => $addressNumber,
                'complement' => $complement,
                'province' => $province, // Neighborhood ('bairro')
                'externalReference' => $order_info['order_id'], // Link payment to the order ID
                'callback' => [
                    'cancelUrl' => $this->url->link('checkout/failure', 'language=' . $this->config->get('config_language'), true),
                    'expiredUrl' => $this->url->link('checkout/failure', 'language=' . $this->config->get('config_language'), true),
                    'successUrl' => $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), true),
                    'autoRedirect' => true // Automatically redirect user to payment page
                ],
                'items' => $items
            ];

            // --- 4. Send cURL Request ---
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
                // Add the order history before redirecting
                $this->model_checkout_order->addHistory(
                    $this->session->data['order_id'],
                    $this->config->get('payment_pix_order_status_id'),
                    'Asaas payment link generated.',
                    true
                );
                $json['redirect'] = $response['link'];
            } else {
                $error_message = 'Payment gateway error.';
                if (isset($response['errors']) && is_array($response['errors']) && count($response['errors']) > 0) {
                    $error_message = $response['errors'][0]['description'];
                }
                $json['error'] = $error_message;
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}