@php require_frontend_packages(['datatables', 'chartjs']); @endphp

@extends('layout.default')

@section('title', $__t('Consumption statistics'))

@section('content')
<div class="row">
	<div class="col">
		<h2 class="title">@yield('title')</h2>
	</div>
</div>

<hr class="my-2">

<div class="row">
	<div class="col-lg-4 col-12 mb-3">
		<label for="product-filter">{{ $__t('Product') }}</label>
		<select class="custom-select form-control"
			id="product-filter">
			@if(empty($products))
			<option value="">{{ $__t('No products found') }}</option>
			@endif
			@foreach($products as $product)
			<option value="{{ $product->id }}" @if($selectedProductId == $product->id) selected @endif>{{ $product->name }}</option>
			@endforeach
		</select>
	</div>
</div>

<script>
	Grocy.ConsumptionStats = @json($consumptionStats);
</script>

<div class="row">
	<div class="col-12 mb-4">
		<h4>{{ $__t('Stock history') }}</h4>
		<canvas id="stock-history-chart"></canvas>
	</div>
</div>

<div class="row">
	<div class="col-lg-6 col-12 mb-4">
		<h4>{{ $__t('Monthly consumption') }}</h4>
		<canvas id="monthly-consumption-chart"></canvas>
	</div>
	<div class="col-lg-6 col-12">
		<table class="table table-sm table-striped" id="monthly-consumption-table">
			<thead>
				<tr>
					<th>{{ $__t('Month') }}</th>
					<th class="text-right">{{ $__t('Consumed amount') }}</th>
				</tr>
			</thead>
			<tbody>
				@if($consumptionStats)
				@foreach($consumptionStats['monthly_consumption'] as $row)
				<tr>
					<td>{{ $row->month }}</td>
					<td class="text-right">{{ number_format($row->amount, 2) }} {{ $__n($row->amount, $consumptionStats['qu_name'], $consumptionStats['qu_name_plural'], true) }}</td>
				</tr>
				@endforeach
				@endif
			</tbody>
		</table>
	</div>
</div>

<div class="row">
	<div class="col-12">
		<h4>{{ $__t('Consumptions') }}</h4>
		<table class="table table-sm table-striped nowrap w-100" id="consumptions-table">
			<thead>
				<tr>
					<th>{{ $__t('Date') }}</th>
					<th>{{ $__t('Type') }}</th>
					<th class="text-right">{{ $__t('Amount') }}</th>
					<th class="text-right">{{ $__t('Price') }}</th>
				</tr>
			</thead>
			<tbody>
				@if($consumptionStats)
				@foreach($consumptionStats['consumptions'] as $row)
				<tr>
					<td>{{ $row->date }}</td>
					<td>{{ $row->transaction_type == 'inventory-correction' ? $__t('Inventory count') : $__t('Consumed') }}</td>
					<td class="text-right">{{ number_format($row->amount, 2) }} {{ $__n($row->amount, $consumptionStats['qu_name'], $consumptionStats['qu_name_plural'], true) }}</td>
					<td class="text-right">{{ $row->price !== null ? number_format($row->price, 2) : '' }}</td>
				</tr>
				@endforeach
				@endif
			</tbody>
		</table>
	</div>
</div>
@stop
