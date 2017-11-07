@extends('layouts.app')
@section('content')

<style type="text/css">
	.login-form {
		width: 70%;
		margin: 0 auto;
		margin-top: 20%;
	}
</style>
<div class="container">
	<div class="card login-form">
		<div class="card-header bg-dark text-white">Login</div>
		<div class="card-block">
			<form class="form-horizontal" role="form" method="POST" action="{{ url('/login') }}">
				{{ csrf_field() }}
				<div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
					<label for="email" class="col-md-4 control-label">E-Mail Address</label>
					<div class="col-md-12">
						<input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}">
						@if ($errors->has('email'))
							<span class="help-block">
								<strong>{{ $errors->first('email') }}</strong>
							</span>
						@endif
					</div>
				</div>

				<div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
					<label for="password" class="col-md-4 control-label">Password</label>
					<div class="col-md-12">
						<input id="password" type="password" class="form-control" name="password">
						@if ($errors->has('password'))
							<span class="help-block">
								<strong>{{ $errors->first('password') }}</strong>
							</span>
						@endif
					</div>
				</div>

				<div class="form-group">
					<div class="col-md-12 col-md-offset-4">
						<div class="checkbox">
							<label>
								<input type="checkbox" name="remember"> Remember Me
							</label>
						</div>
					</div>
				</div>

				<div class="form-group">
					<div class="col-md-12 col-md-offset-4">
						<button type="submit" class="btn btn-primary">
							<i class="fa fa-btn fa-sign-in"></i> Login
						</button>
						<a class="btn btn-link" href="{{ url('/password/reset') }}">Forgot Your Password?</a>
					</div>
				</div>
			</form>
		</div>
	</div>	
</div>
@endsection