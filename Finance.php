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


	function _getDataTableHeaders($print_cols, $symbols){
		$buf = "";
		$buf .= "  <thead>\n";
		$buf .= "    <tr>\n";
		$buf .= "      <th class='year_heading'>".YEAR."</th>\n";
		foreach($symbols as $symbol){
			$buf .= sprintf("      <th colspan='%s'>%s</th>\n", count($print_cols), strtoupper($symbol));
		}
		$buf .= "      <th colspan='3'>Fund Totals</th>\n";
		$buf .= "    </tr>\n";
		$buf .= "    <tr>\n";
		$buf .= "      <th>Date</th>\n";
		foreach($symbols as $symbol){
			foreach($print_cols as $name){
				$buf .= sprintf("      <th>%s</th>\n", $name);
			}
		}
		$buf .= "      <th>Fund Value</th>\n";
		$buf .= "      <th>Fund RoI</th>\n";
		$buf .= "      <th>Fund Delta</th>\n";
		$buf .= "    </tr>\n";
		$buf .= "  </thead>\n";
		return $buf;
	}

	function showReport($print_cols){
		$input = $this->_input;
		$stocks = $this->_stocks;

		printf("\n<table cellpadding='2' cellspacing='0' border='0' id='all_results'>\n");
		printf($this->_getDataTableHeaders($print_cols, $input->getSymbols()));
		printf("  <tbody>\n");

		for($index = 0; $index < $stocks[0]->getDateCount(); $index++){
			printf("    <tr>\n");
			printf("      <td class='th_col'>%s</td>\n", Util::cleanData(Util::COL_DATE, $stocks[0]->getDate($index)));
			for($i = 0; $i < $input->getStockCount(); $i++){
				foreach($print_cols as $col => $name){
					printf("      <td>%s</td>\n", Util::cleanData($col, $stocks[$i]->getDataByColumn($index, $col)));
				}
			}
			printf("      <td>%s</td>\n", Util::cleanData(Util::COL_FUND_SUM, $this->getDailySum($index)));
			printf("      <td>%s</td>\n", Util::cleanData(Util::COL_FUND_CUM, $this->getDailyROI($index)));
			printf("      <td>%s</td>\n", Util::cleanData(Util::COL_FUND_DAILY, $this->getDailyDelta($index)));
			printf("    </tr>\n");

		}
		printf("  </tbody>\n");
		printf("</table>\n");
		printf("<table cellpadding='2' cellspacing='0' border='0'>\n");
		printf("  <thead><tr><th>Stocks</th><th>Alloc</th><th>Capital</th></tr></thead>\n");
		printf("  <tbody>\n");
		printf("    <tr><td class='th_col'>Start</td><td>1</td><td>%s</td></tr>\n", Util::prettyMoney($input->_total_capital));
		foreach($input->getSymbols() as $i => $symbol){
			printf("    <tr><th>%s</th><td>%s</td><td>%s</td></tr>\n", strtoupper($symbol), $input->getCapitals($i) / $input->_total_capital, Util::prettyMoney($input->getCapitals($i)));
		}
		printf("  </tbody>\n");
		printf("</table>\n");

		printf("<table cellpadding='2' cellspacing='0' border='0'>\n");
		printf("  <thead><tr><th>Performance</th><th>Fund</th></tr></thead>\n");
		printf("  <tbody>\n");
		printf("    <tr><td class='th_col'>Annual Return</td><td>%s</td></tr>\n", Util::prettyPercent($this->getAnnualReturn()));
		printf("    <tr><td class='th_col'>Average Daily Return</td><td>%s</td></tr>\n", Util::prettyPercent($this->getAverageDailyReturn(), 3));
		printf("    <tr><td class='th_col'>STDEV Daily Return</td><td>%s</td></tr>\n", Util::prettyPercent($this->getStdDev(), 3));
		printf("    <tr><td class='th_col'>Sharpe Ratio</td><td>%s</td></tr>\n", round($this->getSharpe(), 3));
		printf("  </tbody>\n");
		printf("</table>\n");
	}

	function toJSON($print_cols){
		$input = $this->_input;
		$stocks = $this->_stocks;

		$buf = array();

		$buf['symbols'] = array();		
		foreach($input->getSymbols() as $symbol){
			$buf['symbols'][] .= strtoupper($symbol);
		}
		$buf['year'] = YEAR;
		$buf['stock_fields'] = array();
		foreach($print_cols as $name){
			$buf['stock_fields'][] = $name;
		}
		$buf['fund_fields'] = array("Fund Value", "Fund ROI", "Fund Delta");
		$buf['dates'] = array();

		$buf['stock_data'] = array();
		for($index = 0; $index < $stocks[0]->getDateCount(); $index++){
			$buf['dates'][] = Util::cleanData(Util::COL_DATE, $stocks[0]->getDate($index));
			for($i = 0; $i < $input->getStockCount(); $i++){
				$stock = array();
				foreach($print_cols as $col => $name){
					$stock[] = Util::cleanData($col, $stocks[$i]->getDataByColumn($index, $col));
				}
				$buf['stock_data'][$i][] = $stock;
			}

			$buf['fund_data'] = array();
			$buf['fund_data'][] = Util::cleanData(Util::COL_FUND_SUM, $this->getDailySum($index));
			$buf['fund_data'][] = Util::cleanData(Util::COL_FUND_CUM, $this->getDailyROI($index));
			$buf['fund_data'][] = Util::cleanData(Util::COL_FUND_DAILY, $this->getDailyDelta($index));

		}

		$buf['aggregate_fields'] = array("Stocks", "Alloc", "Capital");
		$buf['aggregate_data'] = array();
		$buf['aggregate_data'][] = array("Start", "1", Util::prettyMoney($input->_total_capital));
		foreach($input->getSymbols() as $i => $symbol){
			$alloc = $input->getCapitals($i) / $input->_total_capital;
			$buf['aggregate_data'][] = array(strtoupper($symbol), $alloc, Util::prettyMoney($input->getCapitals($i)));
		}

		$buf['analysis_fields'] = array("Performance", "Fund");
		$buf['analysis_data'] = array();
		$buf['analysis_data'][] = array("Annual Return", Util::prettyPercent($this->getAnnualReturn()));
		$buf['analysis_data'][] = array("Average Daily", Util::prettyPercent($this->getAverageDailyReturn(), 3));
		$buf['analysis_data'][] = array("StdDev Daily Return", Util::prettyPercent($this->getStdDev(), 3));
		$buf['analysis_data'][] = array("Sharpe Ratio", round($this->getSharpe(), 3));

		echo json_encode($buf);
	}
}