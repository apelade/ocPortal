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
 * @package		core_configuration
 */

class Hook_sitemap_config_category extends Hook_sitemap_base
{
	/**
	 * Find if a page-link will be covered by this node.
	 *
	 * @param  ID_TEXT		The page-link.
	 * @return integer		A SITEMAP_NODE_* constant.
	 */
	function handles_page_link($page_link)
	{
		if (preg_match('#^([^:]*):admin_config(:misc)?$#',$page_link)!=0)
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
	 * @param  ?integer		Maximum number of children before we cut off all children (NULL: no limit).
	 * @param  ?integer		How deep to go from the sitemap root (NULL: no limit).
	 * @param  integer		Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by Google sitemap [deeper is typically less important]).
	 * @param  boolean		Only go so deep as needed to find nodes with permission-support (typically, stopping prior to the entry-level).
	 * @param  ID_TEXT		The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
	 * @param  boolean		Whether to make use of page groupings, to organise stuff with the hook schema, supplementing the default zone organisation.
	 * @param  boolean		Whether to consider secondary categorisations for content that primarily exists elsewhere.
	 * @param  boolean		Whether to filter out non-validated content.
	 * @param  integer		A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
	 * @param  boolean		Whether to return the structure even if there was a callback. Do not pass this setting through via recursion due to memory concerns, it is used only to gather information to detect and prevent parent/child duplication of default entry points.
	 * @return ?array			List of node structures (NULL: working via callback).
	 */
	function get_virtual_nodes($page_link,$callback=NULL,$valid_node_types=NULL,$child_cutoff=NULL,$max_recurse_depth=NULL,$recurse_level=0,$require_permission_support=false,$zone='_SEARCH',$use_page_groupings=false,$consider_secondary_categories=false,$consider_validation=false,$meta_gather=0,$return_anyway=false)
	{
		$nodes=($callback===NULL || $return_anyway)?array():mixed();

		if (($valid_node_types!==NULL) && (!in_array('config_category',$valid_node_types)))
		{
			return $nodes;
		}

		if ($require_permission_support)
		{
			return $nodes;
		}

		$page=$this->_make_zone_concrete($zone,$page_link);

		// Find all categories
		$hooks=find_all_hooks('systems','config');
		$categories=array();
		foreach (array_keys($hooks) as $hook)
		{
			require_code('hooks/systems/config/'.filter_naughty($hook));
			$ob=object_factory('Hook_config_'.$hook);
			$option=$ob->get_details();
			if ((is_null($GLOBALS['CURRENT_SHARE_USER'])) || ($option['shared_hosting_restricted']==0))
			{
				if (!is_null($ob->get_default()))
				{
					$category=$option['category'];
					if (!isset($categories[$category])) $categories[$category]=0;
					$categories[$category]++;
				}
			}
		}
		uksort($categories,'strnatcasecmp');

		if ($child_cutoff!==NULL)
		{
			if (count($categories)>$child_cutoff) return $nodes;
		}

		foreach (array_keys($categories) as $category)
		{
			$child_page_link=$zone.':'.$page.':misc:'.$category;
			$node=$this->get_node($child_page_link,$callback,$valid_node_types,$child_cutoff,$max_recurse_depth,$recurse_level,$require_permission_support,$zone,$use_page_groupings,$consider_secondary_categories,$consider_validation,$meta_gather);
			if (($callback===NULL || $return_anyway) && ($node!==NULL)) $nodes[]=$node;
		}

		return $nodes;
	}

	/**
	 * Find details of a position in the Sitemap.
	 *
	 * @param  ID_TEXT  		The page-link we are finding.
	 * @param  ?string  		Callback function to send discovered page-links to (NULL: return).
	 * @param  ?array			List of node types we will return/recurse-through (NULL: no limit)
	 * @param  ?integer		Maximum number of children before we cut off all children (NULL: no limit).
	 * @param  ?integer		How deep to go from the Sitemap root (NULL: no limit).
	 * @param  integer		Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by XML Sitemap [deeper is typically less important]).
	 * @param  boolean		Only go so deep as needed to find nodes with permission-support (typically, stopping prior to the entry-level).
	 * @param  ID_TEXT		The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
	 * @param  boolean		Whether to make use of page groupings, to organise stuff with the hook schema, supplementing the default zone organisation.
	 * @param  boolean		Whether to consider secondary categorisations for content that primarily exists elsewhere.
	 * @param  boolean		Whether to filter out non-validated content.
	 * @param  integer		A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
	 * @param  ?array			Database row (NULL: lookup).
	 * @param  boolean		Whether to return the structure even if there was a callback. Do not pass this setting through via recursion due to memory concerns, it is used only to gather information to detect and prevent parent/child duplication of default entry points.
	 * @return ?array			Node structure (NULL: working via callback / error).
	 */
	function get_node($page_link,$callback=NULL,$valid_node_types=NULL,$child_cutoff=NULL,$max_recurse_depth=NULL,$recurse_level=0,$require_permission_support=false,$zone='_SEARCH',$use_page_groupings=false,$consider_secondary_categories=false,$consider_validation=false,$meta_gather=0,$row=NULL,$return_anyway=false)
	{
		$matches=array();
		preg_match('#^([^:]*):([^:]*):([^:]*):([^:]*)#',$page_link,$matches);
		$page=$matches[2];
		$category=$matches[4];

		require_all_lang();

		$_category_name=do_lang_tempcode('CONFIG_CATEGORY_'.$category);

		$struct=array(
			'title'=>$_category_name,
			'content_type'=>NULL,
			'content_id'=>NULL,
			'modifiers'=>array(),
			'only_on_page'=>'',
			'page_link'=>$page_link,
			'url'=>NULL,
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
			'permissions'=>array(),
			'has_possible_children'=>false,

			// These are likely to be changed in individual hooks
			'sitemap_priority'=>SITEMAP_IMPORTANCE_LOW,
			'sitemap_refreshfreq'=>'yearly',

			'privilege_page'=>NULL,
		);

		if (!$this->_check_node_permissions($struct)) return NULL;

		if ($callback!==NULL)
			call_user_func($callback,$struct);

		return ($callback===NULL || $return_anyway)?$struct:NULL;
	}
}
