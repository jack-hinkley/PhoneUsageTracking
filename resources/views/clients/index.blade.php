@extends('layouts.app')
@section('content')
<div class="container">
	<div class="content hidden">
		<div class="page-header">
			<h3 class="title pull-left">CLIENTS</h3>
			<a href="clients/create" class="btn btn-success pull-right"><i class="fa fa-plus"></i>&nbsp Add Client</a>
		</div><hr>
		
		<div class="data-container">
		</div>
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

	//	Called because search is generated after the document is ready
	function bind(token){
		//	Listener for when the user presses enter
		$('#search-input').keypress(function(e){
			if(e.which == 13){
				$('#search-button').click();
			}
		});

		//	Change data based on query in search bar
		$('#search-button').click(function(){
			search = $('#search-input').val();
			search_clients(token, search);
		});
	}

	//	Purpose: 	This function makes and ajax call that returns all clients in the system
	//	Params: 	CSRF token
	//	Returns: 	Array of all requested invoices
	function get_clients(token) {
		$.ajax({
			url: "clients/get",
			data: {_token: token},
			method: "POST",
			datatype: "json",
			success: function(data){
				if(!$('.search-input').length) 
					generate_search(data, token);
				$('.data-container').html('');
				$.each(data['clients'], function(key, val){
					$('.data-container').append(
						`<div class="card hidden">
							<div class="card-header">${val['local']}
								<a href="clients/delete/${val['client_id']}" class="btn btn-danger btn-sm pull-right"><i class="fa fa-trash"></i>&nbsp Delete</a>
								<a href="clients/edit/${val['client_id']}" target="_blank" class="btn btn-info btn-sm pull-right"><i class="fa fa-pencil"></i>&nbsp Edit</a>
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
				$.each($('.card'), function(key, val){
					$(this).fadeIn(400);
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

	//	Purpose: 	This function makes and ajax call that returns all clients in the system
	//	Params: 	CSRF token, local search
	//	Returns: 	Array of all requested invoices
	function search_clients(token, search) {
		$.ajax({
			url: "clients/search",
			data: {_token: token, search: search},
			method: "POST",
			datatype: "json",
			success: function(data){
				$('.data-container').html('');
				$.each(data['clients'], function(key, val){
					$('.data-container').append(
						`<div class="card hidden">
							<div class="card-header">${val['local']}
								<a href="clients/delete/${val['client_id']}" class="btn btn-danger btn-sm pull-right"><i class="fa fa-trash"></i>&nbsp Delete</a>
								<a href="clients/edit/${val['client_id']}" target="_blank" class="btn btn-info btn-sm pull-right"><i class="fa fa-pencil"></i>&nbsp Edit</a>
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
				$.each($('.card'), function(key, val){
					$(this).fadeIn(400);
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

	function generate_search(data, token){
		var html;
		$.each(data['clients'], function(key, val){ 
			html += `<option value="${val['local']}">${val['local']}</option>`; 
		});
		$('.data-container').before(
			`<div class="form-group">
				<label for="search">Search</label>
				<div class="input-group">
					<input class="form-control" id="search-input" list="search-list" autocomplete="off" placeholder="Search">
					<datalist id="search-list">
					${ html }
					</datalist>
					<button class="btn btn-warning text-white input-group-addon" id="search-button" style="background-color: #f0ad4e"><i class="fa fa-search"></i>&nbsp Search</button>
				</div>
			</div>`
		);
		bind(token);
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
