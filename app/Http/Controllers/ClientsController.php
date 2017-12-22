<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Clients;
use App\Members;
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
		$clients['clients'] = Clients::all()->toArray();
		return view('clients.create', ['clients' => $clients]);
	}

	public function editindex(Request $request, $id)
	{
		$clients['client'] = $this->db_get_by_id($id);
		$clients['members'] = $this->db_get_members($id);
		return view('clients.edit', ['clients' => $clients]);
	}

	//	AJAX CALLS
	public function get(Request $request)
	{
		$clients['clients'] = $this->db_get_all();

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
		$this->db_edit($id, $request['local'], $request['address'], $request['province'], $request['postal']);
		return '<script>window.close();</script>';
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
	public function db_get_all()
	{
		return Clients::select('*')
			->orderBy('local')
			->get();
	}

	public function db_create($local, $address, $province, $postal)
	{
		$client = new Clients;
		$client->local = $local;
		$client->address = $address;
		$client->province = $province;
		$client->postal = $postal;
		$client->created_at = date('Y-m-d');
		$client->updated_at = date('Y-m-d');
		$client->save();
	}

	public function db_edit($id, $local, $address, $province, $postal)
	{
		$client = Clients::find($id);
		$client->local = $local;
		$client->address = $address;
		$client->province = $province;
		$client->postal = $postal;
		$client->updated_at = date('Y-m-d');
		$client->save();
	}

	public function db_search($search)
	{
		return Clients::where('local', 'like', '%'.$search.'%')->get();
	}

	public function db_get_by_id($id)
	{
		return Clients::where('client_id', '=', $id)
			->get()[0];
	}

	public function db_get_members($id)
	{
		return Members::select('members.first_name', 'members.last_name', 'members.phone', 'members.member_id')
			->where('members.client_id', '=', $id)
			->orderBy('members.first_name')
			->get();
	}

	public function db_get_client($local)
	{
		return Clients::select('*')
			->where('local', 'like', $local)
			->get();
	}
}
