<?php
namespace Opencart\catalog\controller\api;
/**
 * Class Payment Method
 *
 * @package Opencart\Catalog\Controller\Api
 */
class PaymentMethod extends \Opencart\System\Engine\Controller {
	/**
	 * @return void
	 */
	public function index(): void {
		$this->load->language('api/payment_method');

		$json = [];

		// Customer
		$this->load->controller('api/customer');
		$this->load->controller('api/cart');
		$this->load->controller('api/shipping_address');
		$this->load->controller('api/shipping_method.save');

		// 1. Validate customer data exists
		if (!isset($this->session->data['customer'])) {
			$json['error'] = $this->language->get('error_customer');
		}

		// 2. Validate cart has products or vouchers
		if (!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) {
			$json['error'] = $this->language->get('error_product');
		}

		// 3. Validate shipping address and method if required
		if ($this->cart->hasShipping()) {
			if (!isset($this->session->data['shipping_address'])) {
				$json['error'] = $this->language->get('error_shipping_address');
			}

			if (!isset($this->session->data['shipping_method'])) {
				$json['error'] = $this->language->get('error_shipping_method');
			}
		}

		// 4. Validate payment address if required
		if ($this->config->get('config_checkout_payment_address') && !isset($this->session->data['payment_address'])) {
			$json['error'] = $this->language->get('error_payment_address');
		}

		if (!$json) {
			$payment_address = [];

			if (isset($this->session->data['payment_address'])) {
				$payment_address = $this->session->data['payment_address'];
			} elseif ($this->config->get('config_checkout_shipping_address') && isset($this->session->data['shipping_address'])) {
				$payment_address = $this->session->data['shipping_address'];
			}

			$this->load->model('checkout/payment_method');

			$payment_methods = $this->model_checkout_payment_method->getMethods($payment_address);

			if ($payment_methods) {
				$json['payment_methods'] = $this->session->data['payment_methods'] = $payment_methods;
			} else {
				$json['error'] = $this->language->get('error_no_payment');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('api/payment_method');

		$json = [];

		// 1. Validate customer data exists
		if (!isset($this->session->data['customer'])) {
			$json['error'] = $this->language->get('error_customer');
		}

		// 2. Validate cart has products or vouchers
		if (!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) {
			$json['error'] = $this->language->get('error_product');
		}

		// 3. Validate shipping address and method if required
		if ($this->cart->hasShipping()) {
			if (!isset($this->session->data['shipping_address'])) {
				$json['error'] = $this->language->get('error_shipping_address');
			}

			if (!isset($this->session->data['shipping_method'])) {
				$json['error'] = $this->language->get('error_shipping_method');
			}
		}

		// 4. Validate payment address if required
		if ($this->config->get('config_checkout_payment_address') && !isset($this->session->data['payment_address'])) {
			$json['error'] = $this->language->get('error_payment_address');
		}

		// 5. Validate payment Method
		if (!isset($this->request->post['payment_method'])) {
			$payment = $this->request->post['payment_method'];

			if (!isset($payment['name'])) {
				$json['error'] = $this->language->get('error_name');
			}

			if (!isset($payment['code'])) {
				$json['error'] = $this->language->get('error_code');
			}
		} else {
			$json['error'] = $this->language->get('error_payment_method');
		}

		if (!$json) {
			$this->session->data['payment_method'] = $payment;

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
