@extends('layouts.app')
@section('content')
<div class="container">
	<div class="content hidden">
		<div class="page-header">
			<h3 class="title pull-left">PHONE DATA</h3>
			<a href="#" class="btn btn-success pull-right" data-toggle="modal" data-target="#uploadModal"><i class="fa fa-plus"></i>&nbsp Upload XLSX</a>
			<a href="#" class="btn btn-info pull-right" id="download"><i class="fa fa-download"></i>&nbsp Download CSV</a>
			<a href="#" class="btn btn-warning pull-right" id="generate"><i class="fa fa-refresh"></i>&nbsp Generate Report</a>
		</div><hr>
		<?php 
			if(sizeof($phonedata['outstanding']) > 0){
				echo '<a href="phonedata/outstanding" class="btn btn-danger pull-right">'.sizeof($phonedata['outstanding']).' Outstanding Numbers</a><br>';
			}
		 ?>
		<div class="form-group">
			<label for="date-selector">Date</label>	
			<select class="form-control" id="date-selector">
				<?php foreach ($phonedata['dates'] as $key => $date){
					echo '<option value="">'.$date->invoice_date.'</option>';
				} ?>
			</select>
		</div>
		<div class="form-group">
			<label for="local-selector">Local</label>	
			<select class="form-control" id="local-selector">
				<?php foreach ($phonedata['locals'] as $key => $local){
					echo '<option value="">'.$local->local.'</option>';
				} ?>
			</select>
		</div>
		<div class="form-group">
			<label for="search">Search</label>
			<div class="input-group">
				<input class="form-control" name="search" id="search">	
				<a class="btn btn-warning text-white input-group-addon" id="search-button" style="background-color: #f0ad4e" href="#"><i class="fa fa-search"></i>&nbsp Search</a>
			</div>
		</div>
		<br>
		<div class="data-container"></div>
	</div>

	<div class="modal fade" id="uploadModal">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<form action="phonedata/upload" method="POST" enctype="multipart/form-data">
				{{ csrf_field() }}
					<div class="modal-header">
						<h5 class="modal-title">Upload XLSX</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
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
		var local = $('#local-selector').find(':selected').text();
		//	If the select element changes, call_change data function with new date
		$('#date-selector').change(function(){
			date = $('#date-selector').find(':selected').text();
			change_data(token, date, local);
		});
		//	If the select element changes, call change_data function with new local
		$('#local-selector').change(function(){
			local = $('#local-selector').find(':selected').text();
			change_data(token, date, local);
		});
		//	Change data based on query in search bar
		$('#search-button').click(function(){
			search = ($('#search').val()).replace(/\s\s+/g, ' ');
			if(search.length < 1 || search == ' '){
				$(this).parent().find($('.warning-container')).html(`
					<div class="alert alert-danger fade show" role="alert">
						<button type="button" class="close" data-dismiss="alert" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
						The search field cannot be empty!
					</div>`
				);
			} else {
				search_data(token, search);
			}
		});
		//	Listener for when the user presses enter
		$('#search').keypress(function(e){
			if(e.which == 13) {
				$('#search-button').click();
			}
		});
		$('#generate').click(function(){
			if($('#search').val().length > 0)
				window.location.replace('phonedata/generatesearch/'+$('#search').val());
			else
				window.location.replace('phonedata/generate/'+date+'/'+local);
		});
		//	Download CSV based on the current results
		$('#download').click(function(){
			if($('#search').val().length > 0)
				window.location.replace('phonedata/downloadsearch/'+$('#search').val());
			else
				window.location.replace('phonedata/download/'+date+'/'+local);
		});
		//	Change data on page load
		change_data(token, date, local);
	});

	//	Purpose: 	This function makes and ajax call collecting invoices based on the selected date and local
	//						The data on the page will then be replaced with the desired data
	//	Params: 	CSRF token, selected date, local
	//	Returns: 	Array of all requested invoices
	function change_data(token, date, local) {
		$.ajax({
			url: "phonedata/get",
			data: {_token: token, date: date, local: local},
			method: "POST",
			datatype: "json",
			success: function(data){
				$('.data-container').html('');
				if(data['invoices'].length == 0)
					$('.data-container').append('<h3 class="text-muted" style="width: 100%; text-align: center; opacity:0">There are no search results</h3>')
					.fadeIn("slow", function(){ $('.text-muted').animate({'opacity': '1'})});
				var html = '';
				$.each(data['invoices'], function(key, val){
					phone = format_phone(val['phone']);
					html +=
						`<div class="card hidden">
							<div class="card-header">
								${ val['first_name'] } ${val['last_name']}
								<a href="phonedata/details/${val['invoice_id']}" class="btn btn-info btn-sm pull-right"><i class="fa fa-info"></i>&nbsp Details</a>
							</div>
							<div class="card-body">
								<div class="col-sm-12">
									<div class="row">
										<div class="col"><i class="fa fa-phone"></i>&nbsp&nbsp${phone}</div>
										<div class="col"><i class="fa fa-database"></i>&nbsp&nbsp${val['total_data']}MB</div>
										<div class="col"><i class="fa fa-building-o"></i>&nbsp&nbsp${val['local']}</div>
										<div class="col"><i class="fa fa-usd"></i>&nbsp&nbsp$${data['overages'][key]['overage_cost']}</div>
									</div>
								</div>
							</div>
						</div>`;
				});
				//	Clean loading
				$('#search').val('');
				//	Add results to page
				$('.data-container').append(html);
				//	Fade in results
				$.each($('.card'), function(key, val){
					$(this).fadeIn(400);
				});
			}, error: function(){
				if(attempts < 5){
					attempts++;
					change_data(token, date, local);
				}
				else 
					$('.data-container').append('<h3>There was a problem connecting</h3>');
			}
		});
	}

	//	Purpose: 	This function makes and ajax call collecting invoices based on the search
	//						The data on the page will then be replaced with the desired data
	//	Params: 	CSRF token, search term
	//	Returns: 	Array of all requested invoices
	function search_data(token, search){
		$.ajax({
			url: "phonedata/search",
			data: {_token: token, search: search},
			method: "POST",
			datatype: "json",
			success: function(data){
				$('.data-container').html('');
				var html = '';
				$.each(data['invoices'], function(key, val){	
					phone = format_phone(val['phone']);
					html +=
						`<div class="card hidden">
							<div class="card-header">${ val['first_name'] } ${val['last_name']}</div>
							<div class="card-body">
								<div class="col-sm-12">
									<div class="row">
										<div class="col"><i class="fa fa-calendar"></i>&nbsp&nbsp${val['invoice_date']}</div>
										<div class="col"><i class="fa fa-phone"></i>&nbsp&nbsp${phone}</div>
										<div class="col"><i class="fa fa-database"></i>&nbsp&nbsp${val['total_data']}MB</div>
										<div class="col"><i class="fa fa-building-o"></i>&nbsp&nbsp${val['local']}</div>
										<div class="col"><i class="fa fa-usd"></i>&nbsp&nbsp$${data['overages'][key]['overage_cost']}</div>
									</div>
								</div>
							</div>
						</div>`
				});
				//	Add results to page
				$('.data-container').append(html);
				//	Fade in results
				$.each($('.card'), function(key, val){
					$(this).fadeIn(400);
				});
			}, error: function(){
				if(attempts < 5){
					attempts++;
					change_data(token, date, local);
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