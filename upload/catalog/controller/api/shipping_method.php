<?php
namespace Opencart\catalog\controller\api;
/**
 * Class Shipping Method
 *
 * @package Opencart\Catalog\Controller\Api
 */
class ShippingMethod extends \Opencart\System\Engine\Controller {
	/**
	 * @return void
	 */
	public function index(): void {
		$this->load->language('api/shipping_method');

		$json = [];

		// 1. Validate customer data exists
		if (!isset($this->session->data['customer'])) {
			$json['error'] = $this->language->get('error_customer');
		}

		// 2. Validate shipping if required
		if ($this->cart->hasShipping()) {
			if (!isset($this->session->data['shipping_address'])) {
				$json['error'] = $this->language->get('error_shipping_address');
			}
		} else {
			$json['error'] = $this->language->get('error_shipping');
		}

		if (!$json) {
			$this->load->model('checkout/shipping_method');

			$shipping_methods = $this->model_checkout_shipping_method->getMethods($this->session->data['shipping_address']);

			if ($shipping_methods) {
				$json['shipping_methods'] = $this->session->data['shipping_methods'] = $shipping_methods;
			} else {
				$json['error'] = $this->language->get('error_no_shipping');
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
		$this->load->language('api/shipping_method');

		$json = [];

		if ($this->cart->hasShipping()) {
			// 1. Validate customer data exists
			if (!isset($this->session->data['customer'])) {
				$json['error'] = $this->language->get('error_customer');
			}

			// 2. Validate shipping address
			if (!isset($this->session->data['shipping_address'])) {
				$json['error'] = $this->language->get('error_shipping_address');
			}

			// 3. Validate shipping method
			if (isset($this->request->post['shipping_method'])) {
				$shipping = $this->request->post['shipping_method'];

				if (!isset($shipping['name'])) {
					$json['error'] = $this->language->get('error_name');
				}

				if (!isset($shipping['code'])) {
					$json['error'] = $this->language->get('error_code');
				}

				if (!isset($shipping['cost'])) {
					$json['error'] = $this->language->get('error_cost');
				}

				if (!isset($shipping['tax_class_id'])) {
					$json['error'] = $this->language->get('error_tax_class');
				}
			} else {
				$json['error'] = $this->language->get('error_shipping_method');
			}
		} else {
			$json['error'] = $this->language->get('error_shipping');
		}

		if (!$json) {
			$json['success'] = $this->language->get('text_success');

			$this->session->data['shipping_method'] = $shipping + ['text' => $this->currency->format($this->tax->calculate($shipping['cost'], $shipping['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'])];
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
