<?php
namespace Opencart\Catalog\Model\Localisation;

class Payment extends \Opencart\System\Engine\Model {
	public function getAllowedPaymentIdsByService(int $service_id): array {
		$query = $this->db->query("
			SELECT p.description 
			FROM farmax_shop.store s
			INNER JOIN farmax_shop.service se ON s.id = se.storeId
			INNER JOIN farmax_shop.payment p ON se.id = p.serviceId
			WHERE s.storeName = DATABASE() AND se.id = '" . (int)$service_id . "'
		");

		// `description` is assumed to match the payment method `code` (e.g., 'cod', 'bank_transfer')
		return array_column($query->rows, 'description');
	}
}
