<?php
add_action( 'admin_menu', 'simple_news_add_admin_menu' );
add_action( 'admin_init', 'simple_news_settings_init' );

function simple_news_add_admin_menu(  ) {

    add_options_page( 'Simple News', 'Simple News', 'manage_options', 'simple_news', 'simple_news_options_page' );

}

function simple_news_settings_init(  ) {

    register_setting( 'pluginPage', 'simple_news_settings' );

    add_settings_section(
        'simple_news_pluginPage_section',
        __( 'Settings:', 'simple-news' ),
        'simple_news_settings_section_callback',
        'pluginPage'
    );

    add_settings_section(
        'simple_news_settings_section_info',
        __( 'Settings:', 'simple-news' ),
        'simple_news_settings_section_info',
        'pluginPage'
    );

    add_settings_field(
        'simple_news_select_field_0',
        __( 'ImageSize', 'simple-news' ),
        'simple_news_select_field_0_render',
        'pluginPage',
        'simple_news_pluginPage_section'
    );

    add_settings_field(
        'simple_news_checkbox_css',
        __( 'Remove plugin CSS ... I write my own ;-)', 'simple-news' ),
        'simple_news_checkbox_css_render',
        'pluginPage',
        'simple_news_pluginPage_section'
    );

}

function simple_news_select_field_0_render(  ) {

    $options = get_option( 'simple_news_settings' );
    ?>
    <select name='simple_news_settings[simple_news_select_field_0]'>
        <option value='1' <?php selected( $options['simple_news_select_field_0'], 1 ); ?>>ImageSize 80 x 80 - Default</option>
        <option value='2' <?php selected( $options['simple_news_select_field_0'], 2 ); ?>>ImageSize width 375</option>
        <option value='3' <?php selected( $options['simple_news_select_field_0'], 3 ); ?>>ImageSize width 768</option>
        <option value='4' <?php selected( $options['simple_news_select_field_0'], 4 ); ?>>ImageSize widt 9999</option>
    </select>

<?php

}

function simple_news_checkbox_css_render(  ) {

    $options = get_option( 'simple_news_settings' );
    $a = $options;
if (is_array($a) && array_key_exists("simple_news_checkbox_css",$a))
  { } else {
    $options['simple_news_checkbox_css'] = false;
  }
    ?>
      <input type='checkbox' name='simple_news_settings[simple_news_checkbox_css]' <?php checked( $options['simple_news_checkbox_css'], 1 ); ?> value='1'>
    <?php

}


function simple_news_settings_section_callback(  ) {

    echo '<p class="simple_news_info">' . __( 'Choose between 4 image sizes | Unset plugin CSS.', 'simple-news' ) . '</p>';

}

function simple_news_settings_section_info(  ) {
    echo '<div class="simple-news-info">';
    echo '<p><strong>Shortcodes:</strong><br /><em>' . __( '[news] - [news number=2] - [news order=desc] - [news number=3 order=asc] - [news cat=1] - [news cat=1,2]' ) . '</em></p>';
    echo '<p><strong>Widgets:</strong><br />' . __('A widget that will show your defined number of latest news. Options to filter results by category id.') . '</p>';
    echo '</div>';

}

function simple_news_options_page(  ) { ?>
<style>
    .form-table th {min-width: 280px;}
    p.simple_news_info {background: chocolate;padding: 1em;color: #fff;}
    h2 {display: none;}
    .simple-news-info {background:rgba(212, 105, 6, 0.1);padding: 1em;}
</style>
<form action='options.php' method='post' style="background-color: #fff;padding: 1em 2em;margin: 20px 20px 20px 0; box-shadow: 0 0 1px #000;">
<h1>Simple News</h1>
<?php
    settings_fields( 'pluginPage' );
    do_settings_sections( 'pluginPage' );
    submit_button();
?>
</form>

<?php

 } ?>