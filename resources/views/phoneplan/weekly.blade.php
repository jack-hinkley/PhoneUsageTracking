@extends('layouts.app')
@section('content')
	<div class="container">
		<div class="content hidden">
			<div class="page-header">
				<h3 class="title ">WEEKLY USAGE</h3><hr>
					<a href="#" class="btn btn-success pull-right d-print-none" data-toggle="modal" data-target="#uploadModal"><i class="fa fa-plus"></i>&nbsp Upload CSV</a>
					<div class="form-group d-print-none">
						<label for="date-selector">Date</label>	
						<select class="form-control" id="date-selector">
							<?php foreach ($dates as $key => $date){
								echo '<option value="">'.$date->date.'</option>';
							} ?>
						</select>
					</div>
					<div class="data-container"></div>

					<div class="modal fade" id="uploadModal">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<form action="/phoneplan/uploadweekly" method="POST" enctype="multipart/form-data">
								{{ csrf_field() }}
									<div class="modal-header">
										<h5 class="modal-title">Upload CSV</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>
									<div class="modal-body">
										<div class="file-input">
											<span>Click to upload</span>
											<input type="file" name="imported-file" accept=".csv"/>	
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
		</div>
	</div>
<script type="text/javascript">
	setTimeout(function(){ $('.content').removeClass('hidden') }, 100);
	var token = '<?php echo Session::token();?>';
	var date = $('#date-selector').find(':selected').text();
	//	If the select element changes, call_change data function with new date
	$('#date-selector').change(function(){
		date = $('#date-selector').find(':selected').text();
		change_data(token, date);
	});
	change_data(token, date);
	function change_data(token, date) {
		$.ajax({
			url: "/phoneplan/getweekly",
			data: {_token: token, date: date},
			method: "POST",
			datatype: "json",
			success: function(data){
				$('.data-container').html('');
				if(data.length == 0)
					$('.data-container').append('<h3 class="text-muted" style="width: 100%; text-align: center; opacity:0">There are no search results</h3>')
					.fadeIn("slow", function(){ $('.text-muted').animate({'opacity': '1'})});
				var html = '';
				$.each(data, function(key, val){
					phone = format_phone(val['phone']);
					html +=
						`<div class="card hidden">
							<div class="card-header">
								${ val['first_name'] } ${val['last_name']}
							</div>
							<div class="card-body">
								<div class="col-sm-12">
									<div class="row">
										<div class="col"><i class="fa fa-phone"></i>&nbsp&nbsp${phone}</div>
										<div class="col"><i class="fa fa-archive"></i>&nbsp&nbsp${val['plan_data']} MB</div>
										<div class="col"><i class="fa fa-database"></i>&nbsp&nbsp${val['usage']} MB</div>
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
	//	Purpose: 	This function takes a 10 digit phone number and formats it to be reader friendly
	//	Params: 	10 digit phone number
	//	Returns: 	Formated phone number
	function format_phone(phone) {
		var formated = phone.match(/^(\d{3})(\d{3})(\d{4})$/);
		return formated[1]+" "+formated[2]+" "+formated[3];
	}

</script>
@endsection
