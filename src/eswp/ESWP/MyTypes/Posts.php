<?php
/**
 * Posts class file
 * Elasticsearch implementation of Wordpress posts
 * Defines mapping, indexing and searching
 */
 
namespace ESWP\MyTypes;

class Posts extends BaseType {
	public static function order() {
		return 10;
	}
	
	public function document_is_this_type($doc) {
		return
			$doc === "posts" ||
			get_class($doc) === "WP_Post";
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

		//Index the document
		$type = $index->getType(self::get_type());
		$type->addDocument(new \Elastica\Document($id, 
			array(
				"title" => $doc->post_title,
				"content" => $doc->post_content,
				"excerpt" => $doc->post_excerpt,
				"categories" => $_categories,
				"author" => $author->user_nicename,
				"slug" => $doc->guid,
				"modified" => date("Y-m-d") . "T" . date("H:i:s") . ".00000"
			)
		));
	}
	
	public function get_thumbnail($doc) {
		?>
		<h3><a href="<?php echo get_permalink($doc["id"]) ?>"><?php echo $doc["title"] ?></a></h3>
		<p><em>by <?php echo $doc["author"] ?><?php $this->thumbnail_get_categories($doc) ?></em></p>
		<p><?php if (isset($doc["excerpt"])) { echo $doc["excerpt"]; } ?></p>
		<?php
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