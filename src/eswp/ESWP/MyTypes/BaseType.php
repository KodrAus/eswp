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
	
	public function get_query($q) {
		$query = array(
			//By default we don't return the content field in _source for efficiency
			//Instead where no highlights are available we rely on the excerpt
			"_source" => array(
				"exclude" => array(
					"content"
				)
			),
			//Highlights on the content field will still be returned
			"highlight" => array(
				"pre_tags" => array('<span class="highlight">'),
        		"post_tags" => array("</span>"),
				"fields" => array (
					"content" => array(
						"force_source" => true,
						"type" => "postings"
					)
				)
			),
		    "query" => array(
				"function_score" => array(
					"query" => array(
						//We're running 2 queries here:
						//the must query is 'loose' over the phrase the user puts in
						//the should query is 'tight' over the phrase the user puts in
						"bool" => array(
							"must" => array(
								"query_string" => array(
									"query" => $q
								)
							),
							"should" => array(
						        "match" => array(
						            "content" => array(
										"query" => $q,
										"type" => "phrase",
										"boost" => 2,
										"slop" => 0.3,
										"cutoff_frequency" => 0.1
									)
						        )
							)
						)
					),
					"functions" => array(
						//By default Elasticsearch prefers short lengths, meaning stubs get
						//ranked more highly. Here we want to penalize very short articles
						array(
							"filter" => array(
								"bool" => array(
									"must" => array(
										"exists" => array(
											"field" => "content_length"
										)	
									),
									"must" => array(
										"range" => array(
											"content_length" => array(
												"from" => 0,
												"to" => 200
											)
										)
									)
								)
							),
							"boost_factor" => 0.5
						),
						//For the article/post usecase, your users are probably more interested
						//in the most recent content. Here, the score of results will start to
						//decay once they are 'offset' old from today to have a score multiplied by 
						//'decay' by the time they are 'scale' old
						array(
							"filter" => array(
								"exists" => array(
									"field" => "modified"
								)
							),
							"gauss" => array(
								"modified" => array(
									"scale" => "35d",
									"offset" => "14d",
									"decay" => 0.6
								)
							)
						)
					)
				)
		    )
		);
		
		return $query;
	}
	
	public function get_autocomplete($q) {
		//TODO: Add default search for autocomplete
	}
}
?>