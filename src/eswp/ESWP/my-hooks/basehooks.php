<?php
/**
 * basehooks script file
 * Contains default hooks for indexing/deleting Wordpress posts and comments
 */
 
add_action("post_updated", "index_post");
add_action("before_delete_post", "delete_post");
add_action("comment_post", "index_comment");
add_action("deleted_comment", "delete_comment");

add_filter("es_get_all_docs", "get_all_posts");

//Index a Wordpress post
function index_post($post_ID) {
	$post = get_post($post_ID);
	\ESWP\Indexer::index_doc($post_ID, $post);
}

//Delete a Wordpress post
function delete_post($post_ID) {
	$_doc = new \ESWP\MyTypes\Posts();
	$client = \ESWP\Indexer::get_client();
	
	$index = $client->getIndex($_doc->get_index());
	$type = $index->getType($_doc::get_type());
		
	if ($index->exists()) {
		$type->deleteDocument(new \Elastica\Document($post_ID, array()));
	}
}

//Index a Wordpress comment
function index_comment($comment_ID, $comment_approved) {
	$comment = get_comment($comment_ID);
	\ESWP\Indexer::index_doc($comment_ID, $comment);
}

//Delete a Wordpress comment
function deleted_comment(int $comment_ID) {
	$_doc = new \ESWP\MyTypes\Comments();
	$client = \ESWP\Indexer::get_client();
	
	$index = $client->getIndex(\ESWP\MyTypes\BaseType::index);
	$type = $index->getType($_doc::get_type());
		
	if ($index->exists()) {
		$type->deleteDocument(new \Elastica\Document($comment_ID, array()));
	}
}

//Get all Wordpress posts
function get_all_posts($docs) {
	$all = new WP_Query('showposts=10000');

	foreach ($all->posts as $doc) {
		array_push($docs, Array(
			"doc" => $doc, 
			"id" => $doc->ID)
		);
	}
	
	return $docs;
}
?>