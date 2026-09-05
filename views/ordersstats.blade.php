@php require_frontend_packages(['chartjs']); @endphp

@extends('layout.default')

@section('title', $__t('Order statistics'))

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
			<option value="">{{ $__t('All products') }}</option>
			@foreach($products as $product)
			<option value="{{ $product->id }}" @if($selectedProductId == $product->id) selected @endif>{{ $product->name }}</option>
			@endforeach
		</select>
	</div>
</div>

<script>
	Grocy.OrderStats = @json($stats);
</script>

<div class="row">
	<div class="col-lg-6 col-12 mb-4">
		<h4>{{ $__t('Ordered amount') }} — {{ $stats['last_year'] }} {{ $__t('vs') }} {{ $stats['year'] }}</h4>
		<canvas id="ordered-chart"></canvas>
	</div>
	<div class="col-lg-6 col-12 mb-4">
		<h4>{{ $__t('Consumed amount') }} — {{ $stats['last_year'] }} {{ $__t('vs') }} {{ $stats['year'] }}</h4>
		<canvas id="consumed-chart"></canvas>
	</div>
</div>

<div class="row">
	<div class="col-lg-6 col-12">
		<table class="table table-sm table-striped" id="ordered-table">
			<thead>
				<tr>
					<th>{{ $__t('Product') }}</th>
					<th class="text-right">{{ $stats['last_year'] }}</th>
					<th class="text-right">{{ $stats['year'] }}</th>
				</tr>
			</thead>
			<tbody>
				@foreach($stats['ordered'] as $row)
				<tr>
					<td>{{ $row->product_name }}</td>
					<td class="text-right">{{ number_format($row->last_year_amount, 2) }} {{ $__n($row->last_year_amount, $row->qu_name, $row->qu_name_plural, true) }}</td>
					<td class="text-right">{{ number_format($row->this_year_amount, 2) }} {{ $__n($row->this_year_amount, $row->qu_name, $row->qu_name_plural, true) }}</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	<div class="col-lg-6 col-12">
		<table class="table table-sm table-striped" id="consumed-table">
			<thead>
				<tr>
					<th>{{ $__t('Product') }}</th>
					<th class="text-right">{{ $stats['last_year'] }}</th>
					<th class="text-right">{{ $stats['year'] }}</th>
				</tr>
			</thead>
			<tbody>
				@foreach($stats['consumed'] as $row)
				<tr>
					<td>{{ $row->product_name }}</td>
					<td class="text-right">{{ number_format($row->last_year_amount, 2) }} {{ $__n($row->last_year_amount, $row->qu_name, $row->qu_name_plural, true) }}</td>
					<td class="text-right">{{ number_format($row->this_year_amount, 2) }} {{ $__n($row->this_year_amount, $row->qu_name, $row->qu_name_plural, true) }}</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
</div>
@stop
