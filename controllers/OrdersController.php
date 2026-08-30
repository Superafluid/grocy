<?php

namespace Grocy\Controllers;

use Grocy\Services\OrdersService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class OrdersController extends BaseController
{
	public function OrdersList(Request $request, Response $response, array $args)
	{
		return $this->RenderPage($response, 'orders', [
			'orders' => OrdersService::GetInstance()->GetOrders(),
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
		return $this->RenderPage($response, 'ordersstats', [
			'stats' => OrdersService::GetInstance()->GetStats()
		]);
	}
}
