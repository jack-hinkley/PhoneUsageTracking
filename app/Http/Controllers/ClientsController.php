<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Clients;
use App\Http\Controllers\Controller;

class ClientsController extends Controller
{
	//	USER ROUTES
	public function index()
	{
		$clients['clients'] = Clients::all();
		return view('clients.index', ['clients' => $clients ]);
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

	public function search(Request $request)
	{
		$clients['clients'] = $this->db_search($request['search']);
		return $clients;
	}

	public function create(Request $request)
	{
		$this->db_create($request['local'], $request['address'], $request['province'], $request['postal']);
		return redirect('/clients');
	}

	public function edit(Request $request, $id)
	{
		$this->db_create($id, $request['local'], $request['address'], $request['province'], $request['postal']);
		return redirect('/clients');
	}

	public function delete(Request $request, $id)
	{
		Clients::find($id)->delete();
		return redirect('/clients');
	}

	public function autocomplete(Request $request)
	{
		$data = Clients::select("local")
			->where("local","LIKE","%{$request->input('query')}%")
			->get();
		return response()->json($data);
	}

	//	DATABASE CALLS
	public function db_create($local, $address, $province, $postal){
		$client = new Clients;
		$client->local = $local;
		$client->address = $address;
		$client->province = $province;
		$client->postal = $postal;
		$client->created_at = date('Y-m-d');
		$client->updated_at = date('Y-m-d');
		$client->save();
	}

	public function db_edit($id, $local, $address, $province, $postal){
		$client = Clients::find($id);
		$client->local = $local;
		$client->address = $address;
		$client->province = $province;
		$client->postal = $postal;
		$client->updated_at = date('Y-m-d');
		$client->save();
	}

	public function db_search($search){
		return Clients::where('local', 'like', '%'.$search.'%')
			->orwhere('address', 'like', '%'.$search.'%')
			->orwhere('province', 'like', '%'.$search.'%')
			->orwhere('postal', 'like', '%'.$search.'%')
			->get();
	}
}
