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

var orderParam = GetUriParam("order");
if (typeof orderParam !== "undefined")
{
	var targetRow = $('#order-' + orderParam + '-row');
	if (targetRow.length)
	{
		var row = ordersTable.row(targetRow);
		var content = $('#order-items-template-' + orderParam).html();
		if (content)
		{
			row.child(content, 'bg-light p-0').show();
			targetRow.addClass('shown');
			var toggleBtn = targetRow.find('.order-toggle-items-button.btn');
			toggleBtn.find('i').removeClass('fa-chevron-right').addClass('fa-chevron-down');
		}

		if (targetRow[0])
		{
			targetRow[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
		}
	}
}

$(document).on('click', '.order-toggle-items-button', function(e)
{
	e.preventDefault();

	if ($(this).hasClass('disabled'))
	{
		return;
	}

	var orderId = $(this).attr('data-order-id');
	var tr = $('#order-' + orderId + '-row');
	var row = ordersTable.row(tr);
	var toggleBtn = tr.find('.order-toggle-items-button.btn');
	var icon = toggleBtn.find('i');

	if (row.child.isShown())
	{
		row.child.hide();
		tr.removeClass('shown');
		icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
	}
	else
	{
		var content = $('#order-items-template-' + orderId).html();
		if (content)
		{
			row.child(content, 'bg-light p-0').show();
			tr.addClass('shown');
			icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
		}
	}
});

$(document).on('click', '.order-deliver-button', function(e)
{
	e.preventDefault();

	if ($(this).hasClass('disabled'))
	{
		return;
	}

	var orderId = $(this).attr('data-order-id');

	bootbox.confirm({
		message: __t('Are you sure you want to mark this order as delivered and add all items to stock?'),
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
				Grocy.FrontendHelpers.BeginUiBusy();
				Grocy.Api.Put('orders/' + orderId, { 'status': 'delivered' },
					function(result)
					{
						window.location.href = U('/orders');
					},
					function(xhr)
					{
						Grocy.FrontendHelpers.EndUiBusy();
						console.error(xhr);
					}
				);
			}
		}
	});
});

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
				Grocy.FrontendHelpers.BeginUiBusy();
				Grocy.Api.Delete('orders/' + orderId, {},
					function(result)
					{
						window.location.href = U('/orders');
					},
					function(xhr)
					{
						Grocy.FrontendHelpers.EndUiBusy();
						console.error(xhr);
					}
				);
			}
		}
	});
});
