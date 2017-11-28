@extends('layouts.app')
@section('content')
<div class="container">
	<div class="content">
		<div class="page-header">
			<h3 class="title pull-left">EDIT MEMBER</h3>
		</div><hr>
		<a href="/members" class="btn btn-outline-secondary" style="margin-bottom: 20px">Back to Members</a>
		<form action="/members/edit/" method="POST" role="form">
		{{ csrf_field() }}
			<div class="col-md-12">
				<!-- ROW 1 -->
				<div class="row">
					<div class="col">
						<div class="form-group">
							<label for="first_name">First Name</label>
							<input name="first_name" class="form-control" id="first_name" placeholder="First Name" value="<?php echo $members->first_name?>" required>
						</div>
					</div>
					<div class="col">
						<div class="form-group">
							<label for="last_name">Last Name</label>
							<input name="last_name" class="form-control" id="last_name" placeholder="Last Name" value="<?php echo $members->last_name?>" required>
						</div>
					</div>
				</div>
				<!-- ROW 2 -->
				<div class="row">
					<div class="col">
						<div class="form-group">
							<label for="email">Email</label>
							<input name="email" class="form-control" id="email" placeholder="Email" value="<?php echo $members->email?>">
						</div>
					</div>
					<div class="col">
						<div class="form-group">
							<label for="phone">Phone</label>
							<input type="tel" pattern="[0-9\s]+" maxlength="12" name="phone" class="form-control" id="phone" placeholder="Phone" value="<?php 
								preg_match( '/^(\d{3})(\d{3})(\d{4})$/', $members->phone,  $matches );
								$phone = $matches[1].' '.$matches[2].' '.$matches[3];
								echo $phone;
								?>" required>
						</div>
					</div>
					<div class="col">
						<div class="form-group">
							<label for="mobile">Mobile</label>
							<input name="mobile" class="form-control" id="mobile" value="<?php
								if(isset($members->mobile)){
									preg_match( '/^(\d{3})(\d{3})(\d{4})$/', $members->mobile,  $matches );
									$phone = $matches[1].' '.$matches[2].' '.$matches[3];
									echo $phone;
								}								
							?>" placeholder="Mobile">
						</div>
					</div>
				</div>
				<!-- ROW 3 -->
				<div class="row">
					<div class="col">
						<div class="form-group">
							<label for="address">Address</label>
							<input name="address" class="form-control" id="address" placeholder="Address" value="<?php echo $members->address?>">
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
					<div class="col">
						<div class="form-group">
							<label for="postal">Postal Code</label>
							<input name="postal" class="form-control" id="postal" placeholder="Postal Code" pattern="[A-Za-z][0-9][A-Za-z] [0-9][A-Za-z][0-9]" value="<?php echo $members->postal?>">
						</div>
					</div>
				</div>
				<!-- ROW 4 -->
				<div class="row">
					<div class="col">
						<div class="form-group">
							<label for="birthday">Birthday</label>
							<input type="date" name="birthday" class="form-control" id="birthday" value="<?php echo $members->birthday?>">
						</div>
					</div>
					<div class="col">
						<div class="form-group">
							<label for="local">Local</label>
							<input class="form-control" name="local" id="local" list="local-list" placeholder="Local">
							<datalist id="local-list">
								<?php foreach ($locals['locals'] as $key => $local) {
									echo '<option value="'.$local->local.'">';
								} ?>
							</datalist>
						</div>
					</div>
					<div class="col">
						<div class="form-group">
							<label for="plan_rate">Phone Plan Rate ($)</label>
							<input name="plan_rate" class="form-control" id="plan_rate" value="{{ number_format($members->plan_rate, '2', '.', '') }}">
						</div>
					</div>
					<div class="col">
						<div class="form-group">
							<label for="plan_data">Phone Plan Data</label>
							<select class="form-control" name="plan_data" id="plan_data">
								<option value="3072">3,072 MB</option>
								<option value="6144">6,144 MB</option>
							</select>
						</div>
					</div>
					
				</div>
				<input type="submit" class="btn btn-primary" id="submit" value="Update">
			</div>
		</form>
		<div class="data-container"></div>
	</div>
</div>
<script type="text/javascript">
	$(document).ready(function(){
		var id = window.location.href.substr(window.location.href.lastIndexOf('/')+1);
		$('form').attr('action', '/members/edit/'+id);

		$.each($('#province option'), function(key, val){
			if($(val).val() == '<?php echo $members->province?>')
				$(val).attr('selected', 'selected');
		});

		$.each($('#plan_data option'), function(key, val){
			if($(val).val() == '<?php echo $members->plan_data?>')
				$(val).attr('selected', 'selected');
		});
	});
	
</script>
@endsection