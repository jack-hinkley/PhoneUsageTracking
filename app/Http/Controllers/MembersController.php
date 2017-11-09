<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Members;
use App\Clients;
use App\Http\Controllers\Controller;

class MembersController extends Controller
{
	//	USER ROUTES
	public function index()
	{
		$locals['locals'] = Clients::get();
		return view('members.index', ['locals' => $locals]);
	}

	public function createindex(Request $request)
	{
		$locals['locals'] = Clients::get();
		return view('members.create', ['locals' => $locals]);
	}

	public function createphoneindex(Request $request, $phone)
	{
		$locals['locals'] = Clients::get();
		return view('members.create', ['locals' => $locals, 'phone' => $phone]);
	}

	public function editindex(Request $request, $id)
	{
		$locals['locals'] = Clients::get();
		$members = Members::where('member_id', '=', $id)->get()[0];
		return view('members.edit', ['locals' => $locals, 'members' => $members]);
	}


	//	AJAX CALLS
	public function get(Request $request)
	{
		$members['members'] = Members::join('clients', 'members.client_id', '=', 'clients.client_id')
			->where('clients.local', '=', $request['local'])
			->get();
		return $members;
	}

	public function getall(Request $request)
	{
		$members['members'] = Members::select('first_name', 'last_name')->get();
		return $members;
	}

	public function search(Request $request)
	{
		if(strpos($request['search'], ' ')){
			$first_name = explode(' ', $request['search'])[0];
			$last_name = explode(' ', $request['search'])[1];
		} else {
			$first_name = $request['search'];
			$last_name = $request['search'];
		}
		$members['members'] = $this->db_search($first_name, $last_name);
		return $members;
	}	

	public function create(Request $request)
	{
		$phone = str_replace(' ','',$request->input('phone'));
		$mobile = str_replace(' ','',$request->input('mobile'));

		$member = new Members;
		$member->first_name = $request->input('first_name');
		$member->last_name = $request->input('last_name');
		$member->email = $request->input('email');
		$member->phone = $phone;
		$member->mobile =  $mobile;
		$member->address = $request->input('address');
		$member->province = $request->input('province');
		$member->postal = $request->input('postal');
		$member->birthday = $request->input('birthday');
		$member->client_id = $request->input('local');
		$member->created_at = date('Y-m-d');
		$member->updated_at = date('Y-m-d');
		$member->save();
		return redirect('/members');
	}

	public function edit(Request $request, $id)
	{
		$phone = str_replace(' ','',$request->input('phone'));
		$mobile = str_replace(' ','',$request->input('mobile'));

		$member = Members::find($id);
		$member->first_name = $request->input('first_name');
		$member->last_name = $request->input('last_name');
		$member->email = $request->input('email');
		$member->phone = $phone;
		$member->mobile =  $mobile;
		$member->address = $request->input('address');
		$member->province = $request->input('province');
		$member->postal = $request->input('postal');
		$member->birthday = $request->input('birthday');
		$member->client_id = $request->input('local');
		$member->updated_at = date('Y-m-d');
		$member->save();
		return redirect('/members');
	}

	public function delete(Request $request, $id)
	{
		Members::find($id)->delete();
		return redirect('/members');
	}

	// DATABASE CALLS
	public function db_search($first_name, $last_name)
	{
		return Members::join('clients', 'members.client_id', '=', 'clients.client_id')
			->where('members.first_name', 'like', '%'.$first_name.'%')
			->orwhere('members.first_name', 'like', '%'.$last_name.'%')
			->get();
	}

}
