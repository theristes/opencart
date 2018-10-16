<?php
class ControllerCustomerGdpr extends Controller {
	public function index() {
		$this->load->language('customer/gdpr');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('customer/gdpr', 'user_token=' . $this->session->data['user_token'])
		);

		$data['text_info'] = sprintf($this->language->get('text_info'), $this->config->get('config_gdpr_limit'));

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('customer/gdpr', $data));
	}

	public function gdpr() {
		$this->load->language('customer/gdpr');

		if (isset($this->request->get['filter_customer'])) {
			$filter_customer = $this->request->get['filter_customer'];
		} else {
			$filter_customer = '';
		}

		if (isset($this->request->get['filter_email'])) {
			$filter_email = $this->request->get['filter_email'];
		} else {
			$filter_email = '';
		}

		if (isset($this->request->get['filter_customer_group_id'])) {
			$filter_customer_group_id = $this->request->get['filter_customer_group_id'];
		} else {
			$filter_customer_group_id = '';
		}

		if (isset($this->request->get['filter_status'])) {
			$filter_status = $this->request->get['filter_status'];
		} else {
			$filter_status = '';
		}

		if (isset($this->request->get['filter_date_added'])) {
			$filter_date_added = $this->request->get['filter_date_added'];
		} else {
			$filter_date_added = '';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['gdprs'] = array();

		$filter_data = array(
			'filter_customer'          => $filter_customer,
			'filter_email'             => $filter_email,
			'filter_customer_group_id' => $filter_customer_group_id,
			'filter_status'            => $filter_status,
			'filter_date_added'        => $filter_date_added,
			'start'                    => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'                    => $this->config->get('config_limit_admin')
		);

		$this->load->model('customer/gdpr');

		$gdpr_total = $this->model_customer_gdpr->getTotalGdprs($filter_data);

		$results = $this->model_customer_gdpr->getGdprs($filter_data);

		foreach ($results as $result) {
			$data['gdprs'][] = array(
				'customer_id'    => $result['customer_id'],
				'customer'       => $result['customer'],
				'email'          => $result['email'],
				'customer_group' => $result['customer_group'],
				'status'         => $result['status'],
				'date_added'     => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'approve'        => $this->url->link('customer/gdpr/approve', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $result['customer_id']),
				'deny'           => $this->url->link('customer/gdpr/deny', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $result['customer_id']),
				'edit'           => $this->url->link('customer/customer/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $result['customer_id'])
			);
		}

		$url = '';

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_email'])) {
			$url .= '&filter_email=' . urlencode(html_entity_decode($this->request->get['filter_email'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_customer_group_id'])) {
			$url .= '&filter_customer_group_id=' . $this->request->get['filter_customer_group_id'];
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		$data['pagination'] = $this->load->controller('common/pagination', array(
			'total' => $gdpr_total,
			'page'  => $page,
			'limit' => $this->config->get('config_limit_admin'),
			'url'   => $this->url->link('customer/gdpr/gdpr', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}')
		));

		$data['results'] = sprintf($this->language->get('text_pagination'), ($gdpr_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($gdpr_total - $this->config->get('config_limit_admin'))) ? $gdpr_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $gdpr_total, ceil($gdpr_total / $this->config->get('config_limit_admin')));

		$this->response->setOutput($this->load->view('customer/gdpr_list', $data));
	}

	public function approve() {
		$this->load->language('customer/gdpr');

		$json = array();

		if (!$this->user->hasPermission('modify', 'customer/gdpr')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('customer/gdpr');

			$this->model_customer_gdpr->approveGdpr($this->request->get['customer_id']);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function deny() {
		$this->load->language('customer/gdpr');

		$json = array();

		if (!$this->user->hasPermission('modify', 'customer/gdpr')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('customer/gdpr');

			$this->model_customer_gdpr->denyGdpr($this->request->get['customer_id']);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}