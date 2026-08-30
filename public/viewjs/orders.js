var ordersTable = $('#orders-table').DataTable({
	'order': [[1, 'desc']],
	'columnDefs': [
		{ 'orderable': false, 'targets': 0 },
		{ 'searchable': false, 'targets': 0 },
		{ 'type': 'num', 'targets': [5, 6] }
	].concat($.fn.dataTable.defaults.columnDefs)
});
$('#orders-table tbody').removeClass("d-none");
ordersTable.columns.adjust().draw();

$(document).on('click', '.order-delete-button', function(e)
{
	var orderId = $(e.currentTarget).attr('data-order-id');

	bootbox.confirm({
		message: __t('Are you sure you want to delete this order?'),
		buttons: {
			confirm: {
				label: __t('Yes'),
				className: 'btn-success'
			},
			cancel: {
				label: __t('No'),
				className: 'btn-danger'
			}
		},
		closeButton: false,
		callback: function(result)
		{
			if (result === true)
			{
				Grocy.Api.Delete('orders/' + orderId, {},
					function(result)
					{
						window.location.href = U('/orders');
					},
					function(xhr)
					{
						console.error(xhr);
					}
				);
			}
		}
	});
});
