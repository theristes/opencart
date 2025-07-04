<?php
namespace Opencart\Admin\Controller\Module;

class AsaasConfig extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('module/asaas_config');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->validate()) {
            $this->model_setting_setting->editSetting('asaas_config', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('setting/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module'));
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['asaas_config_CONFIG_ASAAS_TOKEN'] = $this->config->get('asaas_config_CONFIG_ASAAS_TOKEN') ?? '';
        $data['asaas_config_CONFIG_ASAAS_URL'] = $this->config->get('asaas_config_CONFIG_ASAAS_URL') ?? '';

        $data['action'] = $this->url->link('module/asaas_config', 'user_token=' . $data['user_token']);
        $data['cancel'] = $this->url->link('setting/extension', 'user_token=' . $data['user_token'] . '&type=module');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('module/asaas_config', $data));
    }

    protected function validate(): bool {
        if (!$this->user->hasPermission('modify', 'module/asaas_config')) {
            $this->session->data['error_warning'] = $this->language->get('error_permission');
            return false;
        }
        return true;
    }
}
