<?php
namespace Opencart\Catalog\Controller\Checkout;

class Service extends \Opencart\System\Engine\Controller {
	public function save(): void {
		$json = [];

		if (isset($this->request->post['service_id'])) {
			$this->session->data['service_id'] = (int)$this->request->post['service_id'];
			$json['success'] = true;
		} else {
			$json['error'] = 'Missing service_id';
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
