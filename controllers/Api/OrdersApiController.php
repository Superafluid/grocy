<?php

namespace Grocy\Controllers\Api;

use Grocy\Controllers\Users\User;
use Grocy\Services\OrdersService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class OrdersApiController extends BaseApiController
{
	public function GetOrders(Request $request, Response $response, array $args)
	{
		return $this->FilteredApiResponse($response, OrdersService::GetInstance()->GetOrders(), $request->getQueryParams());
	}

	public function GetOrder(Request $request, Response $response, array $args)
	{
		try
		{
			return $this->ApiResponse($response, OrdersService::GetInstance()->GetOrder(intval($args['orderId'])));
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage(), 400);
		}
	}

	public function CreateOrder(Request $request, Response $response, array $args)
	{
		User::CheckPermission($request, User::PERMISSION_ORDERS_TRACK);

		try
		{
			$requestBody = $this->GetParsedAndFilteredRequestBody($request);

			if (!array_key_exists('items', $requestBody) || !is_array($requestBody['items']))
			{
				throw new \Exception('An "items" array is required');
			}

			$items = $requestBody['items'];
			unset($requestBody['items']);

			$orderId = OrdersService::GetInstance()->CreateOrder($requestBody, $items);
			return $this->ApiResponse($response, OrdersService::GetInstance()->GetOrder($orderId));
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}

	public function UpdateOrder(Request $request, Response $response, array $args)
	{
		User::CheckPermission($request, User::PERMISSION_ORDERS_EDIT);

		try
		{
			$requestBody = $this->GetParsedAndFilteredRequestBody($request);
			OrdersService::GetInstance()->UpdateOrder(intval($args['orderId']), $requestBody);
			return $this->EmptyApiResponse($response);
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}

	public function DeleteOrder(Request $request, Response $response, array $args)
	{
		User::CheckPermission($request, User::PERMISSION_ORDERS_DELETE);

		try
		{
			OrdersService::GetInstance()->DeleteOrder(intval($args['orderId']));
			return $this->EmptyApiResponse($response);
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}

	public function AddItem(Request $request, Response $response, array $args)
	{
		User::CheckPermission($request, User::PERMISSION_ORDERS_EDIT);

		try
		{
			$requestBody = $this->GetParsedAndFilteredRequestBody($request);
			$itemId = OrdersService::GetInstance()->AddItem(intval($args['orderId']), $requestBody);
			return $this->ApiResponse($response, $this->DB->order_items($itemId));
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}

	public function RemoveItem(Request $request, Response $response, array $args)
	{
		User::CheckPermission($request, User::PERMISSION_ORDERS_EDIT);

		try
		{
			OrdersService::GetInstance()->RemoveItem(intval($args['orderItemId']));
			return $this->EmptyApiResponse($response);
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}

	public function GetStats(Request $request, Response $response, array $args)
	{
		$year = null;
		if (isset($request->getQueryParams()['year']) && filter_var($request->getQueryParams()['year'], FILTER_VALIDATE_INT) !== false)
		{
			$year = intval($request->getQueryParams()['year']);
		}

		try
		{
			return $this->ApiResponse($response, OrdersService::GetInstance()->GetStats($year));
		}
		catch (\Exception $ex)
		{
			return $this->GenericErrorResponse($response, $ex->getMessage());
		}
	}
}
