<?php
namespace Opencart\Catalog\Controller\Api\Sale;
class Reward extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('api/sale/reward');

		$json = [];

		$points = $this->customer->getRewardPoints();

		$points_total = 0;

		foreach ($this->cart->getProducts() as $product) {
			if ($product['points']) {
				$points_total += $product['points'];
			}
		}

		if (empty($this->request->post['reward'])) {
			$json['error'] = $this->language->get('error_reward');
		}

		if ($this->request->post['reward'] > $points) {
			$json['error'] = sprintf($this->language->get('error_points'), $this->request->post['reward']);
		}

		if ($this->request->post['reward'] > $points_total) {
			$json['error'] = sprintf($this->language->get('error_maximum'), $points_total);
		}

		if (!$json) {
			$this->session->data['reward'] = abs($this->request->post['reward']);

			$json['success'] = $this->language->get('text_success');

			$totals = [];
			$taxes = $this->cart->getTaxes();
			$total = 0;

			$this->load->model('checkout/cart');

			($this->model_checkout_cart->getTotals)($totals, $taxes, $total);

			$json['products'] = $this->model_checkout_cart->getProducts();
			$json['vouchers'] = $this->model_checkout_cart->getVouchers();
			$json['totals'] = $totals;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function maximum(): void {
		$this->load->language('api/sale/reward');

		$json = [];

		$json['maximum'] = 0;

		foreach ($this->cart->getProducts() as $product) {
			if ($product['points']) {
				$json['maximum'] += $product['points'];
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function available(): void {
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode(['points' => $this->customer->getRewardPoints()]));
	}

	public function clear(): void {
		unset($this->session->data['reward']);

		$json['success'] = $this->language->get('text_success');

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
