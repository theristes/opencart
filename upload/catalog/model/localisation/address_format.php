<?php
namespace Opencart\Catalog\Model\Localisation;
/**
 * Class Address Format
 *
 * @package Opencart\Admin\Model\Localisation
 */
class AddressFormat extends \Opencart\System\Engine\Model {
	/**
	 * Get Address Format
	 *
	 * @param int $address_format_id
	 *
	 * @return array<string, mixed>
	 */
	public function getAddressFormat(int $address_format_id): array {
		$query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "address_format` WHERE `address_format_id` = '" . (int)$address_format_id . "'");

		return $query->row;
	}
}
