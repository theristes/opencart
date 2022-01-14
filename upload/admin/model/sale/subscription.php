<?php
namespace Opencart\Admin\Model\Sale;
class Subscription extends \Opencart\System\Engine\Model {
	public function getSubscription(int $subscription_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "subscription` WHERE `subscription_id` = '" . (int)$subscription_id . "'");

		return $query->row;
	}

	public function getSubscriptions(array $data): array {
		$sql = "SELECT `s`.`subscription_id`, `s`.`order_id`, `s`.`reference`, CONCAT(o.`firstname`, ' ', o.`lastname`) AS customer, (SELECT ss.`name` FROM `" . DB_PREFIX . "subscription_status` ss WHERE ss.`subscription_status_id` = s.`subscription_status_id` AND ss.`language_id` = '" . (int)$this->config->get('config_language_id') . "') AS subscription_status, `s`.`date_added`, `s`.`date_modified` FROM `" . DB_PREFIX . "subscription` `s` LEFT JOIN `" . DB_PREFIX . "order` `o` ON (`s`.`order_id` = `o`.`order_id`)";

		$implode = [];

		if (!empty($data['filter_subscription_id'])) {
			$implode[] = "`s`.`subscription_id` = '" . (int)$data['filter_subscription_id'] . "'";
		}

		if (!empty($data['filter_order_id'])) {
			$implode[] = "`s`.`order_id` = '" . (int)$data['filter_order_id'] . "'";
		}

		if (!empty($data['filter_reference'])) {
			$implode[] = "`s`.`reference` LIKE '" . $this->db->escape((string)$data['filter_reference']) . "%'";
		}

		if (!empty($data['filter_customer'])) {
			$implode[] = "CONCAT(o.`firstname`, ' ', o.`lastname`) LIKE '" . $this->db->escape((string)$data['filter_customer']) . "%'";
		}

		if (!empty($data['filter_subscription_status_id'])) {
			$implode[] = "`ss`.`subscription_status_id` = '" . (int)$data['filter_subscription_status_id'] . "'";
		}

		if (!empty($data['filter_date_added'])) {
			$implode[] = "DATE(`s`.`date_added`) = DATE('" . $this->db->escape((string)$data['filter_date_added']) . "')";
		}

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		$sort_data = [
			's.subscription_id',
			's.order_id',
			's.reference',
			'customer',
			's.subscription_status',
			's.date_added'
		];

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY `s`.`subscription_id`";
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

	public function getTotalSubscriptions(array $data = []): int {
		$sql = "SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "subscription` `s` LEFT JOIN `" . DB_PREFIX . "order` `o` ON (`s`.`order_id` = o.`order_id`)";

		$implode = [];

		if (!empty($data['filter_subscription_id'])) {
			$implode[] .= "`s`.`subscription_id` = '" . (int)$data['filter_subscription_id'] . "'";
		}

		if (!empty($data['filter_order_id'])) {
			$implode[] .= "`s`.`order_id` = '" . (int)$data['filter_order_id'] . "'";
		}

		if (!empty($data['filter_payment_reference'])) {
			$implode[] .= "`s`.`reference` LIKE '" . $this->db->escape((string)$data['filter_reference']) . "%'";
		}

		if (!empty($data['filter_customer'])) {
			$implode[] .= "CONCAT(o.`firstname`, ' ', o.`lastname`) LIKE '" . $this->db->escape((string)$data['filter_customer']) . "%'";
		}

		if (!empty($data['filter_subscription_status_id'])) {
			$implode[] .= "`ss`.`subscription_status_id` = '" . (int)$data['filter_subscription_status_id'] . "'";
		}

		if (!empty($data['filter_date_added'])) {
			$implode[] .= "DATE(`s`.`date_added`) = DATE('" . $this->db->escape((string)$data['filter_date_added']) . "')";
		}

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		$query = $this->db->query($sql);

		return (int)$query->row['total'];
	}

	public function getTotalSubscriptionsBySubscriptionStatusId(int $subscription_status_id): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "subscription` WHERE `subscription_status_id` = '" . (int)$subscription_status_id . "'");

		return (int)$query->row['total'];
	}

	public function getTransactions(int $subscription_id): array {
		$transaction_data = [];

		$query = $this->db->query("SELECT `order_id`, `amount`, `date_added` FROM `" . DB_PREFIX . "subscription_transaction` WHERE `subscription_id` = '" . (int)$subscription_id . "' ORDER BY `date_added` DESC");

		foreach ($query->rows as $result) {
			$transaction_data[] = [
				'date_added' => $result['date_added'],
				'amount'     => $result['amount'],
				'order_id'   => $result['order_id']
			];
		}

		return $transaction_data;
	}

	public function getTotalTransactions(int $subscription_id): array {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "subscription_transaction` WHERE `subscription_id` = '" . (int)$subscription_id . "'");

		return (int)$query->row['total'];
	}

	public function getHistories(int $subscription_id, int $start = 0, int $limit = 10) {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 10;
		}

		$query = $this->db->query("SELECT sh.`date_added`, ss.`name` AS status, sh.`comment`, sh.`notify` FROM `" . DB_PREFIX . "subscription_history` sh LEFT JOIN `" . DB_PREFIX . "subscription_status` ss ON sh.`subscription_status_id` = ss.`subscription_status_id` WHERE sh.`subscription_id` = '" . (int)$subscription_id . "' AND ss.`language_id` = '" . (int)$this->config->get('config_language_id') . "' ORDER BY sh.`date_added` DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	public function getTotalHistories(int $subscription_id): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "subscription_history` WHERE `subscription_id` = '" . (int)$subscription_id . "'");

		return (int)$query->row['total'];
	}

	public function getTotalHistoriesBySubscriptionStatusId(int $subscription_status_id): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "subscription_history` WHERE `subscription_status_id` = '" . (int)$subscription_status_id . "'");

		return (int)$query->row['total'];
	}
}
