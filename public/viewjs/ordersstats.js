function BuildChartData(rows)
{
	var labels = [];
	var lastYear = [];
	var thisYear = [];

	rows.forEach(function(row)
	{
		labels.push(row.product_name);
		lastYear.push(parseFloat(row.last_year_amount));
		thisYear.push(parseFloat(row.this_year_amount));
	});

	return { labels: labels, lastYear: lastYear, thisYear: thisYear };
}

function RenderBarChart(canvasId, rows, lastYearLabel, thisYearLabel)
{
	var chartData = BuildChartData(rows);

	new Chart(canvasId, {
		type: 'bar',
		data: {
			labels: chartData.labels,
			datasets: [
				{
					label: String(lastYearLabel),
					data: chartData.lastYear
				},
				{
					label: String(thisYearLabel),
					data: chartData.thisYear
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

RenderBarChart('ordered-chart', Grocy.OrderStats.ordered, Grocy.OrderStats.last_year, Grocy.OrderStats.year);
RenderBarChart('consumed-chart', Grocy.OrderStats.consumed, Grocy.OrderStats.last_year, Grocy.OrderStats.year);

$('#product-filter').on('change', function()
{
	var productId = $(this).val();
	window.location.href = U('/ordersstats') + (productId ? '?product_id=' + productId : '');
});
