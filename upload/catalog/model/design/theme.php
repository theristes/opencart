<?php
namespace Opencart\Application\Model\Design;
class Theme extends \Opencart\System\Engine\Model {
	public function getTheme($route) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "theme` WHERE `store_id` = '" . (int)$this->config->get('config_store_id') . "' AND `route` = '" . $this->db->escape($route) . "'");

		return $query->row;
	}
}