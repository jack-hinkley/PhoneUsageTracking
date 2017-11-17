@extends('layouts.app')
@section('content')
	<div class="container">
		<div class="content hidden">
			<div class="page-header">
				<h3 class="title ">OUTSTANDING NUMBERS</h3><hr>
					<div class="col-md-12">
						<ul class="list-group">
							<?php 
							foreach($outstanding['outstanding'] as $key => $out){
								preg_match( '/^(\d{3})(\d{3})(\d{4})$/', $out->phone,  $matches );
								$phone = $matches[1].' '.$matches[2].' '.$matches[3];
								echo '<li class="list-group-item">'.$phone.'<a href="/members/create/'.$out->phone.'"class="btn btn-sm btn-success pull-right"><i class="fa fa-plus"></i>&nbspAdd Member</a></li>';
							}	
							?>
						</ul>
					</div>
			</div>
		</div>
	</div>
<script type="text/javascript">
	setTimeout(function(){ $('.content').removeClass('hidden') }, 100);
</script>
@endsection
