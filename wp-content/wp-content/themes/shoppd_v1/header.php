<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package shoppd_v1
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<link rel="shortcut icon" sizes="128x128" href="<?php echo get_stylesheet_directory_uri(); ?>/favicon.ico" />
	<script
  src="https://code.jquery.com/jquery-3.3.1.min.js"
  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
  crossorigin="anonymous"></script>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'shoppd_v1' ); ?></a>

	<header id="masthead" class="site-header">

		<!-- <div class="site-branding">
			<?php
			the_custom_logo();
			if ( is_front_page() && is_home() ) :
				?>
				<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
				<?php
			else :
				?>
				<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
				<?php
			endif;
			$shoppd_v1_description = get_bloginfo( 'description', 'display' );
			if ( $shoppd_v1_description || is_customize_preview() ) :
				?>
				<p class="site-description"><?php echo $shoppd_v1_description; /* WPCS: xss ok. */ ?></p>
			<?php endif; ?>
		</div> -->

        <!-- .site-branding -->
        <div class="site-header-sticky">
		<nav id="site-navigation" class="main-navigation">
            <h3 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h3>
						<span class="on_m mobile_top_home"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><i class="icon-home"></i></a></span>
            <?php ap_get_template_part( 'search-form' ); ?>

						<div class="on_p menu_font">
							<ul>
								<li><a href="/question/"><span class="htag">#</span><span class="m_name">Q&A 포럼</span></a></li>
								<li><a href="/news-pick/"><span class="htag">#</span><span class="m_name">뉴스 픽</span></a></li>
								<li><a href="/shoplists/"><span class="htag">#</span><span class="m_name">샵리스트</span></a></li>
								<li><a href="/glossary/"><span class="htag">#</span><span class="m_name">용어 사전</span></a></li>
								<!-- <li><a href="/posts/">포스트</a></li> -->
							</ul>
						</div>

						<div class="on_m mobile_top_menu">

							<div class="menu-overlay"></div>
							<a href="#" class="menu-open"><i class="icon-menu"></i></a>

							<div class="side-menu-wrapper">
								<div class="top_login_mobile_section">
								<?php
									if ( is_user_logged_in() ) {
											dynamic_sidebar( 'sidebar-1' );
										} else {
								    	echo '<span class="p_left"><a href="/login/">로그인하세요.</a></span>';
										}
								?>
								<span class="p_right"><a href="#" class="menu-close">×</a></span>
							</div>
							<div class="area_favorite">
								<ul class="favorite_list">
									<li class="fl_item"><a class="favorite_item" href="/question/"><span class="htag">#</span><span class="m_name">Q&A 포럼</span></a></li>
									<li class="fl_item"><a class="favorite_item" href="/news-pick/"><span class="htag">#</span><span class="m_name">뉴스 픽</span></a></li>
									<li class="fl_item"><a class="favorite_item" href="/shoplists/"><span class="htag">#</span><span class="m_name">샵리스트</span></a></li>
							  	<li class="fl_item"><a class="favorite_item" href="/glossary/"><span class="htag">#</span><span class="m_name">용어 사전</span></a></li>
									<li class="fl_item"><a class="favorite_item" href=""></a></li>
									<li class="fl_item"><a class="favorite_item" href=""></a></li>
									<li class="fl_item"><a class="favorite_item" href=""></a></li>
									<li class="fl_item"><a class="favorite_item" href=""></a></li>
								</ul>
							</div>
							</div>

						</div>
		</nav><!-- #site-navigation -->

        </div>
	</header><!-- #masthead -->

	<div id="content" class="site-content">
