<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Invoices;
use App\Clients;
use App\Members;
use App\Data_usage;
use App\Data_cost;
use App\Zones_usage;
use App\Zones_cost;
use App\Http\Controllers\Controller;
use Excel;
use PDF;
use Dompdf\Dompdf;

class phoneplanController extends Controller
{
	//	USER ROUTES
	public function index()
	{
		$invoices['dates'] = $this->db_dates();
		$invoices['locals'] = Clients::get();
		$invoices['outstanding'] = $this->db_outstanding();
		return view('phoneplan.index', ['phoneplan' => $invoices]);
	}

	public function outstandingindex()
	{
		$invoices['outstanding'] = $this->db_outstanding();
		return view('phoneplan.outstanding', ['outstanding' => $invoices]);
	}

	public function detailsindex(Request $request, $id)
	{
		$invoices['invoices'] = $this->db_get_id($id)[0];
		$invoices['zone_usage'] = $this->db_all_zone_usage($invoices['invoices']->phone, $invoices['invoices']->invoice_date);
		$invoices['data_usage'] = $this->db_all_data_usage($invoices['invoices']->phone, $invoices['invoices']->invoice_date);
		$invoices['data_cost'] = $this->total_data_cost($invoices['invoices']->phone, $invoices['invoices']->invoice_date);
		$invoices['zone_cost'] = $this->db_all_zones_cost($invoices['invoices']->phone, $invoices['invoices']->invoice_date);
		return view('phoneplan.details', ['invoice' => $invoices]);
	}

	public function testing()
	{
		// $this->db_all_cost('6473906782', '2016-09-11');
		// $this->db_all_usage('6473906782', '2016-09-11');
		$this->test($this->db_check_column('this_is_a_test', 'zones_usage'));
	}

	//	AJAX CALLS
	public function get(Request $request)
	{
		$invoices['invoices'] = $this->db_get($request['date'], $request['local']);
		return $invoices;
	}

	public function search(Request $request)
	{
		//	If string has a space, check if string is numeric or not if the spaces are replaced
		if(strpos($request['search'], ' ')){
			if(is_numeric(str_replace(' ', '', $request['search']))){
				//	If search is numeric, use the db_search function
				$search = str_replace(' ', '', $request['search']);
				$invoices['invoices'] = $this->db_search($search);
			} else {
				//	If the search is alphabetical, explode string and use the cb_search_name function
				$first_name = explode(' ', $request['search'])[0];
				$last_name = explode(' ', $request['search'])[1];
				$invoices['invoices'] = $this->db_search_name($first_name, $last_name);
				//	If nothing is found, attempt to search the first string in the search
				if(sizeof($invoices['invoices']) == 0){
					$invoices['invoices'] = $this->db_search($first_name);
					//	If nothing is found, attempt to search the second string in the search
					if(sizeof($invoices['invoices']) == 0)
						$invoices['invoices'] = $this->db_search($last_name);
						//	If nothing is found, attempt to search the entire search in the search ???
						if(sizeof($invoices['invoices']) == 0)
							$invoices['invoices'] = $this->db_search($request['search']);
				}
			}
		} else {
			$count = 0;
			if(is_numeric($request['search'])){
				while(substr($request['search'], 0, 1) == 0 || $count == 8){
					$request['search'] = ltrim($request['search'], 0);
					$count++;
				}
			}
			
			$invoices['invoices'] = $this->db_search($request['search']);
		}
		return $invoices;
	}

	public function download(Request $request, $date, $local)
	{
		$this->export($this->db_get_download($date, $local)->toArray());
	}

	public function downloadsearch(Request $request, $search)
	{
		$this->export($this->db_get_download_search($search)->toArray());
	}

	public function upload(Request $request)
	{
		if($request->file('imported-file')) {
			$path = $request->file('imported-file')->getRealPath();
			$data = (Excel::load($path, function($reader) {})->get())->toArray();
			//	Determine which sheet is being used
			if(isset($data[0]['411_count']))
				$zone = 'usage';
			else
				$zone = 'cost';

			//	Check if cost file was uploaded first
			if((Data_usage::count() >= Data_cost::count()) && $zone == 'usage')
				return view('phoneplan.uploaderror');

			//	Handle inserts for other tables
			$data_array = array();
			if(!empty($data)) {
				//	Iterate the spreadsheet by row
				foreach ($data as $row) {
					$data_row = array();
					if(!empty($row)) {
						//	Iterate the row by cell
						foreach ($row as $key => $cell) {
							//	Check cell name against the blacklisted cell names
							if($this->upload_blacklist($key)){
								//	Check if the column exists, if it does create the column
								// $this->db_check_column($key, 'zones_'.$zone);
								if($key == 'invoice_date')
									$cell = substr((string)$cell, 0, 10);
								//	Push cell data in row array
								$data_row[$key] = $cell;
							}
						}
					}
					// Push row into master data array
					array_push($data_array, $data_row);
				}
				
				$this->upload_data($data_array, $zone);
				$this->upload_zones($data_array, $zone);
				if($zone == 'usage') {
					$this->upload_invoices($data_array);
				}
				return back();
			}
		}
	}

	//	DATABASE CALLS
	public function db_get($date, $local)
	{
		return Invoices::select('*')
			->join('members', 'invoices.phone', '=', 'members.phone')
			->join('clients', 'members.client_id', '=', 'clients.client_id')
			// ->join('data_usage', function($join){
			// 	$join->on('invoices.phone', '=', 'data_usage.phone')
			// 	->on('invoices.invoice_date', '=', 'data_usage.invoice_date');
			// })
			// ->join('data_cost', function($join){
			// 	$join->on('invoices.phone', '=', 'data_cost.phone')
			// 	->on('invoices.invoice_date', '=', 'data_cost.invoice_date');
			// })
			->where('invoices.invoice_date', '=', $date)
			->where('clients.local', '=', $local)
			->limit(250)
			->get();
	}

	public function db_get_id($id)
	{
		return Invoices::join('members', 'invoices.phone', '=', 'members.phone')
			->join('clients', 'members.client_id', '=', 'clients.client_id')
			->where('invoices.invoice_id', '=', $id)
			->get();
	}

	public function db_get_download($date, $local)
	{
		return Invoices::select('invoices.invoice_id','invoices.phone','invoices.invoice_date','invoices.local','invoices.total_data','invoices.invoice_total','members.first_name','members.last_name','members.email','members.address','members.province','members.postal','members.plan_rate','members.plan_data')
			->join('members', 'invoices.phone', '=', 'members.phone')
			->join('clients', 'members.client_id', '=', 'clients.client_id')
			->where('invoices.invoice_date', '=', $date)
			->where('clients.local', '=', $local)
			->limit(250)
			->get();
	}

	public function db_get_download_search($search)
	{
		return Invoices::select('invoices.invoice_id','invoices.phone','invoices.invoice_date','invoices.local','invoices.total_data','invoices.invoice_total','members.first_name','members.last_name','members.email','members.address','members.province','members.postal','members.plan_rate','members.plan_data')
			->join('members', 'invoices.phone', '=', 'members.phone')
			->join('clients', 'members.client_id', '=', 'clients.client_id')
			->where('invoices.invoice_id', 'like', '%'.$search.'%')
			->orwhere('members.first_name', 'like', '%'.$search.'%')
			->orwhere('members.last_name', 'like', '%'.$search.'%')
			->orwhere('members.phone', 'like', '%'.$search.'%')
			->orwhere('clients.local', 'like', '%'.$search.'%')
			->orderBy('invoices.invoice_date', 'desc')
			->limit(250)
			->get();
	}

	public function db_get_local_size($local)
	{
		return Clients::where('local', '=', $local)
			->join('members', 'clients.client_id', '=', 'members.client_id')
			->count();
	}

	public function db_get_local_by_phone($phone)
	{
		return Members::select('clients.local', 'members.plan_data')
			->join('clients', 'members.client_id', '=', 'clients.client_id')
			->where('members.phone', '=', $phone)
			->get();
	}

	public function db_search($search)
	{
		return Invoices::join('members', 'invoices.phone', '=', 'members.phone')
			->join('clients', 'members.client_id', '=', 'clients.client_id')
			->where('invoices.invoice_id', 'like', '%'.$search.'%')
			->orwhere('members.first_name', 'like', '%'.$search.'%')
			->orwhere('members.last_name', 'like', '%'.$search.'%')
			->orwhere('members.phone', 'like', '%'.$search.'%')
			->orwhere('clients.local', 'like', '%'.$search.'%')
			->orderBy('invoices.invoice_date', 'desc')
			->limit(250)
			->get();
	}

	public function db_search_name($first_name, $last_name)
	{
		return Invoices::join('members', 'invoices.phone', '=', 'members.phone')
			->join('clients', 'members.client_id', '=', 'clients.client_id')
			->where('members.first_name', 'like', '%'.$first_name.'%')
			->where('members.last_name', 'like', '%'.$last_name.'%')
			->orderBy('invoices.invoice_date', 'desc')
			->limit(250)
			->get();
	}

	public function db_outstanding()
	{
		return Invoices::select('invoices.phone')->distinct()
			->leftjoin('members', 'invoices.phone', '=', 'members.phone')
			->where('members.phone', '=', NULL)
			->get();
	}

	public function db_dates()
	{
		return Invoices::select('invoice_date')
			->distinct()
			->orderBy('invoice_date', 'desc')
			->get();
	}

	public function db_get_member_plan($phone)
	{
		return Members::select('members.plan_rate', 'members.plan_data')
			->where('members.phone', '=', $phone)
			->get()
			->toArray()[0];
	}

	public function db_get_invoice($phone, $date)
	{
		return Invoices::where('invoices.phone', '=', $phone)
			->where('invoices.invoice_date', '=', $date)
			->get()
			->toArray()[0];
	}

	public function db_all_usage($phone, $date)
	{
		//	IM SORRY THERE WAS NO OTHER WAY
		$data = Data_usage::where('phone', '=', $phone)
			->where('invoice_date', '=', $date)
			->sum(
				'data_usage.on_device_domestic_data_mb',
				'data_usage.tether_domestic_data_mb',
				'data_usage.total_domestic_data_mb',
				'data_usage.usa_data_roaming_mb',
				'data_usage.international_data_usage_mb',
				'data_usage.total_roaming_data',
				'data_usage.on_device_domestic_data_mb'
				);

		$zone = Zones_usage::where('phone', '=', $phone)
		->where('invoice_date', '=', $date)
			->get()
			->toArray();

		$zone_usage = 0;
		foreach ($zone as $value) {
			foreach ($value as $key => $val) {
				if($val == null || $key == 'phone' || $key == 'invoice_date' || $key == 'id') continue;
					if(strpos($key, 'data'))
						$zone_usage += $value[$key];	
			}
		}

		return intval($data) + intval($zone_usage);
	}

	public function db_all_cost($phone, $date)
	{
		//	IM SORRY THERE WAS NO OTHER WAY
		$data = Data_cost::select('data_cost.other_long_distance_charges', 'data_cost.canada_to_international_long_distance_charges', 'data_cost.other_charges_and_credits')
			->where('data_cost.phone', '=', $phone)
			->where('data_cost.invoice_date', '=', $date)
			->get()
			->toArray()[0];

		if(Members::where('members.phone', 'like', $phone)->count() > 0)
			$rate =	$this->db_get_member_plan($phone);
		else
			$rate = 65;

		$zone = Zones_cost::where('phone', '=', $phone)
			->where('invoice_date', '=', $date)
			->get()
			->toArray();

		$zone_cost = 0;
		foreach ($zone as $value) {
			foreach ($value as $key => $val) {
				if($val == null || $key == 'phone' || $key == 'invoice_date' || $key == 'id') continue;
					$zone_cost += $value[$key];	
			}
		}

		$data_cost = $data['other_long_distance_charges'] + $data['canada_to_international_long_distance_charges'] + $data['other_charges_and_credits'];
		return $rate['plan_rate'] + $zone_cost + $data_cost;
	}

	public function db_all_zone_usage($phone, $date)
	{
		return Zones_usage::where('phone', '=', $phone)
			->where('invoice_date', '=', $date)
			->get();
	}

	public function db_all_data_usage($phone, $date)
	{
		return Data_usage::where('phone', '=', $phone)
			->where('invoice_date', '=', $date)
			->get();
	}

	public function db_all_zones_cost($phone, $date)
	{
		return Zones_cost::where('phone', '=', $phone)
			->where('invoice_date', '=', $date)
			->get();
	}

	public function db_all_data_cost($phone, $date)
	{
		return Data_cost::where('phone', '=', $phone)
			->where('invoice_date', '=', $date)
			->get();
	}

	public function db_check_column($key, $table)
	{
		$column = DB::select('
			SELECT * 
			FROM information_schema.COLUMNS 
			WHERE TABLE_SCHEMA = "test_laravel_phone_data" 
			AND TABLE_NAME = "'.$table.'"
			AND COLUMN_NAME = "'.$key.'"');

		if(sizeof($column) == 0)
			DB::select('ALTER TABLE '.$table.' ADD '.$key.' DOUBLE');
	}

	//	REUSABLE FUNCTIONS

	//	Purpose:	The purpose of this function is to calculate all charges based on the given dataset
	//	Params:		Takes an array of invoice data from the bell spreadsheet and an array of the locals information (total data used and allowed)
	//	Return:		Returns an array of all costs that aligns with each given dataset
	public function calculateInvoice($data, $locals)
	{
		$phone = (string)$data['mobile_number'];
		$date = $data['invoice_date'];
		$other_cost = round($this->db_all_cost($phone, $date), 2);
		$invoice_total = $other_cost;
		
		if(isset($data['local'])) {
			//	If the allowed usage for the local is less than the amount the local used
			if($locals[$data['local']]['allowed_usage'] < $locals[$data['local']]['total_usage']){
				//	If the member exceeded their data limit
				if($data['total_domestic_data_mb'] > $data['plan_data']) {
					$data_overage = $data['total_domestic_data_mb'] - $data['plan_data'];
					$data_overage_cost = $data_overage * 0.02;
					$invoice_total_data = $other_cost + $data_overage_cost;
				}
			}
		}

		if(isset($invoice_total_data))
			$invoice_total = $invoice_total_data;
		
		return round($invoice_total *= 1.13, 2);
	}

	//	Purpose:	The purpose of this function is to calculate the invoice total from the given phone number and date
	//	Params:		Takes a phone number (string) and the date (string/date)
	//	Return:		Returns invoice total (double)
	public function total_data_cost($phone, $date)
	{
		$member = $this->db_get_member_plan($phone);
		$invoice = $this->db_get_invoice($phone, $date);
		$total_zones_cost = 0;
		$zones = $this->db_all_zones_cost($phone, $date)->toArray();

		$costs['plan_rate'] = $member['plan_rate'];
		$costs['sub_total'] = $invoice['invoice_total'] / 1.13;
		$costs['taxes'] = $invoice['invoice_total'] - $costs['sub_total'];
		
		if (sizeof($zones) > 0) {
			foreach ($zones as $value) {
				foreach ($value as $key => $val) {
					if($val == null || $key == 'phone' || $key == 'invoice_date' || $key == 'id') continue;
					$costs[$key] = $value[$key];
					$total_zones_cost += $value[$key];	
				}
			}
		}
		$costs['total_usage_cost'] = $costs['sub_total'] - ($total_zones_cost + $costs['plan_rate']);
		return $costs;
	}

	//	Purpose:	The purpose of this function export an excel file based on the given dataset
	//	Params:		Takes an array of invoices joined on members and clients
	//	Return:		Exports an XLS file to the users browser
	public function export($data)
	{
		Excel::create('invoices', function($excel) use($data) {
			$excel->sheet('invoices', function($sheet) use($data) {
				$sheet->fromArray($data);
			});
		})->export('xls');
	}

	//	Purpose:	The purpose of this function is to insert data into the invoices table from the spreadsheet
	//	Params:		Takes an array of invoices
	//	Return:		None
	public function upload_invoices($data_set)
	{
		$data_master = array();
		$locals = array();

		foreach ($data_set as $key => $value) {
			$test = $this->db_get_local_by_phone($value['mobile_number'])->toArray();
			if(isset($test[0]['local'])) {
				$data_set[$key]['local'] = $test[0]['local'];
				$data_set[$key]['plan_data'] = $test[0]['plan_data'];
			}
		}

		//	Iterate through invoices and collect total usage and total allowed usage, store in locals array
		foreach ($data_set as $key => $value) {
			if(isset($value['local'])) {
				if(isset($locals[$value['local']])) {
					$locals[$value['local']]['allowed_usage'] = $locals[$value['local']]['allowed_usage'] + $value['plan_data'];
					$locals[$value['local']]['total_usage'] = $locals[$value['local']]['total_usage'] + $value['total_domestic_data_mb'];
				}	else {
					$locals[$value['local']]['allowed_usage'] = $value['plan_data'];
					$locals[$value['local']]['total_usage'] = $value['total_domestic_data_mb'];
				}
			}
		}

		foreach ($data_set as $key => $data) {
			//	Calculate the total invoice price
			$invoice_total = $this->calculateInvoice($data, $locals);

			$data_array = array(
				'invoice_date' => $data['invoice_date'],
				'phone' => $data['mobile_number'],
				'invoice_total' => $invoice_total,
				'local' => null,
				'created_at'=> date('Y-m-d'),
				'updated_at'=> date('Y-m-d')
			);

			//	If the row has a local, add the local name to the invoice array
			if(isset($data['local']))
				$data_array['local'] = $data['local'];

			$data_array['total_data'] = $this->db_all_usage($data['mobile_number'], $data['invoice_date']);
			array_push($data_master, $data_array);
		}
		Invoices::insert($data_master);
	}

	//	Purpose:	The purpose of this function is to insert invoice data from the spreadsheet into the data_cost and data_usage table
	//	Params:		Takes an array of invoices and the which spreadsheet is being used
	//	Return:		None
	public function upload_data($data_set, $zone)
	{
		$data_master = array();
		if ($zone == 'usage') $start = 22;
		else $start = 20;
		foreach ($data_set as $data) {
			$data_array = array();
			$data_array['invoice_date'] = $data['invoice_date'];
			$data_array['phone'] = $data['mobile_number'];
			$count = 0;
			foreach ($data as $key => $val) {
				if($count < $start && $count > 1){
					$data_array[$key] = $val;
				}
				$count++;
			}
			array_push($data_master, $data_array);
		}
		if ($zone == 'usage') Data_usage::insert($data_master);
		else Data_cost::insert($data_master);
	}

	//	Purpose:	The purpose of this function is to insert invoice data from the spreadsheet into the zone_usage and data_cost table
	//	Params:		Takes an array of invoices and the which spreadsheet is being used
	//	Return:		None
	public function upload_zones($data_set, $zone)
	{
		if ($zone == 'usage') $start = 25;
		else $start = 22;
		foreach ($data_set as $data) {
			$data_array = array();
			$date = $data['invoice_date'];
			$phone = $data['mobile_number'];
			$count = 0;
			foreach ($data as $key => $val) {
				if($count >= $start && $val != 0) {
					$data_row = [
						'invoice_date' => $date,
						'phone' => $phone,
						$key => $val
					];
					if ($zone == 'usage') Zones_usage::insert($data_row);
					else Zones_cost::insert($data_row);
				}
				$count++;
			}
		}
	}

	//	Purpose:	The purpose of this function is to compare the given key against the black listed items.
	//	Params:		Takes a column name (string)
	//	Return:		Returns true if there is no match, false if there is a match
	public function upload_blacklist($key_label)
	{
		$blacklist = array(
			'group_id','group_name','account_number','account_name','device_type','user_last_name','user_first_name','status','category','sub_category','esnimei', 'reference','po_number','activation_date','network_type','model_code','model_description','sim_number','deactivation_date','current_adjustments','feature_charges','gst','hst','orst','qst_telecom','qst_other','p.e.i.','bc_pst','sask','manitoba','foreign_tax','total_taxes','hst_pei_tel','hst_on_tel','hst_bc_tel','on_device_domestic_data_charges','total_domestic_data_charges', 'total_roaming_charges','canada_to_canada_long_distance_charges', 'incoming_day_minutes','incoming_night_minutes','incoming_weekend_minutes','outgoing_day_minutes','outgoing_night_minutes','outgoing_weekend_minutes','total_day_minutes','total_night_minutes','total_weekend_minutes','domestic_texts_received','domestic_texts_sent','bell_mobile_to_bell_mobile_minutes','bell_mobile_to_bell_mobile_long_distance_minutes', 'zone_1_voice_cost', 'zone_2_voice_cost', 'zone_3_voice_cost', 'zone_1_data_cost', 'zone_2_data_cost', 'zone_3_data_cost',  'zone_1_voice_usage_minutes',  'zone_2_voice_usage_minutes',  'zone_3_voice_usage_minutes', 'zone_1_data_usage_mb', 'zone_2_data_usage_mb', 'zone_3_data_usage_mb'
		);
		foreach ($blacklist as $key => $value) {
			if($key_label == $value)
				return false;
		}
		return true;
	}

	//	Purpose:	This is a shortcut to print a dataset to the screen and then kill the app.
	//	Params:		dataset you want displayed (array)
	//	Return:		None
	public function test($data)
	{
		echo '<pre>';
		var_dump($data);
		echo '</pre>';
		die;
	}

}
