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
foreach(\ESWP\Client::get_thumbnails($output) as $thumbnail) {
	echo $thumbnail;
}
?>
</div>