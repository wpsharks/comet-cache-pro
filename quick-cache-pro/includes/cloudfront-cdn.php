<?php
/**
 * CloudFront CDN Integration.
 *
 * @package quick_cache\cdn
 * @since 14xxxx Adding CDN support.
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 2
 */
namespace quick_cache // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly: '.basename(__FILE__));

	require_once dirname(__FILE__).'/submodules/aws-php-sdk/vendor/autoload.php';
	require_once dirname(__FILE__).'/cdn-base.php';

	/**
	 * CloudFront CDN Integration.
	 */
	class cloudfront_cdn extends cdn_base
	{
	}
}