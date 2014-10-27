<?php
/**
 * BaseType class file
 * Default implementations for Elasticsearch types
 * Defines mapping, indexing and searching
 */
 
namespace ESWP\MyTypes;

abstract class BaseType {	
	public function get_order() {
		$class = get_called_class();
		$parent = get_parent_class($class);
		
		if (isset($parent) && $parent === "ESWP\MyTypes\BaseType")
		{
			return 50;
		}
		else
		{
			return 10;
		}
	}
	
	public function get_type() {
		//Return the class name by default
		//Or the name of the base class if it isn't BaseType
		$class = get_called_class();
		$parent = get_parent_class($class);
		
		if (isset($parent) && $parent !== "ESWP\MyTypes\BaseType")
		{
			$class = $parent;
		}

		return strtolower(end(explode("\\", $class)));
	}
	
	abstract public function get_thumbnail($doc);

	abstract public function document_is_this_type($doc);
	
	abstract public function index($client, $index, $type, $id, $doc);
	
	public function map($client, $index, $type) {
		//By default let Elasticsearch handle mapping at index time
	}
	
	public function query($client, $query) {
		//By default we execute a simple query_string search on all types in the index
		$q = array(
			"highlight" => array(
				"pre_tags" => array('<span class="highlight">'),
        		"post_tags" => array("</span>"),
				"fields" => array (
					"content" => array(
						"force_source" => true
					)
				)
			),
		    "query" => array(
		        "query_string" => array(
		            "query" => $query,
		        )
		    )
		);
		
		$index = $client->getIndex(\ESWP\Indexer::get_index());

		if ($index->exists()) {
			$path = $index->getName() . "/_search";
	
			$response = $client->request($path, \Elastica\Request::POST, $q);
			$response_array = $response->getData();

			return $response_array;
		}
		
		return null;
	}
}
?>