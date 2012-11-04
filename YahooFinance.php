<?php
require_once("Finance.php");

class YahooFinance extends FinanceProvider{
	var $_url_parts;
	var $_url_protocol;
	var $_url = false;

	//"http://ichart.finance.yahoo.com/table.csv?a=00&b=1&c=2011&d=11&e=31&f=2011&g=d&ignore=.csv&s=%s"

	function __construct($date_array = null){
		$this->_url_protocol = "http";
		$this->_url_parts = array(
			'prefix' => 'ichart.finance.yahoo.com/table.csv',
			'start_month' => array('a', 00),
			'start_dayofmonth' => array('b', 1),
			'start_year' => array('c', 2011),
			'end_month' => array('d', 11),
			'end_dayofmonth' => array('e', 31),
			'end_year' => array('f', 2011),
			'frequency' => array('g', 'd'),
			'ticker_symbol' => array('s', '%s'),
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