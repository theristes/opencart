<?php
namespace Opencart\Catalog\Controller\Extension\Opencart\Module;
/**
 * Class Banner
 *
 * @package Opencart\Catalog\Controller\Extension\Opencart\Module
 */
class Banner extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @param array<string, mixed> $setting
	 *
	 * @return string
	 */
	public function index(array $setting): string {
		static $module = 0;

		// Banner
		$this->load->model('design/banner');

		// Image
		$this->load->model('tool/image');

		$data['banners'] = [];

		$results = $this->model_design_banner->getBanner($setting['banner_id']);

		foreach ($results as $result) {
			if (is_bucket_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8'))) {
				$data['banners'][] = [
					'title' => $result['title'],
					'link'  => $result['link'],
					'image' => fetch_image(html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8')), 
					'width' => $setting['width'],
					'height'=> $setting['height']
				];
			}
		}

		if ($data['banners']) {
			$data['module'] = $module++;

			$data['effect'] = $setting['effect'];
			$data['controls'] = $setting['controls'];
			$data['indicators'] = $setting['indicators'];
			$data['items'] = $setting['items'];
			$data['interval'] = $setting['interval'];
			$data['width'] = $setting['width'];
			$data['height'] = $setting['height'];

			return $this->load->view('extension/opencart/module/banner', $data);
		} else {
			return '';
		}
	}
}
