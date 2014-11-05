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
$output = \ESWP\Client::search_docs($query, $ontype);

//Filter over each result and get the associated type
?>
<div class="search-results">
<?php
foreach($output["hits"]["hits"] as $hit) {
	$type = $hit["_type"];
	$source = $hit["_source"];
	$source["id"] = $hit["_id"];
			
	//This should be moved to some method that is internal to each type
	//If the hit contains a highlight, then use it
	if (isset($hit["highlight"])) {
		$preview = "";
		foreach ($hit["highlight"] as $highlights) {
			foreach ($highlights as $highlight) {
				$preview = $preview . "<p class='search-excerpt'>" . $highlight . "</p>";
			}
		}
	
		$source["excerpt"] = $preview;
	}
	//Otherwise, if an excerpt hasn't been set manually
	elseif (isset($source["excerpt"]) && strlen($source["excerpt"]) === 0) {
		$excerpt_length = 35;
		$preview = $source["content"];
	    $preview = strip_tags(strip_shortcodes($preview));
	    $words = explode(' ', $preview, $excerpt_length + 1);
		
	    if (count($words) > $excerpt_length) {
	        array_pop($words);
	        array_push($words);
			
	        $preview = implode(' ', $words);

			$last_char = substr($preview, -1);
			
			if ($last_char !== "!" && $last_char !== "." && $last_char !== "?") {
				$preview = $preview . "...";
			}
		}
		
	    $source["excerpt"] = "<p class='search-excerpt'>" . $preview . "</p>";
	}
	
	//Get the first type match and execute the get_thumbnail method
	$_type = \ESWP\Client::get_first_type_match_for_doc($type, "es");
	if (isset($_type)) {
		echo $_type->get_thumbnail($source);
	}
}
?>
</div>