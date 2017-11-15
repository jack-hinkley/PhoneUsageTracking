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

class PhonedataController extends Controller
{
	//	USER ROUTES
	public function index()
	{
		$invoices['dates'] = $this->db_dates();
		$invoices['locals'] = Clients::get();
		$invoices['outstanding'] = $this->db_outstanding();
		return view('phonedata.index', ['phonedata' => $invoices]);
	}

	public function outstandingindex()
	{
		$invoices['outstanding'] = $this->db_outstanding();
		return view('phonedata.outstanding', ['outstanding' => $invoices]);
	}

	public function detailsindex(Request $request, $id)
	{
		$invoices['invoices'] = $this->db_get_id($id)[0];
		$invoices['zone_usage'] = $this->db_all_zone_usage($invoices['invoices']->phone, $invoices['invoices']->invoice_date);
		$invoices['data_usage'] = $this->db_all_data_usage($invoices['invoices']->phone, $invoices['invoices']->invoice_date);
		$invoices['data_cost'] = $this->total_data_cost($invoices['invoices']->phone, $invoices['invoices']->invoice_date);
		$invoices['zone_cost'] = $this->db_all_zones_cost($invoices['invoices']->phone, $invoices['invoices']->invoice_date);
		return view('phonedata.details', ['invoice' => $invoices]);
	}

	//	AJAX CALLS
	public function get(Request $request)
	{
		// $test = array();
		$invoices['invoices'] = $this->db_get($request['date'], $request['local']);
		return $invoices;
	}

	public function search(Request $request)
	{
		if(strpos($request['search'], ' ')){
			if(is_numeric(str_replace(' ', '', $request['search']))){
				$search = str_replace(' ', '', $request['search']);
				$invoices['invoices'] = $this->db_search($search);
			} else {
				$first_name = explode(' ', $request['search'])[0];
				$last_name = explode(' ', $request['search'])[1];
				$invoices['invoices'] = $this->db_search_name($first_name, $last_name);
				if(sizeof($invoices['invoices']) == 0){
					$invoices['invoices'] = $this->db_search($first_name);
					if(sizeof($invoices['invoices']) == 0)
						$invoices['invoices'] = $this->db_search($last_name);
				}
			}
		} else {
			$invoices['invoices'] = $this->db_search($request['search']);
		}
		return $invoices;
	}

	public function download(Request $request, $date, $local)
	{
		$invoices['invoices'] = $this->db_get($request['date'], $request['local']);
		
		$data = array();
		foreach ($invoices['invoices']->toArray() as $key => $value) {
			$value['overage data'] = $invoices['overages'][$key]['overage_data'];
			$value['overage cost'] = $invoices['overages'][$key]['overage_cost'];
			array_push($data, $value);
		}
		$this->export($data);
	}

	public function downloadsearch(Request $request, $search)
	{
		$invoices['invoices'] = $this->db_search($request['search']);

		$data = array();
		foreach ($invoices['invoices']->toArray() as $key => $value) {
			$value['overage data'] = $invoices['overages'][$key]['overage_data'];
			$value['overage cost'] = $invoices['overages'][$key]['overage_cost'];
			array_push($data, $value);
		}
		$this->export($data);
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
			// if((Data_usage::count() >= Data_cost::count()) && $zone == 'usage')
			// 	return view('phonedata.uploaderror');

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
				
				// $this->upload_data($data_array, $zone);
				// $this->upload_zones($data_array, $zone);
				if($zone == 'usage') {
					$this->upload_invoices($data_array);
				}
				return back();
			}
		}
	}

	public function generate(Request $request, $date, $local)
	{
		$data = $this->db_get($date, $local);
		$html = '
			<h1>Phone Data</h1>
				<table cellpadding="10">
					<tr><th>Name</th> <th>Phone</th> <th>Data</th> <th>Local</th> <th>Overage Cost</th></tr>';
		foreach ($data as $key => $value) {
			preg_match( '/^(\d{3})(\d{3})(\d{4})$/', $value['phone'],  $matches );
			$phone = $matches[1].' '.$matches[2].' '.$matches[3];
			$html .= '<tr><td>'.$value['first_name'].' '.$value['last_name'].'</td> <td>'.$phone.'</td> <td>'.$value['total_data'].'</td> <td>'.$value['local'].'</td> <td>$'.$value['total_invoice'].'</td></tr>';
		}
		$html .= '</table>';

		$dompdf = new Dompdf();
		$dompdf->loadHtml($html);
		$dompdf->render();
		$dompdf->stream('invoice.pdf');
	}

	public function generatesearch(Request $request, $search)
	{
		$data = $this->db_search($search);
		$html = '
			<h1>Phone Data</h1>
				<table cellpadding="10">
					<tr><th>Name</th> <th>Phone</th> <th>Data</th> <th>Local</th> <th>Overage Cost</th></tr>';
		foreach ($data as $key => $value) {
			preg_match( '/^(\d{3})(\d{3})(\d{4})$/', $value['phone'],  $matches );
			$phone = $matches[1].' '.$matches[2].' '.$matches[3];
			$html .= '<tr><td>'.$value['first_name'].' '.$value['last_name'].'</td> <td>'.$phone.'</td> <td>'.$value['total_data'].'</td> <td>'.$value['local'].'</td> <td>$'.$overages[$key]['overage_cost'].'</td></tr>';
		}
		$html .= '</table>';

		$dompdf = new Dompdf();
		$dompdf->loadHtml($html);
		$dompdf->render();
		$dompdf->stream('invoice.pdf');
	}

	public function test($data)
	{
		echo '<pre>';
		var_dump($data);
		echo '</pre>';
		die;
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
			->where('members.first_name', 'like', '%'.$search.'%')
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
		->sum(
			'zones_usage.zone_1_data_usage_mb',
			'zones_usage.zone_2_data_usage_mb',
			'zones_usage.zone_3_data_usage_mb',
			'zones_usage.zone_1_netherlands_antilles_data_usage',
			'zones_usage.zone_1_austria_data_usage',
			'zones_usage.zone_1_barbados_data_usage',
			'zones_usage.zone_1_belgium_data_usage',
			'zones_usage.zone_1_bahamas_data_usage',
			'zones_usage.zone_1_switzerland_data_usage',
			'zones_usage.zone_1_cyprus_data_usage',
			'zones_usage.zone_1_germany_data_usage',
			'zones_usage.zone_1_denmark_data_usage',
			'zones_usage.zone_1_dominican_republic_data_usage',
			'zones_usage.zone_1_spain_data_usage',
			'zones_usage.zone_1_france_data_usage',
			'zones_usage.zone_1_united_kingdom_data_usage',
			'zones_usage.zone_1_gibraltar_data_usage',
			'zones_usage.zone_1_guadeloupe_data_usage',
			'zones_usage.zone_1_greece_data_usage',
			'zones_usage.zone_1_hong_kong_data_usage',
			'zones_usage.zone_1_croatia_data_usage',
			'zones_usage.zone_1_hungary_data_usage',
			'zones_usage.zone_1_ireland_data_usage',
			'zones_usage.zone_1_iceland_data_usage',
			'zones_usage.zone_1_italy_data_usage',
			'zones_usage.zone_1_jamaica_data_usage',
			'zones_usage.zone_1_monaco_data_usage',
			'zones_usage.zone_1_montenegro_data_usage',
			'zones_usage.zone_1_mexico_data_usage',
			'zones_usage.zone_1_netherlands_data_usage',
			'zones_usage.zone_1_norway_data_usage',
			'zones_usage.zone_1_new_zealand_data_usage',
			'zones_usage.zone_1_portugal_data_usage',
			'zones_usage.zone_1_sweden_data_usage',
			'zones_usage.zone_1_serbia_data_usage',
			'zones_usage.zone_1_sint_maarten_data_usage',
			'zones_usage.zone_1_turks_caicos_islands_data_usage',
			'zones_usage.zone_1_british_virgin_islands_data_usage',
			'zones_usage.zone_2_belize_data_usage',
			'zones_usage.zone_2_costa_rica_data_usage',
			'zones_usage.zone_2_honduras_data_usage',
			'zones_usage.zone_2_india_data_usage',
			'zones_usage.zone_2_cambodia_data_usage',
			'zones_usage.zone_2_thailand_data_usage'
		);
		return intval($data) + intval($zone);
	}

	public function db_all_cost($phone, $date)
	{
		//	IM SORRY THERE WAS NO OTHER WAY
		$data = Data_cost::select('data_cost.rate_plan_charges', 'data_cost.total_roaming_charges')
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
			->select(
				'zones_cost.zone_1_voice_cost',
				'zones_cost.zone_2_voice_cost',
				'zones_cost.zone_3_voice_cost',
				'zones_cost.zone_1_data_cost',
				'zones_cost.zone_2_data_cost',
				'zones_cost.zone_3_data_cost',
				'zones_cost.zone_1_netherlands_antilles_voice_cost',
				'zones_cost.zone_1_australia_voice_cost',
				'zones_cost.zone_1_barbados_voice_cost',
				'zones_cost.zone_1_belgium_voice_cost',
				'zones_cost.zone_1_bahamas_voice_cost',
				'zones_cost.zone_1_switzerland_voice_cost',
				'zones_cost.zone_1_china_voice_cost',
				'zones_cost.zone_1_cyprus_voice_cost',
				'zones_cost.zone_1_germany_voice_cost',
				'zones_cost.zone_1_denmark_voice_cost',
				'zones_cost.zone_1_dominican_republic_voice_cost',
				'zones_cost.zone_1_spain_voice_cost',
				'zones_cost.zone_1_france_voice_cost',
				'zones_cost.zone_1_united_kingdom_voice_cost',
				'zones_cost.zone_1_gibraltar_voice_cost',
				'zones_cost.zone_1_greece_voice_cost',
				'zones_cost.zone_1_ireland_voice_cost',
				'zones_cost.zone_1_italy_voice_cost',
				'zones_cost.zone_1_jamaica_voice_cost',
				'zones_cost.zone_1_monaco_voice_cost',
				'zones_cost.zone_1_montenegro_voice_cost',
				'zones_cost.zone_1_mexico_voice_cost',
				'zones_cost.zone_1_netherlands_voice_cost',
				'zones_cost.zone_1_new_zealand_voice_cost',
				'zones_cost.zone_1_portugal_voice_cost',
				'zones_cost.zone_1_serbia_voice_cost',
				'zones_cost.zone_1_sint_maarten_voice_cost',
				'zones_cost.zone_1_turks_caicos_islands_voice_cost',
				'zones_cost.zone_2_belize_voice_cost',
				'zones_cost.zone_2_india_voice_cost',
				'zones_cost.zone_2_cambodia_voice_cost',
				'zones_cost.zone_2_vietnam_voice_cost',
				'zones_cost.zone_3_kenya_voice_cost',
				'zones_cost.zone_1_netherlands_antilles_data_cost',
				'zones_cost.zone_1_austria_data_cost',
				'zones_cost.zone_1_barbados_data_cost',
				'zones_cost.zone_1_belgium_data_cost',
				'zones_cost.zone_1_bahamas_data_cost',
				'zones_cost.zone_1_switzerland_data_cost',
				'zones_cost.zone_1_cyprus_data_cost',
				'zones_cost.zone_1_germany_data_cost',
				'zones_cost.zone_1_denmark_data_cost',
				'zones_cost.zone_1_dominican_republic_data_cost',
				'zones_cost.zone_1_spain_data_cost',
				'zones_cost.zone_1_france_data_cost',
				'zones_cost.zone_1_united_kingdom_data_cost',
				'zones_cost.zone_1_gibraltar_data_cost',
				'zones_cost.zone_1_guadeloupe_data_cost',
				'zones_cost.zone_1_greece_data_cost',
				'zones_cost.zone_1_hong_kong_data_cost',
				'zones_cost.zone_1_croatia_data_cost',
				'zones_cost.zone_1_hungary_data_cost',
				'zones_cost.zone_1_ireland_data_cost',
				'zones_cost.zone_1_iceland_data_cost',
				'zones_cost.zone_1_italy_data_cost',
				'zones_cost.zone_1_jamaica_data_cost',
				'zones_cost.zone_1_monaco_data_cost',
				'zones_cost.zone_1_montenegro_data_cost',
				'zones_cost.zone_1_mexico_data_cost',
				'zones_cost.zone_1_netherlands_data_cost',
				'zones_cost.zone_1_norway_data_cost',
				'zones_cost.zone_1_new_zealand_data_cost',
				'zones_cost.zone_1_portugal_data_cost',
				'zones_cost.zone_1_sweden_data_cost',
				'zones_cost.zone_1_serbia_data_cost',
				'zones_cost.zone_1_sint_maarten_data_cost',
				'zones_cost.zone_1_turks_caicos_islands_data_cost',
				'zones_cost.zone_1_british_virgin_islands_data_cost',
				'zones_cost.zone_2_belize_data_cost',
				'zones_cost.zone_2_costa_rica_data_cost',
				'zones_cost.zone_2_honduras_data_cost',
				'zones_cost.zone_2_india_data_cost',
				'zones_cost.zone_2_cambodia_data_cost',
				'zones_cost.zone_2_thailand_data_cost'
		)
			->get()
			->toArray();

		$zone_cost = 0;
		foreach ($zone as $value) {
			foreach ($value as $key => $val) {
				if($val == null || $key == 'phone' || $key == 'invoice_date' || $key == 'id') continue;
					$zone_cost += $value[$key];	
			}
		}

		$data_cost = $data['total_roaming_charges'] + $rate['plan_rate'];
		return $data_cost + $zone_cost;
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
			if($locals[$data['local']]['allowed_usage'] < $locals[$data['local']]['total_usage'] ){
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
		if ($zone == 'usage') $start = 22;
		else $start = 20;
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
			'group_id','group_name','account_number','account_name','device_type','user_last_name','user_first_name','status','category','sub_category','esnimei', 'reference','po_number','activation_date','network_type','model_code','model_description','sim_number','deactivation_date','current_adjustments','feature_charges','other_charges_and_credits','gst','hst','orst','qst_telecom','qst_other','p.e.i.','bc_pst','sask','manitoba','foreign_tax','total_taxes','hst_pei_tel','hst_on_tel','hst_bc_tel','on_device_domestic_data_charges','total_domestic_data_charges','canada_to_canada_long_distance_charges', 'canada_to_international_long_distance_charges','other_long_distance_charges','incoming_day_minutes','incoming_night_minutes','incoming_weekend_minutes','outgoing_day_minutes','outgoing_night_minutes','outgoing_weekend_minutes','total_day_minutes','total_night_minutes','total_weekend_minutes','domestic_texts_received','domestic_texts_sent','canada_to_usa_long_distance_minutes','canada_to_international_long_distance_minutes','other_long_distance_minutes','bell_mobile_to_bell_mobile_minutes','bell_mobile_to_bell_mobile_long_distance_minutes'
		);
		foreach ($blacklist as $key => $value) {
			if($key_label == $value)
				return false;
		}
		return true;
	}

}
