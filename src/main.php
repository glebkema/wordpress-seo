<?php
/**
 * Yoast SEO Plugin File.
 *
 * @package Yoast\YoastSEO\Loaders
 */

namespace Yoast\WP\SEO;

use Exception;
use Yoast\WP\SEO\Dependency_Injection\Container_Compiler;
use Yoast\WP\SEO\Generated\Cached_Container;
use Yoast\WP\SEO\Surfaces\Classes_Surface;
use Yoast\WP\SEO\Surfaces\Current_Page_Surface;
use Yoast\WP\SEO\Surfaces\Helpers_Surface;

if ( ! \defined( 'WPSEO_VERSION' ) ) {
	\header( 'Status: 403 Forbidden' );
	\header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * Class Main
 *
 * @property Current_Page_Surface $current_page The Current Page Surface.
 * @property Classes_Surface      $classes      The classes surface.
 * @property Helpers_Surface      $helpers      The helpers surface.
 */
class Main {

	/**
	 * The DI container.
	 *
	 * @var Cached_Container|null
	 */
	private $container;

	/**
	 * Surface classes that provide our external interface.
	 *
	 * @var string[]
	 */
	private $surfaces = [
		'current_page' => Current_Page_Surface::class,
		'classes'      => Classes_Surface::class,
		'helpers'      => Helpers_Surface::class,
	];

	/**
	 * Loads the plugin.
	 *
	 * @throws \Exception If loading fails and YOAST_ENVIRONMENT is development.
	 */
	public function load() {
		if ( $this->container ) {
			return;
		}

		try {
			$this->container = $this->get_container();

			if ( ! $this->container ) {
				return;
			}

			$this->container->get( Loader::class )->load();
		} catch ( \Exception $e ) {
			if ( $this->is_development() ) {
				throw $e;
			}
			// Don't crash the entire site, simply don't load.
			// TODO: Add error notifications here.
		}
	}

	/**
	 * Magic getter for retrieving a property.
	 *
	 * @param string $property The property to retrieve.
	 *
	 * @return string The value of the property.
	 * @throws Exception When the property doesn't exist.
	 */
	public function __get( $property ) {
		if ( isset( $this->{$property} ) ) {
			$this->{$property} = $this->container->get( $this->surfaces[ $property ] );

			return $this->{$property};
		}
		throw new Exception( "Property $property does not exist." );
	}

	/**
	 * Checks if the given property exists as a surface.
	 *
	 * @param string $property The property to retrieve.
	 *
	 * @return bool True when property is set.
	 */
	public function __isset( $property ) {
		return isset( $this->surfaces[ $property ] );
	}

	/**
	 * Loads the DI container.
	 *
	 * @return null|Cached_Container The DI container.
	 *
	 * @throws \Exception If something goes wrong generating the DI container.
	 */
	private function get_container() {
		if ( $this->is_development() && \class_exists( '\Yoast\WP\SEO\Dependency_Injection\Container_Compiler' ) ) {
			// Exception here is unhandled as it will only occur in development.
			Container_Compiler::compile( $this->is_development() );
		}

		if ( \file_exists( __DIR__ . '/generated/container.php' ) ) {
			require_once __DIR__ . '/generated/container.php';

			return new Cached_Container();
		}

		return null;
	}

	/**
	 * Returns whether or not we're in an environment for Yoast development.
	 *
	 * @return bool Whether or not to load in development mode.
	 */
	private function is_development() {
		return \defined( 'YOAST_ENVIRONMENT' ) && \YOAST_ENVIRONMENT === 'development';
	}
}
