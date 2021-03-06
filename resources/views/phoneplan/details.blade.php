@extends('layouts.app')
@section('content')
	<div class="container">
		<div class="content hidden">
			<div class="page-header">
				<h3 class="title d-print-none">INVOICE DETAILS</h3><hr class="d-print-none">
				<a href="/phoneplan" class="btn btn-outline-secondary d-print-none" style="margin-bottom: 20px">Back to Invoices</a>
				<div class="card">
					<div class="card-header">INVOICE #{{ sprintf("%06d",intval($invoice['invoices']->invoice_id)) }}</div>
					<div class="card-body">

						<div class="row">
							<div class="form-group col-md-6">
								<label for="name">Name</label>
								<input name="name" class="form-control info" value="{{ $invoice['invoices']->first_name }} {{ $invoice['invoices']->last_name }}" disabled="true">
							</div>
							<div class="form-group col-md-6">
								<label for="phone">Phone Number</label>
								<input name="phone" class="form-control info" value="<?php 
									preg_match( '/^(\d{3})(\d{3})(\d{4})$/', $invoice['invoices']->phone, $matches);
									$phone = $matches[1].' '.$matches[2].' '.$matches[3]; 
									echo $phone;
									?>" disabled="true">
							</div>

							<div class="form-group col-md-6">
								<label for="name">Address</label>
								<input name="name" class="form-control info" value="{{ $invoice['invoices']->address }}, {{ $invoice['invoices']->province }}, {{ $invoice['invoices']->postal }}" disabled="true">
							</div>
							<div class="form-group col-md-6">
								<label for="local">Local/Home</label>
								<input name="local" class="form-control info" value="{{ $invoice['invoices']->local }}" disabled="true">
							</div>

							<div class="form-group col-md-4">
								<label for="invoice_date">Date</label>
								<input name="invoice_date" class="form-control info" value="{{ $invoice['invoices']->invoice_date }}" disabled="true">
							</div>
							<div class="form-group col-md-4 info">
								<label for="plan_data">Data Plan</label>
								<select name="plan_data" id="plan_data" class="form-control">
									<option>3072 MB</option>
									<option>6144 MB</option>
								</select>
							</div>
							<div class="form-group col-md-4 info">
								<label for="total_data_usage">Total Data Usage</label>
								<input name="total_data_usage" class="form-control" value="{{ $invoice['invoices']->total_data }} MB" disabled="true">
							</div>
						</div>
						<br>
						<h3>COST BREAKDOWN</h3>
						<div class="row">
							@foreach ($invoice['data_cost'] as $key => $val)
								<?php if($val < 0.01) continue; ?>
								<div class="col-md-6">
									<div class="row">
										<div class="col-md-8">{{ str_replace('_', ' ', ucfirst($key)) }}</div>
										<div class="col-md-4" style="text-align: right;">${{ number_format(round($val, 2), 2, '.', '') }}</div>	
									</div>
								</div>
							@endforeach
							@foreach ($invoice['list_cost'] as $key => $val)
								<?php if($val < 0.01) continue; ?>
								<div class="col-md-6">
									<div class="row">
										<div class="col-md-8">{{ str_replace('_', ' ', ucfirst($key)) }}</div>
										<div class="col-md-4" style="text-align: right;">${{ number_format(round($val, 2), 2, '.', '') }}</div>	
									</div>
								</div>
							@endforeach
							@foreach ($invoice['other_cost'] as $key => $val)
								<?php if($val < 0.01 || strpos('_'.$key, 'total')) continue; ?>
								<div class="col-md-6">
									<div class="row">
										<div class="col-md-8">{{ str_replace('_', ' ', ucfirst($key)) }}</div>
										<div class="col-md-4" style="text-align: right;">${{ number_format(round($val, 2), 2, '.', '') }}</div>	
									</div>
								</div>
							@endforeach	
						</div>
						<br>
						<h3>USAGE</h3>
						<div class="row">
							@foreach ($invoice['data_usage'] as $usage)
								@foreach ($usage->toArray() as $key => $val)
									<?php if($key == 'id' || $key == 'invoice_date' || $key == 'phone' || $val < 0.01) continue; ?>
									<div class="col-md-6">
										<div class="row">
											<div class="col-md-8">{{ str_replace('_', ' ', ucfirst($key)) }}</div>
											<div class="col-md-4" style="text-align: right;">{{ $val }}<?php 
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
						<?php if(sizeof($invoice['zone_usage']) > 0){ ?>
						<h3>ZONE USAGE</h3>
						<div class="row">
							@foreach ($invoice['zone_usage'] as $usage)
								@foreach ($usage->toArray() as $key => $val)
									<?php if($key == 'id' || $key == 'invoice_date' || $key == 'phone' || $val < 0.01) continue; ?>
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
							<h1><span class="ml-auto">INVOICE TOTAL:</span><span style="position: absolute; right: 20px">${{ $invoice['invoices']->invoice_total }}</span></h1>	
						</div>
					</div>

				</div>
				<br>
				<button class="btn btn-warning pull-right d-print-none" id="generate"><i class="fa fa-refresh"></i>&nbsp&nbspGenerate Report</button>
			</div>
		</div>
	</div>

<script type="text/javascript">
	setTimeout(function(){ $('.content').removeClass('hidden') }, 100);
	$('#generate').click(function(e){
		e.stopPropagation();
		$('title').text('invoice_{{ sprintf("%06d",intval($invoice['invoices']->invoice_id)) }}');
		window.print();
		$('title').text('USI CRM');
	});

	$.each($('#plan_data option'), function(key, val){
		data_plan = $(val).val().substring(0, $(val).val().length-3);
		if(data_plan == '<?php echo $invoice['invoices']->plan_data?>')
			$(val).attr('selected', 'selected');
	});
	
</script>
@endsection
