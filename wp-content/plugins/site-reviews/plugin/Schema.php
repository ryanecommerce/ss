<?php

namespace GeminiLabs\SiteReviews;

use DateTime;
use GeminiLabs\SiteReviews\App;
use GeminiLabs\SiteReviews\Helper;
use GeminiLabs\SiteReviews\Rating;
use GeminiLabs\SiteReviews\Schema\UnknownType;
use WP_Post;

class Schema
{
	/**
	 * @var array
	 */
	protected $args;

	/**
	 * @var App
	 */
	protected $app;

	/**
	 * @var array
	 */
	protected $reviews;

	public function __construct( App $app )
	{
		$this->app = $app;
	}

	/**
	 * @return array
	 */
	public function build( array $args = [] )
	{
		$this->args = $args;
		$schema = $this->buildSummary( $args );
		$reviews = [];
		foreach( glsr_resolve( 'Database' )->getReviews( $this->args )->reviews as $review ) {
			// Only include critic reviews that have been directly produced by your site, not reviews from third- party sites or syndicated reviews.
			// @see https://developers.google.com/search/docs/data-types/review
			if( $review->review_type != 'local' )continue;
			$reviews[] = $this->buildReview( $review );
		}
		if( !empty( $reviews )) {
			array_walk( $reviews, function( &$review ) {
				unset( $review['@context'] );
				unset( $review['itemReviewed'] );
			});
			$schema['review'] = $reviews;
		}
		return $schema;
	}

	/**
	 * @param null|array $args
	 * @return array
	 */
	public function buildSummary( $args = null )
	{
		if( is_array( $args )) {
			$this->args = $args;
		}
		$buildSummary = glsr_resolve( 'Helper' )->buildMethodName( $this->getSchemaOptionValue( 'type' ), 'buildSummaryFor' );
		$count = $this->getReviewCount();
		$schema = method_exists( $this, $buildSummary )
			? $this->$buildSummary()
			: $this->buildSummaryForCustom();
		if( !empty( $count )) {
			$schema->aggregateRating(
				$this->getSchemaType( 'AggregateRating' )
					->ratingValue( $this->getRatingValue() )
					->reviewCount( $count )
					->bestRating( Rating::MAX_RATING )
					->worstRating( Rating::MIN_RATING )
			);
		}
		$schema = $schema->toArray();
		$args = wp_parse_args( ['count' => -1], $this->args );
		return apply_filters( sprintf( 'site-reviews/schema/%s', $schema['@type'] ), $schema, $args );
	}

	/**
	 * @return void
	 */
	public function render()
	{
		if( empty( $this->app->schemas ))return;
		printf( '<script type="application/ld+json">%s</script>', json_encode(
			apply_filters( 'site-reviews/schema/all', $this->app->schemas ),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		));
	}

	/**
	 * @return void
	 */
	public function store( array $schema )
	{
		$schemas = $this->app->schemas;
		$schemas[] = $schema;
		$this->app->schemas = array_map( 'unserialize', array_unique( array_map( 'serialize', $schemas )));
	}

	/**
	 * @param object $review
	 * @return array
	 */
	protected function buildReview( $review )
	{
		$schema = $this->getSchemaType( 'Review' )
			->doIf( !in_array( 'title', $this->args['hide'] ), function( $schema ) use( $review ) {
				$schema->name( $review->title );
			})
			->doIf( !in_array( 'excerpt', $this->args['hide'] ), function( $schema ) use( $review ) {
				$schema->reviewBody( $review->content );
			})
			->datePublished(( new DateTime( $review->date )))
			->author( $this->getSchemaType( 'Person' )->name( $review->author ))
			->itemReviewed( $this->getSchemaType()->name( $this->getSchemaOptionValue( 'name' )));
		if( !empty( $review->rating )) {
			$schema->reviewRating(
				$this->getSchemaType( 'Rating' )
					->ratingValue( $review->rating )
					->bestRating( Rating::MAX_RATING )
					->worstRating( Rating::MIN_RATING )
			);
		}
		return apply_filters( 'site-reviews/schema/Review', $schema->toArray(), $review, $this->args );
	}

	/**
	 * @param mixed $schema
	 * @return mixed
	 */
	protected function buildSchemaValues( $schema, array $values = [] )
	{
		foreach( $values as $value ) {
			$option = $this->getSchemaOptionValue( $value );
			if( empty( $option ))continue;
			$schema->$value( $option );
		}
		return $schema;
	}

	/**
	 * @return mixed
	 */
	protected function buildSummaryForCustom()
	{
		return $this->buildSchemaValues( $this->getSchemaType(), [
			'description', 'image', 'name', 'url',
		]);
	}

	/**
	 * @return mixed
	 */
	protected function buildSummaryForLocalBusiness()
	{
		return $this->buildSchemaValues( $this->buildSummaryForCustom(), [
			'address', 'priceRange', 'telephone',
		]);
	}

	/**
	 * @return mixed
	 */
	protected function buildSummaryForProduct()
	{
		$offers = $this->buildSchemaValues( $this->getSchemaType( 'AggregateOffer' ), [
			'highPrice', 'lowPrice', 'priceCurrency',
		]);
		return $this->buildSummaryForCustom()
			->offers( $offers )
			->setProperty( '@id', $this->getSchemaOptionValue( 'url' ));
	}

	/**
	 * @return int|float
	 */
	protected function getRatingValue()
	{
		return glsr_resolve( 'Rating' )->getAverage( $this->getReviews() );
	}

	/**
	 * @return int
	 */
	protected function getReviewCount()
	{
		return count( $this->getReviews() );
	}

	/**
	 * @return array
	 */
	protected function getReviews( $force = false )
	{
		if( !isset( $this->reviews ) || $force ) {
			$args = wp_parse_args( ['count' => -1], $this->args );
			$this->reviews = glsr_resolve( 'Database' )->getReviews( $args )->reviews;
		}
		return $this->reviews;
	}

	/**
	 * @param string $option
	 * @param string $fallback
	 * @return string
	 */
	protected function getSchemaOption( $option, $fallback )
	{
		$option = strtolower( $option );
		if( $schemaOption = trim( (string)get_post_meta( intval( get_the_ID() ), 'schema_'.$option, true ))) {
			return $schemaOption;
		}
		$setting = glsr_resolve( 'Database' )->getOption( 'settings.reviews.schema.'.$option );
		if( is_array( $setting )) {
			return $this->getSchemaOptionDefault( $setting, $fallback );
		}
		return !empty( $setting )
			? $setting
			: $fallback;
	}

	/**
	 * @param string $fallback
	 * @return string
	 */
	protected function getSchemaOptionDefault( array $setting, $fallback )
	{
		$setting = wp_parse_args( $setting, [
			'custom' => '',
			'default' => $fallback,
		]);
		return $setting['default'] != 'custom'
			? $setting['default']
			: $setting['custom'];
	}

	/**
	 * @param string $option
	 * @param string $fallback
	 * @return void|string
	 */
	protected function getSchemaOptionValue( $option, $fallback = 'post' )
	{
		$value = $this->getSchemaOption( $option, $fallback );
		if( $value != $fallback ) {
			return $value;
		}
		if( !is_single() && !is_page() )return;
		$method = glsr_resolve( 'Helper' )->buildMethodName( $option, 'getThing' );
		if( method_exists( $this, $method )) {
			return $this->$method();
		}
	}

	/**
	 * @param null|string $type
	 * @return mixed
	 */
	protected function getSchemaType( $type = null )
	{
		if( !is_string( $type )) {
			$type = $this->getSchemaOption( 'type', 'LocalBusiness' );
		}
		$className = glsr_resolve( 'Helper' )->buildClassName( $type, 'Modules\Schema' );
		return class_exists( $className )
			? new $className()
			: new UnknownType( $type );
	}

	/**
	 * @return string
	 */
	protected function getThingDescription()
	{
		$post = get_post();
		if( !( $post instanceof WP_Post )) {
			return '';
		}
		$text = strip_shortcodes( wp_strip_all_tags( $post->post_excerpt ));
		return wp_trim_words( $text, apply_filters( 'excerpt_length', 55 ));
	}

	/**
	 * @return string
	 */
	protected function getThingImage()
	{
		return (string)get_the_post_thumbnail_url( null, 'large' );
	}

	/**
	 * @return string
	 */
	protected function getThingName()
	{
		return get_the_title();
	}

	/**
	 * @return string
	 */
	protected function getThingUrl()
	{
		return (string)get_the_permalink();
	}
}
