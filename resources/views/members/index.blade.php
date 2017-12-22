@extends('layouts.app')
@section('content')
<div class="container">
	<div class="content hidden">
		<div class="page-header">
			<h3 class="title pull-left">MEMBERS</h3>
			<a href="members/create" class="btn btn-success pull-right"><i class="fa fa-plus"></i>&nbsp Add Member</a>
		</div><hr>
		<div class="form-group">
			<label for="local-selector">Local</label>	
			<select class="form-control" id="local-selector">
				<?php foreach ($locals['locals'] as $key => $local) {
					echo '<option value="">'.$local->local.'</option>';
				} ?>
			</select>
		</div>
		<div class="data-container"></div>
	</div>
</div>
<script type="text/javascript">
	var attempts = 0;
	$(document).ready(function(){
		setTimeout(function(){ $('.content').removeClass('hidden') }, 200);
		//	Set token and data
		var token = '<?php echo Session::token();?>';
		var local = $('#local-selector').find(':selected').text();
		//	If the select element changes, call change_data function with new local
		$('#local-selector').change(function(){
			local = $('#local-selector').find(':selected').text();
			get_members(token, local);
		});
		
		generate_search(token);
		get_members(token, local);
	});

	//	Called because search is generated after the document is ready
	function bind(token){
		//	Listener for when the user presses enter
		$('#search-input').keypress(function(e){
			if(e.which == 13)
				$('#search-button').click();
		});

		//	Change data based on query in search bar
		$('#search-button').click(function(){
			search = $('#search-input').val();
			search_members(token, search);
		});
	}

	//	Purpose: 	This function makes and ajax call that returns all members in the system
	//	Params: 	CSRF token, selected date
	//	Returns: 	Array of all requested invoices
	function get_members(token, local) {
		$.ajax({
			url: "members/get",
			data: {_token: token, local: local},
			method: "POST",
			datatype: "json",
			success: function(data){
				$('.data-container').html('');
				$.each(data['members'], function(key, val){
					var phone = format_phone(val['phone']);
					$('.data-container').append(
						`<div class="card hidden">
							<div class="card-header">${val['first_name']} ${val['last_name']}
								<a href="#" class="btn btn-danger btn-sm pull-right" data-toggle="modal" data-target="#deleteModal_${val['member_id']}"><i class="fa fa-trash"></i>&nbsp Delete</a>
								<a href="members/edit/${val['member_id']}" target="_blank" class="btn btn-info btn-sm pull-right"><i class="fa fa-pencil"></i>&nbsp Edit</a>
							</div>
							<div class="card-body">
								<div class="col-sm-12">
									<div class="row">
										<div class="col"><i class="fa fa-mobile"></i>&nbsp&nbsp${phone}</div>
										<div class="col"><i class="fa fa-building-o"></i>&nbsp&nbsp${val['local']}</div>
									</div>
								</div>
							</div>
						</div>

						<div class="modal fade" id="deleteModal_${val['member_id']}" tabindex="-1" role="dialog">
							<div class="modal-dialog" role="document">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title">Delete Member</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>
									<div class="modal-body">
										Are you sure you want to delete ${val['first_name']} ${val['last_name']}
									</div>
									<div class="modal-footer">
										<button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
										<a href="members/delete/${val['member_id']}" class="btn btn-danger">Delete</a>
									</div>
								</div>
							</div>
						</div>`
					);
					//	Clean loading
					$('.content').removeClass('hidden');
				});
				$.each($('.card'), function(key, val){
					$(this).fadeIn(400);
				});
			}, error: function(){
				if(attempts < 5){
					attempts++;
					get_members(token);
				}
				else 
					$('.data-container').append('<h3>There was a problem connecting</h3>');
			}
		});
	}

	//	Purpose: 	This function makes and ajax call that returns all members in the system
	//	Params: 	CSRF token, selected date
	//	Returns: 	Array of all requested invoices
	function search_members(token, search) {
		$.ajax({
			url: "members/search",
			data: {_token: token, search: search},
			method: "POST",
			datatype: "json",
			success: function(data){
				$('.data-container').html('');
				$.each(data['members'], function(key, val){
					var phone = format_phone(val['phone']);
					$('#search-input').val('');
					$('.data-container').append(
						`<div class="card hidden">
							<div class="card-header">${val['first_name']} ${val['last_name']}
								<a href="#" class="btn btn-danger btn-sm pull-right" data-toggle="modal" data-target="#deleteModal_${val['member_id']}"><i class="fa fa-trash"></i>&nbsp Delete</a>
								<a href="members/edit/${val['member_id']}" target="_blank" class="btn btn-info btn-sm pull-right"><i class="fa fa-pencil"></i>&nbsp Edit</a>
							</div>
							<div class="card-body">
								<div class="col-sm-12">
									<div class="row">
										<div class="col"><i class="fa fa-mobile"></i>&nbsp&nbsp${phone}</div>
										<div class="col"><i class="fa fa-building-o"></i>&nbsp&nbsp${val['local']}</div>
									</div>
								</div>
							</div>
						</div>

						<div class="modal fade" id="deleteModal_${val['member_id']}" tabindex="-1" role="dialog">
							<div class="modal-dialog" role="document">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title">Delete Member</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>
									<div class="modal-body">
										Are you sure you want to delete ${val['first_name']} ${val['last_name']}
									</div>
									<div class="modal-footer">
										<button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
										<a href="members/delete/${val['member_id']}" class="btn btn-danger">Delete</a>
									</div>
								</div>
							</div>
						</div>`
					);
					//	Clean loading
					$('.content').removeClass('hidden');
				});
				$.each($('.card'), function(key, val){
					$(this).fadeIn(400);
				});
			}, error: function(){
				if(attempts < 5){
					attempts++;
					get_members(token);
				}
				else 
					$('.data-container').append('<h3>There was a problem connecting</h3>');
			}
		});
	}

	function generate_search(token){
		$.ajax({
			url: "members/getall",
			data: {_token: token},
			method: "POST",
			datatype: "json",
			success: function(data){
				var html;
				$.each(data['members'], function(key, val){ 
					html += `<option value="${val['first_name']} ${val['last_name']}">${val['first_name']} ${val['last_name']}</option>`; 
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