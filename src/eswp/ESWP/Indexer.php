<?php
/**
 * Indexer class file
 * Static service class acts as a mediator between Elasticsearch and Wordpress
 * Implementation provided by Elastica
 */
 
namespace ESWP;

class Indexer {
	//Get an instance of the Elastica client
	//We should build this through some easy to edit configuration
	//Just use a basic url param. So we can support https, basic auth and custom paths
	public static function get_client() {
		$url = get_option("eswp_server");
		return new \Elastica\Client(array(
		    "url" => $url
		));
	}
	
	//Index a generic document. Call this method from your custom hooks
	public static function index_doc($id, $doc) {
		$_doc = self::get_first_type_match_for_doc($doc);

		$client = self::get_client();

		$index = $client->getIndex(self::get_index());
		$type = $index->getType($_doc->get_type());

		try
		{
			if (!$index->exists()) {
				$index->create(array(), true);
				$_doc->map($client, $index, $type);
			}
				
			$_doc->index($client, $index, $type, $id, $doc);
		}
		catch (\Elastica\Exception\Connection\HttpException $e)
		{
			echo 'ESWP: Error indexing: ',  $e->getMessage(), "\n";
		}
	}
	
	//Delete a generic document
	public static function delete_doc($id, $doc) {
		$client = self::get_client();
	
		$index = $client->getIndex(self::get_index());
		$type = $index->getType($doc->get_type());
		
		try
		{
			if ($index->exists()) {
				$type->deleteDocument(new \Elastica\Document($id, array()));
			}
		}
		catch (\Elastica\Exception\Connection\HttpException $e)
		{
			echo 'ESWP: Error deleting: ',  $e->getMessage(), "\n";
		}
	}
	
	//Index all documents
	public static function index_all() {
		$types = self::get_all_types();

		if (has_filter("es_get_all_docs")) {
			$docs = Array();
			$docs = apply_filters("es_get_all_docs", $docs);

			foreach ($docs as $doc) {
				//Index each document
				$_doc = self::_get_first_type_match_for_doc($doc["doc"], $types);
				
				$client = self::get_client();

				$index = $client->getIndex(self::get_index());
				$type = $index->getType($_doc->get_type());
					
				try
				{
					if (!$index->exists()) {
						$index->create(array(), true);
						$_doc->map($client, $index);
					}
	
					$_doc->index($client, $index, $doc["id"], $doc["doc"]);
				}
				catch (\Elastica\Exception\Connection\HttpException $e)
				{
					echo 'ESWP: Error indexing: ',  $e->getMessage(), "\n";
				}
			}
		}
	}
	
	//Standard search
	public static function search_docs($query, $ontype) {
		$client = self::get_client();

		//If the type to search on is set, then use it. Otherwise use BaseType default search
		try
		{
			if (isset($ontype)) {
				return $ontype::query($client, $query);
			}
			else {
				return \ESWP\MyTypes\BaseType::query($client, $query);
			}
		}
		catch (\Elastica\Exception\Connection\HttpException $e)
		{
			echo 'ESWP: Error searching: ',  $e->getMessage(), "\n";
		}
	}
	
	//Get the index
	public static function get_index() {
		$index = get_option("eswp_index");
		
		if (!isset($index)) {
			$index = "wordpress";
		}
		
		return $index;
	}
	
	//Get a specific document type in MyTypes
	public static function get_type($type = "Posts") {
		$_type = "\\ESWP\\MyTypes\\"."$type";
		return new $_type();
	}
	
	//Get the first document type in MyTypes that can index a document (ordered by optional order() method)
	public static function get_first_type_match_for_doc($doc) {
		$types = self::get_all_types();
		return self::_get_first_type_match_for_doc($doc, $types);
	}
	
	//Private method to pass previously found types in to match for recursive functions
	static function _get_first_type_match_for_doc($doc, $types) {
		$sorted_types = self::array_sort($types, "order");

		foreach ($sorted_types as $type) {
			if ($type["type"]->document_is_this_type($doc)) {
				return $type["type"];
			}
		}
			
		return null;
	}
	
	public static function get_all_types() {	
		//This section should be cached?
		$types = scandir(str_replace("/", "\\", dirname(plugin_dir_path( __FILE__ )) . "\\ESWP\\MyTypes"));
		$size = count($types);
		$_types = array();
		
		//Feed in the extra types
		$extra_types = array();
		if(has_filter("es_include_types")) {
			$extra_types = apply_filters("es_include_types", $extra_types);
			
			$size += count($extra_types);
			
			foreach ($extra_types as $type) {
				$order = $size;
				
				if (method_exists($type, "get_order")) {
					$order = $type->get_order();
				}

				array_push($_types, array(
					"order" => $order,
					"type" => $type
				));
			}
		}
		
		//Add the included types
		foreach ($types as $type) {
			if ($type !== "BaseType.php" && trim($type, ".") !== "") {
				$order = $size + 10;
				$_type = "\\ESWP\\MyTypes\\". explode(".", $type)[0];
				
				if (class_exists($_type)) {
					$_type_obj = new $_type();
					
					if (method_exists($_type, "get_order")) {
						$order = $_type_obj->get_order();
					}
			
					array_push($_types, array(
						"order" => $order,
						"type" => $_type_obj
					));
				}
			}
		}

		return $_types;
	}
		
	//Helper method for sorting arrays on certain field
	public static function array_sort($array, $on, $order=SORT_ASC)
	{
	    $new_array = array();
	    $sortable_array = array();
		
	    if (count($array) > 0) {
	        foreach ($array as $k => $v) {
	            if (is_array($v)) {
	                foreach ($v as $k2 => $v2) {
	                    if ($k2 == $on) {
	                        $sortable_array[$k] = $v2;
	                    }
	                }
	            } else {
				    $sortable_array[$k] = $v;
		        }
		    }
		
		    switch ($order) {
		        case SORT_ASC:
		            asort($sortable_array);
		        break;
		        case SORT_DESC:
		            arsort($sortable_array);
		        break;
		    }
		
		    foreach ($sortable_array as $k => $v) {
		        $new_array[$k] = $array[$k];
		    }
		}
		
	    return $new_array;
	}
}
?>