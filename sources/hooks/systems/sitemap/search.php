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
 * @package		search
 */

class Hook_sitemap_search extends Hook_sitemap_base
{
	/**
	 * Find if a page-link will be covered by this node.
	 *
	 * @param  ID_TEXT		The page-link.
	 * @return integer		A SITEMAP_NODE_* constant.
	 */
	function handles_pagelink($pagelink)
	{
		if (preg_match('#^([^:]*):search$#',$pagelink)!=0)
		{
			return SITEMAP_NODE_HANDLED_VIRTUALLY;
		}
		return SITEMAP_NODE_NOT_HANDLED;
	}

	/**
	 * Find details of a virtual position in the sitemap. Virtual positions have no structure of their own, but can find child structures to be absorbed down the tree. We do this for modularity reasons.
	 *
	 * @param  ID_TEXT  		The page-link we are finding.
	 * @param  ?string  		Callback function to send discovered page-links to (NULL: return).
	 * @param  ?array			List of node types we will return/recurse-through (NULL: no limit)
	 * @param  ?integer		How deep to go from the sitemap root (NULL: no limit).
	 * @param  integer		Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by Google sitemap [deeper is typically less important]).
	 * @param  boolean		Only go so deep as needed to find nodes with permission-support (typically, stopping prior to the entry-level).
	 * @param  ID_TEXT		The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
	 * @param  boolean		Whether to make use of page groupings, to organise stuff with the hook schema, supplementing the default zone organisation.
	 * @param  boolean		Whether to filter out non-validated content.
	 * @param  boolean		Whether to consider secondary categorisations for content that primarily exists elsewhere.
	 * @param  integer		A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
	 * @param  boolean		Whether to return the structure even if there was a callback. Do not pass this setting through via recursion due to memory concerns, it is used only to gather information to detect and prevent parent/child duplication of default entry points.
	 * @return ?array			List of node structures (NULL: working via callback).
	 */
	function get_virtual_nodes($pagelink,$callback=NULL,$valid_node_types=NULL,$max_recurse_depth=NULL,$recurse_level=0,$require_permission_support=false,$zone='_SEARCH',$use_page_groupings=false,$consider_secondary_categories=false,$consider_validation=false,$meta_gather=0,$return_anyway=false)
	{
		$nodes=($callback===NULL || $return_anyway)?array():mixed();

		if (($valid_node_types!==NULL) && (!in_array('search',$valid_node_types)))
		{
			return $nodes;
		}

		if ($require_permission_support)
		{
			return $nodes;
		}

		$page=$this->_make_zone_concrete($zone,$pagelink);

		$_hooks=find_all_hooks('modules','search');
		foreach (array_keys($_hooks) as $hook)
		{
			require_code('hooks/modules/search/'.filter_naughty_harsh($hook));
			$ob=object_factory('Hook_search_'.filter_naughty_harsh($hook),true);
			if (is_null($ob)) continue;
			$info=$ob->info(false);
			if (is_null($info)) continue;

			if (($hook=='catalogue_entries') || (array_key_exists('special_on',$info)) || (array_key_exists('special_off',$info)) || (method_exists($ob,'get_tree')) || (method_exists($ob,'ajax_tree')))
			{
				$child_pagelink=$zone.':'.$page.':misc:'.$hook;
				$node=$this->get_node($child_pagelink,$callback,$valid_node_types,$max_recurse_depth,$recurse_level,$require_permission_support,$zone,$use_page_groupings,$consider_secondary_categories,$consider_validation,$meta_gather);
				if (($callback===NULL || $return_anyway) && ($node!==NULL)) $nodes[]=$node;
			}
		}

		return $nodes;
	}

	/**
	 * Find details of a position in the Sitemap.
	 *
	 * @param  ID_TEXT  		The page-link we are finding.
	 * @param  ?string  		Callback function to send discovered page-links to (NULL: return).
	 * @param  ?array			List of node types we will return/recurse-through (NULL: no limit)
	 * @param  ?integer		How deep to go from the Sitemap root (NULL: no limit).
	 * @param  integer		Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by XML Sitemap [deeper is typically less important]).
	 * @param  boolean		Only go so deep as needed to find nodes with permission-support (typically, stopping prior to the entry-level).
	 * @param  ID_TEXT		The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
	 * @param  boolean		Whether to make use of page groupings, to organise stuff with the hook schema, supplementing the default zone organisation.
	 * @param  boolean		Whether to filter out non-validated content.
	 * @param  boolean		Whether to consider secondary categorisations for content that primarily exists elsewhere.
	 * @param  integer		A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
	 * @param  ?array			Database row (NULL: lookup).
	 * @param  boolean		Whether to return the structure even if there was a callback. Do not pass this setting through via recursion due to memory concerns, it is used only to gather information to detect and prevent parent/child duplication of default entry points.
	 * @return ?array			Node structure (NULL: working via callback / error).
	 */
	function get_node($pagelink,$callback=NULL,$valid_node_types=NULL,$max_recurse_depth=NULL,$recurse_level=0,$require_permission_support=false,$zone='_SEARCH',$use_page_groupings=false,$consider_secondary_categories=false,$consider_validation=false,$meta_gather=0,$row=NULL,$return_anyway=false)
	{
		$matches=array();
		preg_match('#^([^:]*):([^:]*):([^:]*):([^:]*)#',$pagelink,$matches);
		$page=$matches[2];
		$hook=$matches[4];

		if (($hook=='catalogue_entry') && ($matches[0]!=$pagelink))
		{
			preg_match('#^([^:]*):([^:]*):([^:]*):([^:]*):catalogue_name=([^:]*)#',$pagelink,$matches);
			$catalogue_name=$matches[5];

			if ($row===NULL)
			{
				$rows=$GLOBALS['SITE_DB']->query_select('catalogues',array('*'),array('c_name'=>$catalogue_name),'',1);
				$row=$rows[0];
			}

			$struct=array(
				'title'=>get_translated_text($row['c_title']),
				'content_type'=>NULL,
				'content_id'=>NULL,
				'pagelink'=>$pagelink,
				'extra_meta'=>array(
					'description'=>NULL,
					'image'=>NULL,
					'image_2x'=>NULL,
					'add_date'=>NULL,
					'edit_date'=>NULL,
					'submitter'=>NULL,
					'views'=>NULL,
					'rating'=>NULL,
					'meta_keywords'=>NULL,
					'meta_description'=>NULL,
					'categories'=>NULL,
					'validated'=>NULL,
					'db_row'=>NULL,
				),
				'permissions'=>array(
					array(
						'type'=>'category',
						'permission_module'=>'catalogues_catalogue',
						'category_name'=>$catalogue_name,
						'page_name'=>$page,
						'is_owned_at_this_level'=>false,
					),
				),
				'has_possible_children'=>false,
				'children'=>array(),

				// These are likely to be changed in individual hooks
				'sitemap_priority'=>SITEMAP_IMPORTANCE_MEDIUM,
				'sitemap_refreshfreq'=>'yearly',
			);

			if (!$this->_check_node_permissions($struct)) return NULL;

			if ($callback!==NULL)
				call_user_func($callback,$struct);

			return ($callback===NULL || $return_anyway)?$struct:NULL;
		}

		require_code('hooks/modules/search/'.filter_naughty_harsh($hook));
		$ob=object_factory('Hook_search_'.filter_naughty_harsh($hook),true);
		$info=$ob->info(false);

		$struct=array(
			'title'=>$info['lang'],
			'content_type'=>NULL,
			'content_id'=>NULL,
			'pagelink'=>$pagelink,
			'extra_meta'=>array(
				'description'=>NULL,
				'image'=>NULL,
				'image_2x'=>NULL,
				'add_date'=>NULL,
				'edit_date'=>NULL,
				'submitter'=>NULL,
				'views'=>NULL,
				'rating'=>NULL,
				'meta_keywords'=>NULL,
				'meta_description'=>NULL,
				'categories'=>NULL,
				'validated'=>NULL,
				'db_row'=>NULL,
			),
			'permissions'=>$info['permissions'],
			'has_possible_children'=>($hook=='catalogue_entry'),

			// These are likely to be changed in individual hooks
			'sitemap_priority'=>SITEMAP_IMPORTANCE_MEDIUM,
			'sitemap_refreshfreq'=>'yearly',

			'permission_page'=>NULL,
		);

		if (!$this->_check_node_permissions($struct)) return NULL;

		if ($callback!==NULL)
			call_user_func($callback,$struct);

		// Categories done after node callback, to ensure sensible ordering
		if ($hook=='catalogue_entry')
		{
			$children=array();
			if (($max_recurse_depth===NULL) || ($recurse_level<$max_recurse_depth))
			{
				$rows=$GLOBALS['SITE_DB']->query_select('catalogues',array('*'));
				foreach ($rows as $row)
				{
					$child_pagelink=$pagelink.':'.$row['c_name'];
					$child_node=$this->get_node($child_pagelink,$callback,$valid_node_types,$max_recurse_depth,$recurse_level,$require_permission_support,$zone,$use_page_groupings,$consider_secondary_categories,$consider_validation,$meta_gather,$row);
					if ($child_node!==NULL)
						$children[]=$child_node;
				}
			}
			$struct['children']=$children;
		}

		return ($callback===NULL || $return_anyway)?$struct:NULL;
	}
}
