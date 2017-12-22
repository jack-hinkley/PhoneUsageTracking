@extends('layouts.app')
@section('content')
<div class="container">
	<div class="content">
		<div class="page-header">
			<h3 class="title pull-left">EDIT CLIENT</h3>
		</div><hr>
		<a href="/clients" class="btn btn-outline-secondary" style="margin-bottom: 20px">Back to Clients</a>
		<form action="/clients/edit/" method="POST" role="form">
		{{ csrf_field() }}
			<div class="col-md-12">
				<!-- ROW 1 -->
				<div class="row">
					<div class="col">
						<div class="form-group">
							<label for="local">Local</label>
							<input name="local" class="form-control" id="local" placeholder="Local" value="{{$clients['client']->local}}" required>
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
							<input name="address" class="form-control" id="address" placeholder="Address" value="{{$clients['client']->address}}">
						</div>
					</div>
					<div class="col">
						<div class="form-group">
							<label for="postal">Postal Code</label>
							<input name="postal" class="form-control" id="postal" placeholder="Postal Code" value="{{$clients['client']->postal}}">
						</div>
					</div>
				</div>
				<input type="submit" class="btn btn-primary" value="Update">
			</div>
		</form>
		<hr>
		<ul class="list-group">
			@foreach ($clients['members'] as $member)
				<li class="list-group-item">
					<div class="row">
						<div class="col-md-10">
							<div class="row">
								<span class="col">{{ $member->first_name }} {{ $member->last_name }}</span>	
								<span class="col"><?php 
									preg_match( '/^(\d{3})(\d{3})(\d{4})$/', $member->phone, $matches);
										$phone = $matches[1].' '.$matches[2].' '.$matches[3];
										echo $phone;
									?>
								</span>
							</div>
						</div>
						<div class="col-md-2">
							<span class="col pull-right"><a href="/members/edit/{{$member->member_id}}" target="_blank" class="btn btn-info btn-sm pull-right"><i class="fa fa-pencil"></i>&nbsp Edit</a></span>
						</div>
					</div>					
				</li>
			@endforeach
		</ul> 
		

	</div>
</div>
<script type="text/javascript">
	$(document).ready(function(){
		var id = window.location.href.substr(window.location.href.lastIndexOf('/')+1);
		$('form').attr('action', '/clients/edit/'+id);

		$.each($('#province option'), function(key, val){
			if($(val).val() == '{{$clients['client']->province}}')
				$(val).attr('selected', 'selected');
		});
	});	
</script>
@endsection