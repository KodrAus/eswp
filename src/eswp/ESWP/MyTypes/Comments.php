<?php
/**
 * Comments class file
 * Elasticsearch implementation of Wordpress comments
 * Defines mapping, indexing and searching
 */
 
namespace ESWP\MyTypes;

class Comments extends BaseType {
	public function wp_document_is_this_type($doc) {
		return isset($doc->comment_ID);
	}	
	public function es_document_is_this_type($doc) {
		return $doc === "comments";
	}
	
	public function map($type) {
		$type->setMapping(array(
			"modified" => array (
				"type" => "date"
			)
		));
	}
	
	public function index($type, $id, $doc) {
		$type->addDocument(new \Elastica\Document($id, 
			array(
				"author" => $doc->comment_author,
				"post_id" => $doc->comment_post_ID,
				"content" => $doc->comment_content,
				"modified" => date("Y-m-d") . "T" . date("H:i:s") . ".00000"
			)
		));
	}
	
	public function get_thumbnail($doc) {
		?><h6>Comment on post <?php echo $doc["post_id"] ?></h6><?php
	}
}
?>