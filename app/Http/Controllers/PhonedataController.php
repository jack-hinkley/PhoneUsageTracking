<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Invoices;
use App\Clients;
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

	//	AJAX CALLS
	public function get(Request $request)
	{
		$invoices['invoices'] = $this->db_get($request['date'], $request['local']);
		$invoices['overages'] = $this->calculateOverages($invoices['invoices']);
		return $invoices;
	}

	public function search(Request $request)
	{
		$invoices['invoices'] = $this->db_search($request['search']);
		$invoices['overages'] = $this->calculateOverages($invoices['invoices']);
		return $invoices;
	}

	public function download(Request $request, $date, $local )
	{
		$invoices['invoices'] = $this->db_get($request['date'], $request['local']);
		$invoices['overages'] = $this->calculateOverages($invoices['invoices']);
		
		$data = array();
		foreach ($invoices['invoices']->toArray() as $key => $value) {
			$value['overage data'] = $invoices['overages'][$key]['overage_data'];
			$value['overage cost'] = $invoices['overages'][$key]['overage_cost'];
			array_push($data, $value);
		}
		$this->export($data);
	}

	public function downloadsearch(Request $request, $search )
	{
		$invoices['invoices'] = $this->db_search($request['search']);
		$invoices['overages'] = $this->calculateOverages($invoices['invoices']);

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
		if($request->file('imported-file')){
			$path = $request->file('imported-file')->getRealPath();
			$data = (Excel::load($path, function($reader) {})->get())->toArray();
			//	Determine which sheet is being used
			if(isset($data[0]['411_count']))
				$zone = 'usage';
			else
				$zone = 'cost';
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
				if($zone == 'usage') $this->upload_invoices($data_array, $zone);

				return back();
			}
		}
	}

	public function generate(Request $request, $date, $local)
	{
		$data = $this->db_get($date, $local);
		$overages = $this->calculateOverages($data);
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

	public function generatesearch(Request $request, $search)
	{
		$data = $this->db_search($search);
		$overages = $this->calculateOverages($data);
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

	//	DATABASE CALLS
	public function db_get($date, $local)
	{
		// return Invoices::select('invoices.*','members.*', 'clients.*','data_usage.total_domestic_data_mb')
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

	//	REUSABLE FUNCTIONS

	//	Purpose:	The purpose of this function is to calculate all charges based on the given dataset
	//	Params:		Takes an array of invoices joined on members and clients
	//	Return:		Returns an array of all costs that aligns with each given dataset
	public function calculateOverages($data)
	{
		//	Init variables
		$total_data = 0;
		$data_max = 3072;
		$cost = array();

		//	Iterate through invoices based on the local and date, and add their total usage
		foreach ($data as $key => $invoice)
			$total_data += intval($invoice->total_data);
		//	If local is over on data calculate cost, else create array of 0's
		if(sizeof($data) * $data_max < $total_data){
			foreach ($data as $key => $invoice) {
				$member_data = intval($invoice->total_data);
				//	If the member uses more data than $data_max, calculate the additional fee
				if($member_data > $data_max){
					$value['overage_cost'] = round((($member_data - $data_max) * 0.02), 2);
					$value['overage_data'] = round((($member_data - $data_max)), 2);
					array_push($cost, $value);
				}	else {
					$value['overage_cost'] = 0;
					$value['overage_data'] = 0;
					array_push($cost, $value);
				}
			}
		} else {
			//	Create array of 0 based on number of invoices
			for($i = 0; $i < sizeof($data); $i++){
				$value['overage_cost'] = 0;
				$value['overage_data'] = 0;
				array_push($cost, $value);
			}
		}
		//	Cost array aligns itself with the invoices array, use key to reference correct member
		return $cost;
	}

	public function export($data)
	{
		Excel::create('invoices', function($excel) use($data) {
			$excel->sheet('invoices', function($sheet) use($data) {
				$sheet->fromArray($data);
			});
		})->export('xls');
	}

	public function upload_invoices($data_set, $zone)
	{
		$data_master = array();
		foreach ($data_set as $key => $data) {
			$data_array = array(
				'invoice_date' => $data['invoice_date'],
				'phone' => $data['mobile_number'],
				'created_at'=> date('Y-m-d'),
				'updated_at'=> date('Y-m-d')
			);
			$data_array['total_data'] = $this->db_all_usage($data['mobile_number'], $data['invoice_date']);
			array_push($data_master, $data_array);
		}
		Invoices::insert($data_master);
	}

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

	public function upload_zones($data_set, $zone)
	{
		$data_master = array();
		if ($zone == 'usage') $start = 22;
		else $start = 20;
		foreach ($data_set as $data) {
			$data_array = array();
			$date = $data['invoice_date'];
			$phone = $data['mobile_number'];
			$count = 0;
			foreach ($data as $key => $val) {
				if($count >= $start){
					if(!empty($val) || $val != 0){
						$data_row = [
							'invoice_date' => $date,
							'phone' => $phone,
							$key => $val
						];
						array_push($data_master, $data_row);
					}
				}
				$count++;
			}			
		}
		if ($zone == 'usage') Zones_usage::insert($data_master);
		else Zones_cost::insert($data_master);
	}

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
