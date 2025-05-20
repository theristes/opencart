<?php
namespace Opencart\Catalog\Model\Localisation;

class Service extends \Opencart\System\Engine\Model {
	public function getServices(): array {
		$sql = "
			SELECT se.id, se.code, CONCAT(se.addressStreet, ', ', se.addressNumber, ' - ', se.addressNeighborhood, ', ', se.addressCity) AS name
			FROM farmax_shop.store s
			INNER JOIN farmax_shop.service se ON se.storeId = s.id
			WHERE s.storeName = DATABASE()
		";

		$query = $this->db->query($sql);
		return $query->rows;
	}
}

