<?php

add_action('admin_menu', 'name_directory_register_menu_entry');
add_action('wp_ajax_name_directory_ajax_names', 'name_directory_names');
add_action('wp_ajax_name_directory_switch_name_published_status', 'name_directory_ajax_switch_name_published_status');


/**
 * Add a menu entry on options
 */
function name_directory_register_menu_entry()
{
    add_menu_page(
        __('Name Directory', 'name-directory'),
        __('Name Directory', 'name-directory'),
        'manage_options',
        'name-directory',
        'name_directory_options',
        'dashicons-index-card',
        120);

    add_submenu_page(
        'name-directory',
        __('Add directory', 'name-directory'),
        __('Add directory', 'name-directory'),
        'manage_options',
        'admin.php?page=name-directory&sub=new-directory');
}


/**
 * This is a little router for the
 * name-directory plugin
 */
function name_directory_options()
{
    if (!current_user_can('manage_options'))
    {
        wp_die( __('You do not have sufficient permissions to access this page.', 'name-directory') );
    }

    $sub_page = '';
    if(! empty($_GET['sub']))
    {
        $sub_page = $_GET['sub'];
    }
    switch($sub_page)
    {
        case 'manage-directory':
            name_directory_names();
            break;
        case 'edit-directory':
            name_directory_edit();
            break;
        case 'new-directory':
            name_directory_edit('new');
            break;
        case 'import':
            name_directory_import();
            break;
        case 'export':
            name_directory_export();
            break;
        default:
            name_directory_show_list();
            break;
    }

}


/**
 * Show the list of directories and all of the
 * links to manage the directories
 */
function name_directory_show_list()
{
    global $wpdb;
    global $name_directory_table_directory;
    global $name_directory_table_directory_name;

    if(! empty($_GET['delete_dir']) && is_numeric($_GET['delete_dir']))
    {
        $name = $wpdb->get_var(sprintf("SELECT `name` FROM %s WHERE id=%d", $name_directory_table_directory, $_GET['delete_dir']));
        $wpdb->delete($name_directory_table_directory, array('id' => $_GET['delete_dir']), array('%d'));
        $wpdb->delete($name_directory_table_directory_name, array('directory' => $_GET['delete_dir']), array('%d'));
        echo "<div class='updated'><p><strong>"
            . sprintf(__('Name directory %s and all entries deleted', 'name-directory'), "<i>" . $name . "</i>")
            . "</strong></p></div>";
    }

    $wp_file = admin_url('admin.php');
    $wp_page = $_GET['page'];
    $wp_url_path = sprintf("%s?page=%s", $wp_file, $wp_page);
    $wp_new_url = sprintf("%s&sub=%s", $wp_url_path, 'new-directory');


    echo '<div class="wrap">';
    echo "<h2>"
        . __('Name Directory management', 'name-directory')
        . " <a href='" . $wp_new_url . "' class='add-new-h2'>" . __('Add directory', 'name-directory') . "</a>"
        . "</h2>";

    if(! empty($_POST['mode']) && ! empty($_POST['dir_id']))
    {
        $wpdb->update(
            $name_directory_table_directory,
            array(
                'name'                    => $_POST['name'],
                'description'             => $_POST['description'],
                'show_title'              => $_POST['show_title'],
                'show_description'        => $_POST['show_description'],
                'show_submit_form'        => $_POST['show_submit_form'],
                'show_search_form'        => $_POST['show_search_form'],
                'show_submitter_name'     => $_POST['show_submitter_name'],
                'show_line_between_names' => $_POST['show_line_between_names'],
                'show_all_names_on_index' => $_POST['show_all_names_on_index'],
                'show_all_index_letters'  => $_POST['show_all_index_letters'],
                'jump_to_search_results'  => $_POST['jump_to_search_results'],
                'nr_columns'              => $_POST['nr_columns'],
                'nr_most_recent'          => intval($_POST['nr_most_recent']),
                'nr_words_description'    => intval($_POST['nr_words_description']),
            ),
            array('id' => intval($_POST['dir_id']))
        );

        echo "<div class='updated'><p>"
            . sprintf(__('Directory %s updated.', 'name-directory'), "<i>" . esc_sql($_POST['name']) . "</i>")
            . "</p></div>";

        unset($_GET['dir_id']);
    }
    elseif(! empty($_POST['mode']) && $_POST['mode'] == "new")
    {
        $wpdb->insert(
            $name_directory_table_directory,
            array(
                'name'                    => $_POST['name'],
                'description'             => $_POST['description'],
                'show_title'              => $_POST['show_title'],
                'show_description'        => $_POST['show_description'],
                'show_submit_form'        => $_POST['show_submit_form'],
                'show_search_form'        => $_POST['show_search_form'],
                'show_submitter_name'     => $_POST['show_submitter_name'],
                'show_line_between_names' => $_POST['show_line_between_names'],
                'show_all_names_on_index' => $_POST['show_all_names_on_index'],
                'show_all_index_letters'  => $_POST['show_all_index_letters'],
                'jump_to_search_results'  => $_POST['jump_to_search_results'],
                'nr_columns'              => $_POST['nr_columns'],
                'nr_most_recent'          => $_POST['nr_most_recent'],
                'nr_words_description'     => $_POST['nr_words_description'],
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d')
        );

        echo "<div class='updated'><p>"
            . sprintf(__('Directory %s created.', 'name-directory'), "<i>" . esc_sql($_POST['name']) . "</i>")
            . "</p></div>";
    }

    $directories = $wpdb->get_results("SELECT * FROM $name_directory_table_directory");
    $num_directories = $wpdb->num_rows;
    $plural = ($num_directories==1)?__('name directory', 'name-directory'):__('name directories', 'name-directory');

    echo "<p>"
        . sprintf(__('You currently have %d %s.', 'name-directory'), $num_directories, $plural)
        . "</p>";
    ?>

    <table class="wp-list-table widefat fixed name-directory" cellspacing="0">
        <thead><?php name_directory_render_admin_overview_table_headerfooter(); ?></thead>

        <tbody>
            <?php
            foreach ( $directories as $directory )
            {
                $entries = $wpdb->get_var(sprintf("SELECT COUNT(`id`) FROM %s WHERE directory=%d", $name_directory_table_directory_name, $directory->id));
                $unpublished = $wpdb->get_var(sprintf("SELECT COUNT(`id`) FROM %s WHERE directory=%d AND `published` = 0", $name_directory_table_directory_name, $directory->id));
                echo sprintf("
                <tr class='type-page status-publish hentry alternate iedit author-self' valign='top'>
                    <th scope='col'>&nbsp;</th>
                    <td class='post-title page-title column-title' style='padding-left: 0;'>
                        <strong><a class='row-title' href='" . $wp_url_path . "&sub=manage-directory&dir=%d' title='%s'>%s</a>
                        <span style='font-weight: normal;'>&nbsp;%s</span></strong>
                        <div class='locked-info'>&nbsp;</div>
                        <div class='row-actions'>
                               <span class='manage'><a href='" . $wp_url_path . "&sub=manage-directory&dir=%d' title='%s'>%s</a>
                             | </span><span><a href='" . $wp_url_path . "&sub=manage-directory&dir=%d#anchor_add_name' title='%s'>%s</a>
                             | </span><span><a href='" . $wp_url_path . "&sub=edit-directory&dir=%d' title='%s'>%s</a>
                             | </span><span><a href='" . $wp_url_path . "&sub=import&dir=%d' title='%s'>%s</a>
                             | </span><span><a href='" . $wp_url_path . "&sub=export&dir=%d' title='%s'>%s</a>
                             | </span><span class='view'><a class='toggle-info' data-id='%s' href='" . $wp_url_path . "&sub=manage-directory&dir=%d#shortcode' title='%s'>%s</a></span>
                             | </span><span class='trash'><a class='namedirectory_confirmdelete submitdelete' href='" . $wp_url_path . "&delete_dir=%d' title=%s'>%s</a>
                        </div>
                    </td>
                    <td>
                        &nbsp; <strong title='%s'>%d</strong>
                        <br /><br />&nbsp;
                    </td>
                    <td>%d</td>
                    <td>%d</td>
                    </tr>",

                    $directory->id, $directory->name, $directory->name,
                    substr($directory->description, 0, 70),
                    $directory->id, __('Add, edit and remove names', 'name-directory'), __('Manage names', 'name-directory'),
                    $directory->id, __('Go to the add-name-form on the Manage page', 'name-directory'), __('Add name', 'name-directory'),
                    $directory->id, __('Edit name, description and appearance settings', 'name-directory'), __('Settings', 'name-directory'),
                    $directory->id, __('Import entries for this directory by uploading a .csv file', 'name-directory'), __('Import', 'name-directory'),
                    $directory->id, __('Download the contents of this directory as a .csv file', 'name-directory'), __('Export', 'name-directory'),
                    $directory->id, $directory->id, __('Show the copy-paste shortcode for this directory', 'name-directory'), __('Shortcode', 'name-directory'),
                    $directory->id, __('Permanently remove this name directory', 'name-directory'), __('Delete', 'name-directory'),

                    __('Number of names in this directory', 'name-directory'),
                    $entries,
                    ($entries - $unpublished),
                    $unpublished
                    );
                    echo sprintf("
                    <tr id='embed_code_%s' style='display: none;'>
                        <td>&nbsp;</td>
                        <td align='right'>%s</td>
                        <td colspan='5'>
                            <input value='[namedirectory dir=\"%s\"]' type='text' size='25' id='title'
                                style='text-align: center; padding: 8px 5px;' />
                        </td>
                    </tr>
                    <tr style='display: none;'><td colspan='7'>&nbsp;</td></tr>",
                    $directory->id,
                    __('To show your directory on your website, use the shortcode on the right.', 'name-directory') . '<br />' .
                    __('Copy the code and paste it in a post or in a page.', 'name-directory') . '<br /><small>' .
                    __('If you want to start with a specific character, like "J", use [namedirectory dir="X" start_with="j"].', 'name-directory') . '</small>',
                    $directory->id);
            }
            ?>
        </tbody>

        <tfoot><?php name_directory_render_admin_overview_table_headerfooter(); ?></tfoot>
    </table>

    <script type='text/javascript'>
        jQuery(document).ready(function()
        {
            jQuery('.toggle-info').on('click', function(event)
            {
                event.preventDefault();
                var toggle_id = jQuery(this).attr('data-id');
                jQuery('#embed_code_' + toggle_id).toggle();
                return false;
            });

            jQuery('.namedirectory_confirmdelete').click(function(){
                delete_directory = confirm('<?php echo __('Are you sure you want to delete this name directory?', 'name-directory') ?>');
                if(delete_directory == false)
                {
                    return false;
                }
            });

        });
    </script>
<?php
}


/**
 * A double purpose function for editing a name-directory and
 * creating a new directory.
 * @param string $mode
 */
function name_directory_edit($mode = 'edit')
{
    if (!current_user_can('manage_options'))
    {
        wp_die( __('You do not have sufficient permissions to access this page.', 'name-directory') );
    }

    global $wpdb;
    global $name_directory_table_directory;

    $wp_file = admin_url('admin.php');
    $wp_page = $_GET['page'];
    $wp_sub  = $_GET['sub'];
    $overview_url = sprintf("%s?page=%s", $wp_file, $wp_page, $wp_sub);
    $wp_url_path = sprintf("%s?page=%s", $wp_file, $wp_page);

    $directory_id = 0;
    if(! empty($_GET['dir']))
    {
        $directory_id = intval($_GET['dir']);
    }
    $directory = $wpdb->get_row("SELECT * FROM " . $name_directory_table_directory . " WHERE `id` = " . $directory_id, ARRAY_A);

    echo '<div class="wrap">';
    if($mode == "new")
    {
        $table_heading  = __('Create new name directory', 'name-directory');
        $button_text    = __('Create', 'name-directory');
        echo "<h2>" . __('Create new name directory', 'name-directory') . "</h2>";
        echo "<p>" . __('Complete the form below to create a new name directory.', 'name-directory');
    }
    else
    {
        $table_heading  = __('Edit this directory', 'name-directory');
        $button_text    = __('Save Changes', 'name-directory');
        echo "<h2>" . __('Edit name directory', 'name-directory') . "</h2>";
        echo "<p>"
            . sprintf(__('You are editing the name, description and settings of directory %s', 'name-directory'),
                $directory['name']);
    }
    echo " <a style='float: right;' href='" . $overview_url . "'>" . __('Back to the directory overview', 'name-directory') . "</a></p>";
    ?>

    <form name="add_name" method="post" action="<?php echo $wp_url_path; ?>">
        <table class="wp-list-table widefat" cellpadding="0">
            <thead>
            <tr>
                <th colspan="2">
                    <?php echo $table_heading; ?>
                    <input type="hidden" name="dir_id" value="<?php echo $directory_id; ?>">
                    <input type="hidden" name="mode" value="<?php echo $mode; ?>">
                </th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td width="29%"><?php echo __('Title', 'name-directory'); ?></td>
                <td width="70%"><input type="text" name="name" value="<?php echo $directory['name']; ?>" size="20" style="width: 100%;"></td>
            </tr>
            <tr>
                <td><?php echo __('Description', 'name-directory'); ?></td>
                <td><textarea name="description" rows="5" style="width: 100%;"><?php echo $directory['description']; ?></textarea></td>
            </tr>

            <?php
            $dir_boolean_settings = array(
                'show_title' => array(
                    'friendly_name' => __('Show title', 'name-directory'),
                    'description' => false,
                ),
                'show_description' => array(
                    'friendly_name' => __('Show description', 'name-directory'),
                    'description' => false,
                ),
                'show_submit_form' => array(
                    'friendly_name' => __('Submit form', 'name-directory'),
                    'description' => __('Visitors can submit suggestions', 'name-directory'),
                ),
                'show_submitter_name' => array(
                    'friendly_name' => __('Submitter name', 'name-directory'),
                    'description' => __('Show the name of the submitter', 'name-directory'),
                ),
                'show_search_form' => array(
                    'friendly_name' => __('Show search form', 'name-directory'),
                    'description' => false,
                ),
                'show_line_between_names' => array(
                    'friendly_name' => __('Show line between names', 'name-directory'),
                    'description' => false,
                ),
                'show_all_names_on_index' => array(
                    'friendly_name' => __('Show all names by default', 'name-directory'),
                    'description' => __('If no, user HAS to use the index before entries are shown', 'name-directory'),
                ),
                'show_all_index_letters' => array(
                    'friendly_name' => __('Show all letters on index', 'name-directory'),
                    'description' => __('If no, just A B D E are shown if there are no entries starting with C', 'name-directory'),
                ),
                'jump_to_search_results' => array(
                    'friendly_name' => __('Jump to Name Directory when searching', 'name-directory'),
                    'description' => __('On the front-end, jump to the Name Directory search box. Particularly useful if you have Name Directory on a long page or onepage websites', 'name-directory'),
                ),
            );

            $dir_options_settings = array(
                'nr_most_recent' => array(
                    'friendly_name' => __('Show most recent names', 'name-directory'),
                    'description' => __('If No, frontend will not show \'Latest\' option.', 'name-directory'),
                    'options' => array(0 => __('No', 'name-directory'), 3 => 3, 5 => 5, 10 => 10, 25 => 25, 50 => 50, 100 => 100)
                ),
                'nr_words_description' => array(
                    'friendly_name' => __('Limit amount of words in description', 'name-directory'),
                    'description' => __('Display a "read-more" link on the website if the description exceeds X characters.', 'name-directory'),
                    'options' => array(0 => __('No', 'name-directory'), 10 => 10, 20 => 20, 25 => 25, 50 => 50, 100 => 100)
                ),
                'nr_columns' => array(
                    'friendly_name' => __('Number of columns', 'name-directory'),
                    'description' => __('The number of (vertical) columns to display the names in', 'name-directory'),
                    'options' => array(1 => 1, 2 => 2, 3 => 3, 4 => 4)
                ),
            );


            foreach($dir_boolean_settings as $setting_name => $setting_props)
            {
                name_directory_render_admin_setting_boolean($directory, $setting_name, $setting_props['friendly_name'], $setting_props['description']);
            }

            foreach($dir_options_settings as $setting_name => $setting_props)
            {
                name_directory_render_admin_setting_options($directory, $setting_name, $setting_props['friendly_name'], $setting_props['description'], $setting_props['options']);
            }
            ?>
            <tr>
                <td>&nbsp;</td>
                <td>
                    <input type="submit" name="submit" class="button button-primary button-large"
                           value="<?php echo $button_text; ?>" />

                    <a class='button button-large' href='<?php echo $overview_url; ?>'>
                        <?php echo __('Cancel', 'name-directory'); ?>
                    </a>
                </td>
            </tr>
            </tbody>
        </table>
    </form>

<?php

}


/**
 * Handle the names in the name directory
 *  - Display all names
 *  - Edit names (ajax and 'oldskool' view)
 *  - Create new names
 */
function name_directory_names()
{
    if (!current_user_can('manage_options'))
    {
        wp_die( __('You do not have sufficient permissions to access this page.', 'name-directory') );
    }

    global $wpdb;
    global $name_directory_table_directory;
    global $name_directory_table_directory_name;

    if(! empty($_GET['delete_name']) && is_numeric($_GET['delete_name']))
    {
        $name = $wpdb->get_var(sprintf("SELECT `name` FROM %s WHERE id=%d", $name_directory_table_directory_name, $_GET['delete_name']));
        $wpdb->delete($name_directory_table_directory_name, array('id' => $_GET['delete_name']), array('%d'));
        echo "<div class='updated'><p>"
            . sprintf(__('Name %s deleted', 'name-directory'), "<i>" . $name . "</i>")
            . "</p></div>";
    }
    else if(! empty($_POST['name_id']))
    {
        $wpdb->update(
            $name_directory_table_directory_name,
            array(
                'name'          => stripslashes_deep($_POST['name']),
                'letter'        => name_directory_get_first_char($_POST['name']),
                'description'   => stripslashes_deep($_POST['description']),
                'published'     => $_POST['published'],
                'submitted_by'  => $_POST['submitted_by'],
            ),
            array('id' => intval($_POST['name_id']))
        );

        if($_POST['action'] == "name_directory_ajax_names")
        {
            echo '<p>';
            echo sprintf(__('Name %s updated', 'name-directory'), "<i>" . esc_sql($_POST['name']) . "</i>");
            echo '. <small><i>' . __('Will be visible when the page is refreshed.', 'name-directory') . '</i> ';
            echo ' <a href="">' . __('Refresh now', 'name-directory') . '</a></small>';
            echo '</p>';
            exit;
        }

        echo "<div class='updated'><p>"
            . sprintf(__('Name %s updated', 'name-directory'), "<i>" . esc_sql($_POST['name']) . "</i>")
            . "</p></div>";

        unset($_GET['edit_name']);
    }
    else if(! empty($_POST['name']))
    {
        $name_exists = name_directory_name_exists_in_directory($_POST['name'], $_POST['directory']);
        if($name_exists && $_POST['action'] == "name_directory_ajax_names")
        {
            echo '<p>';
            echo sprintf(__('Name %s was already on the list, so it was not added', 'name-directory'),
                                '<i>' . esc_sql($_POST['name']) . '</i>');
            echo '</p>';
            exit;
        }

        $wpdb->insert(
            $name_directory_table_directory_name,
            array(
                'directory'     => $_POST['directory'],
                'name'          => stripslashes_deep($_POST['name']),
                'letter'        => name_directory_get_first_char($_POST['name']),
                'description'   => stripslashes_deep($_POST['description']),
                'published'     => $_POST['published'],
                'submitted_by'  => $_POST['submitted_by'],
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s')
        );

        if($_POST['action'] == "name_directory_ajax_names")
        {
            echo '<p>';
            printf(__('New name %s added', 'name-directory'), '<i>' . esc_sql($_POST['name']) . '</i> ');
            echo '. <small><i>' . __('Will be visible when the page is refreshed.', 'name-directory') . '</i> ';
            echo ' <a href="">' . __('Refresh now', 'name-directory') . '</a></small>';
            echo '</p>';
            exit;
        }

        echo "<div class='updated'><p><strong>"
            . sprintf(__('New name %s added', 'name-directory'), "<i>" . esc_sql($_POST['name']) . "</i> ")
            . "</strong></p></div>";
    }
    else if($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        if($_POST['action'] == "name_directory_ajax_names")
        {
            echo '<p>' . __('Please fill in at least a name', 'name-directory') . '</p>';
            exit;
        }

        echo "<div class='error'><p><strong>"
            . __('Please fill in at least a name', 'name-directory')
            . "</strong></p></div>";
    }

    $directory_id = intval($_GET['dir']);

    $wp_file = admin_url('admin.php');
    $wp_page = $_GET['page'];
    $wp_sub  = $_GET['sub'];
    $overview_url = sprintf("%s?page=%s", $wp_file, $wp_page);
    $wp_url_path = sprintf("%s?page=%s&sub=%s&dir=%d", $wp_file, $wp_page, $wp_sub, $directory_id);

    $published_status = '0,1';
    $emphasis_class = 's_all';
    if(! empty($_GET['status']) && $_GET['status'] == 'published')
    {
        $published_status = '1';
        $emphasis_class = 's_published';
    }
    else if(! empty($_GET['status']) && $_GET['status'] == 'unpublished')
    {
        $published_status = '0';
        $emphasis_class = 's_unpublished';
    }

    $directory = $wpdb->get_row("SELECT * FROM " . $name_directory_table_directory . " WHERE `id` = " . $directory_id, ARRAY_A);
    $names = $wpdb->get_results(sprintf("SELECT * FROM %s WHERE `directory` = %d AND `published` IN (%s) ORDER BY `name` ASC",
        $name_directory_table_directory_name, $directory_id, $published_status));

    echo '<div class="wrap">';
    echo "<h2>" . sprintf(__('Manage names for %s', 'name-directory'), $directory['name']) . "</h2>";
    ?>

    <p>
        View:
        <a class='s_all' href='<?php echo $wp_url_path; ?>&status=all'><?php _e('all', 'name-directory'); ?></a> |
        <a class='s_published' href='<?php echo $wp_url_path; ?>&status=published'><?php _e('published', 'name-directory'); ?></a> |
        <a class='s_unpublished' href='<?php echo $wp_url_path; ?>&status=unpublished'><?php _e('unpublished', 'name-directory'); ?></a>

        <span style='float: right';>
            <a href='<?php echo $overview_url; ?>'><?php _e('Back to the directory overview', 'name-directory'); ?></a>
        </span>
    </p>

    <table class="wp-list-table widefat name_directory_names fixed" cellpadding="0">
        <thead>
        <tr>
            <th width="18%"><?php echo __('Name', 'name-directory'); ?></th>
            <th width="54%"><?php echo __('Description', 'name-directory'); ?></th>
            <th width="12%"><?php echo __('Submitter', 'name-directory'); ?></th>
            <th width="9%"><?php echo __('Published', 'name-directory'); ?></th>
            <th width="15%"><?php echo __('Manage', 'name-directory'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        if(empty($names))
        {
            echo sprintf("<tr class='empty-directory'><td colspan='5'>%s</td></tr>",
                __('Currently, there are no names in this directory..', 'name-directory'));
        }
        foreach($names as $name)
        {
            echo sprintf("
                <tr>
                    <td>%s</td><td>%s</td><td>%s</td><td><span title='%s' class='toggle_published' id='nid_%d' data-nameid='%d'>%s</span></td>
                    <td><a class='button button-primary button-small' href='" . $wp_url_path . "&edit_name=%d#anchor_add_form'>%s</a>
                        <a class='button button-small' href='" . $wp_url_path . "&delete_name=%d'>%s</a>
                    </td>
                </tr>",
                $name->name, html_entity_decode(stripslashes($name->description)), $name->submitted_by,
                __('Toggle published status', 'name-directory'), $name->id,
                $name->id, name_directory_yesno($name->published),
                $name->id, __('Edit', 'name-directory'),
                $name->id, __('Delete', 'name-directory'));
        }
        ?>
        </tbody>
    </table>

    <p>&nbsp;</p>

    <?php
    if(! empty($_GET['edit_name']))
    {
        $name = $wpdb->get_row(sprintf("SELECT * FROM `%s` WHERE `id` = %d",
            $name_directory_table_directory_name, $_GET['edit_name']), ARRAY_A);
        $table_heading = __('Edit a name', 'name-directory');
        $save_button_txt = __('Save name', 'name-directory');
    }
    else
    {
        $table_heading = __('Add a new name', 'name-directory');
        $save_button_txt = __('Add name', 'name-directory');
        $name = array('name' => null, 'description' => null, 'submitted_by' => null);
    }

    ?>
    <span style='float: right';>
        <a href='<?php echo $overview_url; ?>'><?php _e('Back to the directory overview', 'name-directory'); ?></a>
    </span>

    <p>&nbsp;</p>

    <div class="hidden" id="add_result"></div>

    <a name="anchor_add_form"></a>
    <form name="add_name" id="add_name_ajax" method="post" action="<?php echo $wp_url_path; ?>">
    <table class="wp-list-table widefat" cellpadding="0">
        <thead>
            <tr>
                <th width="18%"><?php echo $table_heading; ?>
                    <input type="hidden" name="directory" value="<?php echo $directory_id; ?>">
                    <?php
                    if(! empty($_GET['edit_name']))
                    {
                        echo '<input type="hidden" name="name_id" id="edit_name_id" value="' . intval($_GET['edit_name']) . '">';
                    }
                    ?>
                    <input type="hidden" name="action" value="0" id="add_form_ajax_submit" />
                </th>
                <th align="right">

                    <label id="input_compact" title="<?php echo __('Show the compact form, showing only the name, always published)', 'name-directory'); ?>">
                        <input type="radio" name="input_mode" />
                        <?php echo __('Quick add view', 'name-directory'); ?>
                    </label>
                    <label id="input_extensive" title="<?php echo __('Show the full form, which allows you to enter a description and submitter', 'name-directory'); ?>">
                        <input type="radio" name="input_mode" />
                        <?php echo __('Full add view', 'name-directory'); ?>
                    </label>

                </th>
            </tr>
        </thead>
        <tbody>
            <tr id="add_name">
                <td width="18%"><?php echo __('Name', 'name-directory'); ?></td>
                <td width="82%"><input type="text" name="name" value="<?php echo $name['name']; ?>" size="20" style="width: 100%;"></td>
            </tr>
            <tr id="add_description">
                <td><?php echo __('Description', 'name-directory'); ?></td>
                <td><textarea name="description" rows="5" style="width: 100%;"><?php echo stripslashes($name['description']); ?></textarea>
                    <small><strong><?php echo __('Please be careful!', 'name-directory'); ?></strong>
                        <?php echo __('HTML markup is allowed and will we printed on your website and in the WordPress admin.', 'name-directory'); ?></small></td>
            </tr>
            <tr id="add_published">
                <td><?php echo __('Published', 'name-directory'); ?></td>
                <td>
                    <input type="radio" name="published" id="published_yes" value="1" checked="checked">
                    <label for="published_yes"><?php echo __('Yes', 'name-directory') ?></label>

                    <input type="radio" name="published" id="published_no" value="0"
                        <?php
                        if(isset($name['published']) && empty($name['published']))
                        {
                            echo 'checked="checked"';
                        }?>>
                    <label for="published_no"><?php echo __('No', 'name-directory') ?></label>
                </td>
            </tr>
            <tr id="add_submitter">
                <td><?php echo __('Submitted by', 'name-directory'); ?></td>
                <td><input type="text" name="submitted_by" value="<?php echo $name['submitted_by']; ?>" size="20" style="width: 100%;"></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td>
                    <input type="submit" id="add_button" name="Submit" class="button button-primary button-large"
                           value="<?php echo $save_button_txt; ?>" />
                </td>
            </tr>
        </tbody>
    </table>
    </form>

    <?php
    name_directory_print_javascript($emphasis_class);
    name_directory_print_style();
}

/**
 * Import names from a csv file into directory
 */
function name_directory_import()
{
    if (!current_user_can('manage_options'))
    {
        wp_die( __('You do not have sufficient permissions to access this page.', 'name-directory') );
    }

    global $wpdb;
    global $name_directory_table_directory;
    global $name_directory_table_directory_name;

    $directory_id = intval($_GET['dir']);
    $import_success = false;

    if($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        $file = wp_import_handle_upload();

        if( isset($file['error']))
        {
            echo $file['error'];
            return;
        }

        $csv = array_map('str_getcsv', file($file['file']));

        wp_import_cleanup($file['id']);
        array_shift($csv);

        $names_error = 0;
        $names_imported = 0;
        $names_duplicate = 0;
        foreach($csv as $entry)
        {
            if(! $prepared_row = name_directory_prepared_import_row($entry))
            {
                continue;
            }

            if(name_directory_name_exists_in_directory($prepared_row['name'], $directory_id))
            {
                $names_duplicate++;
                continue;
            }

            $db_res = $wpdb->insert(
                $name_directory_table_directory_name,
                array(
                    'directory'     => $directory_id,
                    'name'          => stripslashes_deep($prepared_row['name']),
                    'letter'        => name_directory_get_first_char($prepared_row['name']),
                    'description'   => stripslashes_deep($prepared_row['description']),
                    'published'     => $prepared_row['published'],
                    'submitted_by'  => !empty($prepared_row['submitted_by']) ? $prepared_row['submitted_by'] : '',
                ),
                array('%d', '%s', '%s', '%s', '%d', '%s')
            );

            if($db_res === false)
            {
                $names_error++;
            }
            else
            {
                $names_imported++;
            }
        }

        $notice_class = 'updated';
        $import_success = true;
        $import_message = sprintf(__('Imported %d entries in this directory', 'name-directory'), $names_imported);

        if($names_imported === 0)
        {
            $notice_class = 'error';
            $import_success = false;
            $import_message = __('Could not import any names into Name Directory', 'name-directory');
        }

        if($names_error > 0)
        {
            $notice_class = 'error';
            $import_success = false;
            if($names_imported === 0)
            {
                $import_message .= "! ";
            }
            $import_message .= sprintf(__('There were %d names that produces errors with the WordPress database on import', 'name-directory'), $names_error);
        }

        if($names_duplicate > 0)
        {
            $ignored = (count($csv)==$names_duplicate)?__('all', 'name-directory'):$names_duplicate;
            echo '<div class="error" style="border-left: 4px solid #ffba00;"><p>'
                . sprintf(__('Ignored %s names, because they were duplicate (already in the directory)', 'name-directory'), $ignored)
                . '</p></div>';
        }
        elseif($names_imported === 0)
        {
            $import_message .= ', ' . __('please check your .csv-file', 'name-directory');
        }

        echo '<div class="' . $notice_class . '"><p>' . $import_message . '</p></div>';
    }

    $wp_file = admin_url('admin.php');
    $wp_page = $_GET['page'];
    $wp_sub  = $_GET['sub'];
    $overview_url = sprintf("%s?page=%s", $wp_file, $wp_page);
    $wp_url_path = sprintf("%s?page=%s&sub=%s&dir=%d", $wp_file, $wp_page, $wp_sub, $directory_id);
    $wp_ndir_path = sprintf("%s?page=%s&sub=%s&dir=%d", $wp_file, $wp_page, 'manage-directory', $directory_id);

    $directory = $wpdb->get_row("SELECT * FROM " . $name_directory_table_directory . " WHERE `id` = " . $directory_id, ARRAY_A);

    echo '<div class="wrap">';
    echo '<h2>' . sprintf(__('Import names for %s', 'name-directory'), $directory['name']) . '</h2>';
    echo '<div class="narrow"><p>';
    if(! $import_success && empty($names_duplicate))
    {
        echo __('Use the upload form below to upload a .csv-file containing all of your names (in the first column), description and submitter are optional.', 'name-directory') . ' ';
        echo '<h4>' . __('If you saved it from Excel or OpenOffice, please ensure that:', 'name-directory') . '</h4> ';
        echo '<ol><li>' . __('There is a header row (this contains the column names, the first row will NOT be imported)', 'name-directory');
        echo '</li><li>' . __('Fields are encapsulated by double quotes', 'name-directory');
        echo '</li><li>' . __('Fields are comma-separated', 'name-directory');
        echo '</li></ol>';
        echo '<h4>' . __('If uploading or importing fails, these are your options', 'name-directory') . ':</h4><ol><li>';
        echo sprintf(__('Please check out %s first and ensure your file is formatted the same.', 'name-directory'),
                '<a href="http://plugins.svn.wordpress.org/name-directory/assets/name-directory-import-example.csv" target="_blank">' .
                __('the example import file', 'name-directory') . '</a>') . '</li>';
        echo '<li>
                <a href="https://wiki.openoffice.org/wiki/Documentation/OOo3_User_Guides/Calc_Guide/Saving_spreadsheets#Saving_as_a_CSV_file">OpenOffice csv-export help</a>
              </li>
              <li>
                <a href="https://support.office.com/en-us/article/Import-or-export-text-txt-or-csv-files-e8ab9ff3-be8d-43f1-9d52-b5e8a008ba5c?CorrelationId=fa46399d-2d7a-40bd-b0a5-27b99e96cf68&ui=en-US&rs=en-US&ad=US#bmexport">Excel csv-export help</a>
              </li>
              <li>
                <a href="http://www.freefileconvert.com" target="_blank">' .
                __('Use an online File Convertor', 'name-directory') . '</a>
              </li><li>';
        echo sprintf(__('If everything else fails, you can always ask a question at the %s.', 'name-directory'),
                '<a href="https://wordpress.org/support/plugin/name-directory" target="_blank">' .
                __('plugin support forums', 'name-directory') . '</a>') . ' ';
        echo '</li></ol></p>';

        if(! function_exists('str_getcsv'))
        {
            echo '<div class="error"><p>';
            echo __('Name Directory Import requires PHP 5.3, you seem to have in older version. Importing names will not work for your website.', 'name-directory');
            echo '</p></div>';
        }

        echo '<h3>' . __('Upload your .csv-file', 'name-directory') . '</h3>';
        wp_import_upload_form($wp_url_path);
    }
    echo '</div></div>';
    echo '<a href="' . $wp_ndir_path . '">' . sprintf(__('Back to %s', 'name-directory'), '<i>' . $directory['name'] . '</i>') . '</a>';
    echo ' | ';
    echo '<a href="' . $overview_url . '">' . __('Go to Name Directory Overview', 'name-directory') . '</a>';
}


/**
 * Page to export names from a directory file as a .csv-file
 */
function name_directory_export()
{
    if (!current_user_can('manage_options'))
    {
        wp_die( __('You do not have sufficient permissions to access this page.', 'name-directory') );
    }

    global $wpdb;
    global $name_directory_table_directory;

    $directory = $wpdb->get_row("SELECT * FROM " . $name_directory_table_directory . " WHERE `id` = " . intval($_GET['dir']), ARRAY_A);

    $names = name_directory_get_directory_names($directory);

    echo '<table id="export_names" class="hidden"><thead><tr><th>name</th><th>description</th><th>submitter</th></tr></thead><tbody>';
    foreach($names as $entry)
    {
        echo '<tr><td>' . $entry['name'] . '</td><td>' . html_entity_decode(stripslashes($entry['description'])) . '</td><td>' . $entry['submitted_by'] . '</td></tr>';
    }
    echo '</tbody></table>';

    /* Notify the user of possible not-working export functionality */
    if(stripos($_SERVER['HTTP_USER_AGENT'], 'Chrome') === false && stripos($_SERVER['HTTP_USER_AGENT'], 'Firefox') === false)
    {
        echo '<div class="notice notice-warning"><p>';
        echo __('Name Directory Export works best in Mozilla Firefox, Google Chrome and Internet Explorer 10+.', 'name-directory') . ' ';
        echo __('If you encounter problems (or it does not export) in Internet Explorer or Microsoft Edge, please try another browser.', 'name-directory');
        echo '</div>';
    }

    echo '<div class="wrap">';
    echo '<h2>' . sprintf(__('Export directory %s', 'name-directory'), $directory['name']) . '</h2>';
    echo '<div class="narrow"><p>';
    echo __('Click the Export button to download a .csv file with the contents of your directory.', 'name-directory');
    echo '</p><p><a href="#" id="export_name_directory_names_button" style="text-decoration:none;color:#000;background-color:#ddd;border:1px solid #ccc;padding:8px;">' . __('Export', 'name-directory') . '</a></p>';
    echo '<a href="' . admin_url('admin.php') . '?page=name-directory">' . __('Go to Name Directory Overview', 'name-directory') . '</a>';

    name_directory_print_export_javascript();
}


/**
 * Proxy for the AJAX request to switch published-statusses
 * No params, assumes POST
 */
function name_directory_ajax_switch_name_published_status()
{
    $name_id = intval($_POST['name_id']);
    if(! empty($name_id))
    {
        echo name_directory_switch_name_published_status($name_id);
        exit;
    }

    echo 'Error!';
    exit;
}


/**
 * Print the Style rules by this plugin
 */
function name_directory_print_style()
{
    $style = '

    <style>
    table.name_directory_names td
    {
        border-bottom: 1px solid #F0F0F0;
    }
    .toggle_published:hover {
        cursor: pointer;
    }
    </style>';

    echo $style;
}


/**
 * Print the Javascripts needed by this plugin
 * @param string $emphasis_class
 */
function name_directory_print_javascript($emphasis_class = '')
{
    $js = '

    <script type="text/javascript">
        /* Save a named preference to a cookie */
        function savePreference(name, value)
        {
            var expires = "";
            document.cookie = name+"="+value+expires+"; path=/";
        }

        /* Read the named preference from cookie */
        function readPreference(name)
        {
            var nameEQ = name + "=";
            var ca = document.cookie.split(";");
            for(var i=0;i < ca.length;i++) {
                var c = ca[i];
                while (c.charAt(0)==" ") c = c.substring(1,c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
            }
            return null;
        }

        jQuery(document).ready(function()
        {
            jQuery("#input_compact").on("click", function(e)
            {
                jQuery("#published_yes").attr("checked", "checked");
                jQuery("#add_description, #add_published, #add_submitter").hide();
                savePreference("wp-plugin-nd-add_form", "compact");
            });

            jQuery("#input_extensive").on("click", function(e)
            {
                jQuery("#add_description, #add_published, #add_submitter").show();
                savePreference("wp-plugin-nd-add_form", "extensive");
            });

            var pref = readPreference("wp-plugin-nd-add_form");
            if(pref != null)
            {
                jQuery("#input_" + pref).trigger("click");
                if(! window.location.hash)
                {
                    jQuery("html, body").animate({scrollTop:0}, 1);
                }
            }

            jQuery("#add_form_ajax_submit").val("name_directory_ajax_names");

            jQuery("#add_name_ajax").on("submit", function(e)
            {
                var form_data = jQuery(this).serialize();

                e.preventDefault();

                jQuery("#add_button").attr("disabled", "disabled");

                jQuery.ajax({
                    url: "admin-ajax.php",
                    type: "POST",
                    data: form_data,
                    success: function(data)
                    {
                        jQuery("#add_result").addClass("updated").slideDown().html(data);
                        jQuery("#add_name_ajax input[type=text], #add_name_ajax textarea, #edit_name_id").val("");
                    },
                    error: function(data)
                    {
                        window.location.reload();
                    },
                    complete: function(data)
                    {
                        jQuery("#add_button").removeAttr("disabled");
                    }
                });

                return false;
            });

            jQuery(".toggle_published").on("click", function(e)
            {
                name_id = jQuery(this).attr("data-nameid");
                update_ref = jQuery(this).attr("id");

                jQuery(this).html("<div class=\'spinner\' style=\'display:block;float:left;\'></div>");

                jQuery.ajax({
                    url: "admin-ajax.php",
                    type: "POST",
                    data: { action: "name_directory_switch_name_published_status", name_id: name_id }
                }).done(function(status)
                {
                    jQuery("#" + update_ref).html(status);
                });
            });
        });
    </script>';

    if(! empty($emphasis_class))
    {
        $js .= "<script>jQuery('." . $emphasis_class . "').css('font-weight', 'bold');</script>";
    }

    if(! empty($_GET['edit_name']))
    {
        $js .= "<script>jQuery(document).ready(function(){
                    jQuery('#input_extensive').trigger('click');
                });</script>";
    }

    print $js;
}


/**
 * Print some Javascript which helps on exporting CSV files
 * From: https://jsfiddle.net/mnsinger/65hqxygo/
 * TODO: I really should get around to use .js files :-)
 */
function name_directory_print_export_javascript() {
    echo <<<JS
    <script type="text/javascript">
        function exportTableToCSV(table, filename) {
        
            var rows = table.find('tr:has(td),tr:has(th)');
        
            // Temporary delimiter characters unlikely to be typed by keyboard
            // This is to avoid accidentally splitting the actual contents
            tmpColDelim = String.fromCharCode(11), // vertical tab character
            tmpRowDelim = String.fromCharCode(0), // null character
    
            // actual delimiter characters for CSV format
            colDelim = '","',
            rowDelim = '"\\r\\n"',
    
            // Grab text from table into CSV formatted string
            csv = '"' + rows.map(function (i, row) {
                var row = jQuery(row), cols = row.find('td,th');
    
                return cols.map(function (j, col) {
                    var col = jQuery(col), text = col.text();
                    
                    return text.replace(/"/g, '""'); // escape double quotes
    
                }).get().join(tmpColDelim);
    
            }).get().join(tmpRowDelim).split(tmpRowDelim).join(rowDelim).split(tmpColDelim).join(colDelim) + '"';

            // Data URI
            csvData = 'data:application/csv;charset=utf-8,' + encodeURIComponent(csv);
    
            if (window.navigator.msSaveBlob) { // IE 10+
                window.navigator.msSaveOrOpenBlob(new Blob([csv], {type: "text/plain;charset=utf-8;"}), "csvname.csv")
            } 
            else {
                jQuery(this).attr({ 'download': filename, 'href': csvData, 'target': '_blank' }); 
            }
        }
    
        jQuery(document).ready(function()
        {
            // This must be an a-element hyperlink, so it can download 'data:'-uri's
            jQuery("#export_name_directory_names_button").on('click', function (event)
            {
                exportTableToCSV.apply(this, [jQuery('#export_names'), 'name_directory_export.csv']);
            });
        });
    </script>
JS;

}