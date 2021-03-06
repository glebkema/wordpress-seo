<?php
/**
 * Yoast extension of the Model class.
 *
 * @package Yoast\YoastSEO\ORM\Repositories
 */

namespace Yoast\WP\SEO\Repositories;

use Cassandra\Index;
use Psr\Log\LoggerInterface;
use Yoast\WP\SEO\Builders\Indexable_Builder;
use Yoast\WP\SEO\Builders\Indexable_Hierarchy_Builder;
use Yoast\WP\SEO\Helpers\Current_Page_Helper;
use Yoast\WP\SEO\Loggers\Logger;
use Yoast\WP\SEO\Models\Indexable;
use Yoast\WP\SEO\ORM\ORMWrapper;
use Yoast\WP\SEO\ORM\Yoast_Model;

/**
 * Class Indexable_Repository
 *
 * @package Yoast\WP\SEO\ORM\Repositories
 */
class Indexable_Repository {

	/**
	 * The indexable builder.
	 *
	 * @var Indexable_Builder
	 */
	private $builder;

	/**
	 * Represents the hierarchy repository.
	 *
	 * @var Indexable_Hierarchy_Repository
	 */
	protected $hierarchy_repository;

	/**
	 * The current page helper.
	 *
	 * @var Current_Page_Helper
	 */
	protected $current_page;

	/**
	 * The logger object.
	 *
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * Returns the instance of this class constructed through the ORM Wrapper.
	 *
	 * @param Indexable_Builder              $builder              The indexable builder.
	 * @param Current_Page_Helper            $current_page         The current post helper.
	 * @param Logger                         $logger               The logger.
	 * @param Indexable_Hierarchy_Repository $hierarchy_repository The hierarchy repository.
	 */
	public function __construct(
		Indexable_Builder $builder,
		Current_Page_Helper $current_page,
		Logger $logger,
		Indexable_Hierarchy_Repository $hierarchy_repository

	) {
		$this->builder              = $builder;
		$this->current_page         = $current_page;
		$this->logger               = $logger;
		$this->hierarchy_repository = $hierarchy_repository;
	}

	/**
	 * Starts a query for this repository.
	 *
	 * @return ORMWrapper
	 */
	public function query() {
		return Yoast_Model::of_type( 'Indexable' );
	}

	/**
	 * Attempts to find the indexable for the current WordPress page. Returns false if no indexable could be found.
	 * This may be the result of the indexable not existing or of being unable to determine what type of page the
	 * current page is.
	 *
	 * @return bool|Indexable The indexable, false if none could be found.
	 */
	public function for_current_page() {
		switch ( true ) {
			case $this->current_page->is_simple_page():
				return $this->find_by_id_and_type( $this->current_page->get_simple_page_id(), 'post' );
			case $this->current_page->is_home_static_page():
				return $this->find_by_id_and_type( $this->current_page->get_front_page_id(), 'post' );
			case $this->current_page->is_home_posts_page():
				return $this->find_for_home_page();
			case $this->current_page->is_term_archive():
				return $this->find_by_id_and_type( $this->current_page->get_term_id(), 'term' );
			case $this->current_page->is_date_archive():
				return $this->find_for_date_archive();
			case $this->current_page->is_search_result():
				return $this->find_for_system_page( 'search-result' );
			case $this->current_page->is_post_type_archive():
				return $this->find_for_post_type_archive( $this->current_page->get_queried_post_type() );
			case $this->current_page->is_author_archive():
				return $this->find_by_id_and_type( $this->current_page->get_author_id(), 'user' );
			case $this->current_page->is_404():
				return $this->find_for_system_page( '404' );
		}

		return $this->query()->create( [ 'object_type' => 'unknown' ] );
	}

	/**
	 * Retrieves an indexable by its permalink.
	 *
	 * @param string $permalink The indexable permalink.
	 *
	 * @return bool|Indexable The indexable, false if none could be found.
	 */
	public function find_by_permalink( $permalink ) {
		$permalink      = \trailingslashit( $permalink );
		$permalink_hash = \strlen( $permalink ) . ':' . \md5( $permalink );

		// Find by both permalink_hash and permalink, permalink_hash is indexed so will be used first by the DB to optimize the query.
		return $this->query()
					->where( 'permalink_hash', $permalink_hash )
					->where( 'permalink', $permalink )
					->find_one();
	}

	/**
	 * Retrieves all the indexable instances of a certain object type.
	 *
	 * @param string $object_type The object type.
	 *
	 * @return Indexable[] The array with all the indexable instances of a certain object type.
	 */
	public function find_all_with_type( $object_type ) {
		/**
		 * The array with all the indexable instances of a certain object type.
		 *
		 * @var Indexable[] $indexables
		 */
		$indexables = $this
			->query()
			->where( 'object_type', $object_type )
			->find_many();

		return $indexables;
	}

	/**
	 * Retrieves all the indexable instances of a certain object subtype.
	 *
	 * @param string $object_type     The object type.
	 * @param string $object_sub_type The object subtype.
	 *
	 * @return Indexable[] The array with all the indexable instances of a certain object subtype.
	 */
	public function find_all_with_type_and_sub_type( $object_type, $object_sub_type ) {
		/**
		 * The array with all the indexable instances of a certain object type and subtype.
		 *
		 * @var Indexable[] $indexables
		 */
		$indexables = $this
			->query()
			->where( 'object_type', $object_type )
			->where( 'object_sub_type', $object_sub_type )
			->find_many();

		return $indexables;
	}

	/**
	 * Retrieves the homepage indexable.
	 *
	 * @param bool $auto_create Optional. Create the indexable if it does not exist.
	 *
	 * @return bool|Indexable Instance of indexable.
	 */
	public function find_for_home_page( $auto_create = true ) {
		/**
		 * Indexable instance.
		 *
		 * @var Indexable $indexable
		 */
		$indexable = $this->query()->where( 'object_type', 'home-page' )->find_one();

		if ( $auto_create && ! $indexable ) {
			$indexable = $this->builder->build_for_home_page();
		}

		return $indexable;
	}

	/**
	 * Retrieves the date archive indexable.
	 *
	 * @param bool $auto_create Optional. Create the indexable if it does not exist.
	 *
	 * @return bool|Indexable Instance of indexable.
	 */
	public function find_for_date_archive( $auto_create = true ) {
		/**
		 * Indexable instance.
		 *
		 * @var Indexable $indexable
		 */
		$indexable = $this->query()->where( 'object_type', 'date-archive' )->find_one();

		if ( $auto_create && ! $indexable ) {
			$indexable = $this->builder->build_for_date_archive();
		}

		return $indexable;
	}

	/**
	 * Retrieves an indexable for a post type archive.
	 *
	 * @param string $post_type   The post type.
	 * @param bool   $auto_create Optional. Create the indexable if it does not exist.
	 *
	 * @return bool|Indexable The indexable, false if none could be found.
	 */
	public function find_for_post_type_archive( $post_type, $auto_create = true ) {
		/**
		 * Indexable instance.
		 *
		 * @var Indexable $indexable
		 */
		$indexable = $this->query()
						  ->where( 'object_type', 'post-type-archive' )
						  ->where( 'object_sub_type', $post_type )
						  ->find_one();

		if ( $auto_create && ! $indexable ) {
			$indexable = $this->builder->build_for_post_type_archive( $post_type );
		}

		return $indexable;
	}

	/**
	 * Retrieves the indexable for a system page.
	 *
	 * @param string $object_sub_type The type of system page.
	 * @param bool   $auto_create     Optional. Create the indexable if it does not exist.
	 *
	 * @return bool|Indexable Instance of indexable.
	 */
	public function find_for_system_page( $object_sub_type, $auto_create = true ) {
		/**
		 * Indexable instance.
		 *
		 * @var Indexable $indexable
		 */
		$indexable = $this->query()
						  ->where( 'object_type', 'system-page' )
						  ->where( 'object_sub_type', $object_sub_type )
						  ->find_one();

		if ( $auto_create && ! $indexable ) {
			$indexable = $this->builder->build_for_system_page( $object_sub_type );
		}

		return $indexable;
	}

	/**
	 * Retrieves an indexable by its ID and type.
	 *
	 * @param int    $object_id   The indexable object ID.
	 * @param string $object_type The indexable object type.
	 * @param bool   $auto_create Optional. Create the indexable if it does not exist.
	 *
	 * @return bool|Indexable Instance of indexable.
	 */
	public function find_by_id_and_type( $object_id, $object_type, $auto_create = true ) {
		$indexable = $this->query()
						  ->where( 'object_id', $object_id )
						  ->where( 'object_type', $object_type )
						  ->find_one();

		if ( $auto_create && ! $indexable ) {
			$indexable = $this->builder->build_for_id_and_type( $object_id, $object_type );
		}

		return $indexable;
	}

	/**
	 * Retrieves multiple indexables at once by their IDs and type.
	 *
	 * @param int[]  $object_ids  The array of indexable object IDs.
	 * @param string $object_type The indexable object type.
	 * @param bool   $auto_create Optional. Create the indexable if it does not exist.
	 *
	 * @return Indexable[] An array of indexables.
	 */
	public function find_by_multiple_ids_and_type( $object_ids, $object_type, $auto_create = true ) {
		/**
		 * Represents an array of indexable objects.
		 *
		 * @var Indexable[] $indexables
		 */
		$indexables = $this->query()
						   ->where_in( 'object_id', $object_ids )
						   ->where( 'object_type', $object_type )
						   ->find_many();

		if ( $auto_create ) {
			$indexables_available = [];
			foreach ( $indexables as $indexable ) {
				$indexables_available[] = $indexable->object_id;
			}

			$indexables_to_create = \array_diff( $object_ids, $indexables_available );

			foreach ( $indexables_to_create as $indexable_to_create ) {
				$indexable = $this->builder->build_for_id_and_type( $indexable_to_create, $object_type );
				$indexable->save();

				$indexables[] = $indexable;
			}
		}

		return $indexables;
	}

	/**
	 * Returns all ancestors of a given indexable.
	 *
	 * @param Indexable $indexable The indexable to find the ancestors of.
	 *
	 * @return Indexable[] All ancestors of the given indexable.
	 */
	public function get_ancestors( Indexable $indexable ) {
		$ancestors = $this->hierarchy_repository->find_ancestors( $indexable );

		if ( empty( $ancestors ) ) {
			return [];
		}

		$indexables = [];
		foreach ( $ancestors as $ancestor ) {
			$indexables[] = $ancestor->ancestor_id;
		}

		if ( $indexables[0] === 0 && \count( $indexables ) === 1 ) {
			return [];
		}

		return $this->query()
			->where_in( 'id', $indexables )
			->order_by_expr( 'FIELD(id,' . \implode( ',', $indexables ) . ')' )
			->find_many();
	}
}
