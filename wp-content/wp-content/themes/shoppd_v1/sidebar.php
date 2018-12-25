<?php
/**
 * The sidebar containing the main widget area
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package shoppd_v1
 */

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
	return;
}
?>

<aside id="secondary" class="widget-area">
	<?php dynamic_sidebar( 'sidebar-1' ); ?>
	<?php
		if ( !is_user_logged_in() ) {
				echo '<p class="login_notice">로그인 후 이용해주세요</p>';
			}
	?>
</aside><!-- #secondary -->
