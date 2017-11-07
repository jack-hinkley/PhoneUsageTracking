<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Clients;
use App\Http\Controllers\Controller;

class ClientsController extends Controller
{
	public function index()
	{
		return view('clients.index');
	}

	public function createindex(Request $request)
	{
		return view('clients.create');
	}

	public function editindex(Request $request, $id)
	{
		$clients = Clients::where('client_id', '=', $id)->get()[0];
		return view('clients.edit', ['clients' => $clients]);
	}


	//	AJAX CALLS
	public function get(Request $request)
	{
		$clients['clients'] = Clients::all();
		return $clients;
	}

	public function create(Request $request)
	{
		$client = new Clients;
		$client->local = $request->input('local');
		$client->address = $request->input('address');
		$client->province = $request->input('province');
		$client->postal = $request->input('postal');
		$client->created_at = date('Y-m-d');
		$client->updated_at = date('Y-m-d');
		$client->save();
		return redirect('/clients');
	}

	public function edit(Request $request, $id)
	{
		$client = Clients::find($id);
		$client->local = $request->input('local');
		$client->address = $request->input('address');
		$client->province = $request->input('province');
		$client->postal = $request->input('postal');
		$client->updated_at = date('Y-m-d');
		$client->save();
		return redirect('/clients');
	}

	public function delete(Request $request, $id)
	{
		Clients::find($id)->delete();
		return redirect('/clients');
	}
}
