<?php
/**
 * WPSEO plugin file.
 *
 * @package Yoast\WP\SEO\Integrations\Third_Party
 */

namespace Yoast\WP\SEO\Integrations\Third_Party;

use WPSEO_Replace_Vars;
use Yoast\WP\SEO\Conditionals\Front_End_Conditional;
use Yoast\WP\SEO\Conditionals\WooCommerce_Conditional;
use Yoast\WP\SEO\Helpers\Options_Helper;
use Yoast\WP\SEO\Integrations\Integration_Interface;
use Yoast\WP\SEO\Presentations\Indexable_Presentation;

/**
 * Class WooCommerce
 */
class WooCommerce implements Integration_Interface {

	/**
	 * The options helper.
	 *
	 * @var Options_Helper
	 */
	private $options;

	/**
	 * The WPSEO Replace Vars object.
	 *
	 * @var WPSEO_Replace_Vars
	 */
	private $replace_vars;

	/**
	 * @inheritDoc
	 */
	public static function get_conditionals() {
		return [ WooCommerce_Conditional::class, Front_End_Conditional::class ];
	}

	/**
	 * WooCommerce constructor.
	 *
	 * @param Options_Helper     $options      The options helper.
	 * @param WPSEO_Replace_Vars $replace_vars The replace vars helper.
	 */
	public function __construct( Options_Helper $options, WPSEO_Replace_Vars $replace_vars ) {
		$this->options      = $options;
		$this->replace_vars = $replace_vars;
	}

	/**
	 * @inheritDoc
	 */
	public function register_hooks() {
		\add_filter( 'wpseo_frontend_page_type_simple_page_id', [ $this, 'get_page_id' ] );
		\add_filter( 'wpseo_title', [ $this, 'title' ], 10, 2 );
		\add_filter( 'wpseo_metadesc', [ $this, 'description' ], 10, 2 );
	}

	/**
	 * Returns the ID of the WooCommerce shop page when the currently opened page is the shop page.
	 *
	 * @param int $page_id The page id.
	 *
	 * @return int The Page ID of the shop.
	 */
	public function get_page_id( $page_id ) {
		if ( ! $this->is_shop_page() ) {
			return $page_id;
		}

		return $this->get_shop_page_id();
	}

	/**
	 * Handles the title.
	 *
	 * @param string                 $title        The title.
	 * @param Indexable_Presentation $presentation The indexable presentation.
	 *
	 * @return string The title to use.
	 */
	public function title( $title, Indexable_Presentation $presentation ) {
		if ( $presentation->model->title ) {
			return $title;
		}

		if ( ! $this->is_shop_page() ) {
			return $title;
		}

		if ( ! \is_archive() ) {
			return $title;
		}

		$shop_page_id = $this->get_shop_page_id();
		if ( $shop_page_id === -1 ) {
			return $title;
		}

		$product_template_title = $this->get_product_template( 'title-product', $shop_page_id );
		if ( $product_template_title ) {
			return $product_template_title;
		}

		return $title;
	}

	/**
	 * Handles the meta description.
	 *
	 * @param string                 $description  The title.
	 * @param Indexable_Presentation $presentation The indexable presentation.
	 *
	 * @return string The description to use.
	 */
	public function description( $description, Indexable_Presentation $presentation ) {
		if ( $presentation->model->description ) {
			return $description;
		}

		if ( ! $this->is_shop_page() ) {
			return $description;
		}

		if ( ! \is_archive() ) {
			return $description;
		}

		$shop_page_id = $this->get_shop_page_id();
		if ( $shop_page_id === -1 ) {
			return $description;
		}

		$product_template_description = $this->get_product_template( 'metadesc-product', $shop_page_id );
		if ( $product_template_description ) {
			return $product_template_description;
		}

		return $description;
	}

	/**
	 * Checks if the current page is a WooCommerce shop page.
	 *
	 * @return bool True when the page is a shop page.
	 */
	protected function is_shop_page() {
		if ( ! \is_shop() ) {
			return false;
		}

		if ( \is_search() ) {
			return false;
		}

		return true;
	}

	/**
	 * Uses template for the given option name and replace the replacement variables on it.
	 *
	 * @param string $option_name  The option name to get the template for.
	 * @param string $shop_page_id The page id to retrieve template for.
	 *
	 * @return string The rendered value.
	 */
	protected function get_product_template( $option_name, $shop_page_id ) {
		$template = $this->options->get( $option_name );
		$page     = \get_post( $shop_page_id );

		return $this->replace_vars->replace( $template, $page );
	}

	/**
	 * Returns the id of the set WooCommerce shop page.
	 *
	 * @return int The ID of the set page.
	 */
	protected function get_shop_page_id() {
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return -1;
		}

		return \wc_get_page_id( 'shop' );
	}
}
