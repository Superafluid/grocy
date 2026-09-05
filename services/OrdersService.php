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

	public function GetAllOrderItems(?int $orderId = null)
	{
		$sql = "SELECT
				oi.id,
				oi.order_id,
				oi.product_id,
				p.name AS product_name,
				oi.amount,
				oi.qu_id,
				COALESCE(qu.name, qu_stock.name, '') AS qu_name,
				oi.price,
				oi.note
			FROM order_items oi
			JOIN products p ON p.id = oi.product_id
			LEFT JOIN quantity_units qu ON qu.id = oi.qu_id
			LEFT JOIN quantity_units qu_stock ON qu_stock.id = p.qu_id_stock"
			. ($orderId !== null ? " WHERE oi.order_id = :orderId" : "") . "
			ORDER BY oi.id ASC";

		$pdo = DatabaseService::GetInstance()->GetDbConnectionRaw();
		$statement = $pdo->prepare($sql);
		$params = [];
		if ($orderId !== null)
		{
			$params['orderId'] = $orderId;
		}
		$statement->execute($params);

		return $statement->fetchAll(\PDO::FETCH_OBJ);
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

	public function GetStats(?int $year = null, ?int $productId = null)
	{
		$currentYear = $year ?? intval(date('Y'));
		$lastYear = $currentYear - 1;

		return [
			'year' => $currentYear,
			'last_year' => $lastYear,
			'product_id' => $productId,
			'ordered' => $this->GetOrderedAmountsByYear($currentYear, $lastYear, $productId),
			'consumed' => $this->GetConsumedAmountsByYear($currentYear, $lastYear, $productId)
		];
	}

	private function GetOrderedAmountsByYear(int $currentYear, int $lastYear, ?int $productId = null)
	{
		$sql = "SELECT
				oi.product_id,
				p.name AS product_name,
				qu.name AS qu_name,
				qu.name_plural AS qu_name_plural,
				SUM(CASE WHEN CAST(strftime('%Y', o.ordered_date) AS INTEGER) = :currentYear THEN oi.amount ELSE 0 END) AS this_year_amount,
				SUM(CASE WHEN CAST(strftime('%Y', o.ordered_date) AS INTEGER) = :lastYear THEN oi.amount ELSE 0 END) AS last_year_amount,
				SUM(CASE WHEN CAST(strftime('%Y', o.ordered_date) AS INTEGER) = :currentYear THEN oi.amount * COALESCE(oi.price, 0) ELSE 0 END) AS this_year_value,
				SUM(CASE WHEN CAST(strftime('%Y', o.ordered_date) AS INTEGER) = :lastYear THEN oi.amount * COALESCE(oi.price, 0) ELSE 0 END) AS last_year_value
			FROM order_items oi
			JOIN orders o ON o.id = oi.order_id
			JOIN products p ON p.id = oi.product_id
			JOIN quantity_units qu ON qu.id = p.qu_id_stock
			WHERE o.status != 'cancelled'"
			. ($productId !== null ? ' AND oi.product_id = :productId' : '') . "
			GROUP BY oi.product_id
			HAVING this_year_amount > 0 OR last_year_amount > 0
			ORDER BY p.name COLLATE NOCASE";

		return $this->RunStatsQuery($sql, $currentYear, $lastYear, $productId);
	}

	private function GetConsumedAmountsByYear(int $currentYear, int $lastYear, ?int $productId = null)
	{
		$sql = "SELECT
				sl.product_id,
				p.name AS product_name,
				qu.name AS qu_name,
				qu.name_plural AS qu_name_plural,
				SUM(CASE WHEN CAST(strftime('%Y', COALESCE(sl.used_date, sl.row_created_timestamp)) AS INTEGER) = :currentYear THEN -sl.amount ELSE 0 END) AS this_year_amount,
				SUM(CASE WHEN CAST(strftime('%Y', COALESCE(sl.used_date, sl.row_created_timestamp)) AS INTEGER) = :lastYear THEN -sl.amount ELSE 0 END) AS last_year_amount,
				0 AS this_year_value,
				0 AS last_year_value
			FROM stock_log sl
			JOIN products p ON p.id = sl.product_id
			JOIN quantity_units qu ON qu.id = p.qu_id_stock
			WHERE " . self::CONSUMPTION_LOG_WHERE . " AND sl.undone = 0"
			. ($productId !== null ? ' AND sl.product_id = :productId' : '') . "
			GROUP BY sl.product_id
			HAVING this_year_amount > 0 OR last_year_amount > 0
			ORDER BY p.name COLLATE NOCASE";

		return $this->RunStatsQuery($sql, $currentYear, $lastYear, $productId);
	}

	private function RunStatsQuery(string $sql, int $currentYear, int $lastYear, ?int $productId = null)
	{
		$pdo = DatabaseService::GetInstance()->GetDbConnectionRaw();
		$statement = $pdo->prepare($sql);

		$params = [
			'currentYear' => $currentYear,
			'lastYear' => $lastYear
		];
		if ($productId !== null)
		{
			$params['productId'] = $productId;
		}

		$statement->execute($params);

		return $statement->fetchAll(\PDO::FETCH_OBJ);
	}

	// A consumption is either an explicit "consume" booking, or an inventory count that lowered the stock amount
	private const CONSUMPTION_LOG_WHERE = "(sl.transaction_type = 'consume' OR (sl.transaction_type = 'inventory-correction' AND sl.amount < 0))";

	public function GetProductConsumptionStats(int $productId, int $months = 12)
	{
		$product = $this->DB->products($productId);
		if ($product === null)
		{
			throw new \Exception('Product does not exist');
		}
		$quStock = $this->DB->quantity_units($product->qu_id_stock);

		$pdo = DatabaseService::GetInstance()->GetDbConnectionRaw();

		$consumptionWhere = str_replace('sl.', '', self::CONSUMPTION_LOG_WHERE);

		$monthlySql = "SELECT
				strftime('%Y-%m', COALESCE(used_date, row_created_timestamp)) AS month,
				SUM(-amount) AS amount
			FROM stock_log
			WHERE product_id = :productId
				AND $consumptionWhere
				AND undone = 0
				AND COALESCE(used_date, row_created_timestamp) >= DATE(DATE('now', 'localtime'), :monthsAgo)
			GROUP BY month
			ORDER BY month";
		$monthlyStatement = $pdo->prepare($monthlySql);
		$monthlyStatement->execute([
			'productId' => $productId,
			'monthsAgo' => '-' . $months . ' months'
		]);

		$historySql = "SELECT
				COALESCE(used_date, row_created_timestamp) AS date,
				transaction_type,
				amount,
				SUM(amount) OVER (ORDER BY row_created_timestamp, id) AS stock_amount
			FROM stock_log
			WHERE product_id = :productId AND undone = 0
			ORDER BY row_created_timestamp, id";
		$historyStatement = $pdo->prepare($historySql);
		$historyStatement->execute(['productId' => $productId]);

		$consumptionsSql = "SELECT
				id,
				COALESCE(used_date, row_created_timestamp) AS date,
				transaction_type,
				-amount AS amount,
				price
			FROM stock_log
			WHERE product_id = :productId
				AND $consumptionWhere
				AND undone = 0
			ORDER BY COALESCE(used_date, row_created_timestamp) DESC, id DESC";
		$consumptionsStatement = $pdo->prepare($consumptionsSql);
		$consumptionsStatement->execute(['productId' => $productId]);

		return [
			'product_id' => $productId,
			'qu_name' => $quStock->name,
			'qu_name_plural' => $quStock->name_plural,
			'monthly_consumption' => $monthlyStatement->fetchAll(\PDO::FETCH_OBJ),
			'stock_history' => $historyStatement->fetchAll(\PDO::FETCH_OBJ),
			'consumptions' => $consumptionsStatement->fetchAll(\PDO::FETCH_OBJ)
		];
	}
}
