<?php 
/* 
	Step 1: Write Stock Analysis Tool
	Step 2: Use Tool to Analyze Stocks
	Step 3: ?????
	Step 4: Profit

*/

/**
TODO (This list is not prioritized)
 - I want to separate the information layer from the display layer
 - I want to add tooltips which show the formulas for each record
 - I want to show stdev & average daily return broken up by stock
 - I want to make the daily data collapsable
 - I want to be able to update the investment values and update the page via JS, if the stocklist doesn't change
 - I want to build a solver to get the maximum Sharpe Ratio
 - I want to improve the HTML table output (Proper DOM compliance)
 - I want to make the interface "prettier"
*/


require_once("YahooFinance.php");

?><!DOCTYPE html><?php

define("STOCK_COUNT", 4);
define("YEAR", 2011);
define("REMOTE_URL", "http://ichart.finance.yahoo.com/table.csv?a=00&b=1&c=" . YEAR . "&d=11&e=31&f=" . YEAR . "&g=d&ignore=.csv&s=%s");

class Util{

	const COL_DATE = 0;
	const COL_OPEN = 1;
	const COL_HIGH = 2;
	const COL_LOW = 3;
	const COL_CLOSE = 4;
	const COL_VOLUME = 5;
	const COL_ADJ_CLOSE = 6;
	const COL_CUM = 7;
	const COL_VALUE = 8;
	const COL_FUND_SUM = 9;
	const COL_FUND_CUM = 10;
	const COL_FUND_DAILY = 11;


	static function getGet($var, $index = null){
		return (isset($_GET[$var])) ? ( ((is_array($_GET[$var])) && ($index!==null)) ? $_GET[$var][$index] : $_GET[$var] ) : "";
	}

	static function generateUrl($symbol){
		return sprintf(REMOTE_URL, $symbol);
	}

	static function getStockCount(){
		return (isset($_GET['stockcount'])) ? $_GET['stockcount'] : false;
	}

	static function getSymbolArray(){
		return (isset($_GET['symbol'])) ? $_GET['symbol'] : false;		
	}

	static function getCapitalArray(){
		return (isset($_GET['capital'])) ? $_GET['capital'] : false;
	}

	static function getSymbolCount(){
		$ary = self::getSymbolArray();
		return ($ary !== false) ? count($ary) : 0;
	}

	static function getCapitalCount(){
		$ary = self::getCapitalArray();
		return ($ary !== false) ? count($ary) : 0;
	}

	static function buildRows($handle){
		$rows = array();
		while($row = fgetcsv($handle)){
			$rows[] = $row;
		}
		return array_reverse($rows);
	}

	static function prettyPercent($raw, $precision = 2){
		$rounded = round(trim($raw), $precision+2) * 100;
		return sprintf("%.".$precision."f%%", $rounded);
	}

	static function prettyMoney($raw, $precision = 2){
		$rounded = number_format(trim($raw), $precision);
		return sprintf("$%s", $rounded);
	}

	static function prettyDate($raw, $format = "M-d", $convert = true){
		if($convert){
			$raw = strtotime($raw);
		}
		return date($format, $raw);
	}

	static function cleanData($col, $value){
		switch($col){
			case self::COL_DATE:		$value = self::prettyDate($value, "M-d");	break;
			case self::COL_OPEN:		$value = self::prettyMoney($value); 		break;
			case self::COL_HIGH:		$value = self::prettyMoney($value);			break;
			case self::COL_LOW:			$value = self::prettyMoney($value);			break;
			case self::COL_CLOSE:		$value = self::prettyMoney($value);			break;
			case self::COL_ADJ_CLOSE:	$value = self::prettyMoney($value);			break;
			case self::COL_CUM:			$value = self::prettyPercent($value);		break;
			case self::COL_VALUE:		$value = self::prettyMoney($value);			break;
			case self::COL_FUND_SUM:	$value = self::prettyMoney($value);			break;
			case self::COL_FUND_CUM:	$value = self::prettyPercent($value);		break;
			case self::COL_FUND_DAILY:	$value = self::prettyPercent($value,3);		break;
			default:
			break;
		}
		return $value;
	}

	static function printDataTableHeaders($print_cols, $symbols){
		$buf = "";
		$buf .= "<tr>";
		$buf .= "<th class='year_heading'>".YEAR."</th>";
		for($i = 0; $i < self::getStockCount(); $i++){
			$buf .= sprintf("<th colspan='%s'>%s</th>\n", count($print_cols), strtoupper($symbols[$i]));
		}
		$buf .= "<th colspan='3'>Fund Totals</th>\n";
		$buf .= "</tr>";
		$buf .= "<tr>";
		$buf .= "<th>Date</th>";
		for($i = 0; $i < Util::getStockCount(); $i++){
			foreach($print_cols as $name){
				$buf .= sprintf("<th>%s</th>", $name);
			}
		}
		$buf .= "<th>Fund Value</th>";
		$buf .= "<th>Fund RoI</th>";
		$buf .= "<th>Fund Delta</th>";
		$buf .= "</tr>";
		return $buf;
	}

	// Borrowed From: http://forums.phpfreaks.com/topic/253594-standard-deviation/
	static function standard_deviation($aValues, $bSample = false) {
	    $fMean = array_sum($aValues) / count($aValues);
	    $fVariance = 0.0;
	    foreach ($aValues as $i) {
	        $fVariance += pow($i - $fMean, 2);
	    }
	    $fVariance /= ( $bSample ? count($aValues) - 1 : count($aValues) );
	    return (float) sqrt($fVariance);
	}
}

$print_cols = array();
$print_cols[Util::COL_ADJ_CLOSE] = "Adj Close";
$print_cols[Util::COL_CUM] = "RoI";
$print_cols[Util::COL_VALUE] = "Daily Value";


if(Util::getStockCount()){
	if(Util::getSymbolCount() != Util::getCapitalCount()){
		echo "<h1 class='error'>Error! Number of Stock Symbols (".Util::getSymbolCount().") does not match number of Investment Capital Values (".Util::getCapitalCount().").</h1>";
	}

	$symbols = Util::getSymbolArray();
	$capitals = Util::getCapitalArray();
	$total_capital = array_sum($capitals);


	/* I could combine these loops since count($capitals) should always == count($symbols) == count($stocks).
		But the point of the exercise was to understand the process, not write efficient code.
		And I believe over simplication would detract from the clarity of what's happening.
		Besides, these arrays are only supposed to be 4 elements long.
	*/
	$weights = array();
	foreach($capitals as $i => $capital){
		$weights[$i] = $capital/$total_capital;
	}

	$stocks = array();
	foreach($symbols as $i => $symbol){
		$data_handle = fopen(Util::generateUrl($symbol), 'r');
		$headers = fgetcsv($data_handle);
		$stocks[] = Util::buildRows($data_handle);
	}

	foreach($stocks as $i => $stock){
		$dayone = $stock[0][Util::COL_ADJ_CLOSE];
		$capital = $capitals[$i];
		foreach($stock as $j => $day){
			$dailycum = $day[Util::COL_ADJ_CLOSE] / $dayone;
			$stocks[$i][$j][Util::COL_CUM] = $dailycum;
			$stocks[$i][$j][Util::COL_VALUE] = $dailycum * $capital;
		}
	}
}
?>
<html>
	<head>
		<title>Stock Analyzer</title>
		<style>
		table{float: left;}
		form table{float: none;}
		</style>
	</head>
	<body>
		<div id='input_form'>
			<form action='?' method='get'>
				<input type='hidden' name='stockcount' value='<?php echo STOCK_COUNT; ?>' />
				<table cellpadding='2' cellspacing='2' border='1'>
					<tbody>
						<tr>
							<th>&nbsp;</th>
							<?php 
							for($i = 0; $i < STOCK_COUNT; $i++){printf("<th>Stock %s</th>", $i+1);}
							?>
						</tr>
						<tr>
							<th>Enter Symbol:</th>
							<?php
							for($i = 0; $i < STOCK_COUNT; $i++){printf("<td><input type='text' name='symbol[]' class='symbol' value='%s' /></td>", Util::getGet('symbol', $i));}
							?>
						</tr>						
						<tr>
							<th>Enter Investment:</th>
							<?php
							for($i = 0; $i < STOCK_COUNT; $i++){printf("<td><input type='text' name='capital[]' class='capital' value='%s' /></td>", Util::getGet('capital', $i));}
							?>
						</tr>
					</tbody>					
				</table>
				<input type='submit' value='Generate Report' />
			</form>
		</div>
		<hr />
		<div id='results'>
<?php 
		if(Util::getStockCount()){
			echo "<table cellpadding='2' cellspacing='2' border='1'>\n";
			echo Util::printDataTableHeaders($print_cols, $symbols);
			$yesterdays_roi = false;
			$daily_deltas = array();
			foreach($stocks[0] as $index => $row){
				echo "<tr>";
				echo "<th class='date'>" . Util::cleanData(Util::COL_DATE, $row[Util::COL_DATE]) . "</th>";
				$fund_sum = 0;
				for($i = 0; $i < Util::getStockCount(); $i++){
					$fund_sum += $stocks[$i][$index][Util::COL_VALUE];
					foreach($print_cols as $col => $name){
						printf("<td>%s</td>", Util::cleanData($col, $stocks[$i][$index][$col]));
					}
				}
				$daily_roi = $fund_sum / $total_capital;
				$yesterdays_roi = ($yesterdays_roi===false) ? $daily_roi : $yesterdays_roi;
				$daily_delta = $daily_roi - $yesterdays_roi;
				$daily_deltas[] = $daily_delta;
				printf("<td>%s</td>", Util::cleanData(Util::COL_FUND_SUM, $fund_sum));
				printf("<td>%s</td>", Util::cleanData(Util::COL_FUND_CUM, $daily_roi));
				printf("<td>%s</td>", Util::cleanData(Util::COL_FUND_DAILY, $daily_delta));
				echo "</tr>\n";
				$yesterdays_roi = $daily_roi;
			}
			$annual_return = 0;
			foreach($stocks as $i => $stock){
				$first = array_shift($stock);
				$first = $first[Util::COL_CUM];
				$last = array_pop($stock);
				$last = $last[Util::COL_CUM];
				$annual_return += (($last - $first) * $weights[$i]);
			}
			echo "</table>\n";
			echo "<table cellpadding='2' cellspacing='2' border='1'>\n";
			echo "<tr><th>Stocks</th><th>Alloc</th><th>Capital</th></tr>\n";
			printf("<tr><th>Start</th><td>1</td><td>%s</td></tr>", Util::prettyMoney($total_capital));
			foreach($symbols as $i => $symbol){
				printf("<tr><th>%s</th><td>%s</td><td>%s</td></tr>\n", strtoupper($symbol), $capitals[$i] / $total_capital, Util::prettyMoney($capitals[$i]));
			}
			echo "</table>\n";

			$average_daily_return = array_sum($daily_deltas) / count($daily_deltas);
			$std_dev = Util::standard_deviation($daily_deltas);
			$sharpe = sqrt(count($daily_deltas)) * $average_daily_return / $std_dev;			
			echo "<table cellpadding='2' cellspacing='2' border='1'>\n";
			echo "<tr><th>Performance</th><th>Fund</th></tr>\n";
			printf("<tr><td>Annual Return</td><td>%s</td></tr>\n", Util::prettyPercent($annual_return));
			printf("<tr><td>Average Daily Return</td><td>%s</td></tr>\n", Util::prettyPercent($average_daily_return, 3));
			printf("<tr><td>STDEV Daily Return</td><td>%s</td></tr>\n", Util::prettyPercent($std_dev, 3));
			printf("<tr><td>Sharpe Ratio</td><td>%s</td></tr>\n", round($sharpe, 3));
			echo "</table>\n";
		}
?>
		</div>
	</body>
</html>
<?php





