<?php

namespace Grocy\Controllers;

use Grocy\Services\OrdersService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class OrdersController extends BaseController
{
	public function OrdersList(Request $request, Response $response, array $args)
	{
		$orderItems = OrdersService::GetInstance()->GetAllOrderItems();
		$orderItemsGrouped = [];
		foreach ($orderItems as $item)
		{
			$orderItemsGrouped[$item->order_id][] = $item;
		}

		return $this->RenderPage($response, 'orders', [
			'orders' => OrdersService::GetInstance()->GetOrders(),
			'orderItems' => $orderItemsGrouped,
			'shoppinglocations' => $this->DB->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE')
		]);
	}

	public function OrderEditForm(Request $request, Response $response, array $args)
	{
		if ($args['orderId'] == 'new')
		{
			return $this->RenderPage($response, 'orderform', [
				'mode' => 'create',
				'products' => $this->DB->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'quantityUnits' => $this->DB->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'shoppinglocations' => $this->DB->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE')
			]);
		}
		else
		{
			return $this->RenderPage($response, 'orderform', [
				'mode' => 'edit',
				'order' => OrdersService::GetInstance()->GetOrder(intval($args['orderId'])),
				'products' => $this->DB->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'quantityUnits' => $this->DB->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'shoppinglocations' => $this->DB->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE')
			]);
		}
	}

	public function Stats(Request $request, Response $response, array $args)
	{
		$productId = null;
		if (isset($request->getQueryParams()['product_id']) && filter_var($request->getQueryParams()['product_id'], FILTER_VALIDATE_INT) !== false)
		{
			$productId = intval($request->getQueryParams()['product_id']);
		}

		return $this->RenderPage($response, 'ordersstats', [
			'stats' => OrdersService::GetInstance()->GetStats(null, $productId),
			'products' => $this->DB->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'selectedProductId' => $productId
		]);
	}

	public function ConsumptionStats(Request $request, Response $response, array $args)
	{
		$products = $this->DB->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE');
		$productsArray = iterator_to_array($products);

		$productId = null;
		if (isset($request->getQueryParams()['product_id']) && filter_var($request->getQueryParams()['product_id'], FILTER_VALIDATE_INT) !== false)
		{
			$productId = intval($request->getQueryParams()['product_id']);
		}
		elseif (!empty($productsArray))
		{
			$productId = reset($productsArray)->id;
		}

		return $this->RenderPage($response, 'consumptionstats', [
			'products' => $productsArray,
			'selectedProductId' => $productId,
			'consumptionStats' => $productId !== null ? OrdersService::GetInstance()->GetProductConsumptionStats($productId) : null
		]);
	}
}
