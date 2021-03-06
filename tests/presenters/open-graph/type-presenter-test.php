<?php

namespace Yoast\WP\SEO\Tests\Presenters\Open_Graph;

use Mockery;
use Brain\Monkey;
use Yoast\WP\SEO\Presentations\Indexable_Presentation;
use Yoast\WP\SEO\Presenters\Open_Graph\Type_Presenter;
use Yoast\WP\SEO\Tests\TestCase;

/**
 * Class Type_Presenter_Test
 *
 * @coversDefaultClass \Yoast\WP\SEO\Presenters\Open_Graph\Type_Presenter
 *
 * @group presenters
 * @group open-graph
 * @group type-presenter
 */
class Type_Presenter_Test extends TestCase {
	/**
	 * The indexable presentation.
	 *
	 * @var Indexable_Presentation
	 */
	protected $indexable_presentation;

	/**
	 * The type presenter instance.
	 *
	 * @var Type_Presenter
	 */
	protected $instance;

	/**
	 * Sets up the test class.
	 */
	public function setUp() {
		$this->instance               = new Type_Presenter();
		$this->indexable_presentation = new Indexable_Presentation();

		return parent::setUp();
	}

	/**
	 * Tests whether the presenter returns the correct open graph type.
	 *
	 * @covers ::present
	 */
	public function test_present() {
		$this->indexable_presentation->open_graph_type = 'article';

		$expected = '<meta property="og:type" content="article" />';
		$actual = $this->instance->present( $this->indexable_presentation );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Tests whether the presenter returns an empty string when the open graph type is empty.
	 *
	 * @covers ::present
	 */
	public function test_present_type_is_empty() {
		$this->indexable_presentation->open_graph_type = '';

		$actual = $this->instance->present( $this->indexable_presentation );

		$this->assertEmpty( $actual );
	}

	/**
	 * Tests whether the presenter returns the correct type, when the `wpseo_opengraph_type` filter
	 * is applied.
	 *
	 * @covers ::present
	 * @covers ::filter
	 */
	public function test_present_filter() {
		$this->indexable_presentation->open_graph_type = 'website';

		Monkey\Filters\expectApplied( 'wpseo_opengraph_type' )
			->once()
			->with( 'website', $this->indexable_presentation )
			->andReturn( 'article' );

		$expected = '<meta property="og:type" content="article" />';
		$actual = $this->instance->present( $this->indexable_presentation );

		$this->assertEquals( $expected, $actual );
	}
}
