@extends('layouts.app')
@section('content')
<div class="container">
	<div class="content hidden">
		<div class="page-header">
			<h3 class="title pull-left">CLIENTS</h3>
			<a href="clients/create" class="btn btn-success pull-right"><i class="fa fa-plus"></i>&nbsp Add Client</a>
		</div><hr>
		<div class="data-container"></div>
	</div>
</div>
<script type="text/javascript">
	var attempts = 0;
	$(document).ready(function(){
		setTimeout(function(){ $('.content').removeClass('hidden') }, 200);
		//	Set token and data
		var token = '<?php echo Session::token();?>';
		get_clients(token);
	});

	//	Purpose: 	This function makes and ajax call that returns all clients in the system
	//	Params: 	CSRF token, selected date
	//	Returns: 	Array of all requested invoices
	function get_clients(token) {
		$.ajax({
			url: "clients/get",
			data: {_token: token},
			method: "POST",
			datatype: "json",
			success: function(data){
				$('.data-container').html('');
				$.each(data['clients'], function(key, val){
					$('.data-container').append(
						`<div class="card">
							<div class="card-header">${val['local']}
								<a href="clients/delete/${val['client_id']}" class="btn btn-danger btn-sm pull-right"><i class="fa fa-trash"></i>&nbsp Delete</a>
								<a href="clients/edit/${val['client_id']}" class="btn btn-info btn-sm pull-right"><i class="fa fa-pencil"></i>&nbsp Edit</a>
							</div>
							<div class="card-body">
								<div class="col-sm-12">
									<div class="row">
										<div class="col"><i class="fa fa-map-marker"></i>&nbsp&nbsp${val['address']}, ${val['province']}, ${val['postal']}</div>
									</div>
								</div>
							</div>
						</div>`
					);
				});
			}, error: function(){
				if(attempts < 5){
					attempts++;
					get_clients(token);
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