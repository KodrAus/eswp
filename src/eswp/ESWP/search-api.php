<?php
/**
 * search-api script file
 * Executes Elasticsearch queries and returns results as JSON for API calls
 * Like the SearchPage script, but designed for backend queries, like autocomplete
 */
 
$query = isset($_REQUEST["q"]) ? $_REQUEST["q"] : "";
$ontype = isset($_REQUEST["t"]) ? "\\ESWP\\MyTypes\\" . $_REQUEST["t"] : "\\ESWP\\MyTypes\\BaseType";

$output = null;
$output = \ESWP\Indexer::search_docs($query, $ontype);
	 
if ($output) {
	// callback support for JSONP
	if (isset($_REQUEST["callback"])) {
		header("Content-Type: application/javascript");
		echo $_REQUEST["callback"] . "(" . json_encode($output) . ")";
	}
	else {
		header("Content-Type: application/json");
		echo json_encode($output);
	}
}

die();
?>