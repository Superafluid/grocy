function RenderStockHistoryChart(stockHistory)
{
	var labels = [];
	var data = [];

	stockHistory.forEach(function(row)
	{
		labels.push(row.date);
		data.push(parseFloat(row.stock_amount));
	});

	new Chart('stock-history-chart', {
		type: 'line',
		data: {
			labels: labels,
			datasets: [
				{
					label: __t('Stock amount'),
					data: data,
					fill: false,
					stepped: true
				}
			]
		},
		options: {
			scales: {
				yAxes: [{ ticks: { beginAtZero: true } }]
			}
		}
	});
}

function RenderMonthlyConsumptionChart(monthlyConsumption)
{
	var labels = [];
	var data = [];

	monthlyConsumption.forEach(function(row)
	{
		labels.push(row.month);
		data.push(parseFloat(row.amount));
	});

	new Chart('monthly-consumption-chart', {
		type: 'bar',
		data: {
			labels: labels,
			datasets: [
				{
					label: __t('Consumed amount'),
					data: data
				}
			]
		},
		options: {
			scales: {
				yAxes: [{ ticks: { beginAtZero: true } }]
			}
		}
	});
}

if (Grocy.ConsumptionStats)
{
	RenderStockHistoryChart(Grocy.ConsumptionStats.stock_history);
	RenderMonthlyConsumptionChart(Grocy.ConsumptionStats.monthly_consumption);
}

$('#consumptions-table').DataTable({
	'order': [[0, 'desc']],
	'columnDefs': [
		{ 'type': 'num', 'targets': [2, 3] }
	].concat($.fn.dataTable.defaults.columnDefs)
});

$('#product-filter').on('change', function()
{
	var productId = $(this).val();
	window.location.href = U('/consumptionstats') + (productId ? '?product_id=' + productId : '');
});
