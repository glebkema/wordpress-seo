<?php

namespace Yoast\WP\SEO\Tests\Builders;

use Brain\Monkey;
use Mockery;
use Yoast\WP\SEO\Builders\Indexable_System_Page_Builder;
use Yoast\WP\SEO\Helpers\Options_Helper;
use Yoast\WP\SEO\Models\Indexable;
use Yoast\WP\SEO\ORM\ORMWrapper;
use Yoast\WP\SEO\Tests\TestCase;

/**
 * Class Indexable_Author_Test.
 *
 * @group indexables
 * @group builders
 *
 * @coversDefaultClass \Yoast\WP\SEO\Builders\Indexable_Author_Builder
 * @covers ::<!public>
 *
 * @package Yoast\Tests\Builders
 */
class Indexable_System_Page_Builder_Test extends TestCase {

	/**
	 * Tests the formatting of the indexable data.
	 *
	 * @covers ::build
	 */
	public function test_build() {
		$options_mock = Mockery::mock( Options_Helper::class );
		$options_mock->expects( 'get' )->with( 'title-search-wpseo' )->andReturn( 'search_title' );

		$indexable_mock      = Mockery::mock( Indexable::class );
		$indexable_mock->orm = Mockery::mock( ORMWrapper::class );
		$indexable_mock->orm->expects( 'set' )->with( 'object_type', 'system-page' );
		$indexable_mock->orm->expects( 'set' )->with( 'object_sub_type', 'search-result' );
		$indexable_mock->orm->expects( 'set' )->with( 'title', 'search_title' );
		$indexable_mock->orm->expects( 'set' )->with( 'is_robots_noindex', true );

		$builder = new Indexable_System_Page_Builder( $options_mock );
		$builder->build( 'search-result', $indexable_mock );
	}
}
