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
 * @package		core
 */

class Hook_sitemap_page_grouping extends Hook_sitemap_base
{
	/**
	 * Find if a page-link will be covered by this node.
	 *
	 * @param  ID_TEXT		The page-link.
	 * @return integer		A SITEMAP_NODE_* constant.
	 */
	function handles_pagelink($pagelink)
	{
		$matches=array();
		if (preg_match('#^([^:]*):([^:]*):([^:]*)#',$pagelink,$matches)!=0)
		{
			$zone=$matches[1];
			$page=$matches[2];
			$type=$matches[3];

			if (($zone=='adminzone' && $page=='admin' && $type!='search') || ($zone=='cms' && $page=='cms'))
			{
				return SITEMAP_NODE_HANDLED;
			}
		}
		return SITEMAP_NODE_NOT_HANDLED;
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
	function get_node($pagelink,$callback=NULL,$valid_node_types=NULL,$max_recurse_depth=NULL,$recurse_level=0,$require_permission_support=false,$zone='_SEARCH',$use_page_groupings=false,$consider_secondary_categories=false,$consider_validation=false,$meta_gather=0,$orphaned_pages=NULL,$return_anyway=false)
	{
		require_lang('menus');

		$matches=array();
		preg_match('#^([^:]*):([^:]*):([^:]*)#',$pagelink,$matches);
		$page_grouping=$matches[3];

		$icon=mixed();
		$lang_string=strtoupper($page_grouping);

		// Locate all pages in page groupings, and the icon for this page grouping
		$pages_found=array();
		$hooks=find_all_hooks('systems','page_groupings');
		foreach (array_keys($hooks) as $hook)
		{
			require_code('hooks/systems/page_groupings/'.$hook);

			$ob=object_factory('Hook_page_groupings_'.$hook);
			$links=$ob->run();
			foreach ($links as $link)
			{
				list($_page_grouping)=$link;
				if ($_page_grouping!='')
				{
					$pages_found[$link[2][0]]=true;
				}
				if (($_page_grouping=='') && (($link[2][0]=='cms') || ($link[2][0]=='admin')) && ($link[2][1]==array('type'=>$page_grouping)))
				{
					$icon=$link[1];
					$lang_string=$link[3];
				}
			}
		}

		if ($zone=='_SEARCH')
		{
			// Work out what the zone should be from the $page_grouping (overrides $zone, which we don't trust and must replace)
			switch ($page_grouping)
			{
				case 'structure':
				case 'audit':
				case 'style':
				case 'setup':
				case 'tools':
				case 'security':
					$zone='adminzone';
					break;

				case 'cms':
					$zone='cms';
					break;

				case 'pages':
				case 'rich_content':
				case 'site_meta':
				case 'social':
				default:
					$zone=(get_option('collapse_user_zones')=='1')?'':'site';
					break;
			}
		}

		// Our node
		$struct=array(
			'title'=>is_object($lang_string)?$lang_string:do_lang_tempcode($lang_string),
			'content_type'=>'page_grouping',
			'content_id'=>$page_grouping,
			'pagelink'=>$pagelink,
			'extra_meta'=>array(
				'description'=>NULL,
				'image'=>($icon===NULL)?NULL:find_theme_image('icons/24x24/'.$icon),
				'image_2x'=>($icon===NULL)?NULL:find_theme_image('icons/48x48/'.$icon),
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
					'type'=>'zone',
					'zone_name'=>$zone,
					'is_owned_at_this_level'=>false,
				),
			),
			'children'=>NULL,
			'has_possible_children'=>true,

			// These are likely to be changed in individual hooks
			'sitemap_priority'=>SITEMAP_IMPORTANCE_MEDIUM,
			'sitemap_refreshfreq'=>'weekly',

			'permission_page'=>NULL,
		);

		if (!$this->_check_node_permissions($struct)) return NULL;

		if ($callback!==NULL)
			call_user_func($callback,$struct);

		// Categories done after node callback, to ensure sensible ordering
		if (($max_recurse_depth===NULL) || ($recurse_level<$max_recurse_depth))
		{
			$children=array();

			$root_comcode_pages=collapse_2d_complexity('the_page','p_validated',$GLOBALS['SITE_DB']->query_select('comcode_pages',array('the_page','p_validated'),array('the_zone'=>$zone,'p_parent_page'=>'')));

			$links=array();
			$hooks=find_all_hooks('systems','page_groupings');
			foreach (array_keys($hooks) as $hook)
			{
				require_code('hooks/systems/page_groupings/'.$hook);

				$ob=object_factory('Hook_page_groupings_'.$hook);
				$links=array_merge($links,$ob->run());
			}

			$page_sitemap_ob=$this->_get_sitemap_object('page');
			$entry_point_sitemap_ob=$this->_get_sitemap_object('entry_point');
			$comcode_page_sitemap_ob=$this->_get_sitemap_object('comcode_page');

			// Directly defined in page grouping hook
			$child_links=array();
			foreach ($links as $link)
			{
				if ($link[0]==$page_grouping)
				{
					$title=$link[3];
					$icon=$link[1];

					$page=$link[2][0];
					$_zone=$link[2][2];
					if ($zone!=$_zone) // Not doesn't match. If the page exists in our node's zone as a transparent redirect, override it as in here
					{
						require_code('site');
						$details=_request_page($page,$zone,'redirect');
						if ($details!==false) $_zone=$zone;
					}

					$child_pagelink=$_zone.':'.$page;
					foreach ($link[2][1] as $key=>$val)
					{
						if ($key=='type' || $key=='id')
						{
							$child_pagelink.=':'.urlencode($val);
						} else
						{
							$child_pagelink.=':'.urlencode($key).'='.urlencode($val);
						}
					}

					$details=$this->_request_page_details($page,$_zone);
					$page_type=strtolower($details[0]);

					$description=NULL;
					if (isset($link[4]))
					{
						$description=(is_object($link[4]))?$link[4]:comcode_lang_string($link[4]);
					}

					$child_links[]=array($title,$child_pagelink,$icon,$page_type,$description);
				}
			}

			// Extra ones to get merged in? (orphaned children)
			if ($page_grouping=='pages' || $page_grouping=='tools' || $page_grouping=='cms')
			{
				if ($orphaned_pages===NULL)
				{
					// Any left-behind pages
					$orphaned_pages=array();
					$pages=find_all_pages_wrap($zone,false,/*$consider_redirects=*/true);
					foreach ($pages as $page=>$page_type)
					{
						if (is_integer($page)) $page=strval($page);

						if (preg_match('#^redirect:#',$page_type)!=0)
						{
							$details=$this->_request_page_details($page,$zone);
							$page_type=strtolower($details[0]);
							$pages[$page]=$page_type;
						}

						if ((!isset($pages_found[$page])) && ((strpos($page_type,'comcode')===false) || (!isset($root_comcode_pages[$page]))))
						{
							if ($this->_is_page_omitted_from_sitemap($zone,$page)) continue;

							$orphaned_pages[$page]=$page_type;
						}
					}
				}

				foreach ($orphaned_pages as $page=>$page_type)
				{
					if (is_integer($page)) $page=strval($page);

					$child_pagelink=$zone.':'.$page;

					$child_links[]=array(titleify($page),$child_pagelink,NULL,$page_type,NULL);
				}
			}

			// Render children, in title order
			sort_maps_by($child_links,0);
			foreach ($child_links as $child_link)
			{
				$title=$child_link[0];
				$description=$child_link[4];
				$icon=$child_link[2];
				$child_pagelink=$child_link[1];
				$page_type=$child_link[3];

				$child_row=($icon===NULL)?NULL/*we know nothing of relevance*/:array($title,$icon,$description);

				if (($valid_node_types!==NULL) && (!in_array('page',$valid_node_types)))
				{
					continue;
				}

				if (strpos($page_type,'comcode')!==false)
				{
					if (($valid_node_types!==NULL) && (!in_array('comcode_page',$valid_node_types)))
					{
						continue;
					}

					if (($consider_validation) && (isset($root_comcode_pages[$page])) && ($root_comcode_pages[$page]==0))
					{
						continue;
					}

					$child_node=$comcode_page_sitemap_ob->get_node($child_pagelink,$callback,$valid_node_types,$max_recurse_depth,$recurse_level+1,$require_permission_support,$zone,$use_page_groupings,$consider_secondary_categories,$consider_validation,$meta_gather,$child_row);
				} else
				{
					if (($valid_node_types!==NULL) && (!in_array('page',$valid_node_types)))
					{
						continue;
					}

					if (preg_match('#^([^:]*):([^:]*)(:misc|:\w+=|$)#',$child_pagelink,$matches)!=0)
					{
						$child_node=$page_sitemap_ob->get_node($child_pagelink,$callback,$valid_node_types,$max_recurse_depth,$recurse_level+1,$require_permission_support,$zone,$use_page_groupings,$consider_secondary_categories,$consider_validation,$meta_gather,$child_row);
					} else
					{
						$child_node=$entry_point_sitemap_ob->get_node($child_pagelink,$callback,$valid_node_types,$max_recurse_depth,$recurse_level+1,$require_permission_support,$zone,$use_page_groupings,$consider_secondary_categories,$consider_validation,$meta_gather,$child_row);
					}
				}
				if ($child_node!==NULL)
					$children[]=$child_node;
			}

			$struct['children']=$children;
		}

		return ($callback===NULL || $return_anyway)?$struct:NULL;
	}
}
