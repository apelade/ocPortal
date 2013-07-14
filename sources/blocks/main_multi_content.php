<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

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

class Block_main_multi_content
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Graham';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=2;
		$info['locked']=false;
		$info['parameters']=array('ocselect','param','efficient','filter','filter_b','title','zone','sort','days','lifetime','pinned','no_links','give_context','include_breadcrumbs','max','start','pagination','root','attach_to_url_filter','render_if_empty','guid','as_guest');
		return $info;
	}

	/**
	 * Standard modular cache function.
	 *
	 * @return ?array		Map of cache details (cache_on and ttl) (NULL: module is disabled).
	 */
	function cacheing_environment()
	{
		$info=array();
		$info['cache_on']='(addon_installed(\'content_privacy\') || preg_match(\'#<\w+>#\',(array_key_exists(\'ocselect\',$map)?$map[\'ocselect\']:\'\'))!=0)?NULL:array(array_key_exists(\'as_guest\',$map)?($map[\'as_guest\']==\'1\'):false,array_key_exists(\'guid\',$map)?$map[\'guid\']:\'\',(array_key_exists(\'efficient\',$map) && $map[\'efficient\']==\'1\')?array():$GLOBALS[\'FORUM_DRIVER\']->get_members_groups(get_member(),false,true),array_key_exists(\'render_if_empty\',$map)?$map[\'render_if_empty\']:\'0\',((array_key_exists(\'attach_to_url_filter\',$map)?$map[\'attach_to_url_filter\']:\'0\')==\'1\'),get_param_integer($block_id.\'_max\',array_key_exists(\'max\',$map)?intval($map[\'max\']):30),get_param_integer($block_id.\'_start\',array_key_exists(\'start\',$map)?intval($map[\'start\']):0),((array_key_exists(\'pagination\',$map)?$map[\'pagination\']:\'0\')==\'1\'),((array_key_exists(\'root\',$map)) && ($map[\'root\']!=\'\'))?intval($map[\'root\']):get_param_integer(\'keep_\'.(array_key_exists(\'param\',$map)?$map[\'param\']:\'download\').\'_root\',NULL),(array_key_exists(\'give_context\',$map)?$map[\'give_context\']:\'0\')==\'1\',(array_key_exists(\'include_breadcrumbs\',$map)?$map[\'include_breadcrumbs\']:\'0\')==\'1\',array_key_exists(\'ocselect\',$map)?$map[\'ocselect\']:\'\',array_key_exists(\'no_links\',$map)?$map[\'no_links\']:0,((array_key_exists(\'days\',$map)) && ($map[\'days\']!=\'\'))?intval($map[\'days\']):NULL,((array_key_exists(\'lifetime\',$map)) && ($map[\'lifetime\']!=\'\'))?intval($map[\'lifetime\']):NULL,((array_key_exists(\'pinned\',$map)) && ($map[\'pinned\']!=\'\'))?explode(\',\',$map[\'pinned\']):array(),array_key_exists(\'max\',$map)?intval($map[\'max\']):10,array_key_exists(\'title\',$map)?$map[\'title\']:\'\',array_key_exists(\'param\',$map)?$map[\'param\']:\'download\',array_key_exists(\'filter\',$map)?$map[\'filter\']:\'\',array_key_exists(\'filter_b\',$map)?$map[\'filter_b\']:\'\',array_key_exists(\'zone\',$map)?$map[\'zone\']:\'_SEARCH\',array_key_exists(\'sort\',$map)?$map[\'sort\']:\'recent\')';
		$info['ttl']=(get_value('no_block_timeout')==='1')?60*60*24*365*5/*5 year timeout*/:30;
		return $info;
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		$GLOBALS['SITE_DB']->create_table('feature_lifetime_monitor',array(
			'content_id'=>'*ID_TEXT',
			'block_cache_id'=>'*ID_TEXT',
			'run_period'=>'INTEGER',
			'running_now'=>'BINARY',
			'last_update'=>'TIME',
		));
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_table_if_exists('feature_lifetime_monitor');
	}

	/**
	 * Standard modular run function.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
	function run($map)
	{
		if (isset($map['param']))
		{
			$content_type=$map['param'];
		} else
		{
			if (addon_installed('downloads'))
			{
				$content_type='download';
			} else
			{
				$hooks=find_all_hooks('systems','content_meta_aware');
				$content_type=key($hooks);
			}
		}

		$block_id=get_block_id($map);

		$max=get_param_integer($block_id.'_max',isset($map['max'])?intval($map['max']):30);
		$start=get_param_integer($block_id.'_start',isset($map['start'])?intval($map['start']):0);
		$do_pagination=((isset($map['pagination'])?$map['pagination']:'0')=='1');
		$attach_to_url_filter=((isset($map['attach_to_url_filter'])?$map['attach_to_url_filter']:'0')=='1');
		$root=((isset($map['root'])) && ($map['root']!=''))?intval($map['root']):get_param_integer('keep_'.$content_type.'_root',NULL);

		$guid=isset($map['guid'])?$map['guid']:'';
		$sort=isset($map['sort'])?$map['sort']:'recent'; // recent|top|views|random|title or some manually typed sort order
		if ($sort=='all') $sort='title'; // LEGACY
		if ($sort=='rating') $sort='average_rating'; // LEGACY
		$filter=isset($map['filter'])?$map['filter']:'';
		$filter_b=isset($map['filter_b'])?$map['filter_b']:'';
		if ($filter_b=='*') return new ocp_tempcode(); // Indicates some kind of referencing error, probably caused by Tempcode pre-processing - skip execution
		$ocselect=isset($map['ocselect'])?$map['ocselect']:'';
		$zone=isset($map['zone'])?$map['zone']:'_SEARCH';
		$efficient=(isset($map['efficient'])?$map['efficient']:'1')=='1';
		$title=isset($map['title'])?$map['title']:'';
		$days=((isset($map['days'])) && ($map['days']!=''))?intval($map['days']):NULL;
		$lifetime=((isset($map['lifetime'])) && ($map['lifetime']!=''))?intval($map['lifetime']):NULL;
		$pinned=((isset($map['pinned'])) && ($map['pinned']!=''))?explode(',',$map['pinned']):array();
		$give_context=(isset($map['give_context'])?$map['give_context']:'0')=='1';
		$include_breadcrumbs=(isset($map['include_breadcrumbs'])?$map['include_breadcrumbs']:'0')=='1';

		if ((!file_exists(get_file_base().'/sources/hooks/systems/content_meta_aware/'.filter_naughty_harsh($content_type).'.php')) && (!file_exists(get_file_base().'/sources_custom/hooks/systems/content_meta_aware/'.filter_naughty_harsh($content_type).'.php')))
			return paragraph(do_lang_tempcode('NO_SUCH_CONTENT_TYPE',$content_type),'','red_alert');

		require_code('content');
		$object=get_content_object($content_type);
		$info=$object->info($zone,($filter_b=='')?NULL:$filter_b);
		if ($info===NULL) warn_exit(do_lang_tempcode('IMPOSSIBLE_TYPE_USED'));

		$submit_url=$info['add_url'];
		if ($submit_url!==NULL)
		{
			list($submit_url_zone,$submit_url_map,$submit_url_hash)=page_link_decode($submit_url);
			$submit_url=static_evaluate_tempcode(build_url($submit_url_map,$submit_url_zone,NULL,false,false,false,$submit_url_hash));
		} else $submit_url='';
		if (!has_actual_page_access(NULL,$info['cms_page'],NULL,NULL)) $submit_url='';

		// Get entries

		if (is_array($info['category_field']))
		{
			$category_field_access=$info['category_field'][0];
			$category_field_filter=$info['category_field'][1];
		} else
		{
			$category_field_access=$info['category_field'];
			$category_field_filter=$info['category_field'];
		}
		if (array_key_exists('category_type',$info))
		{
			if (is_array($info['category_type']))
			{
				$category_type_access=$info['category_type'][0];
				$category_type_filter=$info['category_type'][1];
			} else
			{
				$category_type_access=$info['category_type'];
				$category_type_filter=$info['category_type'];
			}
		} else
		{
			$category_type_access=mixed();
			$category_type_filter=mixed();
		}

		$where='';
		$query='FROM '.get_table_prefix().$info['table'].' r';
		if ((!$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) && (!$efficient))
		{
			$_groups=$GLOBALS['FORUM_DRIVER']->get_members_groups(get_member(),false,true);
			$groups='';
			foreach ($_groups as $group)
			{
				if ($groups!='') $groups.=' OR ';
				$groups.='a.group_id='.strval($group);
			}

			if ($category_field_access!==NULL)
			{
				if ($category_type_access==='<zone>')
				{
					$query.=' LEFT JOIN '.get_table_prefix().'group_zone_access a ON (r.'.$category_field_access.'=a.zone_name)';
					$query.=' LEFT JOIN '.get_table_prefix().'group_zone_access ma ON (r.'.$category_field_access.'=ma.zone_name)';
				}
				elseif ($category_type_access==='<page>')
				{
					$query.=' LEFT JOIN '.get_table_prefix().'group_page_access a ON (r.'.$category_field_filter.'=a.page_name AND r.'.$category_field_access.'=a.zone_name AND ('.$groups.'))';
					$query.=' LEFT JOIN '.get_table_prefix().'group_zone_access a2 ON (r.'.$category_field_access.'=a2.zone_name)';
					$query.=' LEFT JOIN '.get_table_prefix().'group_zone_access ma2 ON (r.'.$category_field_access.'=ma2.zone_name)';
				} else
				{
					$query.=' LEFT JOIN '.get_table_prefix().'group_category_access a ON ('.db_string_equal_to('a.module_the_name',$category_type_access).' AND r.'.$category_field_access.'=a.category_name)';
					$query.=' LEFT JOIN '.get_table_prefix().'member_category_access ma ON ('.db_string_equal_to('ma.module_the_name',$category_type_access).' AND r.'.$category_field_access.'=ma.category_name)';
				}
			}
			if (($category_field_filter!==NULL) && ($category_field_filter!=$category_field_access) && ($info['category_type']!=='<page>') && ($info['category_type']!=='<zone>'))
			{
				$query.=' LEFT JOIN '.get_table_prefix().'group_category_access a2 ON ('.db_string_equal_to('a.module_the_name',$category_type_filter).' AND r.'.$category_field_filter.'=a2.category_name)';
				$query.=' LEFT JOIN '.get_table_prefix().'member_category_access ma2 ON ('.db_string_equal_to('ma2.module_the_name',$category_type_access).' AND r.'.$category_field_access.'=ma2.category_name)';
			}
			if ($category_field_access!==NULL)
			{
				if ($where!='') $where.=' AND ';
				if ($info['category_type']==='<page>')
				{
					$where.='(a.group_id IS NULL) AND ('.str_replace('a.','a2.',$groups).') AND (a2.group_id IS NOT NULL)';
					// NB: too complex to handle member-specific page permissions in this
				} else
				{
					$where.='(('.$groups.') AND (a.group_id IS NOT NULL) OR ((ma.active_until IS NULL OR ma.active_until>'.strval(time()).') AND ma.member_id='.strval(get_member()).'))';
				}
			}
			if (($category_field_filter!==NULL) && ($category_field_filter!=$category_field_access) && ($info['category_type']!=='<page>'))
			{
				if ($where!='') $where.=' AND ';
				$where.='(('.str_replace('a.group_id','a2.group_id',$groups).') AND (a2.group_id IS NOT NULL) OR ((ma2.active_until IS NULL OR ma2.active_until>'.strval(time()).') AND ma2.member_id='.strval(get_member()).'))';
			}
			if (array_key_exists('where',$info))
			{
				if ($where!='') $where.=' AND ';
				$where.=$info['where'];
			}
		}

		if ((array_key_exists('validated_field',$info)) && (addon_installed('unvalidated')) && ($info['validated_field']!='') && (has_privilege(get_member(),'see_unvalidated')))
		{
			if ($where!='') $where.=' AND ';
			$where.='r.'.$info['validated_field'].'=1';
		}

		$x1='';
		$x2='';
		if (($filter!='') && ($category_field_filter!==NULL))
		{
			$x1=$this->build_filter($filter,$info,$category_field_filter);
			$parent_spec__table_name=array_key_exists('parent_spec__table_name',$info)?$info['parent_spec__table_name']:$info['table'];
			if (($parent_spec__table_name!==NULL) && ($parent_spec__table_name!=$info['table']))
			{
				$query.=' LEFT JOIN '.$info['connection']->get_table_prefix().$parent_spec__table_name.' parent ON parent.'.$info['parent_spec__field_name'].'=r.'.$info['id_field'];
			}
		}
		if (($filter_b!='') && ($category_field_access!==NULL))
		{
			$x2=$this->build_filter($filter_b,$info,$category_field_access);
		}

		if ($days!==NULL)
		{
			if ($where!='') $where.=' AND ';
			$where.=$info['date_field'].'>='.strval(time()-60*60*24*$days);
		}

		if (is_array($info['id_field'])) $lifetime=NULL; // Cannot join on this
		if ($lifetime!==NULL)
		{
			$block_cache_id=md5(serialize($map));
			$query.=' LEFT JOIN '.$info['connection']->get_table_prefix().'feature_lifetime_monitor m ON m.content_id=r.'.$info['id_field'].' AND '.db_string_equal_to('m.block_cache_id',$block_cache_id);
			if ($where!='') $where.=' AND ';
			$where.='(m.run_period IS NULL OR m.run_period<'.strval($lifetime*60*60*24).')';
		}

		if (array_key_exists('extra_select_sql',$info))
		{
			$extra_select_sql=$info['extra_select_sql'];
		} else $extra_select_sql='';
		if (array_key_exists('extra_table_sql',$info))
		{
			$query.=$info['extra_table_sql'];
		}
		if (array_key_exists('extra_where_sql',$info))
		{
			if ($where!='') $where.=' AND ';
			$where.=$info['extra_where_sql'];
		}

		// ocSelect support
		if ($ocselect!='')
		{
			// Convert the filters to SQL
			require_code('ocselect');
			list($extra_select,$extra_join,$extra_where)=ocselect_to_sql($info['connection'],parse_ocselect($ocselect),$content_type,'');
			$extra_select_sql.=implode('',$extra_select);
			$query.=implode('',$extra_join);
			$where.=$extra_where;
		}

		// Need to pull in title?
		if (($sort=='title') || (strpos($sort,'t.text_original')!==false))
		{
			if ((array_key_exists('title_field',$info)) && (strpos($info['title_field'],':')===false))
			{
				$query.=' LEFT JOIN '.get_table_prefix().'translate t ON t.id=r.'.$info['title_field'].' AND '.db_string_equal_to('t.language',user_lang());
			}
		}

		if (addon_installed('content_privacy'))
		{
			require_code('content_privacy');
			$as_guest=array_key_exists('as_guest',$map)?($map['as_guest']=='1'):false;
			$viewing_member_id=$as_guest?$GLOBALS['FORUM_DRIVER']->get_guest_id():mixed();
			list($privacy_join,$privacy_where)=get_privacy_where_clause($content_type,'r',$viewing_member_id);
			$query.=$privacy_join;
			$where.=$privacy_where;
		}

		// Put query together
		if ($where.$x1.$x2!='')
		{
			if ($where=='') $where='1=1';
			$query.=' WHERE '.$where;
			if ($x1!='') $query.=' AND ('.$x1.')';
			if ($x2!='') $query.=' AND ('.$x2.')';
		}

		if ((($sort=='average_rating') || ($sort=='compound_rating')) && (array_key_exists('feedback_type_code',$info)) && ($info['feedback_type_code']===NULL))
			$sort='title';

		global $TABLE_LANG_FIELDS_CACHE;
		$lang_fields=isset($TABLE_LANG_FIELDS_CACHE[$info['table']])?$TABLE_LANG_FIELDS_CACHE[$info['table']]:array();
		foreach ($lang_fields as $i=>$lang_field)
		{
			$lang_fields[$i]='r.'.$lang_field;
		}

		$first_id_field=is_array($info['id_field'])?$info['id_field'][0]:$info['id_field'];

		// Find what kind of query to run and run it
		if ($filter!='-1')
		{
			switch ($sort)
			{
				case 'random':
				case 'fixed_random ASC':
					$rows=$info['connection']->query('SELECT r.*'.$extra_select_sql.',(MOD(CAST(r.'.$first_id_field.' AS SIGNED),'.date('d').')) AS fixed_random '.$query.' ORDER BY fixed_random',$max,$start,false,true,$lang_fields);
					break;
				case 'recent':
				case 'recent ASC':
				case 'recent DESC':
					if ((array_key_exists('date_field',$info)) && ($info['date_field']!==NULL))
					{
						$rows=$info['connection']->query('SELECT r.*'.$extra_select_sql.' '.$query.' ORDER BY r.'.$info['date_field'].(($sort!='recent asc')?' DESC':' ASC'),$max,$start,false,true,$lang_fields);
						break;
					}
					$sort=$first_id_field;
				case 'views':
					if ((array_key_exists('views_field',$info)) && ($info['views_field']!==NULL))
					{
						$rows=$info['connection']->query('SELECT r.*'.$extra_select_sql.' '.$query.' ORDER BY r.'.$info['views_field'].' DESC',$max,$start,false,true,$lang_fields);
						break;
					}
					$sort=$first_id_field;
				case 'average_rating':
				case 'average_rating ASC':
				case 'average_rating DESC':
					if ((array_key_exists('feedback_type_code',$info)) && ($info['feedback_type_code']!==NULL))
					{
						if ($sort=='average_rating')  $sort.=' DESC';

						$select_rating=',(SELECT AVG(rating) FROM '.get_table_prefix().'rating WHERE '.db_string_equal_to('rating_for_type',$info['feedback_type_code']).' AND rating_for_id='.$first_id_field.') AS average_rating';
						$rows=$info['connection']->query('SELECT r.*'.$extra_select_sql.$select_rating.' '.$query,$max,$start,'ORDER BY '.$sort,$max,$start,false,true,$lang_fields);
						break;
					}
					$sort=$first_id_field;
				case 'compound_rating':
				case 'compound_rating ASC':
				case 'compound_rating DESC':
					if ((array_key_exists('feedback_type_code',$info)) && ($info['feedback_type_code']!==NULL))
					{
						if ($sort=='compound_rating')  $sort.=' DESC';

						$select_rating=',(SELECT SUM(rating-1) FROM '.get_table_prefix().'rating WHERE '.db_string_equal_to('rating_for_type',$info['feedback_type_code']).' AND rating_for_id='.$first_id_field.') AS compound_rating';
						$rows=$info['connection']->query('SELECT r.*'.$extra_select_sql.$select_rating.' '.$query,$max,$start,'ORDER BY '.$sort,$max,$start,false,true,$lang_fields);
						break;
					}
					$sort=$first_id_field;
				default: // Some manual order
					$rows=$info['connection']->query('SELECT r.*'.$extra_select_sql.' '.$query.' ORDER BY '.$sort,$max,$start,false,true,$lang_fields);
					break;
				case 'title':
					if ((array_key_exists('title_field',$info)) && (strpos($info['title_field'],':')===false))
					{
						if ($info['title_field_dereference'])
						{
							$rows=$info['connection']->query('SELECT r.*'.$extra_select_sql.' '.$query.' ORDER BY t.text_original ASC',$max,$start,false,true,$lang_fields);
						} else
						{
							$rows=$info['connection']->query('SELECT r.*'.$extra_select_sql.' '.$query.' ORDER BY r.'.$info['title_field'].' ASC',$max,$start,false,true,$lang_fields);
						}
					} else
					{
						$rows=$info['connection']->query('SELECT r.*'.$extra_select_sql.' '.$query.' ORDER BY r.'.$first_id_field.' ASC',$max,$start,false,true,$lang_fields);
					}
					break;
			}

			$max_rows=$info['connection']->query_value_if_there('SELECT COUNT(*)'.$extra_select_sql.' '.$query,false,true);
		} else
		{
			$rows=array();
			$max_rows=0;
		}

		$pinned_order=array();

		require_code('content');

		// Add in requested pinned awards
		if (($pinned!=array()) && (addon_installed('awards')))
		{
			if (can_arbitrary_groupby())
			{
				$where='';
				foreach ($pinned as $p)
				{
					if (trim($p)=='') continue;
					if ($where!='') $where.=' OR ';
					$where.='a_type_id='.strval(intval($p));
				}
				if ($where=='')
				{
					$awarded_content_ids=array();
				} else
				{
					$awarded_content_ids=collapse_2d_complexity('a_type_id','content_id',$GLOBALS['SITE_DB']->query('SELECT a_type_id,content_id FROM '.get_table_prefix().'award_archive WHERE '.$where.' GROUP BY a_type_id ORDER BY date_and_time DESC',NULL,NULL,false,true));
				}
			} else
			{
				$awarded_content_ids=array();
				foreach ($pinned as $p)
				{
					if (trim($p)=='') continue;
					$where='a_type_id='.strval(intval($p));
					$awarded_content_ids+=collapse_2d_complexity('a_type_id','content_id',$GLOBALS['SITE_DB']->query('SELECT a_type_id,content_id FROM '.get_table_prefix().'award_archive WHERE '.$where.' ORDER BY date_and_time DESC',1,NULL,false,true));
				}
			}

			foreach ($pinned as $p)
			{
				if (!isset($awarded_content_ids[intval($p)])) continue;
				$awarded_content_id=$awarded_content_ids[intval($p)];

				$award_content_row=content_get_row($awarded_content_id,$info);

				if (($award_content_row!==NULL) && ((!addon_installed('unvalidated')) || (!isset($info['validated_field'])) || ($award_content_row[$info['validated_field']]!=0)))
				{
					$pinned_order[]=$award_content_row;
				}
			}
		}

		if (count($pinned_order)>0) // Re-sort with pinned awards if appropriate
		{
			if (count($rows)>0)
			{
				$old_rows=$rows;
				$rows=array();
				$total_count=count($old_rows)+count($pinned_order);
				$used_ids=array();

				// Carry on as it should be
				for ($t_count=0;$t_count<$total_count;$t_count++)
				{
					if (array_key_exists($t_count,$pinned_order)) // Pinned ones go first, so order # for them is in sequence with main loop order
					{
						$str_id=extract_content_str_id_from_data($pinned_order[$t_count],$info);
						if (!in_array($str_id,$used_ids))
						{
							$rows[]=$pinned_order[$t_count];
							$used_ids[]=$str_id;
						}
					} else
					{
						$temp_row=$old_rows[$t_count-count($pinned_order)];
						$str_id=extract_content_str_id_from_data($temp_row,$info);
						if (!in_array($str_id,$used_ids))
						{
							$rows[]=$temp_row;
							$used_ids[]=$str_id;
						}
					}
				}
			}
			else
			{
				switch ($sort)
				{
					case 'recent':
						if (array_key_exists('date_field',$info))
						{
							sort_maps_by($pinned_order,$info['date_field']);
							$rows=array_reverse($pinned_order);
						}
						break;
					case 'views':
						if (array_key_exists('views_field',$info))
						{
							sort_maps_by($pinned_order,$info['views_field']);
							$rows=array_reverse($pinned_order);
						}
						break;
				}
			}
		}

		// Sort out run periods
		if ($lifetime!==NULL)
		{
			$lifetime_monitor=list_to_map('content_id',$GLOBALS['SITE_DB']->query_select('feature_lifetime_monitor',array('content_id','run_period','last_update'),array('block_cache_id'=>$block_cache_id,'running_now'=>1)));
		}

		// Move towards render...

		if ($info['archive_url']!==NULL)
		{
			list($archive_url_zone,$archive_url_map,$archive_url_hash)=page_link_decode($info['archive_url']);
			$archive_url=build_url($archive_url_map,$archive_url_zone,NULL,false,false,false,$archive_url_hash);
		} else $archive_url=new ocp_tempcode();
		$view_url=array_key_exists('view_url',$info)?$info['view_url']:new ocp_tempcode();

		$done_already=array(); // We need to keep track, in case those pulled up via awards would also come up naturally

		$rendered_content=array();
		$content_data=array();
		foreach ($rows as $row)
		{
			if (count($done_already)==$max) break;

			// Get content ID
			$content_id=extract_content_str_id_from_data($row,$info);

			// De-dupe
			if (array_key_exists($content_id,$done_already)) continue;
			$done_already[$content_id]=1;

			// Lifetime managing
			if ($lifetime!==NULL)
			{
				if (!array_key_exists($content_id,$lifetime_monitor))
				{
					// Test to see if it is actually there in the past - we only loaded the "running now" ones for performance reasons. Any new ones coming will trigger extra queries to see if they've been used before, as a tradeoff to loading potentially 10's of thousands of rows.
					$lifetime_monitor+=list_to_map('content_id',$GLOBALS['SITE_DB']->query_select('feature_lifetime_monitor',array('content_id','run_period','last_update'),array('block_cache_id'=>$block_cache_id,'content_id'=>$content_id)));
				}

				if (array_key_exists($content_id,$lifetime_monitor))
				{
					$GLOBALS['SITE_DB']->query_update('feature_lifetime_monitor',array(
						'run_period'=>$lifetime_monitor[$content_id]['run_period']+(time()-$lifetime_monitor[$content_id]['last_update']),
						'running_now'=>1,
						'last_update'=>time(),
					),array('content_id'=>$content_id,'block_cache_id'=>$block_cache_id));
					unset($lifetime_monitor[$content_id]);
				} else
				{
					$GLOBALS['SITE_DB']->query_insert('feature_lifetime_monitor',array(
						'content_id'=>$content_id,
						'block_cache_id'=>$block_cache_id,
						'run_period'=>0,
						'running_now'=>1,
						'last_update'=>time(),
					));
				}
			}

			// Render
			$rendered_content[]=$object->run($row,$zone,$give_context,$include_breadcrumbs,$root,$attach_to_url_filter,$guid);

			// Try and get a better submit url
			$submit_url=str_replace('%21',$content_id,$submit_url);

			$content_data[]=array('URL'=>str_replace('%21',$content_id,$view_url->evaluate()));
		}

		// Sort out run periods of stuff gone
		if ($lifetime!==NULL)
		{
			foreach (array_keys($lifetime_monitor) as $content_id) // Any remaining have not been pulled up
			{
				if (is_integer($content_id)) $content_id=strval($content_id);

				$GLOBALS['SITE_DB']->query_update('feature_lifetime_monitor',array(
					'run_period'=>$lifetime_monitor[$content_id]['run_period']+(time()-$lifetime_monitor[$content_id]['last_update']),
					'running_now'=>0,
					'last_update'=>time(),
				),array('content_id'=>$content_id,'block_cache_id'=>$block_cache_id));
			}
		}

		if ((isset($map['no_links'])) && ($map['no_links']=='1'))
		{
			$submit_url=new ocp_tempcode();
			$archive_url=new ocp_tempcode();
		}

		// Empty? Bomb out somehow
		if (count($rendered_content)==0)
		{
			if ((isset($map['render_if_empty'])) && ($map['render_if_empty']=='0'))
			{
				return new ocp_tempcode();
			}
		}

		// Pagination
		$pagination=mixed();
		if ($do_pagination)
		{
			require_code('templates_pagination');
			$pagination=pagination(do_lang_tempcode($info['content_type_label']),$start,$block_id.'_start',$max,$block_id.'_max',$max_rows);
		}

		return do_template('BLOCK_MAIN_MULTI_CONTENT',array(
			'_GUID'=>($guid!='')?$guid:'9035934bc9b25f57eb8d23bf100b5796',
			'BLOCK_PARAMS'=>block_params_arr_to_str($map),
			'TYPE'=>do_lang_tempcode($info['content_type_label']),
			'TITLE'=>$title,
			'CONTENT'=>$rendered_content,
			'CONTENT_DATA'=>$content_data,
			'SUBMIT_URL'=>$submit_url,
			'ARCHIVE_URL'=>$archive_url,
			'PAGINATION'=>$pagination,

			'START'=>strval($start),
			'MAX'=>strval($max),
			'START_PARAM'=>$block_id.'_start',
			'MAX_PARAM'=>$block_id.'_max',
		));
	}

	/**
	 * Make a filter SQL fragment.
	 *
	 * @param  string		The filter string.
	 * @param  array		Map of details of our content type.
	 * @param  string		The field name of the category to filter against.
	 * @return string		SQL fragment.
	 */
	function build_filter($filter,$info,$category_field_filter)
	{
		$parent_spec__table_name=array_key_exists('parent_spec__table_name',$info)?$info['parent_spec__table_name']:$info['table'];
		$parent_field_name=$category_field_filter;//array_key_exists('parent_field_name',$info)?$info['parent_field_name']:NULL;
		if ($parent_field_name===NULL) $parent_spec__table_name=NULL;
		$parent_spec__parent_name=array_key_exists('parent_spec__parent_name',$info)?$info['parent_spec__parent_name']:NULL;
		$parent_spec__field_name=array_key_exists('parent_spec__field_name',$info)?$info['parent_spec__field_name']:NULL;
		$id_field_numeric=((!array_key_exists('id_field_numeric',$info)) || ($info['id_field_numeric']));
		$category_is_string=((array_key_exists('category_is_string',$info)) && (is_array($info['category_is_string'])?$info['category_is_string'][1]:$info['category_is_string']));
		require_code('ocfiltering');
		$sql=ocfilter_to_sqlfragment($filter,'r.'.$info['id_field'],$parent_spec__table_name,$parent_spec__parent_name,'r.'.$parent_field_name,$parent_spec__field_name,$id_field_numeric,!$category_is_string);
		return $sql;
	}
}

