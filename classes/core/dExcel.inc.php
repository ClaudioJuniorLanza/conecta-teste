<?php
/**
	Uso:
		dExcel::PV (...)
		dExcel::PMT(...)
		dExcel::DAYS360(...)
	
	Para fórmulas em português:
		dExcel::br('VP',   ...);
		dExcel::br('PGTO', ...);
		dExcel::br('DIAS360', ...);
**/

class dExcel{
	static Function PV ($rate, $nper, $pmt, $fv=0, $type=0){
		$fv     = ($fv?$fv:0);
		$type   = ($type?$type:0);
		return ($rate == 0)?
			(-$nper * $pmt - $fv):
			-((($pmt*(1+$rate*$type))*((pow(1+$rate, $nper)-1)/$rate))/pow(1+$rate, $nper))-($fv/pow(1+$rate, $nper));
	}
	static Function PMT($rate, $nper, $pv, $fv=false, $type=false){
		$fv     = ($fv?$fv:0);
		$type   = ($type?$type:0);
		$invert = ($pv<0?true:false);
		$pv     = abs($pv);
		$v      = ((-$rate*($pv*pow(1.0+$rate,$nper)+$fv))/((1.0+$rate*$type)*(pow(1.0+$rate,$nper)-1)));
		
		return ($invert ? -$v : $v);
	}
	static Function DAYS360($start_date, $end_date, $method=false){
		if(!$start_date && !$end_date)
			throw new Exception("DAYS360() - Called without arguments (start_date and end_date are empty).");
		if(!$start_date)
			throw new Exception("DAYS360() - Called without start_date. end_date={$end_date}");
		if(!$end_date)
			throw new Exception("DAYS360() - Called without end_date. start_date={$start_date}");
		
		if(!$method)
			$method = false;
		
		$start_date  = explode("-", array_shift(explode(" ", $start_date)));
		$end_date    = explode("-", array_shift(explode(" ", $end_date  )));
		
		$dayS   = $start_date[2]; $dayE   = $end_date[2];
		$monthS = $start_date[1]; $monthE = $end_date[1];
		$yearS  = $start_date[0]; $yearE  = $end_date[0];
		
		if($dayS > 30){
			$dayS = 30;
		}
		
		if($dayE > 30){
			if(!$method && $dayS < 30){
				$dayE = 1;
				$monthE++;
				if($monthE > 12){
					$monthE = 12;
					$yearE++;
				}
			}
			else{
				$dayE = 30;
			}
		}
		
		$diffYear  = $yearE  - $yearS;
		$diffMonth = $monthE - $monthS;
		$diffDay   = $dayE   - $dayS;
		
		return ($diffYear*360 + $diffMonth*30 + $diffDay);
	}
	
	public static Function __callstatic($a, $b){
		$map = Array(
			'br'=>Array(
				'VP'  =>'PV',
				'PGTO'=>'PMT',
				'DIAS360'=>'DAYS360',
			),
		);
		
		if(isset($map[$a][$b[0]])){
			return call_user_func_array(Array('dExcel', $map[$a][$b[0]]), array_slice($b, 1));
		}
		die("Método não encontrado: {$a}:{$b[0]}.\r\n");
		return false;
	}
}
