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
		$stocks = array();
		while($row = fgetcsv($data_handle)){
			$stocks[] = $row;
		}
		return $stocks;
	}
}

// I want to take in an array of arrays, and create an object, without losing flexibility.
class StockData{
	function __construct(array $data){
		
	}
}