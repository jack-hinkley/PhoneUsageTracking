<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Invoices;
use App\Clients;
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
			$data = Excel::load($path, function($reader) {})->get();
			$this->upload_zones($data);
			if(!empty($data) && $data->count()) {
				foreach ($data->toArray() as $row) {
					if(!empty($row)) {
						$dataArray[] = [
							'invoice_date' => $row['invoice_date'],
							'device_type' => $row['device_type'],
							'phone' => $row['mobile_number'],
							'status' => $row['status'],
							'domestic_data' => $row['on_device_domestic_data_mb'],
							'tether_data' => $row['tether_domestic_data_mb'],
							'total_data' => $row['total_domestic_data_mb'],
							'created_at' => date('Y-m-d'),
							'updated_at' => date('Y-m-d')
						];
					}
				}
				if(!empty($dataArray)) {
					// Invoices::insert($dataArray);
					return back();
				}
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
			$html .= '<tr><td>'.$value['first_name'].' '.$value['last_name'].'</td> <td>'.$phone.'</td> <td>'.$value['total_data'].'</td> <td>'.$value['local'].'</td> <td>'.$overages[$key]['overage_cost'].'</td></tr>';
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
			$html .= '<tr><td>'.$value['first_name'].' '.$value['last_name'].'</td> <td>'.$phone.'</td> <td>'.$value['total_data'].'</td> <td>'.$value['local'].'</td> <td>$0</td></tr>';
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
		return Invoices::join('members', 'invoices.phone', '=', 'members.phone')
			->join('clients', 'members.client_id', '=', 'clients.client_id')
			->where('invoices.invoice_date', '=', $date)
			->where('clients.local', '=', $local)
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
			->simplePaginate(50);

			// ->get();
	}
	// changes

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
		foreach ($data as $key => $invoice) {
			$total_data += intval($invoice->total_data);
		}
		//	If local is over on data calculate cost, else create array of 0's
		if(sizeof($data) * $data_max < $total_data){
			foreach ($data as $key => $invoice) {
				$member_data = intval($invoice->total_data);
				//	If the member uses more data than $data_max, calculate the additional fee
				if($member_data > $data_max){
					$value['overage_cost'] = round((($member_data - $data_max) * 0.02), 2);
					$value['overage_data'] = round((($member_data - $data_max)), 2);
					array_push($cost, $value);
				}
				else{
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

	//	Purpose:	The purpose of this function is to change all table rows to match the zones table
	//						It will then check if there are any matches where there is a value
	//	Params:		Takes an array of invoices from xlsx
	//	Return:		Returns an array 
	public function upload_zones($data, $zone){
		$data_val = array();
		foreach ($data as $key => $value) {
			$date = substr((string)$value['invoice_date'], 0, 10);
			$phone = (string)$value['mobile_number'];
			$count = 0;
			foreach ($value as $key2 => $val) {
				$count++;
				if($count < 57) continue;
				if(!empty($val) || $val != 0){
					$data_row = [
						'invoice_date' => $date,
						'phone' => $phone,
						$key2 => $val
					];
					array_push($data_val, $data_row);
				}
			}
		}
		if($zone == 'usage')
			Zones_usage::insert($data_row);
		else 
			Zones_cost::insert($data_row);
	}

}