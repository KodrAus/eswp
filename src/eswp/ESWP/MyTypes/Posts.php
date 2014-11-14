<?php
/**
 * Posts class file
 * Elasticsearch implementation of Wordpress posts
 * Defines mapping, indexing and searching
 */
 
namespace ESWP\MyTypes;

class Posts extends BaseType {
	public function wp_document_is_this_type($doc) {
		return get_class($doc) === "WP_Post";
	}
	public function es_document_is_this_type($doc) {
		return $doc === "posts";
	}
	
	//The default Post mapping adds a modified datestamp, content length and autocomplete field
	public function map($type) {
		$type->setMapping(array(
			"modified" => array(
				"type" => "date"
			),
			"content" => array(
				"type" => "string",
				"index_options" => "offsets",
				"analyzer" => "english"
			),
			"title_autocomplete" => array(
				"type" => "string",
				"analyzer" => "autocomplete_text"	
			)
		));
	}
	
	public function index($type, $id, $doc) {
		//Get the categories
		$categories = get_the_terms($doc, "category");
		$_categories = array();
		foreach ($categories as $category) {
			array_push($_categories, array(
				"name"=>$category->name,
				"id"=>$category->term_id
			));
		}

		//Get the author
		$author = get_user_by("id", $doc->post_author);
		
		//Excerpt
		$excerpt = "";
		if (!isset($doc->post_excerpt) || strlen($doc->post_excerpt) === 0) {
			$excerpt_length = 35;
			$excerpt = $doc->post_content;
		    $excerpt = strip_tags(strip_shortcodes($excerpt));
		    $words = explode(' ', $excerpt, $excerpt_length + 1);
			
		    if (count($words) > $excerpt_length) {
		        array_pop($words);
		        array_push($words);
				
		        $excerpt = implode(' ', $words);
	
				$last_char = substr($excerpt, -1);
				
				if ($last_char !== "!" && $last_char !== "." && $last_char !== "?") {
					$excerpt = $excerpt . "...";
				}
			}
			
			$doc->post_excerpt = $excerpt;
		}
		else {
			$excerpt = $doc->post_excerpt;
		}
		
		//Content length
		$content_length = strlen($doc->post_content);

		//Index the document
		$type->addDocument(new \Elastica\Document($id, 
			array(
				"title" => $doc->post_title,
				"title_autocomplete" => $doc->post_title,
				"content" => $doc->post_content,
				"content_length" => $content_length,
				"excerpt" => $excerpt,
				"categories" => $_categories,
				"author" => $author->user_nicename,
				"slug" => $doc->guid,
				"modified" => date("Y-m-d") . "T" . date("H:i:s") . ".00000"
			)
		));
	}
	
	public function get_search_thumbnail($doc) {
		if (!isset($doc["excerpt"])) {
			$doc["excerpt"] = "";
		}
		
		return '
		<div class="search-result">
			<h3><a href="' . get_permalink($doc["id"]) .'">' . $doc["title"] . '</a></h3>
			<p><em>by '.$doc["author"] .' ' . $this->thumbnail_get_categories($doc) .'</em></p>
			' . $doc["excerpt"] . '
		</div>
		';
	}
	
	public function get_autocomplete_thumbnail($doc) {
		return '<h3><a href="' . get_permalink($doc["id"]) .'">' . $doc["title"] . '</a></h3>';
	}
	
	function thumbnail_get_categories($doc) {
		if (isset($doc["categories"])) {
			echo " in ";
			
			$c = count($doc["categories"]);
			$i = 0;
			foreach($doc["categories"] as $category) {
				$i++;
				if ($i === $c) {
					echo $category["name"];
				}
				else {
					echo $category["name"] . ", ";
				}
			}
		}
	}
}
?>