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
				<tr>
					<td class="fit-content border-right">
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
					</td>
					<td>{{ $order->ordered_date }}</td>
					<td>{{ $order->arrive_date }}</td>
					<td>{{ $order->shopping_location_name }}</td>
					<td>{{ $order->status }}</td>
					<td class="text-right">{{ $order->item_count }}</td>
					<td class="text-right">{{ number_format($order->total_price, 2) }}</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
</div>
@stop
