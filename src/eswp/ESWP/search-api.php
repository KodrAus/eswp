<?php
/**
 * search-api script file
 * Executes Elasticsearch queries and returns results as JSON for API calls
 * Like the SearchPage script, but designed for backend queries, like autocomplete
 */

$action = isset($_REQUEST["action"]) ? strtolower($_REQUEST["action"]) : "search";

switch ($action) {
	//Execute a search
	case "search":
		$query = isset($_REQUEST["q"]) ? $_REQUEST["q"] : "";
		$ontype = isset($_REQUEST["t"]) ? "\\ESWP\\MyTypes\\" . $_REQUEST["t"] : "\\ESWP\\MyTypes\\BaseType";
		
		$output = null;
		$output = \ESWP\Indexer::search_docs($query, $ontype);
	break;
	//Execute a reindex
	case "reindex":
		$key = isset($_REQUEST["key"]) ? $_REQUEST["key"] : "";
		$keyToMatch = get_option("eswp_api_key");
		
		if ($keyToMatch !== false && strlen($keyToMatch) > 1 && $key === $keyToMatch) {
			\ESWP\Indexer::index_all();
			$output = "Ok";
		}
		else {
			$output = "Unauthorised";
		}
	break;
}

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