<?php
/**
 * Comments class file
 * Elasticsearch implementation of Wordpress comments
 * Defines mapping, indexing and searching
 */
 
namespace ESWP\MyTypes;

class Comments extends BaseType {	
	public function document_is_this_type($doc) {
		return
			$doc === "comments" ||
			isset($doc->comment_ID);
	}
	
	public function map($client, $index) {
		$type = $index->getType(self::get_type());
		$type->setMapping(array(
			"modified" => array (
				"type" => "date"
			)
		));
	}
	
	public function index($client, $index, $id, $doc) {
		$type = $index->getType(self::get_type());
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