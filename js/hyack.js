/* need:
	change handler for conditions checkbox - only show submit if checked
	submit handler - POST to mongo php handler, update on success
*/

$(document).ready(function () {

	var areas = {
		FVC : ['Coho', 'Fraser Cheam', 'Green Timbers', 'Nicomekl', 'Three Rivers', 'WestSurDel'],
		PCC : ['Burnaby', 'East Vancouver', 'Pacific Spirit', 'North Shore', 'Richmond', 'Sea to Sky', 'Sunshine Coast']
	},
	fillAreas = function(council) {
		var source = $("#council"),
			target = $("#area");
		
		var areasForCouncil = areas[council];
		target.empty();
		$.each(areasForCouncil, function(i,area) {
			el = $("<option></option>").attr("value", area).text(area);
			target.append(el);
		});

	},
	toggleAreaInput = function(council) {
		var areaEl = $('#area'),
			input = '<input type="text" id="area" name="area"></input>',
			select = '<select name="area" id="area"></select>';
		
		if (council === "Cascadia") {
			areaEl.replaceWith(input);
		} else {
			areaEl.replaceWith(select);
		}
	},
	orgHandler = function(org) {
		var org = $('#grouptype').val();
		if (org !== "scouts_canada") {
			$('#sc-fields').hide();
		} else {
			$('#sc-fields').show();
		}
	};
	
	fillAreas("FVC");
	$('#council').live('change', function() {
		var source = $(this),
			council = source.val(),
			target = $('#area');
			toggleAreaInput(council);
			if (council === "FVC" || council === 'PCC') {
				fillAreas(council);
			} else if (council === "Cascadia") {
				// can't find a list of Cadcadia areaa to save my life, so we cheap out and swap in a text input instead
			}
	});
	
	$('#grouptype').live('change', orgHandler);
	
	$('.qtyField').change(function() {
		runningTotal = 0;
		if ($(this).val() < 0 || isNaN($(this).val())) {
			$(this).val(0);
		}
		$('.qtyField').each(function() {
			var field = $(this),
			    price = field.attr('data-price'),
				qty = field.val();
			runningTotal = runningTotal + (qty*price);
			$('#total').text(runningTotal);
				
		});
	});
});