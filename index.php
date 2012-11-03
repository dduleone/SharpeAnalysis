<?php 
/* 
	Step 1: Write Stock Analysis Tool
	Step 2: Use Tool to Analyze Stocks
	Step 3: ?????
	Step 4: Profit

*/
?><!DOCTYPE html><?php
//http://ichart.finance.yahoo.com/table.csv?a=00&b=1&c=2011&d=11&e=31&f=2011&g=d&ignore=.csv&s=NKE

define("STOCK_COUNT", 4);
define("YEAR", 2011);
define("REMOTE_URL", "http://ichart.finance.yahoo.com/table.csv?a=00&b=1&c=2011&d=11&e=31&f=" . YEAR . "&g=d&ignore=.csv&s=%s");

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
		$rounded = round($raw, $precision+2) * 100;
		return sprintf("%.".$precision."f%%", $rounded);
	}

	static function prettyMoney($raw, $precision = 2){
		$rounded = number_format($raw, $precision);
		return sprintf("$%s", $rounded);
	}

	static function cleanData($col, $value){
		switch($col){
			case self::COL_DATE:
				$value = date("M-d",strtotime($value));
			break;
			case self::COL_OPEN:
				$value = self::prettyMoney($value);
			break;
			case self::COL_HIGH:
				$value = self::prettyMoney($value);
			break;
			case self::COL_LOW:
				$value = self::prettyMoney($value);
			break;
			case self::COL_CLOSE:
				$value = self::prettyMoney($value);
			break;
			case self::COL_ADJ_CLOSE:
				$value = self::prettyMoney($value);
			break;
			case self::COL_CUM:
				$value = self::prettyPercent($value);
			break;
			case self::COL_VALUE:
				$value = self::prettyMoney($value);
			break;
			case self::COL_FUND_SUM:
				$value = self::prettyMoney($value);
			break;
			case self::COL_FUND_CUM:
				$value = self::prettyPercent($value);
			break;
			case self::COL_FUND_DAILY:
				$value = self::prettyPercent($value,3);
			break;
			default:
			break;
		}
		return $value;
	}

	static function printDatTableHeaders($print_cols, $symbols){
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
	$total_capital = 0;
	foreach($capitals as $capital){
		$total_capital += $capital;
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
	</head>
	<body>
		<div id='input_form'>
			<form action='?' method='get'>
				<input type='hidden' name='stockcount' value='<?php echo STOCK_COUNT; ?>' />
				<table cellpadding='2' cellspacing='2' border='1'>
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
				</table>
				<input type='submit' value='Generate Report' />
			</form>
		</div>
		<hr />
		<div id='results'>
<?php 
		if(Util::getStockCount()){
			echo "<table cellpadding='2' cellspacing='2' border='1'>\n";
			echo Util::printDatTableHeaders($print_cols, $symbols);
			$yesterdays_roi = false;
			foreach($stocks[0] as $index => $row){
				echo "<tr>";
				echo "<th class='date'>" . Util::cleanData(Util::COL_DATE, $row[Util::COL_DATE]) . "</th>";
				$fund_sum = 0;
				for($i = 0; $i < Util::getStockCount(); $i++){
					$fund_sum += $stocks[$i][$index][Util::COL_VALUE];
					foreach($print_cols as $col => $name){
						echo "<td>" . Util::cleanData($col, $stocks[$i][$index][$col]) . "</td>";
					}
				}
				$daily_roi = $fund_sum / $total_capital;
				$yesterdays_roi = ($yesterdays_roi===false) ? $daily_roi : $yesterdays_roi;
				$daily_delta = $daily_roi - $yesterdays_roi;
				echo "<td>" . Util::cleanData(Util::COL_FUND_SUM, $fund_sum) . "</td>";
				echo "<td>" . Util::cleanData(Util::COL_FUND_CUM, $daily_roi) . "</td>";
				echo "<td>" . Util::cleanData(Util::COL_FUND_DAILY, $daily_delta) . "</td>";
				echo "</tr>\n";
				$yesterdays_roi = $daily_roi;
			}
			echo "</table>";
		}
?>
		</div>
	</body>
</html>
<?php





