<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once plugin_dir_path( __FILE__ ) . 'link-library-defaults.php';

/**
 *
 * Render the output of the link-library-cats shortcode
 *
 * @param $LLPluginClass    Link Library main plugin class
 * @param $generaloptions   General Plugin Settings
 * @param $libraryoptions   Selected library settings array
 * @param $settings         Settings ID
 * @return                  List of categories output for browser
 */

function RenderLinkLibraryCategories( $LLPluginClass, $generaloptions, $libraryoptions, $settings, $parent_cat_id = 0, $level = 0 ) {
    $generaloptions = wp_parse_args( $generaloptions, ll_reset_gen_settings( 'return' ) );
    extract( $generaloptions );

    $libraryoptions = wp_parse_args( $libraryoptions, ll_reset_options( 1, 'list', 'return' ) );
    extract( $libraryoptions );

	$linkeditoruser = current_user_can( 'manage_links' );

    /* This case will only happen if the user entered bad data in the admin page or if someone is trying to inject bad data in SQL query */
    if ( !empty( $categorylist ) ) {
        $categorylistarray = explode( ',', $categorylist );

        if ( true === array_filter( $categorylistarray, 'is_int' ) ) {
            return 'List of requested categories is invalid. Please go back to Link Library admin panel to correct.';
        }
    }

    if ( !empty( $excludecategorylist ) ) {
        $excludecategorylistarray = explode( ',', $excludecategorylist );

        if ( true === array_filter( $excludecategorylistarray, 'is_int' ) ) {
            return 'List of requested excluded categories is invalid. Please go back to Link Library admin panel to correct.';
        }
    }

    $output = '';

    $categoryid = '';

    if ( isset($_GET['cat_id'] ) ) {
        $categoryid = intval( $_GET['cat_id'] );
    } elseif ( isset( $_GET['catname'] ) ) {
        $categoryterm = get_term_by( 'name', urldecode( $_GET['catname'] ), 'link_category' );
	    $categoryid = $categoryterm->term_id;
    } elseif ( $showonecatonly ) {
	    $categoryid = $defaultsinglecat_cpt;
    }

    if ( !isset( $_GET['searchll'] ) || true == $showcatonsearchresults ) {
        $countcat = 0;

        $order = strtolower( $order );

        if ( 0 == $level ) {
	        $output .= "<!-- Link Library Categories Output -->\n\n";
        }


        if ( $showonecatonly && ( 'AJAX' == $showonecatmode || empty( $showonecatmode ) ) ) {
            $nonce = wp_create_nonce( 'link_library_ajax_refresh' );

            $output .= "<SCRIPT LANGUAGE=\"JavaScript\">\n";
            $output .= "var ajaxobject;\n";
	        $output .= "if(typeof showLinkCat" . $settings . " !== 'function'){\n";
	        $output .= "window.showLinkCat" . $settings . " = function ( _incomingID, _settingsID, _pagenumber ) {\n";
            $output .= "if (typeof(ajaxobject) != \"undefined\") { ajaxobject.abort(); }\n";

            $output .= "\tjQuery('#contentLoading" . $settings . "').toggle();" .
                "jQuery.ajax( {" .
                "    type: 'POST', " .
                "    url: '" . admin_url( 'admin-ajax.php' ) . "', " .
                "    data: { action: 'link_library_ajax_update', " .
                "            _ajax_nonce: '" . $nonce . "', " .
                "            id : _incomingID, " .
                "            settings : _settingsID, " .
                "            ajaxupdate : true, " .
                "            linkresultpage: _pagenumber }, " .
                "    success: function( data ){ " .
                "            jQuery('#linklist" . $settings. "').html( data ); " .
                "            jQuery('#contentLoading" . $settings . "').toggle();\n" .
                "            } } ); ";
	        $output .= "}\n";
            $output .= "}\n";

            $output .= "</SCRIPT>\n\n";
        }

	    $currentcatletter = '';

	    if ( $cat_letter_filter != 'no' ) {
		    require_once plugin_dir_path( __FILE__ ) . 'render-link-library-alpha-filter.php';
		    $result = RenderLinkLibraryAlphaFilter( $LLPluginClass, $generaloptions, $libraryoptions, $settings, 'normal' );

		    $currentcatletter = $result['currentcatletter'];

		    if ( 'beforecats' == $cat_letter_filter || 'beforecatsandlinks' == $cat_letter_filter ) {
			    $output .= $result['output'];
		    }
	    }

        $link_categories_query_args = array( 'parent' => $parent_cat_id );

        if ( $hide_if_empty ) {
            $link_categories_query_args['hide_empty'] = true;
            global $hide_if_empty_filter;
            $hide_if_empty_filter = $hide_if_empty_filter;
        } else {
            $link_categories_query_args['hide_empty'] = false;
        }

        if ( !$showuserlinks && !$showinvisible && !$showinvisibleadmin ) {
            add_filter( 'get_terms', 'link_library_get_terms_filter_only_publish', 10, 3 );
        } elseif ( $showuserlinks && !$showinvisible && !$showinvisibleadmin ) {
            add_filter( 'get_terms', 'link_library_get_terms_filter_publish_pending', 10, 3 );
        } elseif ( !$showuserlinks && ( $showinvisible || ( $showinvisibleadmin && $linkeditoruser ) ) ) {
            add_filter( 'get_terms', 'link_library_get_terms_filter_publish_draft', 10, 3 );
        } elseif ( $showuserlinks && ( $showinvisible || ( $showinvisibleadmin && $linkeditoruser ) ) ) {
            add_filter( 'get_terms', 'link_library_get_terms_filter_publish_draft_pending', 10, 3 );
        }

        if ( !empty( $categorylist_cpt ) && empty( $singlelinkid ) && $level == 0 ) {
            $link_categories_query_args['include'] = explode( ',', $categorylist_cpt );
        }

        if ( !empty( $excludecategorylist_cpt ) && empty( $singlelinkid ) && $level == 0 ) {
            $link_categories_query_args['exclude'] = explode( ',', $excludecategorylist_cpt );
        }

        if ( ( !empty( $categorysluglist ) || isset( $_GET['cat'] ) ) && empty( $singlelinkid ) && $level == 0 ) {
        	if ( !empty( $categorysluglist ) ) {
		        $link_categories_query_args['slug'] = explode( ',', $categorysluglist );
	        } elseif ( isset( $_GET['cat'] ) ) {
		        $link_categories_query_args['slug'] = $_GET['cat'];
	        }
        }

        if ( isset( $categoryname ) && !empty( $categoryname ) && 'HTMLGETPERM' == $showonecatmode && empty( $singlelinkid ) && $level == 0 ) {
            $link_categories_query_args['slug'] = $categoryname;
        }

        if ( ( !empty( $categorynamelist ) || isset( $_GET['catname'] ) ) && empty( $singlelinkid ) && $level == 0 ) {
            $link_categories_query_args['name'] = explode( ',', urldecode( $categorynamelist ) );
        }

        $validdirections = array( 'ASC', 'DESC' );

        if ( 'name' == $order ) {
            $link_categories_query_args['orderby'] = 'name';
            $link_categories_query_args['order'] = in_array( $direction, $validdirections ) ? $direction : 'ASC';
        } elseif ( 'id' == $order ) {
            $link_categories_query_args['orderby'] = 'id';
            $link_categories_query_args['order'] = in_array( $direction, $validdirections ) ? $direction : 'ASC';
        } elseif ( 'slug' == $order ) {
	        $link_categories_query_args['orderby'] = 'slug';
	        $link_categories_query_args['order'] = in_array( $direction, $validdirections ) ? $direction : 'ASC';
        }

        $link_categories = get_terms( 'link_library_category', $link_categories_query_args );

	    remove_filter( 'get_terms', 'link_library_get_terms_filter_only_publish' );
	    remove_filter( 'get_terms', 'link_library_get_terms_filter_publish_pending' );
	    remove_filter( 'get_terms', 'link_library_get_terms_filter_publish_draft' );
	    remove_filter( 'get_terms', 'link_library_get_terms_filter_publish_draft_pending' );

	    if ( !empty( $link_categories_query_args['include'] ) && !empty( $link_categories_query_args['exclude'] ) ) {
		    foreach( $link_categories as $link_key => $linkcat ) {
			    foreach( $link_categories_query_args['exclude'] as $excludedcat ) {
				    if ( $linkcat->term_id == $excludedcat ) {
					    unset( $link_categories[$link_key] );
				    }
			    }
		    }
	    }

        if ( 'catlist' == $order ) {
            $temp_link_categories = $link_categories;
            $link_categories = array();
            foreach ( $link_categories_query_args['include'] as $sort_link_category_id ) {
                foreach ( $temp_link_categories as $temp_link_cat ) {
                    if ( $sort_link_category_id == $temp_link_cat->term_id ) {
                        $link_categories[] = $temp_link_cat;
                        continue;
                    }
                }
            }
        }

        if ( $debugmode ) {
            $output .= "\n<!-- Category Query: " . print_r( $link_categories_query_args, TRUE ) . "-->\n\n";
            $output .= "\n<!-- Category Results: " . print_r( $link_categories, TRUE ) . "-->\n\n";
        }

        // Display each category
        if ( $link_categories ) {

            if ( $level == 0 ) {
	            $output .=  '<div id="linktable" class="linktable">';

	            if ( 'table' == $flatlist ) {
		            $output .= "<table width=\"" . $table_width . "%\">\n";
	            } elseif ( 'unordered' == $flatlist ) {
		            $output .= "<ul class='menu'>\n";
	            } elseif ( 'dropdown' == $flatlist || 'dropdowndirect' == $flatlist ) {
		            $output .= "<form name='catselect'><select ";
		            if ( 'dropdowndirect' == $flatlist ) {
			            $output .= "onchange='showcategory()' ";
		            }
		            $output .= "name='catdropdown' class='catdropdown'>";
	            }
            } else {
	            if ( 'dropdown' == $flatlist || 'dropdowndirect' == $flatlist ) {

	            } else {
		            $output .= "<ul class='linksubcatlist' style='padding-left: " . $level * 30 . "px;'>\n";
	            }
            }

            foreach ( $link_categories as $catname ) {

	            $childcatparams =  array( 'taxonomy' => 'link_library_category', 'child_of' => $catname->term_id );

	            if ( $hide_if_empty ) {
		            $childcatparams['hide_empty'] = true;
	            } else {
		            $childcatparams['hide_empty'] = false;
	            }

	            $childcategories = get_terms( $childcatparams );

	            $cat_has_children = false;
	            if ( !is_wp_error( $childcategories ) && !empty( $childcategories ) ) {
		            $cat_has_children = true;
	            }

                if ( !empty( $currentcatletter ) && $cat_letter_filter != 'no' ) {
                    if ( substr( $catname->name, 0, 1) != $currentcatletter ) {
                        continue;
                    }
                }

                $catfront = '';
                $cattext = '';
                $catitem = '';

	            $link_query_args = array( 'post_type' => 'link_library_links', 'posts_per_page' => -1 );
	            $link_query_args['post_status'] = array( 'publish' );

	            $link_query_args['tax_query'][] =
		            array(
			            'taxonomy' => 'link_library_category',
			            'field'    => 'term_id',
			            'terms'    => $catname->term_id,
			            'include_children' => false
		            );

	            if ( $showuserlinks ) {
		            $link_query_args['post_status'][] = 'pending';
	            }

	            if ( $showinvisible || ( $showinvisibleadmin && $linkeditoruser ) ) {
		            $link_query_args['post_status'][] = 'draft';
	            }

	            if ( isset( $_GET['searchll'] ) ) {
		            $searchstring = $_GET['searchll'];
		            if ( !empty( $searchstring ) ) {
			            $link_query_args['s'] = $searchstring;
		            }
	            }

	            if ( $current_user_links ) {
		            $user_data = wp_get_current_user();
		            $name_field_value = $user_data->display_name;

		            $link_query_args['meta_query'][] =
			            array(
				            'key'     => 'link_submitter',
				            'value'   => $name_field_value,
				            'compare' => '=',
			            );
		            if ( sizeof( $link_query_args['meta_query'] > 1 ) ) {
			            $link_query_args['meta_query']['relation'] = 'AND';
		            }
	            }

	            $the_link_query = new WP_Query( $link_query_args );
	            $linkcount = $the_link_query->post_count;
	            wp_reset_postdata();

                // Display the category name
                $countcat += 1;
                if ( $level == 0 ) {
	                if ( $num_columns > 0 && 'table' == $flatlist && ( ( 1 == $countcat % $num_columns ) || ( 1 == $num_columns ) ) ) {
		                $output .= "<tr>\n";
	                }

	                if ( 'table' == $flatlist ) {
		                $catfront = "\t<td>";
	                } elseif ( 'unordered' == $flatlist ) {
		                $catfront = "\t<li>";
	                } elseif ( ( 'dropdown' == $flatlist || 'dropdowndirect' == $flatlist ) && ( $linkcount > 0 || !$hide_if_empty )) {
		                $catfront = "\t<option ";
		                if ( !empty( $categoryid ) && $categoryid == $catname->term_id ) {
			                $catfront .= 'selected="selected" ';
		                }
		                $catfront .= 'value="';
	                }
                } else {
	                if ( 'dropdown' == $flatlist || 'dropdowndirect' == $flatlist ) {
		                $catfront = "\t<option ";
		                if ( !empty( $categoryid ) && $categoryid == $catname->term_id ) {
			                $catfront .= 'selected="selected" ';
		                }
		                $catfront .= 'value="';
	                } else {
		                $catfront = "\t<li>";
	                }
                }

                if ( $linkcount > 0 || ( $linkcount == 0 && $cat_has_children ) ) {
	                if ( $showonecatonly ) {
		                if ( 'AJAX' == $showonecatmode || empty( $showonecatmode ) ) {
			                if ( 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist ) {
				                $cattext = "<a href='#' onClick=\"showLinkCat" . $settings . "('" . $catname->term_id. "', '" . $settings . "', 1);return false;\" >";
			                } elseif ( 'dropdown' == $flatlist || 'dropdowndirect' == $flatlist ) {
				                $cattext = $catname->term_id;
			                }
		                } elseif ( 'HTMLGET' == $showonecatmode ) {
			                if ( 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist ) {
				                $cattext = "<a href='";
			                }

			                if ( !empty( $cattargetaddress ) && strpos( $cattargetaddress, '?' ) != false ) {
				                $cattext .= $cattargetaddress . '&cat_id=';
			                } elseif ( !empty( $cattargetaddress ) && strpos( $cattargetaddress, '?' ) == false ) {
				                $cattext .= $cattargetaddress . '?cat_id=';
			                } elseif ( empty( $cattargetaddress ) ) {
				                $cattext .= '?cat_id=';
			                }

			                $cattext .= $catname->term_id;

			                if ( 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist ) {
				                $cattext .= "'>";
			                }
		                } elseif ( 'HTMLGETSLUG' == $showonecatmode ) {
			                if ( 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist ) {
				                $cattext = "<a href='";
			                }

			                if ( !empty( $cattargetaddress ) && strpos( $cattargetaddress, '?' ) != false ) {
				                $cattext .= $cattargetaddress . '&cat=';
			                } elseif ( !empty( $cattargetaddress ) && strpos( $cattargetaddress, '?' ) == false ) {
				                $cattext .= $cattargetaddress . '?cat=';
			                } elseif ( empty( $cattargetaddress ) ) {
				                $cattext .= '?cat=';
			                }

			                $cattext .= $catname->slug;

			                if ( 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist ) {
				                $cattext .= "'>";
			                }
		                }  elseif ( 'HTMLGETCATNAME' == $showonecatmode ) {
			                if ( 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist ) {
				                $cattext = "<a href='";
			                }

			                /* if ( !empty( $cattargetaddress ) && strpos( $cattargetaddress, '?' ) != false ) {
								$cattext .= $cattargetaddress . '&catname=';
							} elseif ( !empty( $cattargetaddress ) && strpos( $cattargetaddress, '?' ) == false ) {
								$cattext .= $cattargetaddress . '?catname=';
							} elseif ( empty( $cattargetaddress ) ) {
								$cattext .= '?catname=';
							} */

			                if ( !empty( $_GET ) ) {
				                $get_array = $_GET;
			                } else {
				                $get_array = array();
			                }

			                $get_array['catname'] = urlencode( $catname->name );
			                $get_query = add_query_arg( $get_array, $cattargetaddress );

			                $cattext .= $get_query;

			                if ( 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist ) {
				                $cattext .= "'>";
			                }
		                } elseif ( 'HTMLGETPERM' == $showonecatmode ) {
			                if ( 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist ) {
				                $cattext = "<a href='";
			                }

			                $cattext .= '/' . $rewritepage . '/' . $catname->slug;

			                if ( 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist ) {
				                $cattext .= "'>";
			                }
		                }
	                } else if ( $catanchor ) {
		                if ( !$pagination ) {
			                if ( 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist ) {
				                $cattext = '<a href="';
			                }

			                $cattext .= '#' . $catname->slug;

			                if ( 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist ) {
				                $cattext .= '">';
			                }
		                } elseif ( $pagination ) {
			                if ( 0 == $linksperpage || empty( $linksperpage ) ) {
				                $linksperpage = 5;
			                }

			                $pageposition = ( $linkcount + 1 ) / $linksperpage;
			                $ceilpageposition = ceil( $pageposition );
			                if ( 0 == $ceilpageposition && !isset( $_GET['linkresultpage'] ) ) {
				                if ( 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist ) {
					                $cattext = '<a href="';
				                }

				                $cattext .= get_permalink() . '#' . $catname->slug;

				                if ( 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist ) {
					                $cattext .= '">';
				                }
			                } else {
				                if ( 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist ) {
					                $cattext = '<a href="';
				                }

				                $cattext .= '?linkresultpage=' . ( $ceilpageposition == 0 ? 1 : $ceilpageposition ) . '#' . $catname->slug;

				                if ( 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist ) {
					                $cattext .= '">';
				                }
			                }
		                }
	                } else {
		                $cattext = '';
	                }

	                if ( 'dropdown' == $flatlist || 'dropdowndirect' == $flatlist ) {
		                $cattext .= '">';
	                }
                } else {
	                $cattext .= '<span class="emptycat">';
                }

                if ( !$showcategorydescheaders || ( $showcategorydescheaders && ( 'right' == $catlistdescpos || empty( $catlistdescpos ) ) ) ) {
	                if ( 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist ) {
		                $catitem .= '<div class="linkcatname">';
	                } elseif ( 'dropdown' == $flatlist || 'dropdowndirect' == $flatlist ) {
		                $space_str = "&nbsp;&nbsp;&nbsp;";
		                $catitem .= str_repeat( $space_str, $level );
	                }
	                $catitem .= $catname->name;
	                if ( $showcatlinkcount && ( $linkcount != 0 || ( $linkcount == 0 && !$cat_has_children ) ) ) {
		                $catitem .= '<span class="linkcatcount"> (' . $linkcount . ')</span>';
	                }
	                if ( 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist ) {
		                $catitem .= '</div>';
	                }
                }

                if ( $showcategorydescheaders ) {
                    $catname->description = esc_html( $catname->description );
                    $catname->description = str_replace( '[', '<', $catname->description );
                    $catname->description = str_replace( ']', '>', $catname->description );
                    $catname->description = str_replace( "&quot;", '"', $catname->description );
                    $catitem .= '<span class="linkcatdesc">' . $catname->description . '</span>';
                }

                if ( $showcategorydescheaders && 'left' == $catlistdescpos ) {
	                if ( 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist ) {
		                $catitem .= '<div class="linkcatname">';
	                } elseif ( 'dropdown' == $flatlist || 'dropdowndirect' == $flatlist ) {
	                	$space_str = "&nbsp;&nbsp;&nbsp;";
	                	$catitem .= str_repeat( $space_str, $level );
	                }
	                $catitem .= $catname->name;
	                if ( $showcatlinkcount && ( $linkcount != 0 || ( $linkcount == 0 && !$cat_has_children ) ) ) {
		                $catitem .= '<span class="linkcatcount"> (' . $linkcount . ')</span>';
	                }
	                $catitem .= '</div>';
                }

                if ( $linkcount > 0 ) {
	                if ( ( $catanchor || $showonecatonly ) && 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist ) {
		                $catitem .= '</a>';
	                } /* else {
		                $catitem .= '</span>';
	                } */
                }

                $output .= ( $catfront . $cattext . $catitem );

	            if ( 'dropdown' == $flatlist || 'dropdowndirect' == $flatlist && ( $linkcount > 0 || !$hide_if_empty )) {
		            $output .= "</option>\n";
	            }

                if ( $cat_has_children ) {
	                $output .= RenderLinkLibraryCategories( $LLPluginClass, $generaloptions, $libraryoptions, $settings, $catname->term_id, $level + 1 );
                }

	            $catterminator = '';
                if ( $level == 0 ) {
	                if ( 'table' == $flatlist ) {
		                $catterminator = "</td>\n";
	                } elseif ( 'unordered' == $flatlist ) {
		                $catterminator = "</li>\n";
	                }
                } elseif ( 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist ) {
	                $catterminator = "</li>\n";
                }

                $output .= ( $catterminator );

                if ( $num_columns > 0 && 'table' == $flatlist && ( 0 == $countcat % $num_columns ) && $level == 0 ) {
                    $output .= "</tr>\n";
                }
            }

            if ( $num_columns > 0 && 'table' == $flatlist && ( 3 == $countcat % $num_columns ) && $level == 0) {
                $output .= "</tr>\n";
            }

            if ( $level == 0 ) {
	            if ( 'table' == $flatlist && $link_categories ) {
		            $output .= "</table>\n";
	            } elseif ( 'unordered' == $flatlist && $link_categories ) {
		            $output .= "</ul>\n";
	            } elseif ( ( 'dropdown' == $flatlist || 'dropdowndirect' == $flatlist ) && $link_categories ) {
		            $output .= "</select>\n";
		            if ( 'dropdown' == $flatlist ) {
			            $output .= "<button type='button' onclick='showcategory()'>" . __('Go!', 'link-library') . "</button>";
		            }
		            $output .= '</form>';
	            }

	            $output .= "</div>\n";
            } elseif ( 'dropdown' != $flatlist && 'dropdowndirect' != $flatlist && $link_categories ) {
            	$output .= '</ul>';
            }

            if ( $showonecatonly && ( 'AJAX' == $showonecatmode || empty( $showonecatmode ) ) ) {
                if ( empty( $loadingicon ) ) {
                    $loadingicon = '/icons/Ajax-loader.gif';
                }

                $output .= "<div class='contentLoading' id='contentLoading" . $settings . "' style='display: none;'><img src='" . plugins_url( $loadingicon, __FILE__ ) . "' alt='Loading data, please wait...'></div>\n";
            }

            if ( 0 == $level && ( 'dropdown' == $flatlist || 'dropdowndirect' == $flatlist ) ) {
                $output .= "<SCRIPT TYPE='text/javascript'>\n";
                $output .= "\tfunction showcategory(){\n";

                if ( $showonecatonly && ( 'AJAX' == $showonecatmode || empty( $showonecatmode ) ) ) {
                    $output .= 'catidvar = document.catselect.catdropdown.options[document.catselect.catdropdown.selectedIndex].value;';
                    $output .= "showLinkCat" . $settings . "(catidvar, '" . $settings . "', 1);return false; }";
                } else {
                    $output .= "\t\tlocation=\n";
                    $output .= "document.catselect.catdropdown.options[document.catselect.catdropdown.selectedIndex].value }\n";
                }
                $output .= "</SCRIPT>\n";
            }
        } else {
            $output .= '<div>' . __('No categories found', 'link-library') . '.</div>';
        }

        if ( 0 == $level ) {
	        $output .= "\n<!-- End of Link Library Categories Output -->\n\n";
        }

    }
    return $output;
}