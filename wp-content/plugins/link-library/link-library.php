<?php
/*
Plugin Name: Link Library
Plugin URI: http://wordpress.org/extend/plugins/link-library/
Description: Display links on pages with a variety of options
Version: 6.1.23
Author: Yannick Lefebvre
Author URI: http://ylefebvre.ca/
Text Domain: link-library

A plugin for the blogging MySQL/PHP-based WordPress.
Copyright 2018 Yannick Lefebvre

Translations:
French Translation courtesy of Luc Capronnier
Danish Translation courtesy of GeorgWP (http://wordpress.blogos.dk)
Italian Translation courtesy of Gianni Diurno
Serbian Translation courtesy of Ogi Djuraskovic (firstsiteguide.com)

This program is free software; you can redistribute it and/or
modify it under the terms of the GNUs General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

You can also view a copy of the HTML version of the GNU General Public
License at http://www.gnu.org/copyleft/gpl.html

I, Yannick Lefebvre, can be contacted via e-mail at ylefebvre@gmail.com
*/

update_option( 'link_manager_enabled', 0 );

require_once(ABSPATH . '/wp-admin/includes/bookmark.php');
require_once plugin_dir_path( __FILE__ ) . 'link-library-defaults.php';
require_once plugin_dir_path( __FILE__ ) . 'rssfeed.php';
//require_once plugin_dir_path( __FILE__ ) . 'blocks/link-library-main.php';

global $my_link_library_plugin;
global $my_link_library_plugin_admin;

/* if ( !get_option( 'link_manager_enabled' ) ) {
    add_filter( 'pre_option_link_manager_enabled', '__return_true' );
} */

function link_library_tweak_plugins_http_filter( $response, $r, $url ) {
	if ( stristr( $url, 'api.wordpress.org/plugins/update-check/1.1' ) ) {
		$wpapi_response = json_decode( $response['body'] );
		$wpapi_response->plugins = link_library_modify_http_response( $wpapi_response->plugins );
		$response['body'] = json_encode( $wpapi_response );
	}

	return $response;
}

function link_library_get_terms_filter_only_publish( $terms, $taxonomies, $args ) {
	global $wpdb;
	global $hide_if_empty_filter;

	$taxonomy = $taxonomies[0];
	if ( ! is_array( $terms ) && count( $terms ) < 1 ) {
		return $terms;
	}

	$filtered_terms = array();

	foreach ( $terms as $term ) {
		$result = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts p JOIN $wpdb->term_relationships rl ON p.ID = rl.object_id WHERE rl.term_taxonomy_id = $term->term_id AND p.post_status = 'publish' LIMIT 1" );

		if ( intval( $result ) > 0 || ( !$hide_if_empty_filter && 0 == intval( $result ) ) ) {
			$filtered_terms[] = $term;
		}
	}
	return $filtered_terms;
}

function link_library_get_terms_filter_publish_pending( $terms, $taxonomies, $args ) {
	global $wpdb;
	global $hide_if_empty_filter;

	$taxonomy = $taxonomies[0];
	if ( ! is_array( $terms ) && count( $terms ) < 1 ) {
		return $terms;
	}

	$filtered_terms = array();

	foreach ( $terms as $term ) {
		$result = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts p JOIN $wpdb->term_relationships rl ON p.ID = rl.object_id WHERE rl.term_taxonomy_id = $term->term_id AND ( p.post_status = 'publish' or p.post_status = 'pending' ) LIMIT 1" );
		if ( intval( $result ) > 0 || ( !$hide_if_empty_filter && 0 == intval( $result ) ) ) {
			$filtered_terms[] = $term;
		}
	}
	return $filtered_terms;
}

function link_library_get_terms_filter_publish_draft( $terms, $taxonomies, $args ) {
	global $wpdb;
	global $hide_if_empty_filter;

	$taxonomy = $taxonomies[0];
	if ( ! is_array( $terms ) && count( $terms ) < 1 ) {
		return $terms;
	}

	$filtered_terms = array();

	foreach ( $terms as $term ) {
		$result = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts p JOIN $wpdb->term_relationships rl ON p.ID = rl.object_id WHERE rl.term_taxonomy_id = $term->term_id AND ( p.post_status = 'publish' or p.post_status = 'draft' ) LIMIT 1" );
		if ( intval( $result ) > 0 || ( !$hide_if_empty_filter && 0 == intval( $result ) ) ) {
			$filtered_terms[] = $term;
		}
	}
	return $filtered_terms;
}

function link_library_get_terms_filter_publish_draft_pending( $terms, $taxonomies, $args ) {
	global $wpdb;
	global $hide_if_empty_filter;

	$taxonomy = $taxonomies[0];
	if ( ! is_array( $terms ) && count( $terms ) < 1 ) {
		return $terms;
	}

	$filtered_terms = array();

	foreach ( $terms as $term ) {
		$result = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts p JOIN $wpdb->term_relationships rl ON p.ID = rl.object_id WHERE rl.term_taxonomy_id = $term->term_id AND ( p.post_status = 'publish' or p.post_status = 'draft' or p.post_status = 'pending' ) LIMIT 1" );
		if ( intval( $result ) > 0 || ( !$hide_if_empty_filter && 0 == intval( $result ) ) ) {
			$filtered_terms[] = $term;
		}
	}
	return $filtered_terms;
}

function link_library_strposX( $haystack, $needle, $number ) {
	if( $number == '1' ){
		return strpos($haystack, $needle);
	} elseif( $number > '1' ){
		return strpos( $haystack, $needle, link_library_strposX( $haystack, $needle, $number - 1 ) + strlen( $needle ) );
	} else {
		return error_log( 'Error: Value for parameter $number is out of range' );
	}
}

function link_library_modify_http_response( $plugins_response ) {

	foreach ( $plugins_response as $response_key => $plugin_response ) {
		if ( plugin_basename(__FILE__) == $plugin_response->plugin ) {
			if ( 3 <= substr_count( $plugin_response->new_version, '.' ) ) {
				$plugin_info = get_plugin_data( __FILE__ );
				$period_position = link_library_strposX( $plugin_info['Version'], '.', 3 );
				if ( false !== $period_position ) {
					$current_version = substr( $plugin_info['Version'], 0, $period_position );
				} else {
					$current_version = $plugin_info['Version'];
				}

				$period_position2 = link_library_strposX( $plugin_response->new_version, '.', 3 );
				if ( false !== $period_position ) {
					$new_version = substr( $plugin_response->new_version, 0, $period_position2 );
				} else {
					$new_version = $plugin_response->new_version;
				}

				$version_diff = version_compare( $current_version, $new_version );

				if ( -1 < $version_diff ) {
					unset( $plugins_response->$response_key );
				}
			}
		}
	}

	return $plugins_response;
}

function ll_expand_posts_search( $search, $query ) {
	global $wpdb;

	if ( $query->query_vars['post_type'] == 'link_library_links' && !empty( $query->query['s'] ) ) {
		$sql    = "
            or exists (
                select * from {$wpdb->postmeta} where post_id={$wpdb->posts}.ID
                and meta_key in ( 'link_description', 'link_notes', 'link_textfield', 'link_url' )
                and meta_value like %s
            )
        ";
		$like   = '%' . $wpdb->esc_like($query->query['s']) . '%';
		$search = preg_replace("#\({$wpdb->posts}.post_title LIKE [^)]+\)\K#",
			$wpdb->prepare($sql, $like), $search);
	}

	return $search;
}

/*********************************** Link Library Class *****************************************************************************/
class link_library_plugin {

	//constructor of class, PHP4 compatible construction for backward compatibility
	function __construct() {

		// Functions to be called when plugin is activated and deactivated
		register_activation_hook( __FILE__, array( $this, 'll_install' ) );
		register_deactivation_hook( __FILE__, array( $this, 'll_uninstall' ) );

		add_action( 'init', array( $this, 'll_init' ) );
		add_action( 'wp_loaded', array( $this, 'll_update_60' ) );

		$newoptions = get_option( 'LinkLibraryPP1', '' );

		if ( empty( $newoptions ) ) {
            global $my_link_library_plugin_admin;

            if ( empty( $my_link_library_plugin_admin ) ) {
                require plugin_dir_path( __FILE__ ) . 'link-library-admin.php';
                $my_link_library_plugin_admin = new link_library_plugin_admin();
            }

			ll_reset_options( 1, 'list', 'return_and_set' );
			ll_reset_gen_settings( 'return_and_set' );
		}
        
		// Add short codes
        add_shortcode( 'link-library', array( $this, 'link_library_func' ) );
		add_shortcode( 'link-library-cats', array( $this, 'link_library_cats_func' ) );
		add_shortcode( 'cats-link-library', array( $this, 'link_library_cats_func' ) );
		add_shortcode( 'link-library-search', array( $this, 'link_library_search_func' ) );
		add_shortcode( 'search-link-library', array( $this, 'link_library_search_func' ) );
		add_shortcode( 'link-library-addlink', array( $this, 'link_library_addlink_func' ) );
		add_shortcode( 'addlink-link-library', array( $this, 'link_library_addlink_func' ) );
		add_shortcode( 'link-library-addlinkcustommsg', array( $this, 'link_library_addlink_func' ) );
		add_shortcode( 'addlinkcustommsg-link-library', array( $this, 'link_library_addlink_func' ) );
		add_shortcode( 'link-library-count', array( $this, 'link_library_count_func' ) );
		add_shortcode( 'link-library-filters', array( $this, 'link_library_filters' ) );

        // Function to determine if Link Library is used on a page before printing headers
        // the_posts gets triggered before wp_head
        add_filter( 'the_posts', array( $this, 'conditionally_add_scripts_and_styles' ) );

		// Function to print information in page header when plugin present
		add_action( 'wp_head', array( $this, 'll_rss_link' ) );

		add_filter( 'wp_title', array( $this, 'll_title_creator' ) );

		// Re-write rules filters to allow for custom permalinks
		add_filter( 'rewrite_rules_array', array( $this, 'll_insertMyRewriteRules' ) );
		add_filter( 'query_vars', array( $this, 'll_insertMyRewriteQueryVars' ) );

        add_action( 'template_redirect', array( $this, 'll_template_redirect' ) );
		add_filter( 'template_include', array( $this, 'll_template_include' ) );
        add_action( 'wp_ajax_link_library_tracker', array( $this, 'link_library_ajax_tracker' ) );
        add_action( 'wp_ajax_nopriv_link_library_tracker', array( $this, 'link_library_ajax_tracker' ) );
        add_action( 'wp_ajax_link_library_ajax_update', array( $this, 'link_library_func') );
        add_action( 'wp_ajax_nopriv_link_library_ajax_update', array( $this, 'link_library_func') );
        add_action( 'wp_ajax_link_library_generate_image', array( $this, 'link_library_generate_image') );
        add_action( 'wp_ajax_nopriv_link_library_generate_image', array( $this, 'link_library_generate_image') );
		add_action( 'wp_ajax_link_library_popup_content', array( $this, 'll_popup_content') );
		add_action( 'wp_ajax_nopriv_link_library_popup_content', array( $this, 'll_popup_content') );

        add_action( 'wp_enqueue_scripts', array( $this, 'll_register_script' ) );

		// Load text domain for translation of admin pages and text strings
		load_plugin_textdomain( 'link-library', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		add_filter( 'kses_allowed_protocols', array( $this, 'll_add_protocols' ) );

		add_filter( 'wp_feed_cache_transient_lifetime' , array( $this, 'feed_cache_filter_handler' ) );

		add_filter( 'post_type_link', array( $this, 'permalink_structure' ), 10, 4 );

		add_action('auth_redirect', array( $this, 'add_pending_count_filter') ); // modify esc_attr on auth_redirect
		add_action('admin_menu', array( $this, 'esc_attr_restore' ) ); // restore on admin_menu (very soon)
	}

	function add_pending_count_filter() {
		add_filter('attribute_escape', array( $this, 'remove_esc_attr_and_count' ), 20, 2);
	}

	function esc_attr_restore() {
		remove_filter('attribute_escape', array( $this, 'remove_esc_attr_and_count' ), 20, 2);
	}

	function remove_esc_attr_and_count( $safe_text = '', $text = '' ) {
		if ( substr_count($text, '%%PENDING_COUNT%%') ) {
			$text = trim( str_replace('%%PENDING_COUNT%%', '', $text) );
			// run only once!
			remove_filter('attribute_escape', 'remove_esc_attr_and_count', 20, 2);
			$safe_text = esc_attr($text);
			// remember to set the right cpt name below
			$linkmoderatecount = 0;

			$args = array(
				'numberposts'   => -1,
				'post_type'     => 'link_library_links',
				'post_status'   => array( 'pending' )
			);
			$linkmoderatecount = count( get_posts( $args ) );
			if ( $linkmoderatecount > 0 ) {
				// we have pending, add the count
				$text = esc_attr($text) . '<span class="awaiting-mod count-' . $linkmoderatecount . '"><span class="pending-count">' . $linkmoderatecount . '</span></span>';
				return $text;
			}
		}
		return $safe_text;
	}

	function feed_cache_filter_handler( $seconds ) {
		$genoptions = get_option( 'LinkLibraryGeneral' );
		$genoptions = wp_parse_args( $genoptions, ll_reset_gen_settings( 'return' ) );

		return $genoptions['rsscachedelay'];
	}

	function ll_init() {
		$genoptions = get_option( 'LinkLibraryGeneral' );
		$genoptions = wp_parse_args( $genoptions, ll_reset_gen_settings( 'return' ) );

		$post_type_args = array(
			'labels' => array(
				'name' => 'Link Library',
				'singular_name' => 'Link',
				'add_new' => 'Add New',
				'add_new_item' => 'Add New Link',
				'edit' => 'Edit',
				'edit_item' => 'Edit Link',
				'new_item' => 'New Link',
				'view' => 'View',
				'view_item' => 'View Link',
				'search_items' => 'Search Links',
				'not_found' => 'No Links found',
				'not_found_in_trash' =>
					'No Links found in Trash',
				'parent' => 'Parent Link',
				'all_items' => 'All Links',
				'menu_name' => _x('Link Library %%PENDING_COUNT%%', 'Link Library', 'link-library'),
			),
			'show_in_nav_menu' => true,
			'show_ui' => true,
			'exclude_from_search' => !$genoptions['exclude_from_search'],
			'publicly_queryable' => $genoptions['publicly_queryable'],
			'menu_position' => 10,
			'supports' =>
				array( 'title', 'editor', 'comments' ),
			'taxonomies' => array( 'link_library_category' ),
			'menu_icon' =>
				plugins_url( 'icons/link-icon.png', __FILE__ ),
			'has_archive' => false,
			'rewrite' => array( 'slug' => $genoptions['cptslug'] . '/%link_library_category%' )
		);

		if ( $genoptions['exclude_from_search'] && $genoptions['publicly_queryable'] ) {
			unset( $post_type_args['exclude_from_search'] );
			unset( $post_type_args['publicly_queryable'] );
			$post_type_args['public'] = true;
		}

        register_post_type( 'link_library_links', $post_type_args );

        register_taxonomy(
            'link_library_category',
            'link_library_links',
            array(
                'labels' => array(
                    'name' => 'Categories',
                    'add_new_item' => 'Add New Link Library Category',
                    'new_item_name' => 'New Link Library Category'
                ),
                'show_ui' => true,
                'show_tagcloud' => false,
                'hierarchical' => true
            )
        );

		register_taxonomy(
			'link_library_tags',
			'link_library_links',
			array(
				'hierarchical' => false,
				'labels' => array( 'name' => 'Tags',
                    'add_new_item' => 'Add New Link Library Tag',
                    'new_item_name' => 'New Link Library Tag' ),
				'show_ui' => true,
				'rewrite' => false,
			)
		);

		add_feed( 'linklibraryfeed', 'link_library_generate_rss_feed' );
	}
	
	function ll_update_60() {
	
		$link_library_60_update = get_option( 'LinkLibrary60Update' );
		$genoptions = get_option( 'LinkLibraryGeneral' );
			
		if ( isset( $_POST['ll60reupdate'] ) ) {
			global $wpdb;

			$wpdb->get_results ( 'DELETE a,b,c
    								FROM wp_posts a
    								LEFT JOIN wp_term_relationships b
        								ON (a.ID = b.object_id)
    								LEFT JOIN wp_postmeta c
        								ON (a.ID = c.post_id)
    								WHERE a.post_type = \'link_library_links\';' );

			$link_category_terms = get_terms( 'link_library_category', array( 'fields' => 'ids', 'hide_empty' => false ) );
			foreach ( $link_category_terms as $value ) {
				wp_delete_term( $value, 'link_library_category' );
			}

			require plugin_dir_path( __FILE__ ) . 'link-library-update-60.php';
			link_library_60_update( $this );
		} elseif ( isset( $_GET['continue60update'] ) ) {
			require plugin_dir_path( __FILE__ ) . 'link-library-update-60.php';
			link_library_60_update( $this, true );
		} else {
			if ( ( false == $link_library_60_update && !empty( $genoptions ) ) ) {
				require plugin_dir_path( __FILE__ ) . 'link-library-update-60.php';
				link_library_60_update( $this );
			}	
		}		
	}

	function permalink_structure( $post_link, $post, $leavename, $sample ) {
		if ( !empty( $post_link ) && false !== strpos( $post_link, '%link_library_category%' ) ) {
			$link_cat_type_term = get_the_terms( $post->ID, 'link_library_category' );
			if ( !empty( $link_cat_type_term ) ) {
				$post_link = str_replace( '%link_library_category%', array_pop( $link_cat_type_term )->slug, $post_link );
			}
		}
		return $post_link;
	}

    /************************** Link Library Installation Function **************************/
    function ll_install() {
        global $wpdb;

        if ( function_exists( 'is_multisite' ) && is_multisite() ) {
            if ( isset( $_GET['networkwide'] ) && ( $_GET['networkwide'] == 1 ) ) {
                $originalblog = $wpdb->blogid;

                $bloglist = $wpdb->get_col( 'SELECT blog_id FROM ' . $wpdb->blogs );
                foreach ( $bloglist as $blog ) {
                    switch_to_blog( $blog );
                    $this->create_table_and_settings();
                }
                switch_to_blog( $originalblog );
                return;
            }
        }
        $this->create_table_and_settings();
    }

    function new_network_site( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
        global $wpdb;

        if ( ! function_exists( 'is_plugin_active_for_network' ) )
            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

        if ( is_plugin_active_for_network( 'link-library/link-library.php' ) ) {
            $originalblog = $wpdb->blogid;
            switch_to_blog( $blog_id );
            $this->create_table_and_settings();
            switch_to_blog( $originalblog );
        }
    }

    function create_table_and_settings() {
        global $wpdb;

        $genoptions = get_option( 'LinkLibraryGeneral' );

        if ( !empty( $genoptions ) ) {
            if ( empty( $genoptions['schemaversion'] ) || floatval( $genoptions['schemaversion'] ) < 3.5 ) {
                $genoptions['schemaversion'] = '3.5';
                update_option( 'LinkLibraryGeneral', $genoptions );
            } elseif ( floatval( $genoptions['schemaversion'] ) < '4.6' ) {
                $genoptions['schemaversion'] = '4.6';
                update_option( 'LinkLibraryGeneral', $genoptions );
            } elseif ( floatval( $genoptions['schemaversion'] ) < '4.7' ) {
                $genoptions['schemaversion'] = '4.7';
                update_option( 'LinkLibraryGeneral', $genoptions );
            } elseif ( floatval( $genoptions['schemaversion'] ) < '4.9' ) {
                $genoptions['schemaversion'] = '4.9';
                update_option( 'LinkLibraryGeneral', $genoptions );
            }

            for ( $i = 1; $i <= $genoptions['numberstylesets']; $i++ ) {
                $settingsname = 'LinkLibraryPP' . $i;
                $options = get_option( $settingsname );

                if ( !empty( $options ) ) {
                    if ( empty( $options['showname'] ) ) {
                        $options['showname'] = true;
                    }

                    if ( isset( $options['show_image_and_name'] ) && $options['show_image_and_name'] == true ) {
                        $options['showname'] = true;
                        $options['show_images'] = true;
                    }

                    if ( empty( $options['sourcename'] ) ) {
                        $options['sourcename'] = 'primary';
                    }

                    if ( empty( $options['sourceimage'] ) ) {
                        $options['sourceimage'] = 'primary';
                    }

                    if ( empty( $options['dragndroporder'] ) ) {
                        if ( $options['imagepos'] == 'beforename' ) {
                            $options['dragndroporder'] = '1,2,3,4,5,6,7,8,9,10,11,12';
                        } elseif ( $options['imagepos'] == 'aftername' ) {
                            $options['dragndroporder'] = '2,1,3,4,5,6,7,8,9,10,11,12';
                        } elseif ( $options['imagepos'] == 'afterrssicons' ) {
                            $options['dragndroporder'] = '2,3,4,5,6,1,7,8,9,10,11,12';
                        }
                    } else if ( !empty( $options['dragndroporder'] ) ) {
                        $elementarray = explode( ',', $options['dragndroporder'] );

                        $allelements = array( '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12' );
                        foreach ( $allelements as $element ) {
                            if ( !in_array( $element, $elementarray ) ) {
                                $elementarray[] = $element;
                                $options['dragndroporder'] = implode( ',', $elementarray );
                            }
                        }
                    }

                    if ( $options['flatlist'] === true ) {
                        $options['flatlist'] = 'unordered';
                    } elseif ( $options['flatlist'] === false ) {
                        $options['flatlist'] = 'table';
                    }
                }

                update_option( $settingsname, $options );
            }
        } else {
			update_option( 'LinkLibrary60Update', true );
        }

		$genoptions['schemaversion'] = '5.0';
		update_option( 'LinkLibraryGeneral', $genoptions );
    }

    function remove_querystring_var( $url, $key ) {

        $keypos = strpos( $url, $key );
        if ( $keypos ) {
            $ampersandpos = strpos( $url, '&', $keypos );
            $newurl = substr( $url, 0, $keypos - 1 );

            if ( $ampersandpos ) {
                $newurl .= substr($url, $ampersandpos);
            }
        } else {
            $newurl = $url;
        }

        return $newurl;
    }

    /************************** Link Library Uninstall Function **************************/
    function ll_uninstall() {
        $genoptions = get_option( 'LinkLibraryGeneral' );

        if ( !empty( $genoptions ) ) {
            if ( isset( $genoptions['stylesheet'] ) && isset( $genoptions['fullstylesheet'] ) && !empty( $genoptions['stylesheet'] ) && empty( $genoptions['fullstylesheet'] ) ) {
                $stylesheetlocation = plugins_url( $genoptions['stylesheet'], __FILE__ );
                if ( file_exists( $stylesheetlocation ) )
                    $genoptions['fullstylesheet'] = file_get_contents( $stylesheetlocation );

                update_option( 'LinkLibraryGeneral', $genoptions );
            }
        }
    }

    function ll_register_script() {
        wp_register_script( 'form-validator', plugins_url( '/form-validator/jquery.form-validator.min.js' , __FILE__ ), array( 'jquery' ), '1.0.0', true );
    }
    
    function db_prefix() {
		global $wpdb;
		if ( method_exists( $wpdb, 'get_blog_prefix' ) ) {
            return $wpdb->get_blog_prefix();
        } else {
            return $wpdb->prefix;
        }
	}

	function ll_add_protocols( $protocols ) {
		$genoptions = get_option( 'LinkLibraryGeneral' );

		if ( isset( $genoptions['extraprotocols'] ) && !empty( $genoptions['extraprotocols'] ) ) {
			$extra_protocol_array = explode( ',', $genoptions['extraprotocols'] );

			if ( !empty( $extra_protocol_array ) ) {
				foreach( $extra_protocol_array as $extra_protocol ) {
					$protocols[] = $extra_protocol;
				}
			}
		}

		return $protocols;
	}
    
    	/******************************************** Print style data to header *********************************************/

	function ll_rss_link() {
		global $llstylesheet, $rss_settings;
		
		if ( !empty( $rss_settings ) ) {
			$settingsname = 'LinkLibraryPP' . $rss_settings;
			$options = get_option( $settingsname );

			$feedtitle = ( empty( $options['rssfeedtitle'] ) ? __('Link Library Generated Feed', 'link-library') : $options['rssfeedtitle'] );

			$xpath = $this->relativePath( dirname( __FILE__ ), ABSPATH );
			echo '<link rel="alternate" type="application/rss+xml" title="' . esc_html( stripslashes( $feedtitle ) ) . '" href="' . home_url('/feed/linklibraryfeed?settingsset=' . $rss_settings/* . '&xpath=' . $xpath*/) . '" />';
			unset( $xpath );
		}

		if ( $llstylesheet ) {
			$genoptions = get_option( 'LinkLibraryGeneral' );
			if ( isset( $genoptions['fullstylesheet'] ) ) {
				echo "<style id='LinkLibraryStyle' type='text/css'>\n";
				echo stripslashes( $genoptions['fullstylesheet'] );
				echo "</style>\n";
			}
		}
	}

	/****************************************** Add Link Category name to page title when option is present ********************************/
	function ll_title_creator( $title ) {
		global $wp_query;
		global $wpdb;
        global $llstylesheet;

        if ( $llstylesheet ) {
            $genoptions = get_option( 'LinkLibraryGeneral' );

            $categoryname = ( isset( $wp_query->query_vars['cat_name'] ) ? $wp_query->query_vars['cat_name'] : '' );
            $catid = ( isset( $_GET['cat_id'] ) ? intval($_GET['cat_id']) : '' );

            $linkcatquery = 'SELECT t.name ';
            $linkcatquery .= 'FROM ' . $this->db_prefix() . 'terms t LEFT JOIN ' . $this->db_prefix(). 'term_taxonomy tt ON (t.term_id = tt.term_id) ';
            $linkcatquery .= 'LEFT JOIN ' . $this->db_prefix() . 'term_relationships tr ON (tt.term_taxonomy_id = tr.term_taxonomy_id) ';
            $linkcatquery .= 'WHERE tt.taxonomy = "link_category" AND ';

            if ( !empty( $categoryname ) ) {
                    $linkcatquery .= 't.slug = "' . $categoryname . '"';
                    $nicecatname = $wpdb->get_var( $linkcatquery );
                    return $title . $genoptions['pagetitleprefix'] . $nicecatname . $genoptions['pagetitlesuffix'];
            } elseif ( !empty( $catid ) ) {
                    $linkcatquery .= 't.term_id = "' . $catid . '"';
                    $nicecatname = $wpdb->get_var( $linkcatquery );
                    return $title . $genoptions['pagetitleprefix'] . $nicecatname . $genoptions['pagetitlesuffix'];
            }
        }

		return $title;
	}
    
    	/************************************* Function to add to rewrite rules for permalink support **********************************/
	function ll_insertMyRewriteRules( $rules ) {
		$newrules = array();

		$genoptions = get_option('LinkLibraryGeneral');

		if ( !empty( $genoptions ) ) {
			for ( $i = 1; $i <= $genoptions['numberstylesets']; $i++ ) {
				$settingsname = 'LinkLibraryPP' . $i;
				$options = get_option( $settingsname );
				
				if ( $options['enablerewrite'] && !empty( $options['rewritepage'] ) ) {
					if ( is_multisite() ) {
						$newrules['(' . $options['rewritepage'] . ')/(.+?)$'] = 'index.php?pagename=$matches[2]&cat_name=$matches[3]';
					} else {
						$newrules['(' . $options['rewritepage'] . ')/(.+?)$'] = 'index.php?pagename=$matches[1]&cat_name=$matches[2]';
					}
                }

				if ( $options['publishrssfeed'] ) {
					$xpath = $this->relativePath( dirname( __FILE__ ), ABSPATH );

					if ( !empty( $options['rssfeedaddress'] ) ) {
                        $newrules['(' . $options['rssfeedaddress'] . ')/(.+?)$'] = home_url() . '/feed/linklibraryfeed?settingsset=$matches[1]';
                    }
					unset( $xpath );
				}
			}
		}

		return $newrules + $rules;
	}

	// Adding the id var so that WP recognizes it
	function ll_insertMyRewriteQueryVars( $vars ) {
		array_push( $vars, 'cat_name' );
		return $vars;
	}

    function relativePath( $from, $to, $ps = DIRECTORY_SEPARATOR ) {
        $arFrom = explode( $ps, rtrim( $from, $ps ) );
        $arTo = explode( $ps, rtrim( $to, $ps ) );
        while( count( $arFrom ) && count( $arTo ) && ( $arFrom[0] == $arTo[0] ) ) {
            array_shift( $arFrom );
            array_shift( $arTo );
        }
        $return = str_pad( '', count($arFrom) * 3, '..'.$ps ) . implode( $ps, $arTo );

        // Don't disclose anything about the path is it's not needed, i.e. is the standard
        if( $return === '../../../' ) {
            $return = '';
        }

        return $return;
    }

	function CheckReciprocalLink( $RecipCheckAddress = '', $external_link = '' ) {
		$response = wp_remote_get( $external_link, array( 'user-agent' => 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36', 'timeout' => 10 ) );

        if( is_wp_error( $response ) ) {
            $response_code = $response->get_error_code();
            if ( 'http_request_failed' == $response_code ) {
                return 'error_403';
            }
        } elseif ( $response['response']['code'] == '200' ) {
	        if ( empty( $RecipCheckAddress ) ) {
		        return 'exists_notfound';
	        } elseif ( strpos( $response['body'], $RecipCheckAddress ) === false ) {
				return 'exists_notfound';
			} elseif ( strpos( $response['body'], $RecipCheckAddress ) !== false ) {
				return 'exists_found';
			}
		}

		return 'unreachable';
	}

    /* Output for users trying to directly call Link Library function, as was possible in pre-1.0 versions */

	function LinkLibraryCategories() {
        return __( 'Link Library no longer supports calling this function with individual arguments. Please use the admin panel to configure Link Library and the do_shortcode function to use Link Library output in your code.', 'link-library' );
	}

	function LinkLibrary() {
        return __( 'Link Library no longer supports calling this function with individual arguments. Please use the admin panel to configure Link Library and the do_shortcode function to use Link Library output in your code.', 'link-library' );
	}
	
	/********************************************** Function to Process [link-library-cats] shortcode *********************************************/
	
	function link_library_cats_func( $atts ) {
		$categorylistoverride = '';
		$excludecategoryoverride = '';
		$settings = '';

		extract( shortcode_atts( array (
			'categorylistoverride' => '',
			'excludecategoryoverride' => '',
			'settings' => ''
		), $atts ) );
		
		if ( empty( $settings ) ) {
			$settings = 1;
		}

        $settingsname = 'LinkLibraryPP' . $settings;
        $options = get_option( $settingsname );

        if ( !empty( $categorylistoverride ) ) {
            $options['categorylist_cpt'] = $categorylistoverride;

	        $update_list = false;
	        $category_list_array = explode( ',', $categorylistoverride );
	        foreach( $category_list_array as $index => $category_text ) {
		        if ( !is_numeric( $category_text ) ) {
			        $update_list = true;
			        $matched_term = get_term_by( 'slug', $category_text, 'link_library_category' );

			        if ( $matched_term ) {
				        $category_list_array[$index] = $matched_term->term_id;
			        } else {
				        unset( $category_list_array[$index] );
			        }
		        }
	        }
	        if ( $update_list ) {
		        $options['categorylist_cpt'] = implode( ',', $category_list_array );
	        }
        }

		if ( !empty( $excludecategoryoverride ) ) {
            $options['excludecategorylist_cpt'] = $excludecategoryoverride;

			$update_list = false;
			$exclude_category_list_array = explode( ',', $excludecategoryoverride );
			foreach( $exclude_category_list_array as $index => $category_text ) {
				if ( !is_numeric( $category_text ) ) {
					$update_list = true;
					$matched_term = get_term_by( 'slug', $category_text, 'link_library_category' );

					if ( $matched_term ) {
						$exclude_category_list_array[$index] = $matched_term->term_id;
					} else {
						unset( $exclude_category_list_array[$index] );
					}
				}
			}
			if ( $update_list ) {
				$options['categorylist_cpt'] = implode( ',', $exclude_category_list_array );
			}
        }

		$genoptions = get_option( 'LinkLibraryGeneral' );

        if ( $genoptions['debugmode'] ) {
            $mainoutputstarttime = microtime( true );
            $timeoutputstart = "\n<!-- Start Link Library Cats Time: " . $mainoutputstarttime . "-->\n";
        }

        require_once plugin_dir_path( __FILE__ ) . 'render-link-library-cats-sc.php';

        if ( $genoptions['debugmode'] ) {
            $timeoutput = "\n<!-- [link-library-cats] shortcode execution time: " . ( microtime( true ) - $mainoutputstarttime ) . "-->\n";
        }

		return ( true == $genoptions['debugmode'] ? $timeoutputstart : '' ) . RenderLinkLibraryCategories( $this, $genoptions, $options, $settings )  . ( true == $genoptions['debugmode'] ? $timeoutput : '' );
	}
	
	/********************************************** Function to Process [link-library-search] shortcode *********************************************/

	function link_library_search_func($atts) {
		$settings = '';

		extract(shortcode_atts(array(
			'settings' => ''
		), $atts));
		
		if ( empty( $settings ) ) {
            $options = get_option('LinkLibraryPP1');
        } else {
			$settingsname = 'LinkLibraryPP' . $settings;
			$options = get_option($settingsname);
		}

        require_once plugin_dir_path( __FILE__ ) . 'render-link-library-search-sc.php';
		return RenderLinkLibrarySearchForm( $options );
	}
	
	/********************************************** Function to Process [link-library-add-link] shortcode *********************************************/

	function link_library_addlink_func($atts, $content, $code) {
		$settings = '';
		$categorylistoverride = '';
		$excludecategoryoverride = '';

		extract(shortcode_atts(array(
			'settings' => '',
			'categorylistoverride' => '',
			'excludecategoryoverride' => ''
		), $atts));
                
		if ( empty( $settings ) ) {
            $settings = 1;
        }

		$settingsname = 'LinkLibraryPP' . $settings;
        $options = get_option($settingsname);
                
        $genoptions = get_option('LinkLibraryGeneral');
				
		if ( !empty( $categorylistoverride ) ) {
            $options['categorylist_cpt'] = $categorylistoverride;
        } elseif ( !empty( $options['addlinkcatlistoverride'] ) ) {
            $options['categorylist_cpt'] = $options['addlinkcatlistoverride'];
        }

		if ( !empty( $excludecategoryoverride ) ) {
            $options['excludecategorylist_cpt'] = $excludecategoryoverride;
        }

        require_once plugin_dir_path( __FILE__ ) . 'render-link-library-addlink-sc.php';
        return RenderLinkLibraryAddLinkForm( $this, $genoptions, $options, $settings, $code);
	}

	/********************************************** Function to Process [link-library-count] shortcode ***************************************/

	function link_library_count_func( $atts ) {
		extract( shortcode_atts( array(
			'categorylistoverride' => '',
			'excludecategoryoverride' => '',
			'settings' => ''
		), $atts ) );

		if ( empty( $settings ) ) {
			$settings = 1;
		}

		$settingsname = 'LinkLibraryPP' . $settings;
		$options = get_option( $settingsname );
		$options = wp_parse_args( $options, ll_reset_options( $settings, 'list', 'return' ) );

		$linkeditoruser = current_user_can( 'manage_options' );

		if ( !empty( $categorylistoverride ) ) {
			$options['categorylist_cpt'] = $categorylistoverride;
		}

		if ( !empty( $excludecategoryoverride ) ) {
			$options['excludecategorylist_cpt'] = $excludecategoryoverride;
		}

		$link_query_args = array( 'post_type' => 'link_library_links', 'posts_per_page' => -1 );
		$link_query_args['post_status'] = array( 'publish' );

		if ( !empty( $options['categorylist_cpt'] ) ) {
			$catlistarray = explode( ',', $options['categorylist_cpt'] );
			$link_query_args['tax_query'] = array( array( 'taxonomy' => 'link_library_category',
													'field' => 'term_id',
													'terms' => $catlistarray,
													'operator' => 'IN' ) );
		}

		if ( !empty( $options['excludecategorylist_cpt'] ) ) {
			$catlistexcludearray = explode( ',', $options['excludecategorylist_cpt'] );
			$link_query_args['tax_query'] = array( array( 'taxonomy' => 'link_library_category',
			                                              'field' => 'term_id',
			                                              'terms' => $catlistexcludearray,
			                                              'operator' => 'NOT IN' ) );
		}

		if ( $options['showuserlinks'] ) {
			$link_query_args['post_status'][] = 'pending';
		}

		if ( $options['showinvisible'] || ( $options['showinvisibleadmin'] && $linkeditoruser ) ) {
			$link_query_args['post_status'][] = 'draft';
		}

		if ( $options['showscheduledlinks'] ) {
			$link_query_args['post_status'][] = 'future';
		}

		$the_link_query = new WP_Query( $link_query_args );

		wp_reset_postdata();

		return $the_link_query->found_posts;
	}

	/********************************************** Function to Process [link-library-filters] shortcode ***************************************/

	function link_library_filters( $atts ) {
		extract( shortcode_atts( array(
			'includetagsids' => '',
			'excludetagsids' => '',
			'showtagfilters' => true,
			'taglabel' => __( 'Tag', 'link-library' ),
			'showpricefilters' => true,
			'pricelabel' => __( 'Price', 'link-library' ),
			'settings' => ''
		), $atts ) );

		if ( empty( $settings ) ) {
			$settings = 1;
		}

		$settingsname = 'LinkLibraryPP' . $settings;
		$options = get_option( $settingsname );
		$genoptions = get_option( 'LinkLibraryGeneral' );

		require_once plugin_dir_path( __FILE__ ) . 'render-link-library-tag-filter-sc.php';
		return RenderLinkLibraryFilterBox( $this, $genoptions, $options, $settings, $includetagsids, $excludetagsids, $showtagfilters, $taglabel, $showpricefilters, $pricelabel );
	}
	
	/********************************************** Function to Process [link-library] shortcode *********************************************/

	function link_library_func( $atts = '' ) {
        if ( isset( $_POST['ajaxupdate'] ) ) {
            check_ajax_referer( 'link_library_ajax_refresh' );
        }

		$settings = '';
		$notesoverride = '';
		$descoverride = '';
		$rssoverride = '';
		$categorylistoverride = '';
		$excludecategoryoverride = '';
		$tableoverride = '';
		$singlelinkid = '';
		$showonecatonlyoverride = false;

		extract( shortcode_atts( array(
			'categorylistoverride' => '',
			'excludecategoryoverride' => '',
			'notesoverride' => '',
			'descoverride' => '',
			'rssoverride' => '',
			'tableoverride' => '',
			'settings' => '',
			'singlelinkid' => '',
			'showonecatonlyoverride' => ''
		), $atts ) );

		if ( empty( $settings ) && !isset( $_POST['settings'] ) ) {
			$settings = 1;
		} else if ( isset( $_POST['settings'] ) ) {
            $settings = intval( $_POST['settings'] );
        }

        $settingsname = 'LinkLibraryPP' . $settings;
        $options = get_option( $settingsname );
        $options['AJAXcatid'] = '';
        $options['AJAXpageid'] = '';

        if ( !empty( $notesoverride ) ) {
            $options['shownotes'] = $notesoverride;
        }

		if ( !empty( $descoverride ) ) {
            $options['showdescription'] = $descoverride;
        }

        if ( !empty( $rssoverride ) ) {
            $options['show_rss'] = $rssoverride;
        }

		if ( !empty( $categorylistoverride ) ) {
            $options['categorylist_cpt'] = $categorylistoverride;

            $update_list = false;
            $category_list_array = explode( ',', $categorylistoverride );
            foreach( $category_list_array as $index => $category_text ) {
            	if ( !is_numeric( $category_text ) ) {
            		$update_list = true;
            		$matched_term = get_term_by( 'slug', $category_text, 'link_library_category' );

            		if ( $matched_term ) {
            			$category_list_array[$index] = $matched_term->term_id;
					} else {
            			unset( $category_list_array[$index] );
					}
				}
			}
			if ( $update_list ) {
				$options['categorylist_cpt'] = implode( ',', $category_list_array );
			}
        }

		if ( !empty( $excludecategoryoverride ) ) {
            $options['excludecategorylist_cpt'] = $excludecategoryoverride;

			$update_list = false;
			$exclude_category_list_array = explode( ',', $excludecategoryoverride );
			foreach( $exclude_category_list_array as $index => $category_text ) {
				if ( !is_numeric( $category_text ) ) {
					$update_list = true;
					$matched_term = get_term_by( 'slug', $category_text, 'link_library_category' );

					if ( $matched_term ) {
						$exclude_category_list_array[$index] = $matched_term->term_id;
					} else {
						unset( $exclude_category_list_array[$index] );
					}
				}
			}
			if ( $update_list ) {
				$options['categorylist_cpt'] = implode( ',', $exclude_category_list_array );
			}
        }

		if ( !empty( $singlelinkid ) ) {
			$options['singlelinkid'] = $singlelinkid;
		}

		if ( $showonecatonlyoverride == 'false' || $showonecatonlyoverride == 'true' ) {
			if ( $showonecatonlyoverride == 'false' ) {
				$options['showonecatonly'] = false;
			} elseif ( $showonecatonlyoverride == 'true' ) {
				$options['showonecatonly'] = true;
			}
		}

		if ( !empty( $tableoverride ) ) {
            $options['displayastable'] = $tableoverride;
        }

        if ( isset( $_POST['ajaxupdate'] ) ) {
            if ( isset( $_POST['id'] ) ) {
                $catID = intval( $_POST['id'] );
                $options['AJAXcatid'] = $catID;
            }

            if ( isset( $_POST['linkresultpage'] ) ) {
                $pageID = intval( $_POST['linkresultpage'] );
                $options['AJAXpageid'] = $pageID;
            }
        }

        $genoptions = get_option( 'LinkLibraryGeneral' );
		
		if ( floatval( $genoptions['schemaversion'] ) < '5.0' ) {
			$this->ll_install();
			$genoptions = get_option( 'LinkLibraryGeneral' );
			
			if ( empty( $settings ) ) {
                $options = get_option( 'LinkLibraryPP1' );
            } else {
				$settingsname = 'LinkLibraryPP' . $settings;
				$options = get_option( $settingsname );
			}
		}

        $linklibraryoutput = '';

        if ( $genoptions['debugmode'] ) {
            $linklibraryoutput .= "\n<!-- Library Settings Info:" . print_r( $options, true ) . "-->\n";
            $mainoutputstarttime = microtime( true );
            $linklibraryoutput .= "\n<!-- Start Time: " . $mainoutputstarttime . "-->\n";
        }

        require_once plugin_dir_path( __FILE__ ) . 'render-link-library-sc.php';
        $linkcount = 1;
        $linklibraryoutput .= RenderLinkLibrary( $this, $genoptions, $options, $settings, false, 0, 0, true, false, $linkcount );

        if ( isset( $_POST['ajaxupdate'] ) ) {
            echo $linklibraryoutput;

            if ( $genoptions['debugmode'] ) {
                echo "\n<!-- Execution Time: " . ( microtime( true ) - $mainoutputstarttime ) . "-->\n";
            }
            exit;
        } else {
            if ( $genoptions['debugmode'] ) {
                $timeoutput = "\n<!-- [link-library] shortcode execution time: " . ( microtime( true ) - $mainoutputstarttime ) . "-->\n";
            }
            return $linklibraryoutput . ( true == $genoptions['debugmode'] ? $timeoutput : '' );
        }
	}

	function conditionally_add_scripts_and_styles( $posts ) {
		if ( empty( $posts ) ) {
            return $posts;
        }

		global $llstylesheet;
		$load_jquery = false;
		$load_thickbox = false;
		$load_recaptcha = false;

		if ( $llstylesheet ) {
			$load_style = true;
		} else {
			$load_style = false;
		}

		$genoptions = get_option( 'LinkLibraryGeneral' );
		$genoptions = wp_parse_args( $genoptions, ll_reset_gen_settings( 'return' ) );

		if ( is_admin() ) {
			$load_jquery = false;
			$load_thickbox = false;
			$load_style = false;
		} else {
			foreach ( $posts as $post ) {
				$continuesearch = true;
				$searchpos = 0;
				$settingsetids = array();
				
				while ( $continuesearch ) {
					$linklibrarypos = stripos( $post->post_content, 'link-library ', $searchpos );
					if ( !$linklibrarypos ) {
						$linklibrarypos = stripos( $post->post_content, 'link-library]', $searchpos );
						if ( !$linklibrarypos ) {
                            if ( stripos( $post->post_content, 'link-library-cats' ) || stripos( $post->post_content, 'link-library-addlink' ) ) {
                                $load_style = true;
	                            if ( 'recaptcha' == $genoptions['captchagenerator'] ) {
		                            $load_recaptcha = true;
	                            }
                            }
                        }
					}

					$continuesearch = $linklibrarypos;

					if ( $continuesearch ) {
						$load_style = true;
						$load_jquery = true;
						$shortcodeend = stripos( $post->post_content, ']', $linklibrarypos );
						if ( $shortcodeend ) {
                            $searchpos = $shortcodeend;
                        } else {
                            $searchpos = $linklibrarypos + 1;
                        }

						if ( $shortcodeend ) {
							$settingconfigpos = stripos( $post->post_content, 'settings=', $linklibrarypos );
							if ( $settingconfigpos && $settingconfigpos < $shortcodeend ) {
								$settingset = substr( $post->post_content, $settingconfigpos + 9, $shortcodeend - $settingconfigpos - 9 );
									
								$settingsetids[] = trim($settingset,'"');

							} else if ( 0 == count($settingsetids) ) {
								$settingsetids[] = 1;
							}
						}
					}	
				}
			}
			
			if ( $settingsetids ) {
				foreach ( $settingsetids as $settingsetid ) {
					$settingsname = 'LinkLibraryPP' . $settingsetid;
					$options = get_option( $settingsname );
					
					if ( $options['showonecatonly'] ) {
						$load_jquery = true;
					}
			
					if ( $options['rsspreview'] || ( isset( $options['enable_link_popup'] ) && $options['enable_link_popup'] ) ) {
						$load_thickbox = true;
					}

					if ($options['publishrssfeed'] == true) {
						global $rss_settings;
						$rss_settings = $settingsetid;
					}	
				}
			}
				
			if ( !empty( $genoptions['includescriptcss'] ) ) {
				$pagelist = explode ( ',', $genoptions['includescriptcss'] );
                $loadscripts = false;
				foreach( $pagelist as $pageid ) {
                    if ( ( $pageid == 'front' && is_front_page() ) ||
                         ( $pageid == 'category' && is_category() ) ||
                         ( $pageid == 'all') ||
                         ( is_page( $pageid ) ) ) {
                        $load_jquery = true;
						$load_thickbox = true;
						$load_style = true;                        
					}
				}   
			}
		}
		
		if ( $load_style ) {
			$llstylesheet = true;
		} else {
			$llstylesheet = false;
		}
	 
		if ( $load_jquery ) {
			wp_enqueue_script( 'jquery' );
		}
			
		if ( $load_thickbox ) {
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_style ( 'thickbox' );
		}

		if ( $load_recaptcha && $genoptions['captchagenerator'] ) {
			wp_enqueue_script( 'google_recaptcha', 'https://www.google.com/recaptcha/api.js', array(), false, true );
		}
	 
		return $posts;
	}

	function ll_popup_content() {
		require_once plugin_dir_path( __FILE__ ) . 'linkpopup.php';
		link_library_popup_content( $this );
	}

    function ll_template_redirect( $template ) {
	    if ( !empty( $_POST['link_library_user_link_submission'] ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'usersubmission.php';
            link_library_process_user_submission( $this );
            return '';
        } else if ( !empty( $_GET['link_library_rss_preview'] ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'rsspreview.php';
            link_library_generate_rss_preview( $this );
            return '';
        } else {
            return $template;
        }
    }

	function ll_template_include( $template_path ) {
		if ( get_post_type() == 'link_library_links' && is_single() ) {
			// checks if the file exists in the theme first,
			// otherwise serve the file from the plugin
			if ( $theme_file = locate_template( array ( 'single-link_library_links.php' ) ) ) {
				$template_path = $theme_file;
			} else {
				add_filter( 'the_content', array( $this, 'll_display_single_link' ), 20 );
			}
		}
		return $template_path;
	}
	
	function ll_get_string_between($string, $start, $end){
		$string = ' ' . $string;
		$ini = strpos($string, $start);
		if ($ini == 0) return '';
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;
		return substr($string, $ini, $len);
	}
	
	function ll_replace_all_between( $beginning, $end, $string, $replace ) {
		$beginningPos = strpos($string, $beginning);
		$endPos = strpos($string, $end);
		if ($beginningPos === false || $endPos === false) {
			return $string;
		}

		$textToDelete = substr($string, $beginningPos, ($endPos + strlen($end)) - $beginningPos);

		return str_replace($textToDelete, $replace, $string);
	}

	function ll_display_single_link( $content ) {

		$genoptions = get_option( 'LinkLibraryGeneral' );
		$genoptions = wp_parse_args( $genoptions, ll_reset_gen_settings( 'return' ) );

		$content = htmlspecialchars_decode( stripslashes( $genoptions['single_link_layout'] ) );

		$item_id = get_the_ID();
		if ( !empty( $item_id ) ) {
			$link = get_post( get_the_ID() );
			if ( !empty( $link ) ) {
				$link_url = esc_url( get_post_meta( get_the_ID(), 'link_url', true ) );
				$link_description = esc_html( get_post_meta( get_the_ID(), 'link_description', true ) );
				$link_large_description = get_post_meta( get_the_ID(), 'link_textfield', true );
				$link_image = esc_url( get_post_meta( get_the_ID(), 'link_image', true ) );
				$link_price = number_format( floatval( get_post_meta( get_the_ID(), 'link_price', true ) ), 2 );
				$link_price_currency = $this->ll_get_string_between( $content, '[currency]', '[/currency]' );
				$link_price_currency_or_free = $this->ll_get_string_between( $content, '[currency_or_free]', '[/currency_or_free]' );
				$link_terms = wp_get_post_terms( get_the_ID(), 'link_library_category' );
				$link_terms_list = '';
				$link_terms_array = array();
				if ( is_array( $link_terms ) ) {
					foreach( $link_terms as $link_term ) {
						$link_terms_array[] = $link_term->name;
					}

					if ( !empty( $link_terms_array ) ) {
						$link_terms_list = implode( ', ', $link_terms_array );
					}
				}

				$content = str_replace( '[link_title]', $link->post_title, $content );
				$content = str_replace( '[link_content]', $link->post_content, $content );
				$content = str_replace( '[link_description]', $link_description, $content );
				$content = str_replace( '[link_large_description]', $link_large_description, $content );
				$content = str_replace( '[link_image]', $link_image, $content );

				$content = str_replace( '[link_price]', $link_price, $content );
				$content = $this->ll_replace_all_between( '[currency]', '[/currency]', $content, $link_price_currency );
				
				if ( floatval( $link_price ) > 0.0 ) {
					$content = str_replace( '[link_price_or_free]', $link_price, $content );
					$content = $this->ll_replace_all_between( '[currency_or_free]', '[/currency_or_free]', $content, $link_price_currency_or_free );
				} else {
					$content = str_replace( '[link_price_or_free]', __( 'Free', 'link-library' ), $content );
					$content = $this->ll_replace_all_between( '[currency_or_free]', '[/currency_or_free]', $content, '' );
				}

				$content = str_replace( '[link_address]', $link_url, $content );
				$content = str_replace( '[link]', '<a href="' . $link_url . '">' . $link->post_title . '</a>', $content );
				$content = str_replace( '[link_category]', $link_terms_list, $content );
			}
		}

		return nl2br( $content );
	}

    function link_library_ajax_tracker() {
        require_once plugin_dir_path( __FILE__ ) . 'tracker.php';
        link_library_process_ajax_tracker( $this );
    }

    function link_library_generate_image() {
        global $my_link_library_plugin_admin;

        if ( empty( $my_link_library_plugin_admin ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'link-library-admin.php';
            $my_link_library_plugin_admin = new link_library_plugin_admin();
        }

        require_once plugin_dir_path( __FILE__ ) . 'link-library-image-generator.php';
        link_library_ajax_image_generator( $my_link_library_plugin_admin );
    }
}

global $my_link_library_plugin;
$my_link_library_plugin = new link_library_plugin();

if ( is_admin() ) {

	/* Determine update method selected by user under General Settings or under Network Settings */
	$updatechannel = 'standard';

	if ( ( function_exists( 'is_multisite' ) && !is_multisite() ) || !function_exists( 'is_multisite' ) ) {
		$genoptions = get_option( 'LinkLibraryGeneral' );
		$genoptions = wp_parse_args( $genoptions, ll_reset_gen_settings( 'return' ) );

		if ( !empty( $genoptions['updatechannel'] ) ) {
			$updatechannel = $genoptions['updatechannel'];
		}
	} else if ( function_exists( 'is_multisite' ) && function_exists( 'is_network_admin' ) && is_multisite() && is_network_admin() ) {
		$networkoptions = get_site_option( 'LinkLibraryNetworkOptions' );

		if ( isset( $networkoptions ) && !empty( $networkoptions['updatechannel'] ) ) {
			$updatechannel = $networkoptions['updatechannel'];
		}
	}

	/* Install filter is user selected monthly updates to filter out dot dot dot minor releases (e.g. 5.8.8.x) */
	if ( 'monthly' == $updatechannel ) {
		add_filter( 'http_response', 'link_library_tweak_plugins_http_filter', 10, 3 );
	}

	if ( empty( $my_link_library_plugin_admin ) ) {
		global $my_link_library_plugin_admin;
		require plugin_dir_path( __FILE__ ) . 'link-library-admin.php';
		$my_link_library_plugin_admin = new link_library_plugin_admin();
	}
}

add_action( 'widgets_init', 'll_create_widgets' );

function ll_create_widgets() {
	register_widget( 'Link_Library_Widget' );
}

class Link_Library_Widget extends WP_Widget {
	// Construction function
	function __construct () {
		parent::__construct( 'link_library', 'Link Library',
			array( 'description' =>
				       'Displays links as configured under Link Library configurations' ) );
	}

	function form( $instance ) {
		$genoptions = get_option( 'LinkLibraryGeneral' );
		$genoptions = wp_parse_args( $genoptions, ll_reset_gen_settings( 'return' ) );

		$selected_library = ( !empty( $instance['selected_library'] ) ? $instance['selected_library'] : 1 );
		$widget_title = ( !empty( $instance['widget_title'] ) ? esc_attr( $instance['widget_title'] ) : 'Links' );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'widget_title' ); ?>">
				<?php echo 'Widget Title:'; ?>
				<input type="text"
				       id="<?php echo $this->get_field_id( 'widget_title' );?>"
				       name="<?php echo $this->get_field_name( 'widget_title' ); ?>"
				       value="<?php echo $widget_title; ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'nb_book_reviews' ); ?>">
				<?php echo 'Select library configuration to display:'; ?>
				<select id="<?php echo $this->get_field_id( 'selected_library' ); ?>"
				        name="<?php echo $this->get_field_name( 'selected_library' ); ?>">
					<?php if ( empty( $genoptions['numberstylesets'] ) ) {
							$numberofsets = 1;
						} else {
							$numberofsets = $genoptions['numberstylesets'];
						}

						for ( $counter = 1; $counter <= $numberofsets; $counter ++ ) {
							$tempoptionname = "LinkLibraryPP" . $counter;
							$tempoptions          = get_option( $tempoptionname );

							echo '<option value="' . $counter . '" ' . selected( $selected_library, $counter ) . '>' . $tempoptions['settingssetname'] . '</option>';
						}
						?>
				</select>
			</label>
		</p>
	<?php }

	function widget( $args, $instance ) {
		// Extract members of args array as individual variables
		extract( $args );
		// Retrieve widget configuration options
		$selected_library = ( !empty( $instance['selected_library'] ) ? $instance['selected_library'] : 1 );
		$widget_title = ( !empty( $instance['widget_title'] ) ? esc_attr( $instance['widget_title'] ) : 'Links' );

		// Display widget title
		echo $before_widget . $before_title;
		echo apply_filters( 'widget_title', $widget_title );
		echo $after_title;

		echo do_shortcode( '[link-library settings="' . $selected_library . '"]');

		echo $after_widget;
	}
}

