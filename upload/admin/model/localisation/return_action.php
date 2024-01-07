<?php
namespace Opencart\Admin\Model\Localisation;
/**
 * Class ReturnAction
 *
 * @package Opencart\Admin\Model\Localisation
 */
class ReturnAction extends \Opencart\System\Engine\Model {
	/**
	 * Add Return Action
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return int
	 */
	public function addReturnAction(array $data): int {
		foreach ($data['return_action'] as $language_id => $value) {
			if (isset($return_action_id)) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "return_action` SET `return_action_id` = '" . (int)$return_action_id . "', `language_id` = '" . (int)$language_id . "', `name` = '" . $this->db->escape($value['name']) . "'");
			} else {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "return_action` SET `language_id` = '" . (int)$language_id . "', `name` = '" . $this->db->escape($value['name']) . "'");

				$return_action_id = $this->db->getLastId();
			}
		}

		$this->cache->delete('return_action');

		return $return_action_id;
	}

	/**
	 * Edit Return Action
	 *
	 * @param int                  $return_action_id
	 * @param array<string, mixed> $data
	 *
	 * @return void
	 */
	public function editReturnAction(int $return_action_id, array $data): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "return_action` WHERE `return_action_id` = '" . (int)$return_action_id . "'");

		foreach ($data['return_action'] as $language_id => $value) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "return_action` SET `return_action_id` = '" . (int)$return_action_id . "', `language_id` = '" . (int)$language_id . "', `name` = '" . $this->db->escape($value['name']) . "'");
		}

		$this->cache->delete('return_action');
	}

	/**
	 * Delete Return Action
	 *
	 * @param int $return_action_id
	 *
	 * @return void
	 */
	public function deleteReturnAction(int $return_action_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "return_action` WHERE `return_action_id` = '" . (int)$return_action_id . "'");

		$this->cache->delete('return_action');
	}

	/**
	 * Get Return Action
	 *
	 * @param int $return_action_id
	 *
	 * @return array<string, mixed>
	 */
	public function getReturnAction(int $return_action_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "return_action` WHERE `return_action_id` = '" . (int)$return_action_id . "' AND `language_id` = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	/**
	 * Get Return Actions
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getReturnActions(array $data = []): array {
		$sql = "SELECT * FROM `" . DB_PREFIX . "return_action` WHERE `language_id` = '" . (int)$this->config->get('config_language_id') . "' ORDER BY `name`";

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$key = md5($sql);

		$return_action_data = $this->cache->get('return_action.' . $key);

		if (!$return_action_data) {
			$query = $this->db->query($sql);

			$return_action_data = $query->rows;

			$this->cache->set('return_action.' . $key, $return_action_data);
		}

		return $return_action_data;
	}

	/**
	 * Get Descriptions
	 *
	 * @param int $return_action_id
	 *
	 * @return array<int, array<string, string>>
	 */
	public function getDescriptions(int $return_action_id): array {
		$return_action_data = [];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "return_action` WHERE `return_action_id` = '" . (int)$return_action_id . "'");

		foreach ($query->rows as $result) {
			$return_action_data[$result['language_id']] = ['name' => $result['name']];
		}

		return $return_action_data;
	}

	/**
	 * Get Total Return Actions
	 *
	 * @return int
	 */
	public function getTotalReturnActions(): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "return_action` WHERE `language_id` = '" . (int)$this->config->get('config_language_id') . "'");

		return (int)$query->row['total'];
	}
}
