/* need:
	form validation
	submit handler - POST to mongo php handler, update on success
	if BSA, change labels for postal code->zip code, province->state
*/

Object.size = function(obj) {
    var size = 0, key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
};

var foo;

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
		var org = $('#organization').val();
		if (org !== "scouts_canada") {
			$('#sc-fields').hide();
		} else {
			$('#sc-fields').show();
		}
	},
	print_errors = function(errors) {
		$('#ajax_response').empty().removeClass().addClass('error');
		$('.field_error').removeClass('field_error');
	    $.each(errors, function(i,v) {
			var error_message = errors[i].error_message,
			    error_type = errors[i].error_type,
			    fields = errors[i].fields;
			var el = $('<div></div>')
					.append( $('<p></p>').html(error_message) )
					.append( $('<ul></ul>').attr('id', error_type) );
			$('#ajax_response').append(el);
			if (errors[i].fields) {
				$.each(errors[i].fields, function(name,label) {
					var li = $('<li></li>').attr('id', 'missing_' + name).html(label);
					$('#' + error_type).append(li);
					$('#' + name).addClass('field_error').prev().addClass('label_error');
				});
			}
		});
	},
	print_success = function(message) {
		console.log(message);
		$('#ajax_response').empty().removeClass().addClass('success').html(message);
		$('#registration_form').hide();
	};
			
	$(":submit").attr("disabled",true);
	
	fillAreas("FVC");
	
	$('#council').live('change', function() {
		var source = $(this),
			council = source.val(),
			target = $('#area');
			toggleAreaInput(council);
			if (council === "FVC" || council === 'PCC') {
				fillAreas(council);
			}
	});
	
	$('#organization').live('change', orgHandler);
	
	$('.qtyField').change(function() {
		var runningTotal = 0;
		if ($(this).val() < 0 || isNaN($(this).val())) {
			$(this).val(0);
		}
		$('.qtyField').each(function() {
			var field = $(this),
			    price = field.attr('data-price'),
				qty = field.val();
			runningTotal = runningTotal + (qty*price);
			if (isNaN(runningTotal)) {
				runningTotal = "---";
			}
			$('#total').text(runningTotal);
			$('#total_amount').val(runningTotal);
				
		});
	});
	
	$('#agree_to_terms').change(function() {
		var val = $(this + ":checked").val();
		if (val != undefined) {
			$(':submit').attr('disabled',false);
		} else {
			$(':submit').attr('disabled',true);
		}
	});
	
	$('form').submit(function() {
		if ($('#agree_to_terms:checked').val() == undefined) {
			return false;
		}
		$('label').removeClass('label_error');
		var response = {status: ""};		
		var set_error = function(error_type, error_message, fields) {
			response.status = 'error';
			if (typeof response.errors === 'undefined') {
				response['errors'] = [];
			}
			response.errors.push ({
				error_type: error_type,
				error_message: error_message,
				fields: fields
			});
		};
		
		var empty_fields = {};
		$('.required').each(function(){
			var field = $(this),
				field_id = field.attr('id'),
				field_label = field.prev().html();
			if (field.val() === "") {
				empty_fields[field_id] = field_label;
			}
		});
		if (Object.size(empty_fields) > 0) {
			set_error("missing_fields", "The following required fields were not completed: ", empty_fields);
		}
		
		var non_numeric_fields = {};
		$('.qtyField').each(function() {
			var field = $(this),
				field_id = field.attr('id'),
				field_label = field.prev().html(),
				val = field.val() !== "" ? parseInt(field.val(), 10) : "";
				if (isNaN(val)) {
					non_numeric_fields[field_id] = field_label;
				}
		});
		if (Object.size(non_numeric_fields) > 0) {
			set_error("non_numeric_fields", "The following fields require a numeric value: ", non_numeric_fields);
		}
		
		if (response.status === 'error') {
			print_errors(response.errors);
			return false;
		}
		
		var url = $(this).attr('action');		
		$.post(url, $('form').serialize(), function(data) {
			data = JSON.parse(data);
			if (data.status === 'error') {
				print_errors(data.errors);
				return false;
			} else if (data.status === 'success') {
				var message = data.message;
				print_success(message);
			}
		});
		
		return false;
	});
});
