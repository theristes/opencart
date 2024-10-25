<?php
namespace Opencart\Catalog\Model\Account;
/**
 * Class Address
 *
 * @package Opencart\Catalog\Model\Account
 */
class Address extends \Opencart\System\Engine\Model {
	/**
	 * Add Address
	 *
	 * @param int                  $customer_id
	 * @param array<string, mixed> $data
	 *
	 * @return int
	 */
	public function addAddress(int $customer_id, array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "address` SET `customer_id` = '" . (int)$customer_id . "', `firstname` = '" . $this->db->escape($data['firstname']) . "', `lastname` = '" . $this->db->escape($data['lastname']) . "', `company` = '" . $this->db->escape($data['company']) . "', `address_1` = '" . $this->db->escape($data['address_1']) . "', `address_2` = '" . $this->db->escape($data['address_2']) . "', `postcode` = '" . $this->db->escape($data['postcode']) . "', `city` = '" . $this->db->escape($data['city']) . "', `zone_id` = '" . (int)$data['zone_id'] . "', `country_id` = '" . (int)$data['country_id'] . "', `custom_field` = '" . $this->db->escape(isset($data['custom_field']) ? json_encode($data['custom_field']) : '') . "', `default` = '" . (isset($data['default']) ? (int)$data['default'] : 0) . "'");

		$address_id = $this->db->getLastId();

		if (!empty($data['default'])) {
			$this->db->query("UPDATE `" . DB_PREFIX . "address` SET `default` = '0' WHERE `address_id` != '" . (int)$address_id . "' AND `customer_id` = '" . (int)$customer_id . "'");
		}

		return $address_id;
	}

	/**
	 * Edit Address
	 *
	 * @param int                  $customer_id
	 * @param int                  $address_id
	 * @param array<string, mixed> $data
	 *
	 * @return void
	 */
	public function editAddress(int $customer_id, int $address_id, array $data): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "address` SET `firstname` = '" . $this->db->escape($data['firstname']) . "', `lastname` = '" . $this->db->escape($data['lastname']) . "', `company` = '" . $this->db->escape($data['company']) . "', `address_1` = '" . $this->db->escape($data['address_1']) . "', `address_2` = '" . $this->db->escape($data['address_2']) . "', `postcode` = '" . $this->db->escape($data['postcode']) . "', `city` = '" . $this->db->escape($data['city']) . "', `zone_id` = '" . (int)$data['zone_id'] . "', `country_id` = '" . (int)$data['country_id'] . "', `custom_field` = '" . $this->db->escape(isset($data['custom_field']) ? json_encode($data['custom_field']) : '') . "', `default` = '" . (isset($data['default']) ? (int)$data['default'] : 0) . "' WHERE `address_id` = '" . (int)$address_id . "' AND `customer_id` = '" . (int)$customer_id . "'");

		if (!empty($data['default'])) {
			$this->db->query("UPDATE `" . DB_PREFIX . "address` SET `default` = '0' WHERE `address_id` != '" . (int)$address_id . "' AND `customer_id` = '" . (int)$customer_id . "'");
		}
	}

	/**
	 * Delete Address
	 *
	 * @param int $customer_id
	 * @param int $address_id
	 *
	 * @return void
	 */
	public function deleteAddress(int $customer_id, int $address_id = 0): void {
		$sql = "DELETE FROM `" . DB_PREFIX . "address` WHERE `customer_id` = '" . (int)$customer_id . "'";

		if ($address_id) {
			$sql .= " AND `address_id` = '" . (int)$address_id . "'";
		}

		$this->db->query($sql);
	}

	/**
	 * Get Address
	 *
	 * @param int $customer_id
	 * @param int $address_id
	 *
	 * @return array<string, mixed>
	 */
	public function getAddress(int $customer_id, int $address_id): array {
		$address_query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "address` WHERE `address_id` = '" . (int)$address_id . "' AND `customer_id` = '" . (int)$customer_id . "'");

		if ($address_query->num_rows) {
			$this->load->model('localisation/country');

			$country_info = $this->model_localisation_country->getCountry($address_query->row['country_id']);

			if ($country_info) {
				$country = $country_info['name'];
				$iso_code_2 = $country_info['iso_code_2'];
				$iso_code_3 = $country_info['iso_code_3'];
				$address_format_id = $country_info['address_format_id'];
			} else {
				$country = '';
				$iso_code_2 = '';
				$iso_code_3 = '';
				$address_format_id = 0;
			}

			$this->load->model('localisation/address_format');

			$address_format_info = $this->model_localisation_address_format->getAddressFormat($address_format_id);

			if ($address_format_info) {
				$address_format = $address_format_info['address_format'];
			} else {
				$address_format = '';
			}

			$this->load->model('localisation/zone');

			$zone_info = $this->model_localisation_zone->getZone($address_query->row['zone_id']);

			if ($zone_info) {
				$zone = $zone_info['name'];
				$zone_code = $zone_info['code'];
			} else {
				$zone = '';
				$zone_code = '';
			}

			return [
				'zone'           => $zone,
				'zone_code'      => $zone_code,
				'country'        => $country,
				'iso_code_2'     => $iso_code_2,
				'iso_code_3'     => $iso_code_3,
				'address_format' => $address_format,
				'custom_field'   => json_decode($address_query->row['custom_field'], true)
			] + $address_query->row;
		} else {
			return [];
		}
	}

	/**
	 * Get Addresses
	 *
	 * @param int $customer_id
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getAddresses(int $customer_id): array {
		$address_data = [];

		$this->load->model('localisation/country');
		$this->load->model('localisation/address_format');
		$this->load->model('localisation/zone');

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "address` WHERE `customer_id` = '" . (int)$customer_id . "'");

		foreach ($query->rows as $result) {
			$country_info = $this->model_localisation_country->getCountry($result['country_id']);

			if ($country_info) {
				$country = $country_info['name'];
				$iso_code_2 = $country_info['iso_code_2'];
				$iso_code_3 = $country_info['iso_code_3'];
				$address_format_id = $country_info['address_format_id'];
			} else {
				$country = '';
				$iso_code_2 = '';
				$iso_code_3 = '';
				$address_format_id = 0;
			}

			$address_format_info = $this->model_localisation_address_format->getAddressFormat($address_format_id);

			if ($address_format_info) {
				$address_format = $address_format_info['address_format'];
			} else {
				$address_format = '';
			}

			$zone_info = $this->model_localisation_zone->getZone($result['zone_id']);

			if ($zone_info) {
				$zone = $zone_info['name'];
				$zone_code = $zone_info['code'];
			} else {
				$zone = '';
				$zone_code = '';
			}

			$address_data[$result['address_id']] = [
				'zone'           => $zone,
				'zone_code'      => $zone_code,
				'country'        => $country,
				'iso_code_2'     => $iso_code_2,
				'iso_code_3'     => $iso_code_3,
				'address_format' => $address_format,
				'custom_field'   => json_decode($result['custom_field'], true)
			] + $result;
		}

		return $address_data;
	}

	/**
	 * Get Total Addresses
	 *
	 * @param int $customer_id
	 *
	 * @return int
	 */
	public function getTotalAddresses(int $customer_id): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "address` WHERE `customer_id` = '" . (int)$customer_id . "'");

		return (int)$query->row['total'];
	}
}
