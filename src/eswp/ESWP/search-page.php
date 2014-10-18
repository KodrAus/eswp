<?php
/**
 * search-page script file
 * Executes Elasticsearch queries and returns results on a Wordpress page containing the [eswp-search] shortcode
 * Like the SearchAPI script, but designed for frontend queries
 */
 
//Get the search parameters
$query = isset($_REQUEST["q"]) ? $_REQUEST["q"] : "";
$ontype = isset($_REQUEST["t"]) ? "\\ESWP\\MyTypes\\" . $_REQUEST["t"] : "\\ESWP\\MyTypes\\BaseType";
	
//Execute the search query
$output = \ESWP\Indexer::search_docs($query, $ontype);

//Filter over each result and get the associated type
foreach($output["hits"]["hits"] as $hit) {
	$type = $hit["_type"];
	$source = $hit["_source"];
	$source["id"] = $hit["_id"];
		
	if (isset($hit["highlight"])) {
		$preview = "";
		foreach ($hit["highlight"] as $highlights) {
			foreach ($highlights as $highlight) {
				$preview = $preview . $highlight;
			}
		}
			
		if (substr($preview, -1) !== ".") {
			$preview = $preview . "...";
		}
			
		$source["excerpt"] = $preview;
	}

	//Get the first type match and execute the get_thumbnail method
	$_type = \ESWP\Indexer::get_first_type_match_for_doc($type);
	if (isset($_type)) {
		echo $_type->get_thumbnail($source);
	}
}
?>