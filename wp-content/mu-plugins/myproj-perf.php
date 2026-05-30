<?php
/**
 * Plugin Name: MyProj Performance Tweaks
 * Description: Action Scheduler tuning — removes the 5s async-runner sleep that blocked
 *              admin-ajax (cart fragments/recalc), disables the loopback async runner
 *              (background jobs run via real cron), and trims the AS log retention.
 * Author:      MyProj
 *
 * Requires on production: a real cron job hitting wp-cron.php (since WP-Cron is disabled
 * and the async runner below is turned off). On local/test that's not required.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove the hard-coded 5-second sleep in ActionScheduler_AsyncRequest_QueueRunner.
 * The upstream sleep guards against MySQL overload when many async requests chain;
 * at this store's job volume it is safe to drop to 0.
 */
add_filter( 'action_scheduler_async_request_sleep_seconds', '__return_zero' );

/**
 * Disable the loopback async queue runner. Background actions then run only via the
 * scheduled cron context, never piggybacking on (and blocking) user requests.
 *
 * NOTE: requires a real server cron on production (wp-cron.php). Keep WP-Cron handled
 * externally; otherwise scheduled actions will not execute.
 */
add_filter( 'action_scheduler_allow_async_request_runner', '__return_false' );

/**
 * Trim Action Scheduler completed/failed log retention (default 30 days) to 3 days
 * to keep wp_actionscheduler_* tables small.
 */
add_filter(
	'action_scheduler_retention_period',
	static function () {
		return 3 * DAY_IN_SECONDS;
	}
);
