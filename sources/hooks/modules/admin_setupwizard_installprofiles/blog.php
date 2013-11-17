<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		news
 */

class Hook_admin_setupwizard_installprofiles_blog
{
	/**
	 * Get info about the installprofile
	 *
	 * @return array			Map of installprofile details
	 */
	function info()
	{
		require_lang('news');
		return array(
			'title'=>do_lang('BLOG'),
		);
	}

	/**
	 * Get a list of addons that are kept with this installation profile (added to the list of addons always kept)
	 *
	 * @return array			Pair: List of addons in the profile, Separated list of ones to show under advanced
	 */
	function get_addon_list()
	{
		return array(
			array('news','newsletter'),
			array());
	}

	/**
	 * Get a map of default settings associated with this installation profile
	 *
	 * @return array			Map of default settings
	 */
	function field_defaults()
	{
		return array(
			'have_default_banners_hosting'=>'0',
			'have_default_banners_donation'=>'1',
			'have_default_banners_advertising'=>'1',
			'have_default_catalogues_projects'=>'0',
			'have_default_catalogues_faqs'=>'0',
			'have_default_catalogues_links'=>'0',
			'have_default_catalogues_contacts'=>'0',
			'keep_personal_galleries'=>'0',
			'keep_news_categories'=>'1',
			'have_default_rank_set'=>'0',
			'show_content_tagging'=>'1',
			'show_content_tagging_inline'=>'1',
			'show_screen_actions'=>'1',
			'rules'=>'liberal',
		);
	}

	/**
	 * Find details of desired blocks
	 *
	 * @return array			Details of what blocks are wanted
	 */
	function default_blocks()
	{
		return array(
			'YES'=>array(
				'main_news',
			),
			'YES_CELL'=>array(
			),
			'PANEL_LEFT'=>array(
			),
			'PANEL_RIGHT'=>array(
				'side_news_categories',
				'side_news_archive',
				'main_newsletter_signup',
				'side_tag_cloud',
				'side_personal_stats',
				'side_calendar',
				'main_search',
				'main_poll',
			),
		);
	}

	/**
	 * Get options for blocks in this profile
	 *
	 * @return array			Details of what block options are wanted
	 */
	function block_options()
	{
		return array(
			'side_calendar'=>array(
				'type'=>'listing',
			),
			'main_news'=>array(
				'blogs'=>'1',
				'show_in_full'=>'1',
				'param'=>'0',
				'fallback_full'=>'13',
				'fallback_archive'=>'10',
				'title'=>'',
			),
		);
	}

	/**
	 * Execute any special code needed to put this install profile into play
	 */
	function install_code()
	{
	}
}
