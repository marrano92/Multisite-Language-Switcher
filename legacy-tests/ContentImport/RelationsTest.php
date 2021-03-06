<?php

namespace lloc\Msls\ContentImport;


use lloc\Msls\MslsOptions;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class RelationsTest extends \Msls_UnitTestCase {

	/**
	 * @var \lloc\Msls\ContentImport\ImportCoordinates
	 */
	public $import_coordinates;

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Relations::class, $sut );
	}

	/**
	 * @return Relations
	 */
	private function make_instance() {
		return new Relations( $this->import_coordinates->reveal() );
	}

	public function test_merge() {
		$creator   = $this->prophesize( MslsOptions::class );
		$dest_lang = 'de_DE';
		$obj_1     = $this->make_instance();
		$obj_2     = $this->make_instance();

		$obj_1->should_create( $creator->reveal(), $dest_lang, $this->factory->post->create() );
		$obj_1->should_create( $creator->reveal(), $dest_lang, $this->factory->post->create() );
		$obj_1->should_create( $creator->reveal(), $dest_lang, $this->factory->post->create() );

		$this->assertCount( 3, $obj_1->get_data() );

		$obj_2->should_create( $creator->reveal(), $dest_lang, $this->factory->post->create() );
		$obj_2->should_create( $creator->reveal(), $dest_lang, $this->factory->post->create() );
		$obj_2->should_create( $creator->reveal(), $dest_lang, $this->factory->post->create() );

		$this->assertCount( 3, $obj_2->get_data() );

		$obj_1->merge( $obj_2 );

		$this->assertCount( 6, $obj_1->get_data() );
	}

	public function test_create() {
		$source_lang = 'it_IT';
		$dest_lang   = 'de_DE';
		$post_ids    = $this->factory->post->create_many( 3 );
		list( $creator_1, $creator_2, $creator_3 ) = array_map( function ( ObjectProphecy $c ) use ( $source_lang, $dest_lang, $post_ids ) {
			static $i;
			$i = $i === null ? 0 : ++$i;

			$c->save( [ $dest_lang => $post_ids[ $i ] ] )->shouldBeCalled();
			$c->get_arg( Argument::type( 'int' ), $post_ids[ $i ] )->willReturn( $post_ids[ $i ] );

			return $c;
		}, [
			$this->prophesize( MslsOptions::class ),
			$this->prophesize( MslsOptions::class ),
			$this->prophesize( MslsOptions::class ),
		] );
		$inverted = [];
		add_filter( 'msls_content_import_relation_local_to_source_create', function ( $_, $local_id, $source_id ) use ( &$inverted ) {
			$inverted[ ] = $local_id;
			return true;
		}, 10, 3 );

		$obj = $this->make_instance();

		$obj->should_create( $creator_1->reveal(), $dest_lang, $post_ids[0] );
		$obj->should_create( $creator_2->reveal(), $dest_lang, $post_ids[1] );
		$obj->should_create( $creator_3->reveal(), $dest_lang, $post_ids[2] );

		$obj->create();

		$this->assertEquals( $post_ids, $inverted );
	}

	function setUp() {
		parent::setUp();
		$this->import_coordinates = $this->prophesize( ImportCoordinates::class );
	}
}
