<?php

namespace Grocy\Services;

class OrdersService extends BaseService
{
	const STATUS_PENDING = 'pending';
	const STATUS_ORDERED = 'ordered';
	const STATUS_DELIVERED = 'delivered';
	const STATUS_CANCELLED = 'cancelled';

	public function GetOrders()
	{
		return $this->DB->orders_current()->orderBy('ordered_date', 'DESC');
	}

	public function GetOrder(int $orderId)
	{
		$order = $this->DB->orders_current($orderId);
		if ($order === null)
		{
			throw new \Exception('Order does not exist');
		}

		$order->items = $this->DB->order_items()->where('order_id', $orderId)->orderBy('id');

		return $order;
	}

	public function CreateOrder(array $orderData, array $items)
	{
		if (empty($items))
		{
			throw new \Exception('An order requires at least one item');
		}

		foreach ($items as $item)
		{
			$this->ValidateItem($item);
		}

		$orderRow = $this->DB->orders()->createRow([
			'shopping_location_id' => $orderData['shopping_location_id'] ?? null,
			'ordered_date' => $orderData['ordered_date'] ?? date('Y-m-d'),
			'arrive_date' => $orderData['arrive_date'] ?? null,
			'status' => $orderData['status'] ?? self::STATUS_PENDING,
			'note' => $orderData['note'] ?? null
		]);
		$orderRow->save();
		$orderId = $this->DB->lastInsertId();

		foreach ($items as $item)
		{
			$this->AddItem($orderId, $item);
		}

		if (($orderData['status'] ?? self::STATUS_PENDING) === self::STATUS_DELIVERED)
		{
			$this->BookStockForOrder($orderId);
		}

		return $orderId;
	}

	public function UpdateOrder(int $orderId, array $orderData)
	{
		$orderRow = $this->DB->orders($orderId);
		if ($orderRow === null)
		{
			throw new \Exception('Order does not exist');
		}

		$wasDelivered = $orderRow->status === self::STATUS_DELIVERED;

		$orderRow->update(array_intersect_key($orderData, array_flip([
			'shopping_location_id', 'ordered_date', 'arrive_date', 'status', 'note'
		])));

		if (!$wasDelivered && $orderRow->status === self::STATUS_DELIVERED)
		{
			$this->BookStockForOrder($orderId);
		}
	}

	public function DeleteOrder(int $orderId)
	{
		$orderRow = $this->DB->orders($orderId);
		if ($orderRow === null)
		{
			throw new \Exception('Order does not exist');
		}

		$this->DB->order_items()->where('order_id', $orderId)->delete();
		$orderRow->delete();
	}

	public function AddItem(int $orderId, array $item)
	{
		if ($this->DB->orders($orderId) === null)
		{
			throw new \Exception('Order does not exist');
		}

		$this->ValidateItem($item);

		$itemRow = $this->DB->order_items()->createRow([
			'order_id' => $orderId,
			'product_id' => $item['product_id'],
			'amount' => $item['amount'],
			'qu_id' => $item['qu_id'] ?? null,
			'price' => $item['price'] ?? null,
			'note' => $item['note'] ?? null
		]);
		$itemRow->save();

		return $this->DB->lastInsertId();
	}

	public function RemoveItem(int $orderItemId)
	{
		$itemRow = $this->DB->order_items($orderItemId);
		if ($itemRow === null)
		{
			throw new \Exception('Order item does not exist');
		}

		$itemRow->delete();
	}

	private function ValidateItem(array $item)
	{
		if (!isset($item['product_id']) || $this->DB->products($item['product_id']) === null)
		{
			throw new \Exception('A valid product_id is required for each order item');
		}

		if (!isset($item['amount']) || !is_numeric($item['amount']) || $item['amount'] <= 0)
		{
			throw new \Exception('A positive amount is required for each order item');
		}
	}

	private function BookStockForOrder(int $orderId)
	{
		$order = $this->DB->orders($orderId);
		$items = $this->DB->order_items()->where('order_id', $orderId);
		$purchasedDate = $order->arrive_date ?? date('Y-m-d');

		foreach ($items as $item)
		{
			StockService::GetInstance()->AddProduct(
				$item->product_id,
				floatval($item->amount),
				null,
				StockService::TRANSACTION_TYPE_PURCHASE,
				$purchasedDate,
				$item->price,
				null,
				$order->shopping_location_id
			);
		}
	}

	public function GetStats(?int $year = null)
	{
		$currentYear = $year ?? intval(date('Y'));
		$lastYear = $currentYear - 1;

		return [
			'year' => $currentYear,
			'last_year' => $lastYear,
			'ordered' => $this->GetOrderedAmountsByYear($currentYear, $lastYear),
			'consumed' => $this->GetConsumedAmountsByYear($currentYear, $lastYear)
		];
	}

	private function GetOrderedAmountsByYear(int $currentYear, int $lastYear)
	{
		$sql = "SELECT
				oi.product_id,
				p.name AS product_name,
				SUM(CASE WHEN CAST(strftime('%Y', o.ordered_date) AS INTEGER) = :currentYear THEN oi.amount ELSE 0 END) AS this_year_amount,
				SUM(CASE WHEN CAST(strftime('%Y', o.ordered_date) AS INTEGER) = :lastYear THEN oi.amount ELSE 0 END) AS last_year_amount,
				SUM(CASE WHEN CAST(strftime('%Y', o.ordered_date) AS INTEGER) = :currentYear THEN oi.amount * COALESCE(oi.price, 0) ELSE 0 END) AS this_year_value,
				SUM(CASE WHEN CAST(strftime('%Y', o.ordered_date) AS INTEGER) = :lastYear THEN oi.amount * COALESCE(oi.price, 0) ELSE 0 END) AS last_year_value
			FROM order_items oi
			JOIN orders o ON o.id = oi.order_id
			JOIN products p ON p.id = oi.product_id
			WHERE o.status != 'cancelled'
			GROUP BY oi.product_id
			HAVING this_year_amount > 0 OR last_year_amount > 0
			ORDER BY p.name COLLATE NOCASE";

		return $this->RunStatsQuery($sql, $currentYear, $lastYear);
	}

	private function GetConsumedAmountsByYear(int $currentYear, int $lastYear)
	{
		$sql = "SELECT
				sl.product_id,
				p.name AS product_name,
				SUM(CASE WHEN CAST(strftime('%Y', COALESCE(sl.used_date, sl.row_created_timestamp)) AS INTEGER) = :currentYear THEN sl.amount ELSE 0 END) AS this_year_amount,
				SUM(CASE WHEN CAST(strftime('%Y', COALESCE(sl.used_date, sl.row_created_timestamp)) AS INTEGER) = :lastYear THEN sl.amount ELSE 0 END) AS last_year_amount,
				0 AS this_year_value,
				0 AS last_year_value
			FROM stock_log sl
			JOIN products p ON p.id = sl.product_id
			WHERE sl.transaction_type = 'consume' AND sl.undone = 0
			GROUP BY sl.product_id
			HAVING this_year_amount > 0 OR last_year_amount > 0
			ORDER BY p.name COLLATE NOCASE";

		return $this->RunStatsQuery($sql, $currentYear, $lastYear);
	}

	private function RunStatsQuery(string $sql, int $currentYear, int $lastYear)
	{
		$pdo = DatabaseService::GetInstance()->GetDbConnectionRaw();
		$statement = $pdo->prepare($sql);
		$statement->execute([
			'currentYear' => $currentYear,
			'lastYear' => $lastYear
		]);

		return $statement->fetchAll(\PDO::FETCH_OBJ);
	}
}
