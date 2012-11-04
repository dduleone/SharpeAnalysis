<?php 
/* 
	Step 1: Write Stock Analysis Tool
	Step 2: Use Tool to Analyze Stocks
	Step 3: ?????
	Step 4: Profit

*/

/**
TODO: (This list is not prioritized)

 - I want to pass the stock data as JSON and let the browser render the tables.

 - I want to add tooltips which show the formulas for each record
 - I want to show stdev & average daily return broken up by stock
 - I want to make the daily data collapsable
 - I want to be able to update the investment values and update the page via JS, if the stocklist doesn't change
 - I want to build a solver to get the maximum Sharpe Ratio
 - I want to improve the HTML table output (Proper DOM compliance)
 - I want to make the interface "prettier"
 - For fun, I want to integrate the option to use Google Finance
 - I need to figure out how to check if a stock symbol is invlaid
 - I need to figure out how to handle if unable to pull data
 - I need to figure out how to handle when there's not enough historical data for one or more of the stocks
*/

//require_once("YahooFinance.php");
require_once("LocalFinance.php");
require_once("Util.php");

define("STOCK_COUNT", 4);
define("YEAR", 2011);

$print_cols = array();
$print_cols[Util::COL_ADJ_CLOSE] = "Adj Close";
$print_cols[Util::COL_CUM] = "RoI";
$print_cols[Util::COL_VALUE] = "Daily Value";

$input = new Input();
$input->validate();

//$finance = new YahooFinance(array('start_year' => YEAR, 'end_year' => YEAR));
$finance = new LocalFinance(array('start_year' => YEAR, 'end_year' => YEAR));
$stocks = $finance->fetchStocks($input);
$fund = new FundData($stocks, $input);

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Stock Analyzer</title>
		<link rel='stylesheet' type='text/css' href='sharpe.css' />
	</head>
	<body>
		<div id='input_form'>
			<form action='?' method='get'>
				<input type='hidden' name='stockcount' value='<?php echo STOCK_COUNT; ?>' />
				<table cellpadding='2' cellspacing='0' border='0' id='form_input'>
					<?php $input->printInputHeaders(STOCK_COUNT); ?>
					<?php $input->printInputBody(STOCK_COUNT); ?>
				</table>
				<input type='submit' value='Generate Report' />
			</form>
		</div>
		<hr />
		<div id='results'><?php if(Util::getGet('stockcount')){$fund->showReport($print_cols);} ?></div>
	</body>
</html>
<?php
