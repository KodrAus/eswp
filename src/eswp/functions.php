<?php
/**
 * Plugin Name: ESWP
 * Description: A framework for indexing and searching Wordpress content in Elasticsearch. This plugin is aimed at developers
 * Version: 0.85
 * Author: Ashley Mannix
 * License: MIT
 */

//Loader
add_action("init", "loader");

function loader() {
	include_once("ESWP/MyTypes/BaseType.php");
	include_once("ESWP/my-hooks/base-hooks.php");
	
    spl_autoload_register("__autoload_elastica");
	spl_autoload_register("__autoload_eswp");

	//Register all hooks
	//I haven"t gone with an autoload solution here because we want to immediately scan and register all items
	$hooks = scandir(str_replace("/", "\\", plugin_dir_path( __FILE__ ) . "\\ESWP\\my-hooks"));
	
	foreach ($hooks as $hook) {
		include(str_replace("/", "\\", plugin_dir_path( __FILE__ ) . "\\ESWP\\my-hooks\\" . $hook));
	}
	
	//Index all items
	//ESWP\Client::index_all();
}

function __autoload_elastica ($class) {
    $path = substr($class, 0);
	$_path = str_replace("/", "\\", plugin_dir_path( __FILE__ ) ."\\lib\\elastica\\lib\\" . $path . ".php");

    if (file_exists($_path)) {
		include($_path);
    }
}

function __autoload_eswp($class) {
	$path = substr($class, 0);
	$_path = str_replace("/", "\\", plugin_dir_path( __FILE__ ) . $path . ".php");

	if (file_exists($_path)) {
		include($_path);
	}
}

//We have two forms of searching in ESWP:
// - Standard search results via a page with shortcode, uses thumbnail templates for display
// - JSON API via an injected template
//It would be nice to have some kind of standard way of doing things, but each seems to work best for their need

//Shortcodes
//[eswp-search]
function eswpearch_func($atts) {
	//Execute the search from the _REQUEST object
	include(str_replace("/", "\\", plugin_dir_path( __FILE__ ) . "\\ESWP\\search-page.php"));
}
add_shortcode("eswp-search", "eswpearch_func");

//Inject the Search API template into the Wordpress cache
include_once(str_replace("/", "\\", plugin_dir_path( __FILE__ ) . "\\ESWP\\SearchAPILoader.php"));

//Widgets
//Search bar for Elasticsearch
//I"d like to see if I can override the default search behaviour rather than relying on extra pages
include_once(str_replace("/", "\\", plugin_dir_path( __FILE__ ) . "\\ESWP\\SearchWidget.php"));
add_action( "widgets_init", function() {
     register_widget("\\ESWP\\SearchWidget");
});

//Options
function eswp_options_init() {
	add_settings_section(
		"eswp_section",
		"ESWP",
		"eswp_section_callback_function",
		"general"
	);

	//Server URL
 	add_settings_field(
		"eswp_server",
		"Elasticsearch Server",
		"eswp_server_callback_function",
		"general",
		"eswp_section"
	);
	
	//Index name
 	add_settings_field(
		"eswp_index",
		"Elasticsearch Index",
		"eswp_index_callback_function",
		"general",
		"eswp_section"
	);
	
	//Auth key for the JSON API
	add_settings_field(
		"eswp_api_key",
		"API Key",
		"eswp_key_callback_function",
		"general",
		"eswp_section"
	);

 	register_setting("general", "eswp_server");
	register_setting("general", "eswp_index");
	register_setting("general", "eswp_api_key");
 }
 
add_action( "admin_init", "eswp_options_init" );

function eswp_section_callback_function() {
	echo "<p>ESWP Server Settings</p>";
}

function eswp_server_callback_function() {
	//Initialise API key with a default value
	if ( get_option( "eswp_server" ) === false ) {
    	update_option( "eswp_server", "http://localhost:9200" );
	}
 	echo '<input name="eswp_server" id="gv_thumbnails_insert_into_excerpt" type="url" value="' . get_option( 'eswp_server' ) . '" class="code" /> Fully qualified URL of your Elasticsearch server: {protocol}://{user}:{pass}@{host}:{port}/{path}';
}

function eswp_index_callback_function() {
	//Initialise API key with a default value
	if ( get_option( "eswp_index" ) === false ) {
    	update_option( "eswp_server", \ESWP\Client::get_index() );
	}
 	echo '<input name="eswp_index" id="gv_thumbnails_insert_into_excerpt" type="text" value="' . get_option( 'eswp_index' ) . '" class="code" /> The name of the index to use for Wordpress';
}

function eswp_key_callback_function() {
	//Initialise API key with a default value
	if ( get_option( "eswp_api_key" ) === false ) {
    	update_option( "eswp_api_key", bin2hex(openssl_random_pseudo_bytes(16)) );
	}
	
 	echo '<input name="eswp_api_key" id="gv_thumbnails_insert_into_excerpt" type="text" value="' . get_option( 'eswp_api_key' ) . '" class="code" /> Authorization key for ESWPs JSON API';
}
?>