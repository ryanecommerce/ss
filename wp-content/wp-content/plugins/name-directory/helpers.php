<?php
/**
 * This file is part of the NameDirectory plugin for WordPress
 */



/**
 * Return the first character of a word,
 * or hashtag, may the word begin with a number
 * @param $name
 * @return string
 */
function name_directory_get_first_char($name)
{
    $first_char = mb_strtoupper(mb_substr($name, 0, 1));
    if(is_numeric($first_char))
    {
        $first_char = '#';
    }

    return $first_char;
}


/**
 * Prepare an associative array to be used for the csv importer
 * @param array $row (csv-row)
 * @param int $published (optional)
 * @return array|bool
 */
function name_directory_prepared_import_row($row, $published=1)
{
    // Don't continue when there is no name to add (first column in csv-row)
    if(empty($row[0]))
    {
        return false;
    }

    $row_props = array('name', 'description', 'submitted_by');
    $prepared_row = array('published' => $published);
    foreach($row_props as $index=>$prop)
    {
        if(! empty($row[$index]))
        {
            $prepared_row[$prop] = $row[$index];
        }
    }

    return $prepared_row;
}


/**
 * Return localized yes or no based on a variable
 * @param $var
 * @return string
 */
function name_directory_yesno($var)
{
    if(! empty($var))
    {
        return __('Yes', 'name-directory');
    }

    return __('No', 'name-directory');
}


/**
 * Switches the published state of a name and returns the human readable value
 * @param (numeric) $name_id
 * @return string
 */
function name_directory_switch_name_published_status($name_id)
{
    global $wpdb;
    global $name_directory_table_directory_name;

    $wpdb->query($wpdb->prepare("UPDATE `%s` SET `published`=1 XOR `published` WHERE id=%d",
        esc_sql($name_directory_table_directory_name),
        intval($name_id)));
    sleep(0.1);

    return name_directory_yesno($wpdb->get_var(sprintf("SELECT `published` FROM `%s` WHERE id=%d",
        $name_directory_table_directory_name, intval($name_id))));
}


/**
 * Check if a given name already exists in a Name Directory
 * @param $name
 * @param $directory
 * @return bool
 */
function name_directory_name_exists_in_directory($name, $directory)
{
    global $wpdb;
    global $name_directory_table_directory_name;

    $wpdb->get_results(sprintf("SELECT 1 FROM `%s` WHERE `name` = '%s' AND `directory` = %d",
        $name_directory_table_directory_name, esc_sql($name), intval($directory)));

    return (bool)$wpdb->num_rows;
}


/**
 * Construct a plugin URL
 * @param string $index
 * @param null $exclude
 * @return string
 */
function name_directory_make_plugin_url($index = 'name_directory_startswith', $exclude = null)
{
    $url = array();
    $parsed = parse_url($_SERVER['REQUEST_URI']);
    if(! empty($parsed['query']))
    {
        parse_str($parsed['query'], $url);
    }

    if(! empty($exclude))
    {
        unset($url[$exclude]);
    }

    unset($url[$index]);
    unset($url['page_id']);
    $paste_char = '?';
    if(strpos(get_permalink(), '?') !== false)
    {
        $paste_char = '&';
    }
    $url[$index] = '';

    return get_permalink() . $paste_char . http_build_query($url);
}


/**
 * Get the names of given directory, maybe only with the char?
 * @param $directory
 * @param array $name_filter
 * @return mixed
 */
function name_directory_get_directory_names($directory, $name_filter = array())
{
    global $wpdb;
    global $name_directory_table_directory_name;
    $sql_filter = "";
    $limit = "";
    $order_by = "ORDER BY `letter`, `name` ASC";

    if(! empty($name_filter['character']))
    {
        $sql_filter .= " AND `letter`='" . $name_filter['character'] . "' ";
    }

    if(! empty($directory['show_description']) && ! empty($name_filter['containing']))
    {
        $sql_filter .= " AND (`name` LIKE '%" . $name_filter['containing'] . "%' OR `description` LIKE '%" . $name_filter['containing'] . "%') ";
    }
    elseif(! empty($name_filter['containing']))
    {
        $sql_filter .= " AND `name` LIKE '%" . $name_filter['containing'] . "%' ";
    }

    if(! empty($name_filter['character']) && $name_filter['character'] == 'latest')
    {
        $sql_filter = "";
        $order_by = "ORDER BY `id` DESC";
        $limit = " LIMIT " . $directory['nr_most_recent'];
    }


    $names = $wpdb->get_results(sprintf("
		SELECT *
		FROM %s
		WHERE `directory` = %d AND `published` = 1
		%s
		%s %s",
        esc_sql($name_directory_table_directory_name),
        esc_sql($directory['id']),
        $sql_filter,
        $order_by,
        $limit),
        ARRAY_A
    );

    return $names;
}


/**
 * Get the directory with the supplied ID
 * @param $id
 * @return mixed
 */
function name_directory_get_directory_properties($id)
{
    global $wpdb;
    global $name_directory_table_directory;

    $directory = $wpdb->get_row(sprintf("SELECT * FROM %s WHERE `id` = %d",
        esc_sql($name_directory_table_directory),
        esc_sql($id)), ARRAY_A);

    return $directory;
}


/**
 * Get names in a specified directory (specified by ID)
 * @param $id
 * @return mixed
 */
function name_directory_get_directory_start_characters($id)
{
    global $wpdb;
    global $name_directory_table_directory_name;

    $characters = $wpdb->get_col(sprintf("SELECT DISTINCT `letter` FROM %s WHERE `directory` = %d ORDER BY `letter` ASC",
        esc_sql($name_directory_table_directory_name),
        esc_sql($id)));

    return array_values($characters);
}


/**
 * Send an email to the WordPress admin e-mailaddress
 * Notify the admin that a new name has been submitted to the directory and
 * that this name has to be reviewed first before publishing
 */
function name_directory_notify_admin_of_new_submission($directory, $input)
{
    $admin_email = get_option('admin_email');
    wp_mail($admin_email,
        __('New submission for Name Directory', 'name-directory'),
        __('Howdy,', 'name-directory') . "\n\n" .
        sprintf(__('There was a new submission to the Name Directory on %s at %s', 'name-directory'), get_option('blogname'), get_option('home')) . "\n\n" .
        sprintf("%s: %s", __('Name', 'name-directory'), $input['name_directory_name']) . "\n" .
        sprintf("%s: %s", __('Description', 'name-directory'), $input['name_directory_description']) . "\n" .
        sprintf("%s: %s", __('Submitted by', 'name-directory'), $input['name_directory_submitter']) . "\n\n" .
        __('This new submission does not have the published status.', 'name-directory') . ' ' .
        __('Please login to your WordPress admin to review and accept the submission.', 'name-directory') . "\n\n" .
        sprintf("Link: %s/wp-admin/admin.php?page=name-directory&sub=manage-directory&dir=%d&status=unpublished", get_option('home'), $directory) . "\n\n" .
        sprintf("Your %s WordPress site", get_option('blogname')));
}


/**
 * Get the first X words of the sent description
 * @param $description
 * @param int $words
 * @return string
 */
function name_directory_get_words($description, $words = 10)
{
    preg_match("/(?:\w+(?:\W+|$)){0,$words}/", $description, $matches);
    return $matches[0];
}


/**
 * Helper to render an admin options row with just a Yes/No question
 * @param $directory
 * @param $setting_name
 * @param $setting_friendly_name
 * @param $setting_description
 */
function name_directory_render_admin_setting_boolean($directory, $setting_name, $setting_friendly_name, $setting_description)
{
    echo '<tr>
            <td>' . $setting_friendly_name . (empty($setting_description) ? '' : '<br><small>' . $setting_description . '</small>') . '</td>
            <td>
                <label for="' . $setting_name . '_yes">
                    <input type="radio" name="' . $setting_name . '" id="' . $setting_name . '_yes" value="1" checked="checked" />
                    &nbsp;' . __('Yes', 'name-directory') . '
                </label>

                &nbsp; &nbsp;

                <label for="' . $setting_name . '_no">
                    <input type="radio" name="' . $setting_name . '" id="' . $setting_name . '_no" value="0"
                        ' . (empty($directory[$setting_name]) ? 'checked="checked"' : '') . '>' .
                    ' &nbsp; ' . __('No', 'name-directory') . '
                </label>
            </td>
        </tr>';
}


/**
 * Helper to render an admin options row with multiple choice options
 * @param $directory
 * @param $setting_name
 * @param $setting_friendly_name
 * @param $setting_description
 * @param $options
 */
function name_directory_render_admin_setting_options($directory, $setting_name, $setting_friendly_name, $setting_description, $options)
{
    echo '<tr>
            <td>' . $setting_friendly_name . (empty($setting_description) ? '' : '<br><small>' . $setting_description . '</small>') . '</td>
            <td>
                <select name="' . $setting_name . '">';
                foreach($options as $option => $name)
                {
                    $selected = null;
                    if(! empty($directory[$setting_name]) && $option == $directory[$setting_name])
                    {
                        $selected = " selected";
                    }
                    echo '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
                }
    echo '      </select>
            </td>
        </tr>';
}

/**
 * Render header and footer of the overview-table
 */
function name_directory_render_admin_overview_table_headerfooter()
{
    echo '<tr>
            <th width="1%" scope="col" class="manage-column column-cb check-column">&nbsp;</th>
            <th width="52%" scope="col" id="title" class="manage-column column-title sortable desc">
                <span>' . __('Title', 'name-directory') . '</span>
            </th>
            <th width="13%" scope="col">' . __('Entries', 'name-directory') . '</th>
            <th width="13%" scope="col">' . __('Published', 'name-directory') . '</th>
            <th width="13%" scope="col">' . __('Unpublished', 'name-directory') . '</th>
        </tr>';
}

/**
 * Display a message after updating, let people know there is a new menu entry
 */
function name_directory_after_update_menu_notice()
{
    echo '<div class="notice notice-info is-dismissible"><p><strong>' . __('Name Directory has been updated.', 'name-directory') . '</strong></p>
          <p>' . __('You can now find Name Directory directly in your Admin menu!', 'name-directory') . '</p></div>';
}
