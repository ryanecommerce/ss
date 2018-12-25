<?php
/**
 * Display question archive
 *
 * Template for rendering base of AnsPress.
 *
 * @link https://anspress.io
 * @since 4.1.0
 *
 * @package AnsPress
 * @package Templates
 */
?>

<?php dynamic_sidebar( 'ap-top' ); ?>

<div class="ap-row">
    <?php if ( is_active_sidebar( 'ap-sidebar' ) && is_anspress() ) { ?>
        <div class="ap-question-right ap-col-3">
            <?php dynamic_sidebar( 'ap-sidebar' ); ?>
        </div>
    <?php } ?>

	<div id="ap-lists" class="<?php echo is_active_sidebar( 'ap-sidebar' ) && is_anspress() ? 'ap-col-9' : 'ap-col-12'; ?>">
		<?php ap_get_template_part( 'question-list' ); ?>
	</div>
</div>
