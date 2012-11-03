<?php
//http://ichart.finance.yahoo.com/table.csv?a=00&b=1&c=2011&d=11&e=31&f=2011&g=d&ignore=.csv&s=NKE


define("STOCK_COUNT", 4);

function getGet($var, $index = null){
	if(isset($_GET[$var])){
		if( ($index !== null) && (is_array($_GET[$var])) ){
			return $_GET[$var][$index];
		} else {
			return $_GET[$var];
		}
	} else {
		return "";
	}
}

function buildRows($handle){
	$rows = array();
	while($row = fgetcsv($handle)){
		$rows[] = $row;
	}
	$rows = array_reverse($rows);
	return $rows;
}

$results = false;
$years = array();
//print_r($_GET);
if(isset($_GET['symbol'])){
	$results = true;
	$table = "";

	foreach($_GET['symbol'] as $index => $symbol){
		if(!isset($_GET['capital'][$index]) || !is_numeric($_GET['capital'][$index])){
			$_GET['capital'][$index] = 0;
		}

		$_url = "http://ichart.finance.yahoo.com/table.csv?a=00&b=1&c=2011&d=11&e=31&f=2011&g=d&ignore=.csv&s=" . $symbol;
		$data_handle = fopen($_url, 'r');
		$headers = fgetcsv($data_handle);
		$rows = buildRows($data_handle);
		$year = new TradeYear(getGet('capital', $index));
		foreach($rows as $row){
			$year->addRow($row);
		}
		$table[] = $year->dump();
		$years[] = $year;
	}

	$buffer = "";
	foreach($table[0] as $index => $year){
		$a = array();
		for($i = 0; $i < STOCK_COUNT; $i++){
			$a[] = $table[$i][$index];
		}
		$buffer[] = implode(",", $a);
	}
	var_dump($buffer);
	
}
?><!DOCTYPE html>
<html>
	<head>
		<title>Stock Analyzer</title>
	</head>
	<body>
		<div id='input_form'>
			<form action='?' method='get'>
<?php
					for($i = 0; $i < STOCK_COUNT; $i++){
?>
						<div class='stock'>
							<h3>Stock <?php echo $i; ?></h3>
							<label>Enter Symbol: </label><input type='text' name='symbol[]' class='symbol' value='<?php echo getGet('symbol', $i); ?>' /><br />
							<label>Enter Investment: </label><input type='text' name='capital[]' class='capital' value='<?php echo getGet('capital', $i); ?>' /><br />
						</div>
<?php
					} 
?>
				<input type='submit' value='go' />
			</form>
		</div>
		<div id='results'>
<?php 
		if($results){
//			foreach($years as $year) {
				echo $years->prettyDump();
//			}
		}
?>
		</div>
	</body>
</html>
<?php


class TradeYear{
	var $_days;
	var $_capital;
	
	function __construct($capital = 0){
		$this->_capital = $capital;
	}
	
	function addDay(TradeDay $day){
		$this->_days[] = $day;
	}
	
	function addRow($row){
		$day = new TradeDay($row);
		if(count($this->_days) > 0){
			$day->setCum($this->_days[0]->getAdjClose());
		} else {
			$day->setCum($day->getAdjClose());
		}
		$day->setCapital($this->_capital);
		$this->addDay($day);
	}
		
	function dump(){
		$buffer = "";
		foreach($this->_days as $date => $day){
			$buffer .= $day->dump();
		}
		return $buffer;
	}
	
	function prettyDump(){
		$buffer = "";
		$buffer .= "<table border='1' cellpadding='1' cellspacing='1'>\n";
		$buffer .= "  <tr>\n";
		$buffer .= "    <th>Date</th>\n";
		$buffer .= "    <th>Open</th>\n";
		$buffer .= "    <th>High</th>\n";
		$buffer .= "    <th>Low</th>\n";
		$buffer .= "    <th>Close</th>\n";
		$buffer .= "    <th>Volume</th>\n";
		$buffer .= "    <th>Adjsted Close</th>\n";
		$buffer .= "    <th>Cum</th>\n";
		$buffer .= "    <th>Value</th>\n";
		$buffer .= "</tr>\n";
		
		foreach($this->_days as $date => $day){
			$buffer .= $day->prettyDump();
		}
		$buffer .= "</table>\n";
		return $buffer;
	}
}

class TradeDay{
	var $_data;
	var $_cum;
	var $_value;
	var $_capital;
	
	function __construct($row){
		$this->_data = array();
		$this->_data['date'] = $row[0];
		$this->_data['open'] = $row[1];
		$this->_data['high'] = $row[2];
		$this->_data['low'] = $row[3];
		$this->_data['close'] = $row[4];
		$this->_data['volume'] = $row[5];
		$this->_data['adj_close'] = $row[6];
	}

	function setCum($dayone){
		$this->_cum = $this->_data['adj_close'] / $dayone;
	}
	
	function setCapital($capital){
		$this->_capital = $capital;
	}

	function getAdjClose(){
		return $this->_data['adj_close'];
	}
	
	function dump(){
		return json_encode($this->_data);
	}
	
	function prettyCum(){
		return (round($this->getCum(), 4) * 100);
	}
	
	function getCum(){
		return $this->_cum;
	}
	
	function getValue(){
		return $this->getCum() * $this->_capital;
	}
	
	function prettyValue(){
		return round($this->getValue(), 4);
	}
	
	function prettyDump($wrap_tr = true){
		$buffer = "";
		foreach($this->_data as $field => $data){
			$buffer .= "    <td>" . $data . "</td>\n";
		}
		$buffer .= sprintf("    <td>%.2f%%</td>\n", $this->prettyCum());
		$buffer .= sprintf("    <td>$%.2f</td>\n", $this->prettyValue());
		if($wrap_tr){
			$buffer = "  <tr>\n" . $buffer . "</tr>\n";
		}
		return $buffer;
	}
}
