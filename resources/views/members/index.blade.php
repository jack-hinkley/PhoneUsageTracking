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
				<?php foreach ($locals['locals'] as $key => $local){
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
		get_members(token, local);
	});

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
						`<div class="card">
							<div class="card-header">${val['first_name']} ${val['last_name']}
								<a href="#" class="btn btn-danger btn-sm pull-right" data-toggle="modal" data-target="#deleteModal_${val['member_id']}"><i class="fa fa-trash"></i>&nbsp Delete</a>
								<a href="members/edit/${val['member_id']}" class="btn btn-info btn-sm pull-right"><i class="fa fa-pencil"></i>&nbsp Edit</a>
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

	//	Purpose: 	This function takes a 10 digit phone number and formats it to be reader friendly
	//	Params: 	10 digit phone number
	//	Returns: 	Formated phone number
	function format_phone(phone) {
		var formated = phone.match(/^(\d{3})(\d{3})(\d{4})$/);
		return formated[1]+" "+formated[2]+" "+formated[3];
	}
</script>
@endsection