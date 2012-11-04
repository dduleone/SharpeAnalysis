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
 - For fun, I want to integrate the option to use Google Finance
 - I need to figure out how to check if a stock symbol is invlaid
 - I need to figure out how to handle if unable to pull data
 - I need to figure out how to handle when there's not enough historical data for one or more of the stocks
*/

require_once("YahooFinance.php");
require_once("Util.php");

define("STOCK_COUNT", 4);
define("YEAR", 2011);

$print_cols = array();
$print_cols[Util::COL_ADJ_CLOSE] = "Adj Close";
$print_cols[Util::COL_CUM] = "RoI";
$print_cols[Util::COL_VALUE] = "Daily Value";

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Stock Analyzer</title>
		<style type='text/css'>
		table{float: left;}
		form table{float: none;}
		.th_col{font-weight: bold;}
		th,.th_col{background: #ccc;}
		*{font-family: monospace;}
		td,th{border: 1px black solid;}
		table{border-collapse: collapse;}
		</style>
	</head>
	<body>
		<div id='input_form'>
			<form action='?' method='get'>
				<input type='hidden' name='stockcount' value='<?php echo STOCK_COUNT; ?>' />
				<table cellpadding='2' cellspacing='0' border='0' id='form_input'>
					<tbody><?php
						getInputHeaders();
						getTickerSymbolInputs();
						getInvestmentCapitalInputs();
					?>
					</tbody>
				</table>
				<input type='submit' value='Generate Report' />
			</form>
		</div>
		<hr />
		<div id='results'><?php if(Util::getGet('stockcount')){generateReport($print_cols);} ?></div>
	</body>
</html>
<?php

function getInputHeaders(){
	echo "<tr>";
	echo "<th>&nbsp;</th>";
	for($i = 0; $i < STOCK_COUNT; $i++){
		printf("<th>Stock %s</th>", $i+1);
	}
	echo "</tr>";
}

function getTickerSymbolInputs(){
	echo "<tr>";
	echo "<th>Enter Symbol:</th>";
	for($i = 0; $i < STOCK_COUNT; $i++){
		printf("<td><input type='text' name='symbol[]' class='symbol' value='%s' /></td>", Util::getGet('symbol', $i));
	}
	echo "</tr>";
}

function getInvestmentCapitalInputs(){
	echo "<tr>";
	echo "<th>Enter Investment:</th>";
	for($i = 0; $i < STOCK_COUNT; $i++){
		printf("<td><input type='text' name='capital[]' class='capital' value='%s' /></td>", Util::getGet('capital', $i));
	}
	echo "</tr>";
}

function getDataTableHeaders($print_cols, $symbols){
	$buf = "";
	$buf .= "  <thead>\n";
	$buf .= "    <tr>\n";
	$buf .= "      <th class='year_heading'>".YEAR."</th>\n";
	for($i = 0; $i < Util::getGet('stockcount'); $i++){
		$buf .= sprintf("      <th colspan='%s'>%s</th>\n", count($print_cols), strtoupper($symbols[$i]));
	}
	$buf .= "      <th colspan='3'>Fund Totals</th>\n";
	$buf .= "    </tr>\n";
	$buf .= "    <tr>\n";
	$buf .= "      <th>Date</th>\n";
	for($i = 0; $i < Util::getGet('stockcount'); $i++){
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

function generateReport($print_cols){
	$finance = new YahooFinance(array('start_year' => YEAR, 'end_year' => YEAR));
	$input = new Input();
	$input->validate();
	$stocks = $finance->fetchStocks($input);
	$fund = new FundData($stocks, $input);

	printf("\n<table cellpadding='2' cellspacing='0' border='0' id='all_results'>\n");
	printf(getDataTableHeaders($print_cols, $input->getSymbols()));
	printf("  <tbody>\n");

	for($index = 0; $index < $stocks[0]->getDateCount(); $index++){
		printf("    <tr>\n");
		printf("      <td class='th_col'>%s</td>\n", Util::cleanData(Util::COL_DATE, $stocks[0]->getDate($index)));
		for($i = 0; $i < $input->getStockCount(); $i++){
			foreach($print_cols as $col => $name){
				printf("      <td>%s</td>\n", Util::cleanData($col, $stocks[$i]->getDataByColumn($index, $col)));
			}
		}
		printf("      <td>%s</td>\n", Util::cleanData(Util::COL_FUND_SUM, $fund->getDailySum($index)));
		printf("      <td>%s</td>\n", Util::cleanData(Util::COL_FUND_CUM, $fund->getDailyROI($index)));
		printf("      <td>%s</td>\n", Util::cleanData(Util::COL_FUND_DAILY, $fund->getDailyDelta($index)));
		printf("    </tr>\n");

	}

	echo "  </tbody>\n";
	echo "</table>\n";
	echo "<table cellpadding='2' cellspacing='0' border='0'>\n";
	echo "  <thead><tr><th>Stocks</th><th>Alloc</th><th>Capital</th></tr></thead>\n";
	echo "  <tbody>\n";
	printf("    <tr><td class='th_col'>Start</td><td>1</td><td>%s</td></tr>\n", Util::prettyMoney($input->_total_capital));
	foreach($input->getSymbols() as $i => $symbol){
		printf("    <tr><th>%s</th><td>%s</td><td>%s</td></tr>\n", strtoupper($symbol), $input->getCapitals($i) / $input->_total_capital, Util::prettyMoney($input->getCapitals($i)));
	}
	echo "  </tbody>\n";
	echo "</table>\n";

	printf("<table cellpadding='2' cellspacing='0' border='0'>\n");
	printf("  <thead><tr><th>Performance</th><th>Fund</th></tr></thead>\n");
	printf("  <tbody>\n");
	printf("    <tr><td class='th_col'>Annual Return</td><td>%s</td></tr>\n", Util::prettyPercent($fund->getAnnualReturn()));
	printf("    <tr><td class='th_col'>Average Daily Return</td><td>%s</td></tr>\n", Util::prettyPercent($fund->getAverageDailyReturn(), 3));
	printf("    <tr><td class='th_col'>STDEV Daily Return</td><td>%s</td></tr>\n", Util::prettyPercent($fund->getStdDev(), 3));
	printf("    <tr><td class='th_col'>Sharpe Ratio</td><td>%s</td></tr>\n", round($fund->getSharpe(), 3));
	printf("  </tbody>\n");
	printf("</table>\n");
}