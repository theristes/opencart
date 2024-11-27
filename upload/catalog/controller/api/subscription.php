<?php
namespace Opencart\Catalog\Controller\Api;
/**
 * Class Subscription
 *
 * Subscription API
 *
 * @package Opencart\Catalog\Controller\Api
 */
class Subscription extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('api/subscription');

		if (isset($this->request->get['call'])) {
			$call = $this->request->get['call'];
		} else {
			$call = '';
		}

		// Allowed calls
		switch ($call) {
			case 'cart':
				$output = $this->getCart();
				break;
			case 'product_add':
				$output = $this->addProduct();
				break;
			case 'shipping_methods':
				$output = $this->getShippingMethods();
				break;
			case 'payment_methods':
				$output = $this->getPaymentMethods();
				break;
			case 'history_add':
				$output = $this->addHistory();
				break;
			default:
				$output = ['error' => $this->language->get('error_call')]; // JSON error message if call not found
				break;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($output));
	}

	/**
	 * Set Customer
	 *
	 * @return array
	 */
	protected function setCustomer(): array {
		$this->load->language('api/order');

		$output = [];

		// Customer
		if ($this->request->post['customer_id']) {
			$customer_id = $this->request->post['customer_id'];
		} else {
			$customer_id = 0;
		}

		$this->load->model('account/customer');

		$customer_info = $this->model_account_customer->getCustomer($customer_id);

		if (!$customer_info) {
			$output['error'] = $this->language->get('error_customer');
		}

		if (!$output) {
			// Log the customer in
			$this->customer->login($customer_info['email'], '', true);

			$this->session->data['customer'] = $customer_info;

			$output['success'] = $this->language->get('text_success');
		}

		return $output;
	}

	/**
	 * Set Payment Address
	 *
	 * @return array
	 */
	protected function setPaymentAddress(): array {
		$this->load->language('api/order');

		$output = [];

		if (!isset($this->request->post['payment_address_id'])) {
			$address_id = (int)$this->request->post['payment_address_id'];
		} else {
			$address_id = 0;
		}

		$this->load->model('account/address');

		$address_info = $this->model_account_address->getAddress($this->customer->getId(), $address_id);

		if (!$address_info) {
			$output['error'] = $this->language->get('error_payment_address');
		}

		if (!$output) {
			$this->session->data['payment_address'] = $address_info;

			$output['success'] = $this->language->get('text_success');
		}

		return $output;
	}

	/**
	 * Set shipping address
	 *
	 * @return array
	 */
	protected function setShippingAddress(): array {
		$this->load->language('api/order');

		$output = [];

		if (!isset($this->request->post['shipping_address_id'])) {
			$address_id = (int)$this->request->post['shipping_address_id'];
		} else {
			$address_id = 0;
		}

		$this->load->model('account/address');

		$address_info = $this->model_account_address->getAddress($this->customer->getId(), $address_id);

		if (!$address_info) {
			$output['error'] = $this->language->get('error_shipping_address');
		}

		if (!$output) {
			$this->session->data['shipping_address'] = $address_info;

			$output['success'] = $this->language->get('text_success');
		}

		return $output;
	}

	/**
	 * Get shipping methods
	 *
	 * @return array
	 */
	protected function getShippingMethods(): array {
		$this->setCustomer();

		$output = $this->load->controller('api/cart');

		if (isset($output['error'])) {
			return $output;
		}

		$this->setPaymentAddress();
		$this->setShippingAddress();

		return $this->load->controller('api/shipping_method.getShippingMethods');
	}

	/**
	 * Get payment methods
	 *
	 * @return array
	 */
	protected function getPaymentMethods(): array {
		$this->setCustomer();

		$output = $this->load->controller('api/cart');

		if (isset($output['error'])) {
			return $output;
		}

		$this->setPaymentAddress();
		$this->setShippingAddress();

		$this->load->controller('api/shipping_method');

		return $this->load->controller('api/payment_method.getPaymentMethods');
	}

	/**
	 * Set payment method
	 *
	 * @return array
	 */
	protected function setPaymentMethod(): array {
		$this->setCustomer();

		$output = $this->load->controller('api/cart');

		if (isset($output['error'])) {
			return $output;
		}

		$this->setPaymentAddress();
		$this->setShippingAddress();

		$this->load->controller('api/shipping_method');

		$output = $this->load->controller('api/payment_method');

		$output['products'] = $this->load->controller('api/cart.getProducts');
		$output['shipping_required'] = $this->cart->hasShipping();

		return $output;
	}

	/**
	 * Get cart
	 *
	 * @return array
	 */
	protected function getCart(): array {
		$this->setCustomer();

		// If any errors at the cart level such as products don't exist then we want to return the error
		$output = $this->load->controller('api/cart');

		$this->setPaymentAddress();
		$this->setShippingAddress();

		$this->load->controller('api/shipping_method');
		$this->load->controller('api/payment_method');

		$output['products'] = $this->load->controller('api/cart.getProducts');
		$output['shipping_required'] = $this->cart->hasShipping();

		return $output;
	}

	/**
	 * Add product
	 *
	 * @return array
	 */
	protected function addProduct(): array {
		$this->setCustomer();

		$output = $this->load->controller('api/cart');

		if (isset($output['error'])) {
			return $output;
		}
		
		$this->setPaymentAddress();
		$this->setShippingAddress();

		$output = $this->load->controller('api/cart.addProduct');

		$output['products'] = $this->load->controller('api/cart.getProducts');
		$output['shipping_required'] = $this->cart->hasShipping();

		//print_r($this->request->post);

		return $output;
	}

	/**
	 * Add Order history
	 *
	 * @return array
	 */
	protected function addHistory(): array {
		$this->load->language('api/order');

		$output = [];

		// Add keys for missing post vars
		$keys = [
			'order_id',
			'order_status_id',
			'comment',
			'notify',
			'override'
		];

		foreach ($keys as $key) {
			if (!isset($this->request->post[$key])) {
				$this->request->post[$key] = '';
			}
		}

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder((int)$this->request->post['order_id']);

		if (!$order_info) {
			$output['error'] = $this->language->get('error_order');
		}

		if (!$output) {
			$this->model_checkout_order->addHistory((int)$this->request->post['order_id'], (int)$this->request->post['order_status_id'], (string)$this->request->post['comment'], (bool)$this->request->post['notify'], (bool)$this->request->post['override']);

			$output['success'] = $this->language->get('text_success');
		}

		return $output;
	}
}
