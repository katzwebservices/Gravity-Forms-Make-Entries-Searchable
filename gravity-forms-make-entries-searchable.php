<?php
/*
Plugin Name: Gravity Forms - Make Entries Searchable
Plugin URI: https://gravityview.co
Description: Make entries searchable!
Version: 1.0
Author: Katz Web Services, Inc.
Author URI: https://katz.co
*/

add_filter( 'posts_results', 'gf_make_entries_searchable', 10, 2 );

function gf_make_entries_searchable( $results, $object ) {
	global $wp_query;

	if( !is_search() || !is_main_query()  ) { return $results; }

	$search_criteria = array();

	$search_criteria["status"] = "active";

	foreach ($object->query as $key => $value) {
		$search_criteria["field_filters"][] = array( 'key' => 0, 'value' => $value );
	}

	$search_criteria["field_filters"]["mode"] = "any";

	$entries = GFAPI::get_entries( 0, $search_criteria );

	$posts = array();
	foreach ($entries as $key => $entry) {
		$posts[] = new Gravity_Forms_Entry_As_Post( $entry );
	}

	return $results + $posts;
}

add_filter( 'post_link', 'gf_make_entries_searchable_link', 10, 3 );

function gf_make_entries_searchable_link( $permalink, $post, $leavename ) {

	if( $post->post_type !== 'gravity-forms-entry' ) {
		return $permalink;
	}

	return admin_url( 'admin.php?page=gf_entries&view=entry&id='.$post->post_parent.'&lid='.$post->guid );
}

class Gravity_Forms_Entry_As_Post {

	/**
	 * @todo Use `post_link` filter to modify permalink for this
	 * @param [type] $entry [description]
	 */
	function __construct( $entry ) {

		$form = GFAPI::get_form( $entry['form_id'] );

		// Fake ID
		$this->ID = time() + $entry['id'];
		$this->post_title = 'Entry #'.$entry['id'];
		$this->post_author = $entry['created_by'];
		$this->post_date = $entry['date_created'];
		$this->post_content = GFCommon::replace_variables('{all_fields}', $form, $entry);
		$this->post_excerpt = NULL;
		$this->post_status = $entry['status'] === 'active' ? 'publish' : 'draft';
		$this->ping_status = 'closed';
		$this->comment_status = 'closed';
		$this->post_name = sanitize_title( 'Entry #'.$entry['id'] );
		$this->post_type = 'gravity-forms-entry';

		// To pass to the permalink
		$this->post_parent = $form['id'];
		$this->guid = $entry['id'];
	}

}