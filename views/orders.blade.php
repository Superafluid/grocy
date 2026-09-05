@php require_frontend_packages(['datatables']); @endphp

@extends('layout.default')

@section('title', $__t('Orders'))

@section('content')
<div class="row">
	<div class="col">
		<div class="title-related-links">
			<h2 class="title">@yield('title')</h2>
			<div class="float-right @if($embedded) pr-5 @endif">
				<button class="btn btn-outline-dark d-md-none mt-2 order-1 order-md-3"
					type="button"
					data-toggle="collapse"
					data-target="#related-links">
					<i class="fa-solid fa-ellipsis-v"></i>
				</button>
			</div>
			<div class="related-links collapse d-md-flex order-2 width-xs-sm-100"
				id="related-links">
				<a class="btn btn-primary responsive-button permission-ORDERS_TRACK m-1 mt-md-0 mb-md-0 float-right show-as-dialog-link"
					href="{{ $U('/order/new?embedded') }}">
					{{ $__t('Add') }}
				</a>
				<a class="btn btn-outline-secondary m-1 mt-md-0 mb-md-0 float-right"
					href="{{ $U('/ordersstats') }}">
					{{ $__t('Statistics') }}
				</a>
			</div>
		</div>
	</div>
</div>

<hr class="my-2">

<div class="row">
	<div class="col">
		<table id="orders-table"
			class="table table-sm table-striped nowrap w-100">
			<thead>
				<tr>
					<th class="border-right"></th>
					<th>{{ $__t('Ordered date') }}</th>
					<th>{{ $__t('Arrive date') }}</th>
					<th>{{ $__t('Store') }}</th>
					<th>{{ $__t('Status') }}</th>
					<th class="text-right">{{ $__t('Items') }}</th>
					<th class="text-right">{{ $__t('Total price') }}</th>
				</tr>
			</thead>
			<tbody class="d-none">
				@foreach($orders as $order)
				@php $items = $orderItems[$order->id] ?? []; @endphp
				<tr id="order-{{ $order->id }}-row"
					data-order-id="{{ $order->id }}">
					<td class="fit-content border-right">
						<a class="btn btn-success btn-sm order-deliver-button permission-ORDERS_EDIT @if($order->status == 'delivered') disabled @endif"
							href="#"
							data-order-id="{{ $order->id }}"
							data-toggle="tooltip"
							title="{{ $__t('Mark delivered / add to stock') }}">
							<i class="fa-solid fa-check"></i>
						</a>
						<a class="btn btn-info btn-sm permission-ORDERS_EDIT show-as-dialog-link"
							href="{{ $U('/order/') }}{{ $order->id }}?embedded"
							data-toggle="tooltip"
							title="{{ $__t('Edit this item') }}">
							<i class="fa-solid fa-edit"></i>
						</a>
						<a class="btn btn-danger btn-sm order-delete-button permission-ORDERS_DELETE"
							href="#"
							data-order-id="{{ $order->id }}"
							data-toggle="tooltip"
							title="{{ $__t('Delete this item') }}">
							<i class="fa-solid fa-trash"></i>
						</a>
						<a class="btn btn-outline-secondary btn-sm order-toggle-items-button @if($order->item_count == 0) disabled @endif"
							href="#"
							data-order-id="{{ $order->id }}"
							data-toggle="tooltip"
							title="{{ $__t('Show/hide items') }}">
							<i class="fa-solid fa-chevron-right"></i>
						</a>
					</td>
					<td>{{ $order->ordered_date }}</td>
					<td>{{ $order->arrive_date }}</td>
					<td>{{ $order->shopping_location_name }}</td>
					<td>{{ $order->status }}</td>
					<td class="text-right">
						<span class="order-toggle-items-button cursor-pointer"
							data-order-id="{{ $order->id }}"
							data-toggle="tooltip"
							title="{{ $__t('Show/hide items') }}">
							{{ $order->item_count }}
						</span>
					</td>
					<td class="text-right">{{ number_format($order->total_price, 2) }}</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
</div>

@foreach($orders as $order)
@php $items = $orderItems[$order->id] ?? []; @endphp
<div id="order-items-template-{{ $order->id }}"
	class="d-none">
	<div class="p-3 bg-light border-top border-bottom">
		<h6 class="font-weight-bold text-secondary mb-2">
			<i class="fa-solid fa-list mr-1"></i> {{ $__t('Order items') }}
		</h6>
		@if(!empty($items) && count($items) > 0)
		<div class="table-responsive">
			<table class="table table-sm table-bordered table-hover bg-white mb-0">
				<thead class="thead-light">
					<tr>
						<th>{{ $__t('Product') }}</th>
						<th class="text-right">{{ $__t('Amount') }}</th>
						<th>{{ $__t('Quantity unit') }}</th>
						<th class="text-right">{{ $__t('Price') }}</th>
						<th class="text-right">{{ $__t('Total') }}</th>
						<th>{{ $__t('Note') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach($items as $item)
					<tr>
						<td>{{ $item->product_name }}</td>
						<td class="text-right">{{ floatval($item->amount) == intval($item->amount) ? intval($item->amount) : number_format($item->amount, 2) }}</td>
						<td>{{ $item->qu_name }}</td>
						<td class="text-right">@if($item->price !== null){{ number_format($item->price, 2) }}@endif</td>
						<td class="text-right">@if($item->price !== null){{ number_format($item->amount * $item->price, 2) }}@endif</td>
						<td>{{ $item->note }}</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>
		@else
		<div class="text-muted font-italic">
			{{ $__t('No items in this order') }}
		</div>
		@endif
		@if(!empty($order->note))
		<div class="mt-2 text-muted">
			<small><strong>{{ $__t('Note') }}:</strong> {{ $order->note }}</small>
		</div>
		@endif
	</div>
</div>
@endforeach
@stop
