<?php
namespace WebSharks\ZenCache\Pro;

/**
 * Utilities.
 *
 * @since 150422 Rewrite.
 */
class CronUtils extends AbsBase
{
    /**
     * Extends WP-Cron schedules.
     *
     * @since 140422 First documented version.
     *
     * @attaches-to `cron_schedules` filter.
     *
     * @param array $schedules An array of the current schedules.
     *
     * @return array Revised array of WP-Cron schedules.
     */
    public function extend_cron_schedules($schedules)
    {
        $schedules['every15m'] = array('interval' => 900, 'display' => __('Every 15 Minutes', $this->text_domain));

        return $this->apply_wp_filters(__METHOD__, $schedules, get_defined_vars());
    }
}
