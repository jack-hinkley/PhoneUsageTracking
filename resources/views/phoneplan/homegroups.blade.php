@extends('layouts.app')
@section('content')
<div class="container">
	<div class="content hidden">
		<div class="page-header">
			<h3 class="title pull-left d-print-none">PHONE PLANS</h3>
			<a href="/phoneplan" class="btn btn-outline-secondary pull-right d-print-none"><i class="fa fa-arrow-left"></i>&nbsp Back to Invoices</a>
		</div><hr class="d-print-none">
		<div class="form-group d-print-none">
			<label for="date-selector">Date</label>	
			<select class="form-control" id="date-selector">
				<?php foreach ($invoice['dates'] as $key => $date){
					echo '<option value="">'.$date->invoice_date.'</option>';
				} ?>
			</select>
		</div>
		<br>
		<div class="data-container"></div>
	</div>

	<div class="modal fade" id="uploadModal">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<form action="phoneplan/upload" method="POST" enctype="multipart/form-data">
				{{ csrf_field() }}
					<div class="modal-body">
						<div class="file-input">
							<span>Click to upload</span>
							<input type="file" name="imported-file" accept=".xlsx"/>	
						</div>
					</div>
					<div class="modal-footer">
						<button type="submit" class="btn btn-primary">Upload</button>
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	var attempts = 0;
	$(document).ready(function(){
		setTimeout(function(){ $('.content').removeClass('hidden') }, 200);
		//	Set token and data
		var token = '<?php echo Session::token();?>';
		var date = $('#date-selector').find(':selected').text();
		//	If the select element changes, call_change data function with new date
		$('#date-selector').change(function(){
			date = $('#date-selector').find(':selected').text();
			change_data(token, date);
		});
		//	Change data on page load
		change_data(token, date);
	});

	//	Purpose: 	This function makes and ajax call collecting invoices based on the selected date and local
	//						The data on the page will then be replaced with the desired data
	//	Params: 	CSRF token, selected date, local
	//	Returns: 	Array of all requested invoices
	function change_data(token, date) {
		$.ajax({
			url: "gethomes",
			data: {_token: token, date: date},
			method: "POST",
			datatype: "json",
			success: function(data){
				// $('#generate').data('date', date);
				$('.data-container').html('');
				var total_cost = 0;
				$.each(data['invoices'], function(key, val){
					total_cost += parseFloat(val['invoice_total']);
				});
				if(data['invoices'].length == 0)
					$('.data-container').append('<h3 class="text-muted" style="width: 100%; text-align: center; opacity:0">There are no search results</h3>')
					.fadeIn("slow", function(){ $('.text-muted').animate({'opacity': '1'})});
				last_local = data['invoices'][0]['local']; 
				var html = `<h5>${ last_local } </h5>`;
				$.each(data['invoices'], function(key, val) {
					phone = format_phone(val['phone']);

					if(last_local != val['local']) {
						html += `<br><h5>${ val['local'] } </h5>`;
						last_local = val['local'];
					}

					html +=
						`<ul class="list-group">
							<li class="list-group-item">
								<div class="row">
									<div class="col-md-11">
										<div class="row">
											<div class="col">${ val['first_name'] } ${val['last_name']}</div>
											<div class="col">${ phone }</div>
											<div class="col">$${ val['invoice_total'] }</div>
											<div class="col">
												<select name="plan_data" class="form-control plan_data d-print-none" style="height: 32px; padding-top: 3px">
													<option>3072 MB</option>
													<option>6144 MB</option>
												</select>
												<span class="d-print-block">${ val['plan_data'] } MB</span>
											</div>
										</div>
									</div>
									<div class="col-md-1">
										<div class="pull-right"><a href="/phoneplan/details/${val['invoice_id']}" target="_blank" class="btn btn-info btn-sm pull-right d-print-none"><i class="fa fa-info"></i>&nbsp Details</a></div>
									</div>
								</div>
							</li>
						</ul>`;
				});
				//	Add results to page
				$('.data-container').append(html);

				//	Change all select boxes to reflect 
				$.each($('.plan_data'), function(k, v){
					$.each($(v).find('option'), function(key, val){						
						data_plan = $(val).val().substring(0, $(val).val().length-3);
						if(data_plan == data['invoices'][k]['plan_data'])
							$(val).attr('selected', 'selected');
					});
				});
				
				//	Fade in results
				$.each($('.card'), function(key, val){
					$(this).fadeIn(400);
				});
			}, error: function(){
				if(attempts < 5){
					attempts++;
					change_data(token, date);
				}
				else 
					$('.data-container').append('<h3>There was a problem connecting</h3>');
			}
		});
	}

	//	Purpose: 	This function takes a 10 digit phone number and formats it to be reader friendly
	//	Params: 	10 digit phone number
	//	Returns: 	Formated phone number
	function format_phone(phone) {
		var formated = phone.match(/^(\d{3})(\d{3})(\d{4})$/);
		return formated[1]+" "+formated[2]+" "+formated[3];
	}
</script>
@endsection