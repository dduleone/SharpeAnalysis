<?php
require_once("Finance.php");

class LocalFinance extends FinanceProvider{
	var $_url_parts;
	var $_url_protocol;
	var $_url = false;

	function __construct($date_array = null){
		$this->_url_protocol = "http";
		$this->_url_parts = array(
			'prefix' => '127.0.0.1/sharpe/csv/%s.csv',
			'start_month' => array('a', 00),
			'start_dayofmonth' => array('b', 1),
			'start_year' => array('c', 2011),
			'end_month' => array('d', 11),
			'end_dayofmonth' => array('e', 31),
			'end_year' => array('f', 2011),
			'frequency' => array('g', 'd'),
			'ticker_symbol' => array('s', 'stock'),
			'suffix' => array('ignore', '.csv')
		);

		if($date_array !== null){
			foreach($date_array as $key => $value){
				if(isset($this->_url_parts[$key])){
					$this->_url_parts[$key][1] = $value;
				}
			}
		}

	}

	function fetchStockData($ticker_symbol){
		$data = parent::fetchStockData($ticker_symbol);
		$headers = array_shift($data);
		return array_reverse($data);
	}
}