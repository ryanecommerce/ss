<?php

/**
 * Requires Akismet plugin >= v3.0
 *
 * @package   GeminiLabs\SiteReviews
 * @copyright Copyright (c) 2017, Paul Ryley
 * @license   GPLv3
 * @since     2.11.0
 * -------------------------------------------------------------------------------------------------
 */

namespace GeminiLabs\SiteReviews;

use GeminiLabs\SiteReviews\Commands\SubmitReview;

class Akismet
{
	/**
	 * @return bool
	 */
	public function isSpam( SubmitReview $review )
	{
		if( !$this->isActive() ) {
			return false;
		}
		$submission = [
			'blog' => get_option( 'home' ),
			'blog_charset' => get_option( 'blog_charset' ),
			'blog_lang' => get_locale(),
			'comment_author' => $review->author,
			'comment_author_email' => $review->email,
			'comment_content' => $review->title."\n\n".$review->content,
			'comment_type' => 'review',
			'referrer' => filter_input( INPUT_SERVER, 'HTTP_REFERER' ),
			'user_agent' => filter_input( INPUT_SERVER, 'HTTP_USER_AGENT' ),
			'user_ip' => $review->ipAddress,
			// 'user_role' => 'administrator',
			// 'is_test' => 1,
		];
		foreach( $_SERVER as $key => $value ) {
			if( is_array( $value ) || in_array( $key, ['HTTP_COOKIE', 'HTTP_COOKIE2', 'PHP_AUTH_PW'] ))continue;
			$submission[$key] = $value;
		}
		return $this->check( apply_filters( 'site-reviews/akismet/submission', $submission, $review ));
	}

	/**
	 * @return bool
	 */
	protected function check( array $submission )
	{
		$response = \Akismet::http_post( $this->buildQuery( $submission ), 'comment-check' );
		return apply_filters( 'site-reviews/akismet/is-spam',
			$response[1] == 'true',
			$submission,
			$response
		);
	}

	/**
	 * @return string
	 */
	protected function buildQuery( array $data )
	{
		$query = [];
		foreach( $data as $key => $value ) {
			if( is_array( $value ) || is_object( $value ))continue;
			if( $value === false ) {
				$value = '0';
			}
			$value = trim( $value );
			if( !strlen( $value ))continue;
			$query[] = urlencode( $key ).'='.urlencode( $value );
		}
		return implode( '&', $query );
	}

	/**
	 * @return bool
	 */
	protected function isActive()
	{
		$check = glsr_get_option( 'reviews-form.akismet' ) != 'yes'
			|| !is_callable( ['Akismet', 'get_api_key'] )
			|| !is_callable( ['Akismet', 'http_post'] )
			? false
			: (bool) \Akismet::get_api_key();
		return apply_filters( 'site-reviews/akismet/is-active', $check );
	}
}
