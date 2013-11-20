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

class Hook_sitemap_page extends Hook_sitemap_base
{
	/**
	 * Find if a page-link will be covered by this node.
	 *
	 * @param  ID_TEXT		The page-link.
	 * @return integer		A SITEMAP_NODE_* constant.
	 */
	function handles_page_link($page_link)
	{
		$matches=array();
		if (preg_match('#^([^:]*):([^:]+)(:misc)?$#',$page_link,$matches)!=0)
		{
			$zone=$matches[1];
			$page=$matches[2];

			$details=$this->_request_page_details($page,$zone);
			if ($details!==false)
			{
				if (strpos($details[0],'COMCODE')===false) // We don't handle Comcode pages here, comcode_page handles those
				{
					return SITEMAP_NODE_HANDLED;
				}
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
		preg_match('#^([^:]*):([^:]*)#',$page_link,$matches);
		$page=$matches[2];

		$this->_make_zone_concrete($zone,$page_link);

		$zone_default_page=get_zone_default_page($zone);

		$details=$this->_request_page_details($page,$zone);

		$path=end($details);
		$row=$this->_load_row_from_page_groupings($row,$zone,$page);

		$struct=array(
			'title'=>make_string_tempcode(escape_html(titleify($page))),
			'content_type'=>'page',
			'content_id'=>$zone.':'.$page,
			'modifiers'=>array(),
			'only_on_page'=>'',
			'page_link'=>$page_link,
			'url'=>NULL,
			'extra_meta'=>array(
				'description'=>NULL,
				'image'=>NULL,
				'image_2x'=>NULL,
				'add_date'=>(($meta_gather & SITEMAP_GATHER_TIMES)!=0)?filectime(get_file_base().'/'.$path):NULL,
				'edit_date'=>(($meta_gather & SITEMAP_GATHER_TIMES)!=0)?filemtime(get_file_base().'/'.$path):NULL,
				'submitter'=>NULL,
				'views'=>NULL,
				'rating'=>NULL,
				'meta_keywords'=>NULL,
				'meta_description'=>NULL,
				'categories'=>NULL,
				'validated'=>NULL,
				'db_row'=>(($meta_gather & SITEMAP_GATHER_DB_ROW)!=0)?$row:NULL,
			),
			'permissions'=>array(
				array(
					'type'=>'zone',
					'zone_name'=>$zone,
					'is_owned_at_this_level'=>false,
				),
				array(
					'type'=>'page',
					'zone_name'=>$zone,
					'page_name'=>$page,
					'is_owned_at_this_level'=>true,
				),
			),
			'children'=>NULL,
			'has_possible_children'=>false,

			// These are likely to be changed in individual hooks
			'sitemap_priority'=>($zone_default_page==$page)?SITEMAP_IMPORTANCE_ULTRA:SITEMAP_IMPORTANCE_HIGH,
			'sitemap_refreshfreq'=>($zone_default_page==$page)?'daily':'weekly',

			'privilege_page'=>NULL,
		);

		switch ($details[0])
		{
			case 'HTML':
			case 'HTML_CUSTOM':
				$page_contents=file_get_contents(get_file_base().'/'.$path);
				$matches=array();
				if (preg_match('#\<title[^\>]*\>#',$page_contents,$matches)!=0)
				{
					$start=strpos($page_contents,$matches[0])+strlen($matches[0]);
					$end=strpos($page_contents,'</title>',$start);
					$_title=substr($page_contents,$start,$end-$start);
					if ($_title!='')
						$struct['title']=make_string_tempcode($_title);
				}
				break;

			case 'MODULES':
			case 'MODULES_CUSTOM':
				require_all_lang();
				$test=do_lang('MODULE_TRANS_NAME_'.$page,NULL,NULL,NULL,NULL,false);
				if ($test!==NULL)
					$struct['title']=do_lang_tempcode('MODULE_TRANS_NAME_'.$page);
				break;
		}

		// Get more details from menu link / page grouping?
		$this->_ameliorate_with_row($struct,$row,$meta_gather);

		if (!$this->_check_node_permissions($struct)) return NULL;

		$call_struct=true;

		$children=array();

		$has_entry_points=true;

		if (($max_recurse_depth===NULL) || ($recurse_level<$max_recurse_depth) || (!isset($row[1])))
		{
			// Look for entry points to put under this
			if (($details[0]=='MODULES' || $details[0]=='MODULES_CUSTOM') && (!$require_permission_support))
			{
				$functions=extract_module_functions(get_file_base().'/'.$path,array('get_entry_points','get_wrapper_icon'),array(/*$check_perms=*/true,/*$member_id=*/NULL,/*$support_crosslinks=*/true,/*$be_deferential=*/true));
				if (is_null($functions[0]))
				{
					if (is_file(get_file_base().'/'.str_replace('/modules_custom/','/modules/',$path)))
					{
						$path=str_replace('/modules_custom/','/modules/',$path);
						$functions=extract_module_functions(get_file_base().'/'.$path,array('get_entry_points','get_wrapper_icon'),array(/*$check_perms=*/true,/*$member_id=*/NULL,/*$support_crosslinks=*/true,/*$be_deferential=*/true));
					}
				}

				$has_entry_points=false;

				if (!is_null($functions[0]))
				{
					$entry_points=is_array($functions[0])?call_user_func_array($functions[0][0],$functions[0][1]):eval($functions[0]);

					if ((!is_null($entry_points)) && (count($entry_points)>0))
					{
						$struct['has_possible_children']=true;

						$entry_point_sitemap_ob=$this->_get_sitemap_object('entry_point');

						$has_entry_points=true;

						if (isset($entry_points['!']))
						{
							// "!" indicates no entry-points but that the page is accessible without them
							if (!isset($row[1]))
							{
								$_title=$entry_points['!'][0];
								if (is_object($_title))
								{
									$struct['title']=$_title;
								} else
								{
									$struct['title']=(preg_match('#^[A-Z\_]+$#',$_title)==0)?make_string_tempcode($_title):do_lang_tempcode($_title);
								}
								if (!is_null($entry_points['!'][1]))
								{
									if (($meta_gather & SITEMAP_GATHER_IMAGE)!=0)
									{
										$struct['extra_meta']['image']=find_theme_image('icons/24x24/'.$entry_points['!'][1]);
										$struct['extra_meta']['image_2x']=find_theme_image('icons/48x48/'.$entry_points['!'][1]);
									}
								}
							}
							unset($entry_points['!']);
						}
						elseif ((isset($entry_points['misc'])) || (count($entry_points)==1))
						{
							// Misc/only moves some details down and is then skipped (alternatively we could haved blanked out our container node to make it a non-link)
							$move_down_entry_point=(count($entry_points)==1)?key($entry_points):'misc';
							if (!isset($row[1]))
							{
								if (substr($struct['page_link'],-strlen(':'.$move_down_entry_point))!=':'.$move_down_entry_point)
									$struct['page_link'].=':'.$move_down_entry_point;
								/*$_title=$entry_points[$move_down_entry_point][0];	Actually our name derived from the page grouping or natural name is more appropriate
								if (is_object($_title))
								{
									$struct['title']=$_title;
								} else
								{
									$struct['title']=(preg_match('#^[A-Z\_]+$#',$_title)==0)?make_string_tempcode($_title):do_lang_tempcode($_title);
								}*/
								if (!is_null($entry_points[$move_down_entry_point][1]))
								{
									if (($meta_gather & SITEMAP_GATHER_IMAGE)!=0)
									{
										$struct['extra_meta']['image']=find_theme_image('icons/24x24/'.$entry_points[$move_down_entry_point][1]);
										$struct['extra_meta']['image_2x']=find_theme_image('icons/48x48/'.$entry_points[$move_down_entry_point][1]);
									}
								}
							}
							unset($entry_points[$move_down_entry_point]);
						} else
						{
							$struct['page_link']=''; // Container node is non-clickable

							// Is the icon for the container explicitly defined within get_wrapper_icon()?
							if (!is_null($functions[1]))
							{
								if (($meta_gather & SITEMAP_GATHER_IMAGE)!=0)
								{
									$icon=is_array($functions[1])?call_user_func_array($functions[1][0],$functions[1][1]):eval($functions[1]);
									$struct['extra_meta']['image']=find_theme_image('icons/24x24/'.$icon);
									$struct['extra_meta']['image_2x']=find_theme_image('icons/48x48/'.$icon);
								}
							}
						}

						if (($max_recurse_depth===NULL) || ($recurse_level<$max_recurse_depth))
						{
							foreach (array_keys($entry_points) as $entry_point)
							{
								if (strpos($entry_point,':')===false)
								{
									$child_page_link=$zone.':'.$page.':'.$entry_point;
								} else
								{
									$child_page_link=$entry_point;
								}

								if (preg_match('#^([^:]*):([^:]*):([^:]*)(:.*|$)#',$child_page_link)!=0)
								{
									$child_node=$entry_point_sitemap_ob->get_node($child_page_link,$callback,$valid_node_types,$child_cutoff,$max_recurse_depth,$recurse_level+1,$require_permission_support,$zone,$use_page_groupings,$consider_secondary_categories,$consider_validation,$meta_gather);
								} else
								{
									$child_node=$this->get_node($child_page_link,$callback,$valid_node_types,$child_cutoff,$max_recurse_depth,$recurse_level+1,$require_permission_support,$zone,$use_page_groupings,$consider_secondary_categories,$consider_validation,$meta_gather);
								}
								if ($child_node!==NULL)
									$children[$child_node['page_link']]=$child_node;
							}
						}
					}
				}
			}

			if (!$has_entry_points)
			{
				$struct['page_link']='';
			}

			// Look for virtual nodes to put under this
			$hooks=find_all_hooks('systems','sitemap');
			foreach (array_keys($hooks) as $_hook)
			{
				require_code('hooks/systems/sitemap/'.$_hook);
				$ob=object_factory('Hook_sitemap_'.$_hook);
				if ($ob->is_active())
				{
					$is_handled=$ob->handles_page_link($page_link);
					if ($is_handled==SITEMAP_NODE_HANDLED_VIRTUALLY)
					{
						$struct['privilege_page']=$ob->get_privilege_page($page_link);
						$struct['has_possible_children']=true;

						$virtual_child_nodes=$ob->get_virtual_nodes($page_link,$callback,$valid_node_types,$child_cutoff,$max_recurse_depth,$recurse_level+1,$require_permission_support,$zone,$use_page_groupings,$consider_secondary_categories,$consider_validation,$meta_gather,true);
						if (is_null($virtual_child_nodes)) $virtual_child_nodes=array();
						foreach ($virtual_child_nodes as $child_node)
						{
							if ((count($virtual_child_nodes)==1) && (preg_match('#^'.preg_quote($page_link,'#').':misc(:[^:=]*$|$)#',$child_node['page_link'])!=0) && (!$require_permission_support))
							{
								// Put as container instead
								if ($child_node['extra_meta']['image']=='')
								{
									$child_node['extra_meta']['image']=$struct['extra_meta']['image'];
									$child_node['extra_meta']['image_2x']=$struct['extra_meta']['image_2x'];
								}
								$struct=$child_node;
								if ($struct['children']!==NULL)
									$children=array_merge($children,$struct['children']);
								$struct['children']=NULL;
								$call_struct=false; // Already been called in get_virtual_nodes
							} else
							{
								if (($max_recurse_depth===NULL) || ($recurse_level<$max_recurse_depth))
								{
									if ($callback===NULL)
										$children[$child_node['page_link']]=$child_node;
								}
							}
						}
					}
				}
			}

			if (!$has_entry_points)
			{
				if ($children==array())
				{
					return NULL;
				}
			}
		}

		if ($callback!==NULL && $call_struct)
			call_user_func($callback,$struct);

		if (($max_recurse_depth===NULL) || ($recurse_level<$max_recurse_depth))
		{
			// Finalise children
			if ($callback!==NULL)
			{
				foreach ($children as $child_struct)
				{
					call_user_func($callback,$child_struct);
				}
				$children=array();
			}
			$struct['children']=array_values($children);

			sort_maps_by($children,'title');
		}

		return ($callback===NULL || $return_anyway)?$struct:NULL;
	}
}
