<?php
/**
 * Client class file
 * Static service class acts as a mediator between Elasticsearch and Wordpress
 * Implementation provided by Elastica
 */
 
namespace ESWP;

class Client {
	//Get an instance of the Elastica client
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
			\ESWP\Index::map($client, $index);
			
			$_doc->map($client, $index, $type);
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
					\ESWP\Index::map($client, $index);
					
					$_doc->map($client, $index, $type);
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
	public static function search_docs($q, $on_type, $query_type = "search") {
		$client = self::get_client();

		//If the type to search on is set, then use it. Otherwise use BaseType default search
		try
		{
			if (isset($on_type)) {
				return self::execute_query($client, forward_static_call_array(array($on_type, $query_type . "_query"), array($q)));
			}
			else {
				return self::execute_query($client, forward_static_call_array(array(\ESWP\MyTypes\BaseType, $query_type . "_query"), array($q)));
			}
		}
		catch (\Elastica\Exception\Connection\HttpException $e)
		{
			echo 'ESWP: Error searching: ',  $e->getMessage(), "\n";
		}
	}
	
	private static function execute_query($client, $query) {
		$index = $client->getIndex(self::get_index());

		if ($index->exists()) {
			$path = $index->getName() . "/_search";
	
			$response = $client->request($path, \Elastica\Request::POST, $query);
			$response_array = $response->getData();

			return $response_array;
		}
		
		return null;
	}
	
	public static function get_thumbnails($result, $thumbnail_type = "search") {
		$thumbnails = array();

		foreach($result["hits"]["hits"] as $hit) {
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
				array_push($thumbnails, call_user_func_array(array($_type, "get_".$thumbnail_type."_thumbnail"), array($source)));
			}
		}

		return $thumbnails;
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
	public static function get_first_type_match_for_doc($doc, $doc_type = "wp") {
		$types = self::get_all_types();
		return self::_get_first_type_match_for_doc($doc, $types, $doc_type);
	}
	
	//Private method to pass previously found types in to match for recursive functions
	static function _get_first_type_match_for_doc($doc, $types, $doc_type = "wp") {
		$check = $doc_type . "_document_is_this_type";
		$sorted_types = self::array_sort($types, "order");

		foreach ($sorted_types as $type) {
			if ($type["type"]->$check($doc)) {
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