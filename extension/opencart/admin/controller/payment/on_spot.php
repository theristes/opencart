<?php
namespace Opencart\Admin\Controller\Extension\Opencart\Payment;

class OnSpot extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('payment/on_spot');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if ($this->request->server['REQUEST_METHOD'] === 'POST') {
            $this->model_setting_setting->editSetting('payment_on_spot', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'type=payment&user_token=' . $this->session->data['user_token']));
        }

        $data['heading_title'] = $this->language->get('heading_title');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');

        $data['payment_on_spot_status'] = $this->config->get('payment_on_spot_status') ?? '';
        $data['payment_on_spot_sort_order'] = $this->config->get('payment_on_spot_sort_order') ?? 0;

        $data['action'] = $this->url->link('payment/on_spot', 'user_token=' . $this->session->data['user_token']);
        $data['cancel'] = $this->url->link('marketplace/extension', 'type=payment&user_token=' . $this->session->data['user_token']);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('payment/on_spot', $data));
    }
}
