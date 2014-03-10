<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_ocf
 */

class Block_main_members
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
		$info['parameters']=array(
			'display_mode',
			'must_have_avatar',
			'must_have_photo',
			'include_form',
			'filter',
			'filters_row_a',
			'filters_row_b',
			'ocselect',
			'usergroup',
			'max',
			'start',
			'pagination',
			'sort',
			'parent_gallery',
			'per_row',
			'guid',
		);
		return $info;
	}

	/**
	 * Standard modular cache function.
	 *
	 * @return ?array	Map of cache details (cache_on and ttl) (NULL: module is disabled).
	 */
	function cacheing_environment()
	{
		$info=array();
		$info['cache_on']='array(
			array_key_exists(\'display_mode\',$map)?$map[\'display_mode\']:\'avatars\',
			array_key_exists(\'must_have_avatar\',$map)?($map[\'must_have_avatar\']==\'1\'):false,
			array_key_exists(\'must_have_photo\',$map)?($map[\'must_have_photo\']==\'1\'):false,
			array_key_exists(\'include_form\',$map)?($map[\'include_form\']==\'1\'):true,
			array_key_exists(\'filter\',$map)?$map[\'filter\']:\'*\',
			array_key_exists(\'filters_row_a\',$map)?$map[\'filters_row_a\']:\'\',
			array_key_exists(\'filters_row_b\',$map)?$map[\'filters_row_b\']:\'\',
			array_key_exists(\'ocselect\',$map)?$map[\'ocselect\']:\'\',
			array_key_exists(\'usergroup\',$map)?$map[\'usergroup\']:\'\',
			get_param_integer($block_id.\'_max\',array_key_exists(\'max\',$map)?intval($map[\'max\']):30),
			get_param_integer($block_id.\'_start\',array_key_exists(\'start\',$map)?intval($map[\'start\']):0),
			((array_key_exists(\'pagination\',$map)?$map[\'pagination\']:\'0\')==\'1\'),
			get_param($block_id.\'_sort\',array_key_exists(\'sort\',$map)?$map[\'sort\']:\'m_join_time DESC\'),
			array_key_exists(\'parent_gallery\',$map)?$map[\'parent_gallery\']:\'\',
			array_key_exists(\'per_row\',$map)?intval($map[\'per_row\']):0,
			array_key_exists(\'guid\',$map)?$map[\'guid\']:\'\',
		)';
		$info['ttl']=60;
		return $info;
	}

	/**
	 * Standard modular run function.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
	function run($map)
	{
		if (get_forum_type()!='ocf') return paragraph(do_lang_tempcode('NO_OCF'),'red_alert');

		require_code('ocf_members');
		require_code('ocf_members2');
		require_code('ocfiltering');

		require_css('ocf_member_directory');

		$block_id=get_block_id($map);

		$guid=array_key_exists('guid',$map)?$map['guid']:'';

		$has_exists=db_has_subqueries($GLOBALS['SITE_DB']->connection_read);

		$where='id<>'.strval($GLOBALS['FORUM_DRIVER']->get_guest_id());

		$usergroup=array_key_exists('usergroup',$map)?$map['usergroup']:'';

		$ocselect=array_key_exists('ocselect',$map)?$map['ocselect']:'';
		if ((!empty($map['filters_row_a'])) || (!empty($map['filters_row_b'])))
		{
			$filters_row_a=array_key_exists('filters_row_a',$map)?$map['filters_row_a']:'';
			$filters_row_b=array_key_exists('filters_row_b',$map)?$map['filters_row_b']:'';
		} else
		{
			$filters_row_a='m_username='.php_addslashes(do_lang('USERNAME')).',usergroup='.php_addslashes(do_lang('GROUP'));
			$filters_row_b='';
			$cpfs=ocf_get_all_custom_fields_match(ocf_get_all_default_groups(),1,1,NULL,NULL,1,NULL);
			$_filters_row_a=2;
			$_filters_row_b=0;
			foreach ($cpfs as $cpf)
			{
				$cf_name=get_translated_text($cpf['cf_name']);
				if (in_array($cpf['cf_type'],array('combo','combo_multi','float','integer','list','long_text','long_trans','radiolist','short_text','short_text_multi','short_trans','short_trans_multi')))
				{
					$filter_term=str_replace(',','\,',$cf_name).'='.str_replace(',','\,',$cf_name);
					if ($_filters_row_a<6)
					{
						if ($filters_row_a!='') $filters_row_a.=',';
						$filters_row_a.=$filter_term;
						$_filters_row_a++;
					} else
					{
						if ($filters_row_b!='') $filters_row_b.=',';
						$filters_row_b.=$filter_term;
						$_filters_row_b++;
					}
				}
			}
		}
		foreach (array($filters_row_a,$filters_row_b) as $filters_row)
		{
			foreach (array_keys(block_params_str_to_arr($filters_row)) as $filter_term)
			{
				if ($filter_term!='')
				{
					if ($filter_term=='usergroup')
					{
						$usergroup=either_param('filter_'.$block_id.'_'.$filter_term,$usergroup);
					} else
					{
						if ($ocselect!='') $ocselect.=',';
						$ocselect.=$filter_term.'~=<'.$block_id.'_'.$filter_term.'>';
					}
				}
			}
		}
		if ($ocselect!='')
		{
			require_code('ocselect');
			$content_type='member';
			list($ocselect_extra_select,$ocselect_extra_join,$ocselect_extra_where)=ocselect_to_sql($GLOBALS['FORUM_DB'],parse_ocselect($ocselect),$content_type,'');
			$extra_select_sql=implode('',$ocselect_extra_select);
			$extra_join_sql=implode('',$ocselect_extra_join);
		} else
		{
			$extra_select_sql='';
			$extra_join_sql='';
			$ocselect_extra_where='';
		}
		$where.=$ocselect_extra_where;

		$filter=array_key_exists('filter',$map)?$map['filter']:'*';
		$where.=' AND ('.ocfilter_to_sqlfragment($filter,'id').')';

		if ($usergroup!='')
		{
			if (is_numeric($usergroup))
			{
				$group_id=intval($usergroup);
			} else
			{
				$group_id=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_groups g JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON t.id=g.g_name','g.id',array('text_original'=>$usergroup));
				if (is_null($group_id))
				{
					return paragraph(do_lang_tempcode('MISSING_RESOURCE'),'red_alert');
				}
			}
			if ($has_exists)
			{
				$where.=' AND (m_primary_group='.strval($group_id).' OR EXISTS(SELECT gm_member_id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_group_members x WHERE x.gm_member_id=r.id AND gm_validated=1 AND gm_group_id='.strval($group_id).'))';
			} else
			{
				$where.=' AND (m_primary_group='.strval($group_id).' OR gm_member_id=r.id AND gm_group_id='.strval($group_id).')';
			}
		}

		if ((!has_privilege(get_member(),'see_unvalidated')) && (addon_installed('unvalidated'))) $where.=' AND m_validated=1';

		$include_form=array_key_exists('include_form',$map)?($map['include_form']=='1'):true;

		$must_have_avatar=array_key_exists('must_have_avatar',$map)?($map['must_have_avatar']=='1'):false;
		if ($must_have_avatar)
		{
			$where.=' AND '.db_string_not_equal_to('m_avatar_url','');
		}
		$must_have_photo=array_key_exists('must_have_photo',$map)?($map['must_have_photo']=='1'):false;
		if ($must_have_photo)
		{
			$where.=' AND '.db_string_not_equal_to('m_photo_url','');
		}

		$display_mode=array_key_exists('display_mode',$map)?$map['display_mode']:'avatars';
		$show_avatar=true;
		switch ($display_mode)
		{
			case 'listing':
				$show_avatar=true;
				break;

			case 'boxes':
				$show_avatar=true;
				break;

			case 'photos':
				$show_avatar=false;
				break;

			case 'media':
				if (addon_installed('galleries'))
				{
					require_css('galleries');
					$show_avatar=true;
					break;
				}

			case 'avatars':
			default:
				$show_avatar=false;
				$display_mode='avatars';
				break;
		}

		$parent_gallery=array_key_exists('parent_gallery',$map)?$map['parent_gallery']:'';
		if ($parent_gallery=='') $parent_gallery='%';

		$per_row=array_key_exists('per_row',$map)?intval($map['per_row']):0;
		if ($per_row==0) $per_row=NULL;

		inform_non_canonical_parameter($block_id.'_sort');
		$sort=get_param($block_id.'_sort',array_key_exists('sort',$map)?$map['sort']:'m_join_time DESC');
		$sortables=array(
			'm_username'=>do_lang_tempcode('USERNAME'),
			'm_cache_num_posts'=>do_lang_tempcode('COUNT_POSTS'),
			'm_join_time'=>do_lang_tempcode('JOIN_DATE'),
			'm_last_visit_time'=>do_lang_tempcode('LAST_VISIT_TIME'),
			'm_profile_views'=>do_lang_tempcode('PROFILE_VIEWS'),
			'random'=>do_lang_tempcode('RANDOM'),
		);
		if (strpos(get_db_type(),'mysql')!==false)
		{
			$sortables['m_total_sessions']=do_lang_tempcode('LOGIN_FREQUENCY');
		}
		if (strpos($sort,' ')===false) $sort.=' ASC';
		list($sortable,$sort_order)=explode(' ',$sort,2);
		switch ($sort)
		{
			case 'random ASC':
			case 'random DESC':
				$sort='RAND() ASC';
				break;
			case 'm_total_sessions ASC':
				$sort='m_total_sessions/(UNIX_TIMESTAMP()-m_join_time) ASC';
				break;
			case 'm_total_sessions DESC':
				$sort='m_total_sessions/(UNIX_TIMESTAMP()-m_join_time) DESC';
				break;
			case 'm_join_time':
			case 'm_last_visit_time':
				$sort.=','.'id '.$sort_order; // Also order by ID, in case lots joined at the same time
				break;
			default:
				if (!isset($sortables[preg_replace('# (ASC|DESC)$#','',$sort)]))
				{
					$sort='m_join_time DESC';
				}
				break;
		}

		$sql='SELECT r.*'.$extra_select_sql.' FROM ';
		$main_sql=$GLOBALS['FORUM_DB']->get_table_prefix().'f_members r';
		$main_sql.=$extra_join_sql;
		if ((!$has_exists) && ($usergroup!=''))
		{
			$main_sql.=' LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_group_members g ON (r.id=g.gm_member_id AND gm_validated=1)';
		}
		$main_sql.=' WHERE '.$where;
		$sql.=$main_sql;
		$sql.=(can_arbitrary_groupby()?' GROUP BY r.id':'');
		$sql.=' ORDER BY '.$sort;
		$count_sql='SELECT COUNT(DISTINCT r.id) FROM '.$main_sql;

		inform_non_canonical_parameter($block_id.'_max');
		$max=get_param_integer($block_id.'_max',array_key_exists('max',$map)?intval($map['max']):30);
		if ($max==0) $max=30;
		inform_non_canonical_parameter($block_id.'_start');
		$start=get_param_integer($block_id.'_start',array_key_exists('start',$map)?intval($map['start']):0);

		$max_rows=$GLOBALS['FORUM_DB']->query_value_if_there($count_sql);

		$rows=$GLOBALS['FORUM_DB']->query($sql,($display_mode=='media')?($max+$start):$max,($display_mode=='media')?NULL:$start);
		$rows=remove_duplicate_rows($rows,'id');

		/*if (count($rows)==0)	We let our template control no-result output
		{
			return do_template('BLOCK_NO_ENTRIES',array(
				'HIGH'=>false,
				'TITLE'=>do_lang_tempcode('RECENT',make_string_tempcode(integer_format($max)),do_lang_tempcode('MEMBERS')),
				'MESSAGE'=>do_lang_tempcode('NO_ENTRIES'),
				'ADD_NAME'=>'',
				'SUBMIT_URL'=>'',
			));
		}*/

		$hooks=NULL;
		if (is_null($hooks)) $hooks=find_all_hooks('modules','topicview');
		$hook_objects=NULL;
		if (is_null($hook_objects))
		{
			$hook_objects=array();
			foreach (array_keys($hooks) as $hook)
			{
				require_code('hooks/modules/topicview/'.filter_naughty_harsh($hook));
				$object=object_factory('Hook_'.filter_naughty_harsh($hook),true);
				if (is_null($object)) continue;
				$hook_objects[$hook]=$object;
			}
		}

		$cnt=0;
		$member_boxes=array();
		foreach ($rows as $row)
		{
			$member_id=$row['id'];
			$box=render_member_box($member_id,true,$hooks,$hook_objects,$show_avatar,NULL,false);

			if ($display_mode=='media')
			{
				$gallery_sql='SELECT name,fullname FROM '.get_table_prefix().'galleries WHERE';
				$gallery_sql.=' name LIKE \''.db_encode_like('member\_'.strval($member_id).'\_'.$parent_gallery).'\'';
				$galleries=$GLOBALS['SITE_DB']->query($gallery_sql);
				foreach ($galleries as $gallery)
				{
					$num_images=$GLOBALS['SITE_DB']->query_select_value('images','COUNT(*)',array('cat'=>$gallery['name'],'validated'=>1));
					$num_videos=$GLOBALS['SITE_DB']->query_select_value('videos','COUNT(*)',array('cat'=>$gallery['name'],'validated'=>1));
					if (($num_images>0) || ($num_videos>0))
					{
						if ($cnt>=$start)
						{
							$member_boxes[]=array(
								'I'=>strval($cnt-$start+1),
								'BREAK'=>(!is_null($per_row)) && (($cnt-$start+1)%$per_row==0),
								'BOX'=>$box,
								'MEMBER_ID'=>strval($member_id),
								'GALLERY_NAME'=>$gallery['name'],
								'GALLERY_TITLE'=>get_translated_text($gallery['fullname']),
							);
						}

						$cnt++;
						if ($cnt+$start==$max) break; // We have to read deep with media mode, as the number to display is not determinable within an SQL limit range
					}
				}
			} else
			{
				$member_boxes[$member_id]=array(
					'I'=>strval($cnt+1),
					'BREAK'=>(!is_null($per_row)) && (($cnt+1)%$per_row==0),
					'BOX'=>$box,
					'MEMBER_ID'=>strval($member_id),
					'GALLERY_NAME'=>'',
					'GALLERY_TITLE'=>'',
				);

				$cnt++;
				if ($cnt==$max) break;
			}
		}

		require_code('templates_results_table');

		if (($display_mode=='listing') && (count($rows)>0))
		{
			$results_entries=new ocp_tempcode();

			$_fields_title=array();
			$_fields_title[]=(get_option('display_name_generator')=='')?do_lang_tempcode('USERNAME'):do_lang_tempcode('NAME');
			$_fields_title[]=do_lang_tempcode('PRIMARY_GROUP');
			if (addon_installed('points'))
				$_fields_title[]=do_lang_tempcode('POINTS');
			if (addon_installed('ocf_forum'))
				$_fields_title[]=do_lang_tempcode('COUNT_POSTS');
			if (get_option('use_lastondate')=='1')
				$_fields_title[]=do_lang_tempcode('LAST_VISIT_TIME');
			if (get_option('use_joindate')=='1')
				$_fields_title[]=do_lang_tempcode('JOIN_DATE');
			$fields_title=results_field_title($_fields_title,$sortables,'md_sort',$sortable.' '.$sort_order);
			require_code('ocf_members2');
			foreach ($rows as $row)
			{
				$_entry=array();

				$_entry[]=do_template('OCF_MEMBER_DIRECTORY_USERNAME',array(
					'ID'=>strval($row['id']),
					'USERNAME'=>$row['m_username'],
					'URL'=>$GLOBALS['FORUM_DRIVER']->member_profile_url($row['id'],true,true),
					'AVATAR_URL'=>addon_installed('ocf_member_avatars')?$row['m_avatar_url']:$row['m_photo_thumb_url'],
					'PHOTO_THUMB_URL'=>$row['m_photo_thumb_url'],
					'VALIDATED'=>($row['m_validated']==1),
					'CONFIRMED'=>($row['m_validated_email_confirm_code']==''),
					'BOX'=>$member_boxes[$row['id']]['BOX'],
				));

				$member_primary_group=ocf_get_member_primary_group($row['id']);
				$primary_group=ocf_get_group_link($member_primary_group);
				$_entry[]=$primary_group;

				if (addon_installed('points'))
				{
					require_code('points');
					$_entry[]=integer_format(total_points($row['id']));
				}

				if (addon_installed('ocf_forum'))
					$_entry[]=integer_format($row['m_cache_num_posts']);

				if (get_option('use_joindate')=='1')
					$_entry[]=escape_html(get_timezoned_date($row['m_join_time'],false));

				if (get_option('use_lastondate')=='1')
					$_entry[]=escape_html(get_timezoned_date($row['m_last_visit_time'],false));

				$results_entries->attach(results_entry($_entry));
			}
			$results_table=results_table(do_lang_tempcode('MEMBERS'),$start,$block_id.'_start',$max,$block_id.'_max',$max_rows,$fields_title,$results_entries,$sortables,$sortable,$sort_order,$block_id.'_sort');

			$sorting=new ocp_tempcode();
		} else
		{
			$results_table=new ocp_tempcode();

			$do_pagination=((array_key_exists('pagination',$map)?$map['pagination']:'0')=='1');
			if ($do_pagination)
			{
				require_code('templates_pagination');
				$pagination=pagination(do_lang_tempcode('MEMBERS'),$start,$block_id.'_start',$max,$block_id.'_max',$max_rows,true);
			} else
			{
				$pagination=new ocp_tempcode();
			}

			$sorting=results_sorter($sortables,$sortable,$sort_order,$block_id.'_sort');
		}

		$_usergroups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(true,false,false);
		$usergroups=array();
		require_code('ocf_groups2');
		foreach ($_usergroups as $group_id=>$group)
		{
			$num=ocf_get_group_members_raw_count($group_id,true);
			$usergroups[$group_id]=array('USERGROUP'=>$group,'NUM'=>strval($num));
		}

		$symbols=NULL;
		if (get_option('allow_alpha_search')=='1')
		{
			$alpha_query=$GLOBALS['FORUM_DB']->query('SELECT m_username FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_members WHERE id<>'.strval(db_get_first_id()).' ORDER BY m_username ASC');
			$symbols=array(array('START'=>'0','SYMBOL'=>do_lang('ALL')),array('START'=>'0','SYMBOL'=>'#'));
			foreach (array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z') as $s)
			{
				foreach ($alpha_query as $i=>$q)
				{
					if (strtolower(substr($q['m_username'],0,1))==$s)
					{
						break;
					}
				}
				if (substr(strtolower($q['m_username']),0,1)!=$s) $i=intval($symbols[count($symbols)-1]['START']);
				$symbols[]=array('START'=>strval(intval($max*floor(floatval($i)/floatval($max)))),'SYMBOL'=>$s);
			}
		}

		$has_active_filter=false;
		foreach (array_keys($_GET) as $key)
		{
			if (substr($key,0,strlen($block_id.'_filter_'))==$block_id.'_filter_')
			{
				$has_active_filter=true;
				break;
			}
		}

		return do_template('BLOCK_MAIN_MEMBERS',array(
			'_GUID'=>$guid,
			'BLOCK_ID'=>$block_id,
			'START'=>strval($start),
			'MAX'=>strval($max),
			'SORTABLE'=>$sortable,
			'SORT_ORDER'=>$sort_order,
			'FILTERS_ROW_A'=>$filters_row_a,
			'FILTERS_ROW_B'=>$filters_row_b,
			'ITEM_WIDTH'=>is_null($per_row)?'':float_to_raw_string(99.0/*avoid possibility of rounding issues as pixels won't divide perfectly*//floatval($per_row)).'%',
			'PER_ROW'=>is_null($per_row)?'':strval($per_row),
			'DISPLAY_MODE'=>$display_mode,
			'MEMBER_BOXES'=>$member_boxes,
			'PAGINATION'=>new ocp_tempcode(),
			'RESULTS_TABLE'=>$results_table,
			'USERGROUPS'=>$usergroups,
			'SYMBOLS'=>$symbols,
			'HAS_ACTIVE_FILTER'=>$has_active_filter,
			'INCLUDE_FORM'=>$include_form,
			'SORT'=>$sorting,
		));
	}

}
