@extends('layout.default')

@if($mode == 'edit')
@section('title', $__t('Edit order'))
@else
@section('title', $__t('Create order'))
@endif

@section('content')
<div class="row">
	<div class="col">
		<h2 class="title">@yield('title')</h2>
	</div>
</div>

<hr class="my-2">

<div class="row">
	<div class="col-lg-8 col-12">

		<script>
			Grocy.EditMode = '{{ $mode }}';
			Grocy.EditObjectId = @if($mode == 'edit'){{ $order->id }}@else null @endif;
		</script>

		<form id="order-form"
			novalidate>

			<div class="form-row">
				<div class="form-group col-md-3">
					<label for="ordered_date">{{ $__t('Ordered date') }}</label>
					<input type="text"
						class="form-control"
						required
						id="ordered_date"
						name="ordered_date"
						value="@if($mode == 'edit'){{ $order->ordered_date }}@else{{ date('Y-m-d') }}@endif">
				</div>
				<div class="form-group col-md-3">
					<label for="arrive_date">{{ $__t('Arrive date') }}</label>
					<input type="text"
						class="form-control"
						id="arrive_date"
						name="arrive_date"
						value="@if($mode == 'edit'){{ $order->arrive_date }}@endif">
				</div>
				<div class="form-group col-md-3">
					<label for="shopping_location_id">{{ $__t('Store') }}</label>
					<select class="custom-select form-control"
						id="shopping_location_id"
						name="shopping_location_id">
						<option value="">{{ $__t('None') }}</option>
						@foreach($shoppinglocations as $shoppinglocation)
						<option value="{{ $shoppinglocation->id }}"
							@if($mode == 'edit' && $order->shopping_location_id == $shoppinglocation->id) selected @endif>
							{{ $shoppinglocation->name }}
						</option>
						@endforeach
					</select>
				</div>
				<div class="form-group col-md-3">
					<label for="status">{{ $__t('Status') }}</label>
					<select class="custom-select form-control"
						id="status"
						name="status">
						@foreach(['pending', 'ordered', 'delivered', 'cancelled'] as $status)
						<option value="{{ $status }}"
							@if($mode == 'edit' && $order->status == $status) selected @endif>
							{{ $status }}
						</option>
						@endforeach
					</select>
				</div>
			</div>

			<div class="form-group">
				<label for="note">{{ $__t('Note') }}</label>
				<textarea class="form-control"
					id="note"
					name="note">@if($mode == 'edit'){{ $order->note }}@endif</textarea>
			</div>

			<hr class="my-2">
			<h4>{{ $__t('Items') }}</h4>

			<table class="table table-sm"
				id="order-items-table">
				<thead>
					<tr>
						<th>{{ $__t('Product') }}</th>
						<th>{{ $__t('Amount') }}</th>
						<th>{{ $__t('Quantity unit') }}</th>
						<th>{{ $__t('Price') }}</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					@if($mode == 'edit')
					@foreach($order->items as $item)
					<tr class="order-item-row">
						<td>
							<select class="custom-select form-control item-product">
								@foreach($products as $product)
								<option value="{{ $product->id }}" @if($product->id == $item->product_id) selected @endif>{{ $product->name }}</option>
								@endforeach
							</select>
						</td>
						<td><input type="number" step="0.01" class="form-control item-amount" value="{{ $item->amount }}"></td>
						<td>
							<select class="custom-select form-control item-qu">
								<option value="">{{ $__t('None') }}</option>
								@foreach($quantityUnits as $quantityUnit)
								<option value="{{ $quantityUnit->id }}" @if($quantityUnit->id == $item->qu_id) selected @endif>{{ $quantityUnit->name }}</option>
								@endforeach
							</select>
						</td>
						<td><input type="number" step="0.01" class="form-control item-price" value="{{ $item->price }}"></td>
						<td><button type="button" class="btn btn-danger btn-sm remove-item-row"><i class="fa-solid fa-trash"></i></button></td>
					</tr>
					@endforeach
					@endif
				</tbody>
			</table>

			<button type="button"
				id="add-item-row-button"
				class="btn btn-outline-secondary btn-sm mb-3">
				<i class="fa-solid fa-plus"></i> {{ $__t('Add item') }}
			</button>

			<template id="order-item-row-template">
				<tr class="order-item-row">
					<td>
						<select class="custom-select form-control item-product">
							@foreach($products as $product)
							<option value="{{ $product->id }}">{{ $product->name }}</option>
							@endforeach
						</select>
					</td>
					<td><input type="number" step="0.01" class="form-control item-amount" value="1"></td>
					<td>
						<select class="custom-select form-control item-qu">
							<option value="">{{ $__t('None') }}</option>
							@foreach($quantityUnits as $quantityUnit)
							<option value="{{ $quantityUnit->id }}">{{ $quantityUnit->name }}</option>
							@endforeach
						</select>
					</td>
					<td><input type="number" step="0.01" class="form-control item-price" value=""></td>
					<td><button type="button" class="btn btn-danger btn-sm remove-item-row"><i class="fa-solid fa-trash"></i></button></td>
				</tr>
			</template>

			<button id="save-order-button"
				class="btn btn-success">
				{{ $__t('Save') }}
			</button>
		</form>
	</div>
</div>
@stop
