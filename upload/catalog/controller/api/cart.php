<?php
namespace Opencart\catalog\controller\api;
/**
 * Class Cart
 *
 * @package Opencart\Catalog\Controller\Api\Sale
 */
class Cart extends \Opencart\System\Engine\Controller {
	public function index(): array {
		$this->load->language('api/cart');

		$output = [];

		if (isset($this->request->post['product'])) {
			$products = (array)$this->request->post['product'];
		} else {
			$products = [];
		}

		$this->load->model('catalog/product');

		foreach ($products as $key => $product) {
			$product_info = $this->model_catalog_product->getProduct((int)$product['product_id']);

			if ($product_info) {
				if (isset($product['quantity'])) {
					$quantity = (int)$product['quantity'];
				} else {
					$quantity = 1;
				}

				if (isset($product['option'])) {
					$option = array_filter((array)$product['option']);
				} else {
					$option = [];
				}

				if (isset($product['subscription_plan_id'])) {
					$subscription_plan_id = (int)$product['subscription_plan_id'];
				} else {
					$subscription_plan_id = 0;
				}

				// Merge variant code with options
				foreach ($product_info['variant'] as $option_id => $value) {
					$option[$option_id] = $value;
				}

				// Validate that the options we are selecting exist
				foreach ($option as $product_option_id => $value) {
					$product_option_info = $this->model_catalog_product->getOption($product['product_id'], $product_option_id);


					if (!$product_option_info) {
						$output['error']['product'][$key]['option_' . $product_option_id] = $this->language->get('error_option');

					print_r($value);

					if ($product_option_info) {
						//$error['option_' . $product_option_id] = sprintf($this->language->get('error_option'), $value['name']);

						$error['option_' . $product_option_id] = 'refreg';

					}
				}

				// Validate options
				$product_options = $this->model_catalog_product->getOptions($product['product_id']);

				foreach ($product_options as $product_option) {
					if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
						$output['error']['product'][$key]['option_' . $product_option['product_option_id']] = sprintf($this->language->get('error_required'), $product_option['name']);
					}
				}

				// Validate subscription plan
				$subscriptions = $this->model_catalog_product->getSubscriptions($product['product_id']);

				if ($subscriptions && !in_array($product['subscription_plan_id'], array_column($subscriptions, 'subscription_plan_id'))) {
					$output['error']['product'][$key]['subscription'] = $this->language->get('error_subscription');
				}
			} else {
				$output['error']['product'][$key]['product'] = $this->language->get('error_product');
			}

			if (!$output) {
				$this->cart->add($product['product_id'], $quantity, $option, $subscription_plan_id);
			}
		}

		if (!$output) {
			$output['success'] = $this->language->get('text_success');
		}

		return $output;
	}

	/**
	 * Add
	 *
	 * Add any single product
	 *
	 * @return array
	 */
	public function addProduct(): array {
		$this->load->language('api/cart');

		$output = [];

		// Add any single products
		if (isset($this->request->post['product_id'])) {
			$product_id = (int)$this->request->post['product_id'];
		} else {
			$product_id = 0;
		}

		if (isset($this->request->post['quantity'])) {
			$quantity = (int)$this->request->post['quantity'];
		} else {
			$quantity = 1;
		}

		if (isset($this->request->post['option'])) {
			$option = array_filter((array)$this->request->post['option']);
		} else {
			$option = [];
		}

		if (isset($this->request->post['subscription_plan_id'])) {
			$subscription_plan_id = (int)$this->request->post['subscription_plan_id'];
		} else {
			$subscription_plan_id = 0;
		}

		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if ($product_info) {
			// If variant get master product
			if ($product_info['master_id']) {
				$product_id = $product_info['master_id'];
			}

			// Merge variant code with options
			foreach ($product_info['variant'] as $option_id => $value) {
				$option[$option_id] = $value;
			}

			// Validate Options
			$product_options = $this->model_catalog_product->getOptions($product_id);

			foreach ($product_options as $product_option) {
				if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
					$output['error']['option_' . $product_option['product_option_id']] = sprintf($this->language->get('error_required'), $product_option['name']);
				}
			}

			// Validate subscription plan
			$subscriptions = $this->model_catalog_product->getSubscriptions($product_id);

			if ($subscriptions && !in_array($subscription_plan_id, array_column($subscriptions, 'subscription_plan_id'))) {
				$output['error']['subscription'] = $this->language->get('error_subscription');
			}
		} else {
			$output['error']['warning'] = $this->language->get('error_product');
		}

		if (!$output) {
			$this->cart->add($product_id, $quantity, $option, $subscription_plan_id);

			$output['success'] = $this->language->get('text_success');
		}

		return $output;
	}

	/**
	 * Get products
	 *
	 * @return array
	 */
	public function getProducts(): array {
		// We fetch any products that have an error

		// Stock
		if (!$this->cart->hasStock() && (!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning'))) {
			$output['error']['stock'] = $this->language->get('error_stock');
		}

		$product_data = [];

		$this->load->model('checkout/cart');

		$products = $this->model_checkout_cart->getProducts();

		//'stock' => $product['stock'] ? $this->language->get('error_stock') : '',
		//'minimum' => $product['minimum'] ? $this->language->get('error_minimum') : ''

		foreach ($products as $product) {
			$product_data[] = [
				'subscription_plan_id' => $product['subscription'] ? $product['subscription']['subscription_plan_id'] : 0,
				'subscription'         => $product['subscription'] ? $product['subscription']['description'] : '',
				'price_text'           => $product['price_text'],
				'price'                => $product['price'],

				'total_text'           => $product['total_text'],
				'total'                => $product['total']
			] + $product;

				'total_text'           => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'], $this->session->data['currency']),
				'total'                => $product['total'],
				'error'                => ['stock' => $product['stock']]
			];
		}

		// We fetch any products that have an error
		foreach ($this->products as $product) {
			$description = '';

			if ($product['subscription']) {
				if ($product['subscription']['trial_status']) {
					$trial_price = $this->currency->format($this->tax->calculate($product['subscription']['trial_price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					$trial_cycle = $product['subscription']['trial_cycle'];
					$trial_frequency = $this->language->get('text_' . $product['subscription']['trial_frequency']);
					$trial_duration = $product['subscription']['trial_duration'];

					$description .= sprintf($this->language->get('text_subscription_trial'), $trial_price, $trial_cycle, $trial_frequency, $trial_duration);
				}

				$price = $this->currency->format($this->tax->calculate($product['subscription']['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				$cycle = $product['subscription']['cycle'];
				$frequency = $this->language->get('text_' . $product['subscription']['frequency']);
				$duration = $product['subscription']['duration'];

				if ($duration) {
					$description .= sprintf($this->language->get('text_subscription_duration'), $price, $cycle, $frequency, $duration);
				} else {
					$description .= sprintf($this->language->get('text_subscription_cancel'), $price, $cycle, $frequency);
				}
			}

			$product_data[] = [
				'product_id'           => $product['product_id'],
				'name'                 => $product['name'],
				'model'                => $product['model'],
				'option'               => $product['option'],
				'subscription_plan_id' => $product['subscription_plan_id'],
				'subscription'         => $description,

				'error' => $product['error']
			];

		}

		return $product_data;
	}

	public function getTotals(): array {
		$totals = [];
		$taxes = $this->cart->getTaxes();
		$total = 0;

		$this->load->model('checkout/cart');

		($this->model_checkout_cart->getTotals)($totals, $taxes, $total);

		$total_data = [];

		foreach ($totals as $total) {
			$total_data[] = [
				'title' => $total['title'],
				'text'  => $this->currency->format($total['value'], $this->session->data['currency'])
			];
		}

		return $total_data;
	}
}
