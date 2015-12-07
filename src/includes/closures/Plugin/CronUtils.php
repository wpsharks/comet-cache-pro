<?php
namespace WebSharks\ZenCache\Pro;

/*
 * Extends WP-Cron schedules.
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `cron_schedules` filter.
 *
 * @param array $schedules An array of the current schedules.
 *
 * @return array Revised array of WP-Cron schedules.
 */
$self->extendCronSchedules = function ($schedules) use ($self) {
    $schedules['every15m'] = array(
        'interval' => 900,
        'display'  => __('Every 15 Minutes', SLUG_TD),
    );
    return $schedules;
};


/*
 * Resets Cron Setup and clears WP-Cron schedules.
 *
 * @since 15xxxx Fixing bug with Auto-Cache Engine cron disappearing in some scenarios
 *
 * @return array Revised array of WP-Cron schedules.
 *
 * @note This MUST happen upon uninstall and deactivation due to buggy WP_Cron behavior; see http://bit.ly/1lGdr78
 */
$self->resetCronsSetup = function ( ) use ($self) {
    if (is_multisite()) { // Main site CRON jobs.
        switch_to_blog(get_current_site()->blog_id);
        wp_clear_scheduled_hook('_cron_'.GLOBAL_NS.'_auto_cache');
        wp_clear_scheduled_hook('_cron_'.GLOBAL_NS.'_cleanup');
        restore_current_blog(); // Restore current blog.
    } else { // Standard WP installation.
        wp_clear_scheduled_hook('_cron_'.GLOBAL_NS.'_auto_cache');
        wp_clear_scheduled_hook('_cron_'.GLOBAL_NS.'_cleanup');
    }
    $self->updateOptions(array('crons_setup' => '0')); // Reset so that crons are rescheduled upon next activation
};
