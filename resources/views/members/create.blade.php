@extends('layouts.app')
@section('content')
<div class="container">
	<div class="content">
		<div class="page-header">
			<h3 class="title pull-left">CREATE MEMBER</h3>
		</div><hr>
		<?php 
			if(isset($phone)) 
				echo '<a href="/phonedata/outstanding" class="btn btn-outline-secondary" style="margin-bottom: 20px">Back to Outstanding Numbers</a>';
			else 
				echo '<a href="/members" class="btn btn-outline-secondary" style="margin-bottom: 20px">Back to Members</a>';
		 ?>
		
		<form action="/members/create" method="POST" role="form">
		{{ csrf_field() }}
			<div class="col-md-12">
				<!-- ROW 1 -->
				<div class="row">
					<div class="col">
						<div class="form-group">
							<label for="first_name">First Name</label>
							<input name="first_name" class="form-control" id="first_name" placeholder="First Name" required>
						</div>
					</div>
					<div class="col">
						<div class="form-group">
							<label for="last_name">Last Name</label>
							<input name="last_name" class="form-control" id="last_name" placeholder="Last Name" required>
						</div>
					</div>
				</div>
				<!-- ROW 2 -->
				<div class="row">
					<div class="col">
						<div class="form-group">
							<label for="email">Email</label>
							<input name="email" class="form-control" id="email" placeholder="Email" required>
						</div>
					</div>
					<div class="col">
						<div class="form-group">
							<label for="phone">Phone</label>
							<input type="tel" pattern="[0-9\s]+" maxlength="12" name="phone" class="form-control" id="phone" placeholder="Phone" required 
								<?php if(isset($phone)) {
									preg_match( '/^(\d{3})(\d{3})(\d{4})$/', $phone,  $matches );
								$formatted = $matches[1].' '.$matches[2].' '.$matches[3];
								echo 'value="'.$formatted.'"'; 
								}?>	>
						</div>
					</div>
					<div class="col">
						<div class="form-group">
							<label for="mobile">Mobile</label>
							<input name="mobile" class="form-control" id="mobile" placeholder="Mobile">
						</div>
					</div>
				</div>
				<!-- ROW 3 -->
				<div class="row">
					<div class="col">
						<div class="form-group">
							<label for="address">Address</label>
							<input name="address" class="form-control" id="address" placeholder="Address" required>
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
							<input name="postal" class="form-control" id="postal" placeholder="Postal Code">
						</div>
					</div>
				</div>
				<!-- ROW 4 -->
				<div class="row">
					<div class="col">
						<div class="form-group">
							<label for="birthday">Birthday</label>
							<input type="date" name="birthday" class="form-control" id="birthday">
						</div>
					</div>
					<div class="col">
						<div class="form-group">
							<label for="local">Local</label>
							<select class="form-control" name="local" id="local">
								<?php foreach ($locals['locals'] as $key => $local){
									echo '<option value="'.$local['client_id'].'">'.$local->local.'</option>';
								} ?>
							</select>
						</div>
					</div>
				</div>
				<input type="submit" class="btn btn-primary" value="Submit">
			</div>
		</form>
		<div class="data-container"></div>
	</div>
</div>
@endsection