<?php
namespace Opencart\Admin\Model\Customer;
/**
 * Class Custom Field
 *
 * @example $customer_model = $this->model_customer_customer;
 *
 * Can be called from $this->load->model('customer/custom_field');
 *
 * @package Opencart\Admin\Model\Customer
 */
class CustomField extends \Opencart\System\Engine\Model {
	/**
	 * Add Custom Field
	 *
	 * @param array<string, mixed> $data array of data
	 *
	 * @return int returns the primary key of the new custom field record
	 */
	public function addCustomField(array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "custom_field` SET `type` = '" . $this->db->escape((string)$data['type']) . "', `value` = '" . $this->db->escape((string)$data['value']) . "', `validation` = '" . $this->db->escape((string)$data['validation']) . "', `location` = '" . $this->db->escape((string)$data['location']) . "', `status` = '" . (bool)($data['status'] ?? 0) . "', `sort_order` = '" . (int)$data['sort_order'] . "'");

		$custom_field_id = $this->db->getLastId();

		foreach ($data['custom_field_description'] as $language_id => $custom_field_description) {
			$this->addDescription($custom_field_id, $language_id, $custom_field_description);
		}

		if (isset($data['custom_field_customer_group'])) {
			foreach ($data['custom_field_customer_group'] as $custom_field_customer_group) {
				if (isset($custom_field_customer_group['customer_group_id'])) {
					$this->addCustomerGroup($custom_field_id, $custom_field_customer_group);
				}
			}
		}

		if (isset($data['custom_field_value'])) {
			foreach ($data['custom_field_value'] as $custom_field_value) {
				$this->addValue($custom_field_id, $custom_field_value);
			}
		}

		return $custom_field_id;
	}

	/**
	 * Edit Custom Field
	 *
	 * @param int                  $custom_field_id primary key of the custom field record
	 * @param array<string, mixed> $data            array of data
	 *
	 * @return void
	 */
	public function editCustomField(int $custom_field_id, array $data): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "custom_field` SET `type` = '" . $this->db->escape((string)$data['type']) . "', `value` = '" . $this->db->escape((string)$data['value']) . "', `validation` = '" . $this->db->escape((string)$data['validation']) . "', `location` = '" . $this->db->escape((string)$data['location']) . "', `status` = '" . (bool)($data['status'] ?? 0) . "', `sort_order` = '" . (int)$data['sort_order'] . "' WHERE `custom_field_id` = '" . (int)$custom_field_id . "'");

		$this->deleteDescriptions($custom_field_id);

		foreach ($data['custom_field_description'] as $language_id => $custom_field_description) {
			$this->addDescription($custom_field_id, $language_id, $custom_field_description);
		}

		$this->deleteCustomerGroups($custom_field_id);

		if (isset($data['custom_field_customer_group'])) {
			foreach ($data['custom_field_customer_group'] as $custom_field_customer_group) {
				if (isset($custom_field_customer_group['customer_group_id'])) {
					$this->addCustomerGroup($custom_field_id, $custom_field_customer_group);
				}
			}
		}

		$this->deleteValues($custom_field_id);

		if (isset($data['custom_field_value'])) {
			foreach ($data['custom_field_value'] as $custom_field_value) {
				$this->addValue($custom_field_id, $custom_field_value);
			}
		}
	}

	/**
	 * Delete Custom Field
	 *
	 * @param int $custom_field_id primary key of the custom field record
	 *
	 * @return void
	 */
	public function deleteCustomField(int $custom_field_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "custom_field` WHERE `custom_field_id` = '" . (int)$custom_field_id . "'");

		$this->deleteDescriptions($custom_field_id);
		$this->deleteCustomerGroups($custom_field_id);
		$this->deleteValues($custom_field_id);
	}

	/**
	 * Get Custom Field
	 *
	 * @param int $custom_field_id primary key of the custom field record
	 *
	 * @return array<string, mixed> custom field record that has custom field ID
	 */
	public function getCustomField(int $custom_field_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "custom_field` `cf` LEFT JOIN `" . DB_PREFIX . "custom_field_description` `cfd` ON (`cf`.`custom_field_id` = `cfd`.`custom_field_id`) WHERE `cf`.`custom_field_id` = '" . (int)$custom_field_id . "' AND `cfd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	/**
	 * Get Custom Fields
	 *
	 * @param array<string, mixed> $data array of filters
	 *
	 * @return array<int, array<string, mixed>> custom field records
	 */
	public function getCustomFields(array $data = []): array {
		if (empty($data['filter_customer_group_id'])) {
			$sql = "SELECT * FROM `" . DB_PREFIX . "custom_field` `cf` LEFT JOIN `" . DB_PREFIX . "custom_field_description` `cfd` ON (`cf`.`custom_field_id` = `cfd`.`custom_field_id`) WHERE `cfd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "'";
		} else {
			$sql = "SELECT * FROM `" . DB_PREFIX . "custom_field_customer_group` `cfcg` LEFT JOIN `" . DB_PREFIX . "custom_field` `cf` ON (`cfcg`.`custom_field_id` = `cf`.`custom_field_id`) LEFT JOIN `" . DB_PREFIX . "custom_field_description` `cfd` ON (`cf`.`custom_field_id` = `cfd`.`custom_field_id`) WHERE `cfd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "'";
		}

		if (!empty($data['filter_name'])) {
			$sql .= " AND LCASE(`cfd`.`name`) LIKE '" . $this->db->escape(oc_strtolower($data['filter_name']) . '%') . "'";
		}

		if (isset($data['filter_status'])) {
			$sql .= " AND `cf`.`status` = '" . (bool)$data['filter_status'] . "'";
		}

		if (isset($data['filter_location'])) {
			$sql .= " AND `cf`.`location` = '" . $this->db->escape((string)$data['filter_location']) . "'";
		}

		if (!empty($data['filter_customer_group_id'])) {
			$sql .= " AND `cfcg`.`customer_group_id` = '" . (int)$data['filter_customer_group_id'] . "'";
		}

		$sort_data = [
			'cfd.name',
			'cf.type',
			'cf.location',
			'cf.status',
			'cf.sort_order'
		];

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY `cfd`.`name`";
		}

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

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * Get Total Custom Fields
	 *
	 * @return int total number of custom field records
	 */
	public function getTotalCustomFields(): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "custom_field`");

		return (int)$query->row['total'];
	}

	/**
	 * Add Description
	 *
	 * @param int                  $custom_field_id primary key of the custom field record
	 * @param int                  $language_id     primary key of the language record
	 * @param array<string, mixed> $data            array of data
	 *
	 * @return void
	 */
	public function addDescription(int $custom_field_id, int $language_id, array $data): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "custom_field_description` SET `custom_field_id` = '" . (int)$custom_field_id . "', `language_id` = '" . (int)$language_id . "', `name` = '" . $this->db->escape($data['name']) . "'");
	}

	/**
	 * Delete Descriptions
	 *
	 * @param int $custom_field_id primary key of the custom field record
	 *
	 * @return void
	 */
	public function deleteDescriptions(int $custom_field_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "custom_field_description` WHERE `custom_field_id` = '" . (int)$custom_field_id . "'");
	}

	/**
	 * Delete Descriptions By Language ID
	 *
	 * @param int $language_id primary key of the language record
	 *
	 * @return void
	 */
	public function deleteDescriptionsByLanguageId(int $language_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "custom_field_description` WHERE `language_id` = '" . (int)$language_id . "'");
	}

	/**
	 * Get Descriptions
	 *
	 * @param int $custom_field_id primary key of the custom field record
	 *
	 * @return array<int, array<string, string>> description records that have custom field ID
	 */
	public function getDescriptions(int $custom_field_id): array {
		$custom_field_data = [];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "custom_field_description` WHERE `custom_field_id` = '" . (int)$custom_field_id . "'");

		foreach ($query->rows as $result) {
			$custom_field_data[$result['language_id']] = $result;
		}

		return $custom_field_data;
	}

	/**
	 * Get Descriptions By Language ID
	 *
	 * @param int $language_id primary key of the language record
	 *
	 * @return array<int, array<string, string>> description records that have information ID
	 */
	public function getDescriptionsByLanguageId(int $language_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "custom_field_description` WHERE `language_id` = '" . (int)$language_id . "'");

		return $query->rows;
	}

	/**
	 * Add Customer Group
	 *
	 * @param int                  $custom_field_id primary key of the custom field record
	 * @param array<string, mixed> $data            array of data
	 *
	 * @return void
	 */
	public function addCustomerGroup(int $custom_field_id, array $data): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "custom_field_customer_group` SET `custom_field_id` = '" . (int)$custom_field_id . "', `customer_group_id` = '" . (int)$data['customer_group_id'] . "', `required` = '" . (int)(isset($data['required']) ? 1 : 0) . "'");
	}

	/**
	 * Delete Customer Groups
	 *
	 * @param int $custom_field_id primary key of the custom field record
	 *
	 * @return void
	 */
	public function deleteCustomerGroups(int $custom_field_id): void {
		$this->db->query("delete FROM `" . DB_PREFIX . "custom_field_customer_group` WHERE `custom_field_id` = '" . (int)$custom_field_id . "'");
	}

	/**
	 * Get Customer Groups
	 *
	 * @param int $custom_field_id primary key of the custom field record
	 *
	 * @return array<int, array<string, mixed>> customer group records that have custom field ID
	 */
	public function getCustomerGroups(int $custom_field_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "custom_field_customer_group` WHERE `custom_field_id` = '" . (int)$custom_field_id . "'");

		return $query->rows;
	}

	/**
	 * Add Value
	 *
	 * @param int                  $custom_field_id primary key of the custom field record
	 * @param array<string, mixed> $data            array of data
	 *
	 * @return int returns the primary key of the new custom field value record
	 */
	public function addValue(int $custom_field_id, array $data): int {
		if ($data['custom_field_value_id']) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "custom_field_value` SET `custom_field_value_id` = '" . (int)$data['custom_field_value_id'] . "', `custom_field_id` = '" . (int)$custom_field_id . "', `sort_order` = '" . (int)$data['sort_order'] . "'");
		} else {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "custom_field_value` SET `custom_field_id` = '" . (int)$custom_field_id . "', `sort_order` = '" . (int)$data['sort_order'] . "'");
		}

		$custom_field_value_id = $this->db->getLastId();

		foreach ($data['custom_field_value_description'] as $language_id => $custom_field_value_description) {
			$this->addValueDescription($custom_field_value_id, $custom_field_id, $language_id, $custom_field_value_description);
		}

		return $custom_field_value_id;
	}

	/**
	 * Delete Values
	 *
	 * @param int $custom_field_id primary key of the custom field record
	 *
	 * @return void
	 */
	public function deleteValues(int $custom_field_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "custom_field_value` WHERE `custom_field_id` = '" . (int)$custom_field_id . "'");

		$this->deleteValueDescriptions($custom_field_id);
	}

	/**
	 * Get Value
	 *
	 * @param int $custom_field_value_id primary key of the custom field value record
	 *
	 * @return array<string, mixed> value record that has custom field value ID
	 */
	public function getValue(int $custom_field_value_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "custom_field_value` `cfv` LEFT JOIN `" . DB_PREFIX . "custom_field_value_description` `cfvd` ON (`cfv`.`custom_field_value_id` = `cfvd`.`custom_field_value_id`) WHERE `cfv`.`custom_field_value_id` = '" . (int)$custom_field_value_id . "' AND `cfvd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	/**
	 * Get Values
	 *
	 * @param int $custom_field_id primary key of the custom field record
	 *
	 * @return array<int, array<string, mixed>> value records that have custom field ID
	 */
	public function getValues(int $custom_field_id): array {
		$custom_field_value_data = [];

		$custom_field_value_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "custom_field_value` `cfv` LEFT JOIN `" . DB_PREFIX . "custom_field_value_description` `cfvd` ON (`cfv`.`custom_field_value_id` = `cfvd`.`custom_field_value_id`) WHERE `cfv`.`custom_field_id` = '" . (int)$custom_field_id . "' AND `cfvd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "' ORDER BY `cfv`.`sort_order` ASC");

		foreach ($custom_field_value_query->rows as $custom_field_value) {
			$custom_field_value_data[$custom_field_value['custom_field_value_id']] = $custom_field_value;
		}

		return $custom_field_value_data;
	}

	/**
	 * Add Value Description
	 *
	 * @param int                  $custom_field_value_id          primary key of the custom field value record
	 * @param int                  $custom_field_id                primary key of the custom field record
	 * @param int                  $language_id                    primary key of the language record
	 * @param array<string, mixed> $custom_field_value_description array of data
	 *
	 * @return void
	 */
	public function addValueDescription(int $custom_field_value_id, int $custom_field_id, int $language_id, array $custom_field_value_description): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "custom_field_value_description` SET `custom_field_value_id` = '" . (int)$custom_field_value_id . "', `language_id` = '" . (int)$language_id . "', `custom_field_id` = '" . (int)$custom_field_id . "', `name` = '" . $this->db->escape($custom_field_value_description['name']) . "'");
	}

	/**
	 * Delete Value Descriptions
	 *
	 * @param int $custom_field_id primary key of the custom field record
	 *
	 * @return void
	 */
	public function deleteValueDescriptions(int $custom_field_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "custom_field_value_description` WHERE `custom_field_id` = '" . (int)$custom_field_id . "'");
	}

	/**
	 * Delete Value Descriptions By Language ID
	 *
	 * @param int $language_id primary key of the language record
	 *
	 * @return void
	 */
	public function deleteValueDescriptionsByLanguageId(int $language_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "custom_field_value_description` WHERE `language_id` = '" . (int)$language_id . "'");
	}

	/**
	 * Get Value Descriptions
	 *
	 * @param int $custom_field_id primary key of the custom field record
	 *
	 * @return array<int, array<string, mixed>> value description records that have custom field ID
	 */
	public function getValueDescriptions(int $custom_field_id): array {
		$custom_field_value_data = [];

		$custom_field_value_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "custom_field_value` WHERE `custom_field_id` = '" . (int)$custom_field_id . "'");

		foreach ($custom_field_value_query->rows as $custom_field_value) {
			$custom_field_value_description_data = [];

			$custom_field_value_description_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "custom_field_value_description` WHERE `custom_field_value_id` = '" . (int)$custom_field_value['custom_field_value_id'] . "'");

			foreach ($custom_field_value_description_query->rows as $custom_field_value_description) {
				$custom_field_value_description_data[$custom_field_value_description['language_id']] = ['name' => $custom_field_value_description['name']];
			}

			$custom_field_value_data[] = ['custom_field_value_description' => $custom_field_value_description_data] + $custom_field_value;
		}

		return $custom_field_value_data;
	}

	/**
	 * Get Value Descriptions By Language ID
	 *
	 * @param int $language_id primary key of the language record
	 *
	 * @return array<int, array<string, string>> value description records that have language ID
	 */
	public function getValueDescriptionsByLanguageId(int $language_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "custom_field_value_description` WHERE `language_id` = '" . (int)$language_id . "'");

		return $query->rows;
	}
}
