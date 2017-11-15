@extends('layouts.app')
@section('content')
	<div class="container">
		<div class="content hidden">
			<div class="page-header">
				<h3 class="title ">INVOICE DETAILS</h3><hr>
				<a href="/phonedata" class="btn btn-outline-secondary" style="margin-bottom: 20px">Back to Invoices</a>
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
						<br>
						<h3>COST BREAKDOWN</h3>
						<div class="row">
							@foreach ($invoice['data_cost'] as $key => $val)
									<div class="col-md-6">
										<div class="row">
											<div class="col-md-8">{{ str_replace('_', ' ', ucfirst($key)) }}</div>
											<div class="col-md-4" style="text-align: right;">${{ number_format(round($val, 2), 2, '.', '') }}</div>	
										</div>
									</div>
							@endforeach	
						</div>
						<br>
						<?php if(sizeof($invoice['data_usage']) > 0){ ?>
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
													else if(strpos($key, 'minutes')) echo ' Minutes';
													else echo ' Texts';
													?>
												</div>	
											</div>
										</div>
									@endforeach	 
								@endforeach	
							</div>
							<br>
						<?php } ?>
						<?php if(sizeof($invoice['zone_usage']) > 0){ ?>
						<h3>ZONE USAGE</h3>
						<div class="row">
							@foreach ($invoice['zone_usage'] as $usage)
								@foreach ($usage->toArray() as $key => $val)
									<?php if($key == 'id' || $key == 'invoice_date' || $key == 'phone' || $val < 1) continue; ?>
									<div class="col-md-6">
										<div class="row">
											<div class="col-md-8">{{ str_replace('_', ' ', ucfirst($key)) }}</div>
											<div class="col-md-4" style="text-align: right;">{{ ceil($val) }}<?php 
												if(strpos($key, 'mb') || strpos($key, 'data')) echo ' MB';
												else if(strpos($key, 'minutes') || strpos($key, 'voice')) echo ' Minutes';
												else echo ' Texts';
												?>													
											</div>
										</div>
									</div>
								@endforeach	 
							@endforeach	
						</div>
						<br>
						<?php } ?>
						<div>
							<h2><span class="ml-auto">Total Invoice:</span><span style="position: absolute; right: 20px">${{ $invoice['invoices']->invoice_total }}</span></h2>	
						</div>
					</div>

				</div>
				<br>
				<button class="btn btn-warning pull-right"><i class="fa fa-refresh"></i>&nbsp&nbspGenerate Report</button>
			</div>
		</div>
	</div>
<script type="text/javascript">
	setTimeout(function(){ $('.content').removeClass('hidden') }, 100);
</script>
@endsection
