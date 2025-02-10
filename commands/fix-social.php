<?php

class FixSocial
{

	public static function init()
	{
		WP_CLI::add_command('social fix', __CLASS__);
	}

	public function __invoke($args, $assoc_args): void
	{
		WP_CLI::success("Fixing social_media options at network level");
		global $wpdb;
		$sites = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

		$is_dry_run = isset($assoc_args['dry-run']);
		$remove_twitterx = isset($assoc_args['no-twitter-x']);
		$disable = isset($assoc_args['disable']);
		$enable = isset($assoc_args['enable']);

		foreach ($sites as $site_id) {
			// Skip main site
			if ($site_id === 1) {
				continue;
			}

			$defaults = [
				'social_media_options' => [],
			];

			switch_to_blog($site_id);
			WP_CLI::success("Processing site {$site_id}");
			$current_web_options = get_option('pressbooks_theme_options_web');

			if (!is_array($current_web_options)) {
				WP_CLI::log("Weird, there are no pressbooks_theme_options_web for site: {$site_id}");
				continue;
			}

			if($disable) {
				WP_CLI::success("Disabling sharing options on site: {$site_id}");
				$defaults['social_media_options'] = [];
			}

			// skip if this site has already social_media_options
			if (!empty($current_web_options['social_media_options']) && !$disable) {
				WP_CLI::log("Skip social_media_options for site {$site_id}, already in place!");
				continue;
			}

			// enable options if they were enabled only or enabled is true
			if (!empty($current_web_options['social_media']) || $enable) {
				WP_CLI::success("Enabling sharing options on site: {$site_id}");
				$defaults['social_media_options'] = [
					'twitter',
					'linkedin',
					'email'
				];
				// remove twitter if the argument is true
				if ($remove_twitterx) {
					WP_CLI::success("Removing X/Twitter for site: {$site_id}");
					unset($defaults['social_media_options'][0]);
				}
			}

			// remove old social media options key
			unset($current_web_options['social_media']);

			// preserve current settings and merge new social media options key
			$new_web_options = [...$current_web_options, 'social_media_options' => $defaults['social_media_options']];

			if ($is_dry_run) {
				WP_CLI::log("Dry-run: Would update social_media_options for site {$site_id} with: " . print_r($new_web_options, true));
			} else {
				update_option('pressbooks_theme_options_web', $new_web_options);
				WP_CLI::success("Updated social_media_options for site {$site_id}");
			}
			restore_current_blog();
		}
	}
}

FixSocial::init();
$arguments = ['social', 'fix'];
$options = [];

foreach (array_slice($args, 2) as $arg) {
	switch ($arg) {
		case 'no-twitter-x':
			$options['no-twitter-x'] = true;
			break;
		case 'dry-run':
			$options['dry-run'] = true;
			break;
		case 'disable':
			$options['disable'] = true;
			break;
		case 'enable':
			$options['enable'] = true;
			break;
		default:
			WP_CLI::error("Unknown parameter: $arg");
	}
}
WP_CLI::run_command($arguments, $options);
