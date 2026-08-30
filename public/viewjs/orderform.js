$('#add-item-row-button').on('click', function()
{
	var template = document.getElementById('order-item-row-template');
	var clone = document.importNode(template.content, true);
	$('#order-items-table tbody').append(clone);
});

$(document).on('click', '.remove-item-row', function(e)
{
	$(e.currentTarget).closest('tr').remove();
});

function CollectOrderItems()
{
	var items = [];
	$('#order-items-table tbody .order-item-row').each(function()
	{
		var row = $(this);
		items.push({
			product_id: parseInt(row.find('.item-product').val()),
			amount: parseFloat(row.find('.item-amount').val()),
			qu_id: row.find('.item-qu').val() ? parseInt(row.find('.item-qu').val()) : null,
			price: row.find('.item-price').val() ? parseFloat(row.find('.item-price').val()) : null
		});
	});

	return items;
}

$('#save-order-button').on('click', function(e)
{
	e.preventDefault();

	if (!Grocy.FrontendHelpers.ValidateForm('order-form', true))
	{
		return;
	}

	var items = CollectOrderItems();
	if (items.length === 0)
	{
		Grocy.FrontendHelpers.ShowGenericError(__t('An order requires at least one item'), '');
		return;
	}

	var jsonData = {
		ordered_date: $('#ordered_date').val(),
		shopping_location_id: $('#shopping_location_id').val() ? parseInt($('#shopping_location_id').val()) : null,
		status: $('#status').val(),
		note: $('#note').val(),
		items: items
	};

	Grocy.FrontendHelpers.BeginUiBusy('order-form');

	var onSuccess = function()
	{
		if (GetUriParam('embedded') !== undefined)
		{
			window.parent.postMessage(WindowMessageBag('Reload'), Grocy.BaseUrl);
		}
		else
		{
			window.location.href = U('/orders');
		}
	};

	var onError = function(xhr)
	{
		Grocy.FrontendHelpers.EndUiBusy('order-form');
		Grocy.FrontendHelpers.ShowGenericError('Error while saving', xhr.response);
	};

	if (Grocy.EditMode === 'create')
	{
		Grocy.Api.Post('orders', jsonData, onSuccess, onError);
	}
	else
	{
		Grocy.Api.Put('orders/' + Grocy.EditObjectId, jsonData, onSuccess, onError);
	}
});

setTimeout(function()
{
	$('#ordered_date').focus();
}, Grocy.FormFocusDelay);
