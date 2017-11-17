<style type="text/css">
	.nav-right {
		right: 20px; 
		position: absolute;
	}
</style>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top d-print-none">
	<a class="navbar-brand" href="/">USI CRM</a>
	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
	 <span class="navbar-toggler-icon"></span>
	</button>
	<div class="collapse navbar-collapse" id="navbarNav">
		<ul class="navbar-nav">
			<li class="nav-item">
				<a class="nav-link" href="/">Home</a>
			</li>
			@if (Auth::check())
			<li class="nav-item">
				<a class="nav-link" href="{{ url('/phoneplan') }}">Phone Plans</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="{{ url('/clients') }}">Clients</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="{{ url('/members') }}">Members</a>
			</li>
		</ul>
		<ul class="nav navbar-nav nav-right" >
			<li class="dropdown">
				<a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown" role="button" aria-expanded="false">
				{{ Auth::user()->name }} <span class="caret"></span>
				</a>
				<ul class="dropdown-menu" role="menu">
					<li><a href="{{ url('/logout') }}"><i class="fa fa-btn fa-sign-out"></i>Logout</a></li>
				</ul>
			</li>
		</ul>
		@else
			<li class="nav-item">
				<a class="nav-link" href="{{ url('/login') }}">Login</a>
			</li>
		</ul>
		@endif
		
	</div>
</nav>