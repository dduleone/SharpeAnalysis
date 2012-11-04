<?php
abstract class FinanceProvider{
	// Must extend this class and populate $_url_parts!!

	var $_url_parts;
	var $_url_protocol;

	private function _collapseUrl(){
		if($this->_url === false){
			$tmp = array();
			foreach($this->_url_parts as $part){
				if(is_array($part)){
					$tmp[] = implode("=", $part);
				}
			}
			$url = $this->_url_parts['prefix'] . '?' . implode("&", $tmp);
			$this->_url = $url;
		}
		return $this->_url;
	}

	private function _getStockDataUrl($ticker_symbol = ''){
		if(!is_string($ticker_symbol) || strlen($ticker_symbol) < 1 ){
			return false; // No Stock Symbol Provided!
		}

		$url = sprintf("%s://" . $this->_collapseUrl(), $this->_url_protocol, $ticker_symbol);
		return $url;
	}

	function fetchStockData($ticker_symbol){
		$data_handle = fopen($this->_getStockDataUrl($ticker_symbol), 'r');
		$data = array();
		while($row = fgetcsv($data_handle)){
			$data[] = $row;
		}
		return $data;
	}

	function fetchStocks($input){
		$stocks = array();
		foreach($input->getSymbols() as $index => $ticker_symbol){
			$stocks[] = new StockData($this->fetchStockData($ticker_symbol), $input->getCapitals($index));
		}
		return $stocks;
	}
}

// I want to take in an array of arrays, and create an object, without losing flexibility.
class StockData{
	var $_raw_data;
	var $_investment;
	var $_dayone;

	function __construct(array $data, $investment){
		$this->_raw_data = $data;
		$this->_investment = $investment;
		$this->_dayone = $this->_raw_data[0][Util::COL_ADJ_CLOSE];

		foreach($this->_raw_data as $j => $day){
			$dailycum = $day[Util::COL_ADJ_CLOSE] / $this->_dayone;
			$this->_raw_data[$j][Util::COL_CUM] = $dailycum;
			$this->_raw_data[$j][Util::COL_VALUE] = $dailycum * $this->_investment;
		}
	}

	// I'm building this here as a crutch while I convert the application.
	// I intend to remove this method. Do not use it.
	function getData(){
		return $this->_raw_data;
	}

	function getDateCount(){
		return count($this->_raw_data);
	}

	function getDataByColumn($day, $col){
		return $this->_raw_data[$day][$col];
	}

	function getDate($day){
		return $this->_raw_data[$day][Util::COL_DATE];
	}

	function getOpenValue($day){
		return $this->_raw_data[$day][Util::COL_OPEN];
	}

	function getHighValue($day){
		return $this->_raw_data[$day][Util::COL_HIGH];
	}

	function getLowValue($day){
		return $this->_raw_data[$day][Util::COL_LOW];
	}

	function getCloseValue($day){
		return $this->_raw_data[$day][Util::COL_CLOSE];
	}

	function getVolume($day){
		return $this->_raw_data[$day][Util::COL_VOLUME];
	}

	function getAdjustedCloseValue($day){
		return $this->_raw_data[$day][Util::COL_ADJ_CLOSE];
	}

	function getCumROI($day){
		return $this->_raw_data[$day][Util::COL_CUM];
	}

	function getValuation($day){
		return $this->_raw_data[$day][Util::COL_VALUE];
	}
}

class FundData{
	var $_stocks;
	var $_input;

	var $_daily_sums;
	var $_daily_rois;
	var $_daily_deltas;
	function __construct($stocks, $input){
		$this->_stocks = $stocks;
		$this->_input = $input;

		$this->_daily_sums = array();
		$this->_daily_rois = array();
		$this->_daily_deltas = array();

		$yesterdays_roi = false;
		for($i = 0; $i < $this->_stocks[0]->getDateCount(); $i++){
			$daily_sum = $this->getDailyValuation($i);
			$daily_roi = $daily_sum / $this->_input->_total_capital;
			$yesterdays_roi = ($yesterdays_roi===false) ? $daily_roi : $yesterdays_roi;
			$daily_delta = $daily_roi - $yesterdays_roi;
			$yesterdays_roi = $daily_roi;

			$this->_daily_sums[] = $daily_sum;
			$this->_daily_rois[] = $daily_roi;
			$this->_daily_deltas[] = $daily_delta;			
		}
	}

	function getDailySum($day){
		return $this->_daily_sums[$day];
	}

	function getDailyROI($day){
		return $this->_daily_rois[$day];
	}

	function getDailyDelta($day){
		return $this->_daily_deltas[$day];
	}

	function getDailyValuation($day){
		$sum = 0;
		foreach($this->_stocks as $stock){
			$sum += $stock->getValuation($day);
		}
		return $sum;
	}

	function getAnnualReturn(){
		$sum = 0;
		foreach($this->_stocks as $i => $stock){
			$first = $stock->getCumROI(0);
			$last = $stock->getCumROI($stock->getDateCount()-1);
			$sum += ($last - $first) * $this->_input->getWeights($i);
		}
		return $sum;
	}

	function getAverageDailyReturn(){
		return array_sum($this->_daily_deltas) / count($this->_daily_deltas);
	}

	function getStdDev(){
		return Util::standard_deviation($this->_daily_deltas);
	}

	function getSharpe(){
		return sqrt(count($this->_daily_deltas)) * $this->getAverageDailyReturn() / $this->getStdDev();
	}
}