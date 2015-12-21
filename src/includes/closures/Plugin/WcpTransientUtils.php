<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\ZenCache\Pro;

/*
* Automatically wipes expired transients.
*
* @since 151220 Adding support for expired transients.
*
* @param bool $manually True if wiping is done manually.
* @param boolean $maybe Defaults to a true value.
*
* @throws \Exception If a wipe failure occurs.
*
* @return int Total DB rows wiped by this routine (if any).
*/
$self->wipeExpiredTransients = function ($manually = false, $maybe = true) use ($self) {
    if (!is_multisite()) {
        return $self->clearExpiredTransients();
    }
    $counter = 0; // Initialize.

    if (!$self->options['enable']) {
        return $counter; // Nothing to do.
    }
    if ($maybe && !$self->options['cache_clear_transients_enable']) {
        return $counter; // Not enabled at this time.
    }
    $time                     = time(); // Current UTC time.
    $wpdb                     = $self->wpdb(); // WP database class.
    $_transient_timeout_      = $wpdb->esc_like('_transient_timeout_');
    $_site_transient_timeout_ = $wpdb->esc_like('_site_transient_timeout_');

    switch_to_blog(get_current_site()->blog_id);
    $sql = '
        DELETE FROM `timeouts`, `transients`
            USING `'.esc_sql($wpdb->options).'` AS `timeouts`
        JOIN `'.esc_sql($wpdb->options).'` `transients` ON `transients`.`option_name` = REPLACE(`timeouts`.`option_name`, \'_timeout\', \'\')
        WHERE (`timeouts`.`option_name` LIKE \''.esc_sql($_transient_timeout_).'%\' OR `timeouts`.`option_name` LIKE \''.esc_sql($_site_transient_timeout_).'%\')
            AND CAST(`timeouts`.`option_value` AS UNSIGNED) < \''.esc_sql($time).'\'';
    $counter += (int) $wpdb->query(trim($sql));

    $child_blogs = wp_get_sites();
    $child_blogs = is_array($child_blogs) ? $child_blogs : array();

    foreach ($child_blogs as $_child_blog) {
        switch_to_blog($_child_blog['blog_id']);
        $_sql = '
            DELETE FROM `timeouts`, `transients`
                USING `'.esc_sql($wpdb->options).'` AS `timeouts`
            JOIN `'.esc_sql($wpdb->options).'` `transients` ON `transients`.`option_name` = REPLACE(`timeouts`.`option_name`, \'_timeout\', \'\')
            WHERE (`timeouts`.`option_name` LIKE \''.esc_sql($_transient_timeout_).'%\' OR `timeouts`.`option_name` LIKE \''.esc_sql($_site_transient_timeout_).'%\')
                AND CAST(`timeouts`.`option_value` AS UNSIGNED) < \''.esc_sql($time).'\'';
        $counter += (int) $wpdb->query(trim($_sql));
    }
    unset($_child_blog, $_sql); // Housekeeping.

    restore_current_blog();

    return $counter;
};

/*
* Automatically clears expired transients.
*
* @since 151220 Adding support for expired transients.
*
* @param bool $manually True if clearing is done manually.
* @param boolean $maybe Defaults to a true value.
*
* @throws \Exception If a clear failure occurs.
*
* @return int Total DB rows cleared by this routine (if any).
*/
$self->clearExpiredTransients = function ($manually = false, $maybe = true) use($self) {
    $counter = 0; // Initialize.

    if (!$self->options['enable']) {
        return $counter; // Nothing to do.
    }
    if ($maybe && !$self->options['cache_clear_transients_enable']) {
        return $counter; // Not enabled at this time.
    }
    $time                     = time(); // Current UTC time.
    $wpdb                     = $self->wpdb(); // WP database class.
    $_transient_timeout_      = $wpdb->esc_like('_transient_timeout_');
    $_site_transient_timeout_ = $wpdb->esc_like('_site_transient_timeout_');

    $sql = '
        DELETE FROM `timeouts`, `transients`
            USING `'.esc_sql($wpdb->options).'` AS `timeouts`
        JOIN `'.esc_sql($wpdb->options).'` `transients` ON `transients`.`option_name` = REPLACE(`timeouts`.`option_name`, \'_timeout\', \'\')
        WHERE (`timeouts`.`option_name` LIKE \''.esc_sql($_transient_timeout_).'%\' OR `timeouts`.`option_name` LIKE \''.esc_sql($_site_transient_timeout_).'%\')
            AND CAST(`timeouts`.`option_value` AS UNSIGNED) < \''.esc_sql($time).'\'';

    $counter += (int) $wpdb->query(trim($sql));

    return $counter;
};
/*[/pro]*/
