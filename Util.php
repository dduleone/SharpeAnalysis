<?php 
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

class Input{
	var $_symbols;
	var $_capitals;
	var $_total_capital;
	var $_stockcount;
	var $_weights;

	function __construct(){
		$this->_symbols = Util::getGet('symbol');
		$this->_capitals = Util::getGet('capital');
		$this->_stockcount = Util::getGet('stockcount');
		$this->_total_capital = array_sum($this->_capitals);

		$this->_weights = array();
		foreach($this->_capitals as $capital){
			$this->_weights[] = $capital/$this->_total_capital;
		}
	}

	function getSymbols($index = null){
		if(is_numeric($index)){
			return $this->_symbols[$index];
		}
		return $this->_symbols;
	}

	function getCapitals($index = null){
		if(is_numeric($index)){
			return $this->_capitals[$index];
		}		
		return $this->_capitals;
	}

	function getWeights($index = null){
		if(is_numeric($index)){
			return $this->_weights[$index];
		}
		return $this->_weights;
	}

	function getStockCount(){
		return $this->_stockcount;
	}

	function getSymbolCount(){
		return count($this->getSymbols());
	}

	function getCapitalCount(){
		return count($this->getCapitals());
	}

	function printInputHeaders($count){
		printf("  <thead>\n");
		printf("    <tr>\n");
		printf("      <th>&nbsp;</th>\n");
		for($i = 0; $i < $count; $i++){
			printf("      <th>Stock %s</th>\n", $i+1);
		}
		printf("    </tr>\n");
		printf("  </thead>\n");
	}

	function getTickerSymbolInputs($count){
		printf("    <tr>\n");
		printf("      <td class='th_col'>Enter Symbol:</td>\n");
		for($i = 0; $i < $count; $i++){
			printf("      <td><input type='text' name='symbol[]' class='symbol' value='%s' /></td>\n", $this->getSymbols($i));
		}
		printf("    </tr>\n");
	}

	function getInvestmentCapitalInputs($count){
		printf("    <tr>\n");
		printf("      <td class='th_col'>Enter Investment:</td>\n");
		for($i = 0; $i < $count; $i++){
			printf("      <td><input type='text' name='capital[]' class='capital' value='%s' /></td>\n", $this->getCapitals($i));
		}
		printf("    </tr>\n");
	}

	function printInputBody($count){
		printf("  <tbody>\n");
		$this->getTickerSymbolInputs($count);
		$this->getInvestmentCapitalInputs($count);
		printf("  </tbody>\n");
	}

	function validate(){
		if($this->getSymbolCount() != $this->getCapitalCount()) {
			printf("<h1 class='error'>Error! Number of Stock Symbols (%s) does not match number of Investment Capital Values (%s).</h1>", $this->getSymbolCount(), $this->getCapitalCount());
		}
	}
}