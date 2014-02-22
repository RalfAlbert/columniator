<?php
/**
 * WordPress-Plugin Columninator
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

class Columninator
{

	/**
	 * Flag for tidy. True if tidy is available, else false
	 * @var boolean
	 */
	private $tidy_available = false;

	/**
	 * Internally used by the class
	 * @var mixed
	 */
	private $tag, $regexp = '';

	/**
	 * Internally used. Stores the content from the HTML tags
	 * which should be protected
	 * @var array
	 */
	private $protected_contents = array();

	/**
	 * This is an array with HTML tags which will be protected by the class.
	 * It is still a very basic protection and can be expanded within the
	 * class configuration
	 * @var array
	 */
	public $html_tags = array(
			'links'      => '#<a(.+)</a>#uUis',
			'headings'   => '#<.\d>(.+)</.\d>#uUis',
			'images'     => '#<img(.+)/>#uUis',
			'divs'       => '#<div(.+)</div>#uUis',
			'blockquote' => '#<blockquote(.+)</blockquote>#uUis',
			'cite'       => '#<cite(.+)</cite>#uUis',
	);

	/**
	 * The final result
	 * @var string
	 */
	public $content = '';

	/**
	 * Words per column
	 * @var unknown
	 */
	public $words_per_col = 100;

	/**
	 * Number of columns for each column-block
	 * @var unknown
	 */
	public $num_cols = 2;

	/**
	 * Divider between the column-blocks
	 * @var unknown
	 */
	public $cols_divider = '<hr />';

	/**
	 * CSS classes applied to the columns.
	 * @var array
	 */
	public $cols_css = array(
			'left-column',
			'right-column'
	);

	/**
	 * Flag if the content of each coumn should be wrapped
	 * with p-tags.
	 * @var boolean
	 */
	public $wrap_content_in_paragraph = true;

	/**
	 * HTML that will be wrapped around each column-block
	 * @var unknown
	 */
	public $wrapper_for_cols = '';

	/**
	 * The constructor checks if the tidy extension is available
	 */
	public function __construct() {

		$this->tidy_available = extension_loaded( 'tidy' );

	}

	/**
	 * Convert content into columns
	 * @param  string $content The content to be converted
	 * @return string $content In columns converted content
	 */
	public function convert_to_columns( $content = NULL ) {

		$this->content = $this->protect_content( $content );

		$pages          = array();
		$words_per_page = $this->words_per_col * $this->num_cols;
		$count_col_css  = count( $this->cols_css ) - 1;

		$words      = explode( ' ', $this->content );
		$word_count = count( $words );

		while ( ! empty( $words ) ) {

			$pieces  =  array_slice( $words, 0, $words_per_page );
			array_splice( $words, 0, $words_per_page );

			$current_page = '';

			for ( $current_col = 0; $current_col < $this->num_cols; $current_col++ ) {

				$column = implode( ' ', array_slice( $pieces, $current_col * $this->words_per_col, $this->words_per_col ) );
				$column = $this->sanitize_html( $column );
				$column = trim( $column );

				// wrap content in paragraphs
				if ( '<p>' != substr( $column, 0, 3 ) && true == $this->wrap_content_in_paragraph )
					$column = sprintf( '<p>%s</p>', $column );

				// get the css class for the current column
				// use the last class if the number of cols is greater than the available number
				// of css classes
				$css_num = ( $current_col > $count_col_css ) ?
				$count_col_css : $current_col;

				// create page container
				$column = sprintf(
						'<div class="%s">%s</div>',
						$this->cols_css[ $css_num ],
						$column
				);

				$current_page .= $column;

			}

			if ( ! empty( $this->wrapper_for_cols ) )
				$current_page = sprintf( $this->wrapper_for_cols, $current_page );

			$pages[] = $current_page;

		}

		$this->content = implode( $this->cols_divider, $pages );

		$this->content = $this->unprotect_content( $this->content );

		return $this->content;

	}

	/**
	 * Return the actual content
	 * @return string $content The (converted) content
	 */
	public function get_content() {

		return $this->content;

	}

	/**
	 * Try to 'repair' corrupted HTML
	 * @param  string $html HTML to be repaired
	 * @return string $html Repaired HTML
	 */
	public function sanitize_html( $html ) {

		$html = $this->add_open_tags( $html );

		/*
		 * use tidy if extension is available
		 * tidy is a bit more powerfull as force_balance_tags()
		 *
		 * otherwise
		 *
		 * use WPs force_balance_tags if tidy is not available
		 * this should close all open tags in the right order
		 */

		return ( true == $this->tidy_available ) ?
			$this->use_tidy( $html ) : $this->use_wp_formatting( $html );

	}

	/**
	 * Add opening tags if closing tags without opening tags exists
	 * @param  string $html HTML to be sanitized
	 * @return string $html Sanitized HTML
	 */
	public function add_open_tags( $html ) {

		$closed = array();

		// protect all tags that are opened AND closed
		$html = preg_replace( '#<([^</]+)>(.*)</(.+)>#uUis', '[$1]$2[/$3]', $html );

		// find closing tags without opening tags and add the opening tag
		preg_match_all( '#</(.+)>#uUis', $html, $closed );

		foreach ( $closed[1] as $tag )
			$html = sprintf( '<%s>%s', $tag, $html );

		// unprotect the protected tags
		$html = preg_replace( '#\[([^\[/]+)\](.*)\[/(.+)\]#uUis', '<$1>$2</$3>', $html );

		return $html;

	}

	/**
	 * Use tidy on maybe corrupted HTML
	 * @param  string $html Maybe corrupted HTML
	 * @return string $html Repaired HTML
	 */
	protected function use_tidy( $html ) {

		// Specify configuration
		$config = array(
				'indent'         => false,
				'output-xhtml'   => true,
				'wrap'           => 200
		);

		// Tidy
		$tidy = new tidy;
		$tidy->parseString( $html, $config, 'utf8' );
		$tidy->cleanRepair();

		// get body
		$body = array();
		preg_match( '#<body>(.+)</body#uis', $tidy->value, $body );

		return ( isset( $body[1] ) ) ? $body[1] : $html;

	}

	/**
	 * Use WordPress formatting (force_balance_tags) on maybe corrupted HTML
	 * @param  string $html Maybe corrupted HTML
	 * @return string $html Repaired HTML
	 */
	protected function use_wp_formatting( $html ) {

			if ( ! function_exists( 'force_balance_tags' ) )
				require_once ABSPATH . '/wp-includes/formatting.php';

			return force_balance_tags( $html );

	}

	/**
	 * Protect content which should not be disrupted
	 * @param  string $content String with HTML tags to be protected
	 * @return string $content String with replaced (protected) HTML tags
	 */
	public function protect_content( $content ) {

		foreach ( $this->html_tags as $tag => $regexp ) {
			$this->tag    = $tag;
			$this->regexp = $regexp;
			$content = $this->protect_html( $content );
		}

		return $content;

	}


	/**
	 * Replace HTML tags with a placeholder
	 * @param  string $content Content to protect
	 * @return string $content Protected content
	 */
	public function protect_html( $content ) {

		return preg_replace_callback(
				$this->regexp,
				array( $this, 'protect_html_callback' ),
				$content
		);

	}


	/**
	 * Callback for Columninator::protect_html()
	 * Stores the result from the preg_match_callback() in the internal array
	 * and returns a placeholder
	 * @param  array  $match   Array with search results from preg_match_callback()
	 * @return string Ambigous Placeholder for the current HTML tag
	 */
	public function protect_html_callback( $match ) {

		if ( isset( $match[0] ) ) {
			$this->protected_contents[ $this->tag ][] = $match[0];
			return sprintf( '[%s-%d]', $this->tag, count( $this->protected_contents[ $this->tag ] ) - 1 );
		}

		return '';

	}


	/**
	 * Revert the protection from Columninator::protect_content()
	 * @param  string $content Content to unprotect
	 * @return string $content Unprotected content
	 */
	public function unprotect_content( $content ) {

		foreach ( $this->html_tags as $tag => $regexp ) {
			$this->tag    = $tag;
			$this->regexp = sprintf( '#\[%s-(\d+)\]#U', $tag );
			$content = $this->unprotect_html( $content );
		}

		return $content;

	}


	/**
	 * Revert the protection from Columninator::protect_html()
	 * This method will replace the placeholders set in Columninator::protect_html()
	 * with the stored HTML tags
	 * @param  string $content String with placeholders
	 * @return string $content String with replacements
	 */
	public function unprotect_html( $content ) {

		return preg_replace_callback(
				$this->regexp,
				array( $this, 'unprotect_html_callback' ),
				$content
		);

	}


	/**
	 * Callback for Columninator::unprotect_html()
	 * This method will replace the placeholders with the stored HTML tags
	 * @param  array  $match Results from preg_match_callback()
	 * @return string String with replaced HTML tags
	 */
	public function unprotect_html_callback( $match ) {

		if ( isset( $match[1] ) && isset( $this->protected_contents[ $this->tag ][ $match[1] ] ) )
			return $this->protected_contents[ $this->tag ][ $match[1] ];

		return '';

	}


}
