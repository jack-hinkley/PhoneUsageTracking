@extends('layouts.app')
@section('content')
<div class="container">
	<div class="content">
		<div class="page-header">
			<h3 class="title pull-left">CREATE CLIENT</h3>
		</div><hr>
		<a href="/clients" class="btn btn-outline-secondary" style="margin-bottom: 20px">Back to Clients</a>
		<div id="error"></div>
		<form action="/clients/create" method="POST" role="form" onsubmit="return validation()" id="form">
		{{ csrf_field() }}
			<div class="col-md-12">
				<!-- ROW 1 -->
				<div class="row">
					<div class="col">
						<div class="form-group">
							<label for="local">Local</label>
							<input name="local" class="form-control" id="local" placeholder="Local" required>
						</div>
					</div>
					<div class="col">
						<div class="form-group">
							<label for="province">Province</label>
							<select name="province" class="form-control" id="province">
								<option value="Alberta">Alberta</option>
								<option value="British Columbia">British Columbia</option>
								<option value="Manitoba">Manitoba</option>
								<option value="New Brunswick">New Brunswick</option>
								<option value="Newfoundland and Labrador">Newfoundland and Labrador</option>
								<option value="Nova Scotia">Nova Scotia</option>
								<option value="Ontario" selected>Ontario</option>
								<option value="Prince Edward Island">Prince Edward Island</option>
								<option value="Quebec">Quebec</option>
								<option value="Saskatchewan">Saskatchewan</option>
								<option value="Northwest Territories">Northwest Territories</option>
								<option value="Nunavut">Nunavut</option>
								<option value="Yukon">Yukon</option>
							</select>	
						</div>
					</div>
				</div>
				<!-- ROW 2 -->
				<div class="row">
					<div class="col">
						<div class="form-group">
							<label for="address">Address</label>
							<input name="address" class="form-control" id="address" placeholder="Address">
						</div>
					</div>
					<div class="col">
						<div class="form-group">
							<label for="postal">Postal Code</label>
							<input name="postal" class="form-control" id="postal" placeholder="Postal Code" >
						</div>
					</div>
				</div>
				<input type="submit" class="btn btn-primary" value="Submit">
			</div>
		</form>
		<div class="data-container"></div>
	</div>
</div>

<script type="text/javascript">
	function validation() {
		var token = '<?php echo Session::token();?>';
		var local = $('#local').val();
		var result = true;
		validate = ["<?php
			foreach ($clients['clients'] as $key => $value) {
				echo $value['local'].'", "';
			}
			?>"];
		$.each(validate, function(key, val){
			if(val == local) result = false;
		});
		if(result == true) {
			return true;
		}	else {
			$('#error').html(`
				<div class="alert alert-warning fade show" role="alert">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close">
				    <span aria-hidden="true">&times;</span>
				  </button>
			  	<strong>Warning!</strong> This client already exists
				</div>
			`);
			return false;
		}
	}
</script>
@endsection