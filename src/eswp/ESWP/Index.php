<?php 
/**
 * Index class file
 * Default implementations for Elasticsearch index with custom analysers for autocomplete
 * Defines analysis for index
 */
 
namespace ESWP;

class Index {
	public static function map($client, $index) {
		//Maybe we should use the same American spelling?
		$analysers = array(
			"autocomplete_num" => array(
				"type" => "custom",
				"tokenizer" => "engram",
				"filter" => array(
					"lowercase"
				)
			),
			"autocomplete_text" => array(
				"type" => "custom",
				"tokenizer" => "whitespace",
				"filter" => array(
					"engram",
					"lowercase"
				)
			)
		);
		
		$filters = array(
			"engram" => array(
				"type" => "edgeNGram",
				"min_gram" => 3,
				"max_gram" => 20
			)
		);
		
		$tokenisers = array(
			"engram" => array(
				"type" => "edgeNGram",
				"min_gram" => 3,
				"max_gram" => 20
			)			
		);
		
		if (has_filter("es_get_index_analysers")) {
			$analysers = apply_filters("es_get_index_analysers", $analysers);
		}
		
		if (has_filter("es_get_index_filters")) {
			$filters = apply_filters("es_get_index_filters", $filters);
		}
		
		if (has_filter("es_get_index_tokenizers")) {
			$tokenisers = apply_filters("es_get_index_tokenisers", $tokenisers);
		}
		
		if (!$index->exists()) {
			$index->create(
				array(
					"analysis" => array(
						"analyzer" => $analysers,
						"filter" => $filters,
						"tokenizer" => $tokenisers
					)
				)
			);
		}
	}
}
?>