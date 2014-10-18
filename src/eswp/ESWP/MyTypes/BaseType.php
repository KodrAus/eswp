<?php
/**
 * BaseType class file
 * Default implementations for Elasticsearch types
 * Defines mapping, indexing and searching
 */
 
namespace ESWP\MyTypes;

abstract class BaseType {
	public static function _get_index() {
		return "wordpress";
	}
	
	public function get_index() {
		return self::_get_index();
	}
	
	public static function get_type() {
		return strtolower(end(explode("\\", get_called_class())));
	}
	
	abstract public function get_thumbnail($doc);

	abstract public function document_is_this_type($doc);
	
	abstract public function index($client, $index, $post_ID, $_post);
	
	public function map($client, $index) {
		//By default let Elasticsearch handle mapping at index time
	}
	
	public function query($client, $query) {
		//By default we execute a simple query_string search on all types in the index
		$q = array(
			"highlight" => array(
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
		
		$index = $client->getIndex(self::_get_index());

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