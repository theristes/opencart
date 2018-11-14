<?php
class ControllerMailGdpr extends Controller {
	// catalog/model/customer/gdpr/addGdpr
	public function confirm(&$route, &$args, &$output) {
		$this->load->model('account/customer');

		$customer_info = $this->model_account_customer->getCustomer($args[0]);

		if ($customer_info) {
			$this->load->language('mail/gdpr');

			if ($this->config->get('config_logo')) {
				$data['logo'] = html_entity_decode($this->config->get('config_logo'), ENT_QUOTES, 'UTF-8');
			} else {
				$data['logo'] = '';
			}

			$data['text_hello'] = sprintf($this->language->get('text_hello'), html_entity_decode($customer_info['firstname'], ENT_QUOTES, 'UTF-8'));
			$data['text_greeting'] = sprintf($this->language->get('text_greeting'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$data['text_delete'] = sprintf($this->language->get('text_delete'), $this->config->get('config_gdpr_limit'));

			$data['ip'] = $this->request->server['REMOTE_ADDR'];

			$data['store'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

			$mail = new Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

			$mail->setTo($customer_info['email']);
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(sprintf($this->language->get('text_subject'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8')));
			$mail->setHtml($this->load->view('mail/gdpr', $data));
			$mail->send();
		}
	}

	// catalog/model/customer/gdpr/addGdpr
	public function request(&$route, &$args, &$output) {
		$this->load->model('account/customer');

		$customer_info = $this->model_account_customer->getCustomer($args[0]);

		if ($customer_info) {
			$this->load->language('mail/gdpr');

			if ($this->config->get('config_logo')) {
				$data['logo'] = html_entity_decode($this->config->get('config_logo'), ENT_QUOTES, 'UTF-8');
			} else {
				$data['logo'] = '';
			}

			$data['text_hello'] = sprintf($this->language->get('text_hello'), html_entity_decode($customer_info['firstname'], ENT_QUOTES, 'UTF-8'));
			$data['text_greeting'] = sprintf($this->language->get('text_greeting'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$data['text_delete'] = sprintf($this->language->get('text_delete'), $this->config->get('config_gdpr_limit'));

			$data['ip'] = $this->request->server['REMOTE_ADDR'];

			$data['store'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

			$mail = new Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

			$mail->setTo($customer_info['email']);
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(sprintf($this->language->get('text_subject'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8')));
			$mail->setHtml($this->load->view('mail/gdpr', $data));
			$mail->send();
		}
	}



	// catalog/model/customer/gdpr/addGdpr
	public function data(&$route, &$args, &$output) {
		$this->load->model('account/customer');

		$customer_info = $this->model_account_customer->getCustomer($args[0]);

		if ($customer_info) {
			$this->load->language('mail/gdpr');

			if ($this->config->get('config_logo')) {
				$data['logo'] = html_entity_decode($this->config->get('config_logo'), ENT_QUOTES, 'UTF-8');
			} else {
				$data['logo'] = '';
			}

			$data['text_hello'] = sprintf($this->language->get('text_hello'), html_entity_decode($customer_info['firstname'], ENT_QUOTES, 'UTF-8'));
			$data['text_greeting'] = sprintf($this->language->get('text_greeting'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$data['text_delete'] = sprintf($this->language->get('text_delete'), $this->config->get('config_gdpr_limit'));


			public function mail() {
				$data['firstname'] = $this->customer->getFirstname();
				$data['lastname'] = $this->customer->getFirstname();
				$data['email'] = $this->customer->getEmail();
				$data['telephone'] = $this->customer->getTelephone();

				// Addresses
				$data['addresses'] = array();

				$this->load->model('account/address');

				$results = $this->model_account_address->getAddresses($this->customer->getId());

				foreach ($results as $result) {
					$address = array(
						'firstname' => $result['firstname'],
						'lastname' => $result['lastname'],
						'address_1' => $result['address_1'],
						'address_2' => $result['address_2'],
						'city' => $result['city'],
						'postcode' => $result['postcode'],
						'country' => $result['country'],
						'zone' => $result['zone']
					);

					if (!in_array($address, $data['addresses'])) {
						$data['addresses'][] = $address;
					}
				}

				// Order Addresses
				$this->load->model('account/order');

				$results = $this->model_account_order->getOrders($this->customer->getId());

				foreach ($results as $result) {
					$address = array(
						'firstaname' => $result['payment_firstaname'],
						'lastname' => $result['payment_lastname'],
						'address_1' => $result['payment_address_1'],
						'address_2' => $result['payment_address_2'],
						'city' => $result['payment_city'],
						'postcode' => $result['payment_postcode'],
						'country' => $result['payment_country'],
						'zone' => $result['payment_zone']
					);

					if (!in_array($address, $data['addresses'])) {
						$data['addresses'][] = $address;
					}

					$address = array(
						'firstname' => $result['shipping_firstname'],
						'lastname' => $result['shipping_lastname'],
						'address_1' => $result['shipping_address_1'],
						'address_2' => $result['shipping_address_2'],
						'city' => $result['shipping_city'],
						'postcode' => $result['shipping_postcode'],
						'country' => $result['shipping_country'],
						'zone' => $result['shipping_zone']
					);

					if (!in_array($address, $data['addresses'])) {
						$data['addresses'][] = $address;
					}
				}

				// Ip's
				$data['ips'] = array();

				$this->load->model('account/customer');

				$results = $this->model_account_customer->getIps($this->customer->getId());

				foreach ($results as $result) {
					$data['ips'][] = array(
						'ip' => $result['ip'],
						'country' => $result['country'],
						'date_added' => date($this->language->get('datetime_format'), strtotime($result['date_added']))
					);
				}

			}







			$data['ip'] = $this->request->server['REMOTE_ADDR'];

			$data['store'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

			$mail = new Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

			$mail->setTo($customer_info['email']);
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(sprintf($this->language->get('text_subject'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8')));
			$mail->setHtml($this->load->view('mail/gdpr', $data));
			$mail->send();
		}
	}



}
