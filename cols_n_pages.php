<?php
/**
 * WordPress-Plugin Columns And Pages
 *
 * PHP version 5.2
 *
 * @category   PHP
 * @package    WordPress
 * @subpackage RalfAlbert\View\Columniator
 * @author     Ralf Albert <me@neun12.de>
 * @license    GPLv3 http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    0.1
 * @link       http://wordpress.com
 */

/**
 * Plugin Name: Columns And Pages
 * Plugin URI:  http://yoda.neun12.de
 * Text Domain:
 * Domain Path:
 * Description: Explode the content in columns and pages. No post content will be modified by this plugin in the database!!
 * Author:      Ralf Albert
 * Author URI:  http://yoda.neun12.de/
 * Version:     1.0
 * License:     GPLv3
 */

/*
 * We want to use the '<!--nextpage-->' tag to split the post content
 * into single pages. WordPress will split the post before the filter
 * 'the_content' will be done. So we have to use a very early filter
 * to apply the '<!--nextpage-->' tags to the post content
 */
add_filter( 'the_posts', 'columns_and_pages', 0, 1 );

function columns_and_pages( $posts = NULL ){

	if ( empty( $posts ) || ! is_main_query() || ! is_singular() )
		return $posts;

	if ( ! class_exists( 'Columninator' ) )
		require_once 'columninator.php';

	/*
	 * Create the Columninator object and configure it
	 */
	$cap = new Columninator();

	// set words per column
	$cap->words_per_col = 30;

	// set number of columns
	$cap->num_cols      = 3;

	// set the divider between each columns-block
	$cap->cols_divider  = '<!--nextpage-->';

	// set css classes for column #1, #2 & #3
	$cap->cols_css      = array( 'left-column', 'middle-column', 'right-column' );

	// wrap the columns in this container
	$cap->wrapper_for_cols = '<div class="columns">%s</div>';


	// split the content of each post into columns
	foreach ( $posts as $post ) {
		$cap->convert_to_columns( $post->post_content );
		$post->post_content = $cap->get_content();
	}

	return $posts;

}
