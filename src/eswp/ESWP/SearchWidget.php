<?php
/**
 * SearchWidget class file
 * An implementation of a search box using Elasticsearch via ESWP instead of MySQL
 * This has been done separately rather than overriding the default Wordpress search to maximise compatibility
 * It also allows us to use AJAX for aggregation updates instead of requiring a complete refresh
 */
 
namespace ESWP;

class SearchWidget extends \WP_Widget {
	public function __construct() {
		parent::__construct(
			"eswp_search_widget", // Base ID
			__("ESWP Search", "text_domain"), // Name
			array( "description" => __( "Search with Elasticsearch", "text_domain" ), ) // Args
		);
	}

	public function widget( $args, $instance ) {
		//Get the search page
		$page = $this->get_search_page();
		
		if (isset($page) && $page !== "") {
			?>
			<form role="search" method="post" class="search-form" action="<?php echo $page ?>">
				<label>
					<input type="hidden" value="Posts" name="t">
					<input type="text" class="search-field" placeholder="search" value="" name="q" title="Search">
				</label>
				<input type="submit" class="search-submit" value="Search">
			</form>
			<?php
		}
	}

	public function form( $instance ) {
		// outputs the options form on admin
	}

	public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
	}
	
	public function get_search_api() {
		//This section should be cached?
	    $pages = get_pages(array(
			"meta_key" => "_wp_page_template",
			"meta_value" => "api-template.php"
		));
	
	    foreach($pages as $page) {
	        return get_permalink($page->ID);
	    }
		
	    return null;
	}
	
	public function get_search_page() {
		//This section should be cached?
	    $pages = get_pages(array(
			"post_type" => "page",
			"post_status" => "publish")
		);
		
	    foreach($pages as $page) {
			$pattern = get_shortcode_regex();
		    if (preg_match_all("/". $pattern . "/s", $page->post_content, $matches)
		        && array_key_exists( 2, $matches)
		        && in_array("eswp-search", $matches[2]))
		    {
		        return get_permalink($page->ID);
		    }
	    }
		
	    return null;
	}
}
?>