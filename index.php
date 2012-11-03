<?php
//http://ichart.finance.yahoo.com/table.csv?a=00&b=1&c=2011&d=11&e=31&f=2011&g=d&ignore=.csv&s=NKE


define("STOCK_COUNT", 4);

class Util{

	const REMOTE_URL = "http://ichart.finance.yahoo.com/table.csv?a=00&b=1&c=2011&d=11&e=31&f=2011&g=d&ignore=.csv&s=%s";

	const COL_DATE = 0;
	const COL_OPEN = 1;
	const COL_HIGH = 2;
	const COL_LOW = 3;
	const COL_CLOSE = 4;
	const COL_VOLUME = 5;
	const COL_ADJ_CLOSE = 6;
	const COL_CUM = 7;
	const COL_VALUE = 8;


	static function getGet($var, $index = null){
		return (isset($_GET[$var])) ? ( ((is_array($_GET[$var])) && ($index!==null)) ? $_GET[$var][$index] : $_GET[$var] ) : "";
	}

	static function generateUrl($symbol){
		return sprintf(self::REMOTE_URL, $symbol);
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

	static function prettyPercent($raw){
		$rounded = round($raw, 4) * 100;
		return sprintf("%.2f%%", $rounded);
	}

	static function prettyMoney($raw){
		$rounded = number_format($raw, 2);
		return sprintf("$%s", $rounded);
	}

	static function cleanData($col, $value){
		switch($col){
			case self::COL_DATE:
			break;
			case self::COL_OPEN:
			break;
			case self::COL_HIGH:
			break;
			case self::COL_LOW:
			break;
			case self::COL_CLOSE:
			break;
			case self::COL_VOLUME:
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
			default:
			break;
		}
		return $value;
	}
}

$print_cols = array();
$print_cols[Util::COL_ADJ_CLOSE] = "Adj Close";
$print_cols[Util::COL_CUM] = "Cum %";
$print_cols[Util::COL_VALUE] = "Daily Value";


if(Util::getStockCount()){
	if(Util::getSymbolCount() != Util::getCapitalCount()){
		echo "<h1 class='error'>Error! Number of Stock Symbols (".Util::getSymbolCount().") does not match number of Investment Capital Values (".Util::getCapitalCount().").</h1>";
	}

	$symbols = Util::getSymbolArray();
	$capitals = Util::getCapitalArray();

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
?><!DOCTYPE html>
<html>
	<head>
		<title>Stock Analyzer</title>
	</head>
	<body>
		<div id='input_form'>
			<form action='?' method='get'>
				<input type='hidden' name='stockcount' value='<?php echo STOCK_COUNT; ?>' />
<?php
					for($i = 0; $i < STOCK_COUNT; $i++){
?>
						<div class='stock'>
							<h3>Stock <?php echo $i; ?></h3>
							<label>Enter Symbol: </label><input type='text' name='symbol[]' class='symbol' value='<?php echo Util::getGet('symbol', $i); ?>' /><br />
							<label>Enter Investment: </label><input type='text' name='capital[]' class='capital' value='<?php echo Util::getGet('capital', $i); ?>' /><br />
						</div>
<?php
					} 
?>
				<input type='submit' value='go' />
			</form>
		</div>
		<div id='results'>
<?php 
		if(Util::getStockCount()){
?>
			<table cellpadding='2' cellspacing='2' border='1'>
			<tr>
				<th class='blank'>&nbsp;</th>
<?php
			for($i = 0; $i < Util::getStockCount(); $i++){
				echo "<th colspan='".count($print_cols)."'>" . strtoupper($symbols[$i]) . "</th>\n";
			}
?>
				</tr>
				<tr>
					<th>Date</th>
<?php
					foreach($stocks as $count){
						foreach($print_cols as $col => $name){
							echo "<th>".$name."</th>";
						}
					}
?>
				</tr>
<?php
			foreach($stocks[0] as $index => $row){
				echo "<tr>";
				echo "<th class='date'>" . $row[Util::COL_DATE] . "</th>";
				for($i = 0; $i < Util::getStockCount(); $i++){
					foreach($print_cols as $col => $name){
						echo "<td>" . Util::cleanData($col, $stocks[$i][$index][$col]) . "</td>";
					}
				}
				echo "</tr>\n";
			}
?>
			</table>
<?php
		}
?>
		</div>
	</body>
</html>
<?php





