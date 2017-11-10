@extends('layouts.app')
@section('content')
	<div class="container">
		<div class="content hidden">
			<div class="page-header">
				<h3 class="title ">INVOICE DETAILS</h3><hr>
				<div class="card">
					<div class="card-header">INVOICE {{ $invoice['invoices']->invoice_id }}</div>
					<div class="card-body">
						<div class="row">
							<div class="form-group col-md-6">
								<label for="name">Member Name</label>
								<input name="name" class="form-control" value="{{ $invoice['invoices']->first_name }} {{ $invoice['invoices']->last_name }}" disabled="true">
							</div>
							<div class="form-group col-md-6">
								<label for="name">Phone Number</label>
								<input name="name" class="form-control" value="<?php 
									preg_match( '/^(\d{3})(\d{3})(\d{4})$/', $invoice['invoices']->phone, $matches);
									$phone = $matches[1].' '.$matches[2].' '.$matches[3]; 
									echo $phone;
									?>" disabled="true">
							</div>

							<div class="form-group col-md-6">
								<label for="name">Date</label>
								<input name="name" class="form-control" value="{{ $invoice['invoices']->invoice_date }}" disabled="true">
							</div>
							<div class="form-group col-md-6">
								<label for="name">Total Data</label>
								<input name="name" class="form-control" value="{{ $invoice['invoices']->total_data }} MB" disabled="true">
							</div>

							<div class="form-group col-md-6">
								<label for="name">Address</label>
								<input name="name" class="form-control" value="{{ $invoice['invoices']->address }}, {{ $invoice['invoices']->province }}, {{ $invoice['invoices']->postal }}" disabled="true">
							</div>
							<div class="form-group col-md-6">
								<label for="name">Local</label>
								<input name="name" class="form-control" value="{{ $invoice['invoices']->local }}" disabled="true">
							</div>
						</div>
						<h3>USAGE</h3>
						<div class="row">
							@foreach ($invoice['data_usage'] as $usage)
								@foreach ($usage->toArray() as $key => $val)
									<?php if($key == 'id' || $key == 'invoice_date' || $key == 'phone' || $val < 1) continue; ?>
									<div class="col-md-6">
										<div class="row">
											<div class="col-md-8">{{ str_replace('_', ' ', ucfirst($key)) }}</div>
											<div class="col-md-4" style="text-align: right;">{{ $val }}<?php 
												if(strpos($key, 'mb') || strpos($key, 'data')) echo ' MB';
												else echo ' Minutes';
												?></div>	
										</div>
									</div>
								@endforeach	 
							@endforeach	
						</div>
						<br>
						<h3>ZONE USAGE</h3>
						<div class="row">
							@foreach ($invoice['zone_usage'] as $usage)
								@foreach ($usage->toArray() as $key => $val)
									<?php if($key == 'id' || $key == 'invoice_date' || $key == 'phone' || $val < 1) continue; ?>
									<div class="col-md-6">
										<div class="row">
											<div class="col-md-8">{{ str_replace('_', ' ', ucfirst($key)) }}</div>
											<div class="col-md-4" style="text-align: right;">{{ ceil($val) }}<?php 
												if(strpos($val, 'mb') || strpos($val, 'data')) echo ' MB';
												else echo ' Minutes';
												?></div>
										</div>
									</div>
								@endforeach	 
							@endforeach	
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<script type="text/javascript">
	setTimeout(function(){ $('.content').removeClass('hidden') }, 100);
</script>
@endsection
