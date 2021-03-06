<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_ocf
 */

/**
 * Standard code module initialisation function.
 */
function init__ocf_members()
{
	global $CUSTOM_FIELD_CACHE;
	$CUSTOM_FIELD_CACHE=array();
	global $MEMBER_CACHE_FIELD_MAPPINGS;
	$MEMBER_CACHE_FIELD_MAPPINGS=array();
	global $PRIMARY_GROUP_MEMBERS;
	$PRIMARY_GROUP_MEMBERS=array();
	global $MAY_WHISPER_CACHE;
	$MAY_WHISPER_CACHE=array();
}

/**
 * Find all the Private Topic filter categories employed by the current member.
 *
 * @param  boolean	Whether to only show ones that already have things in (i.e. not default ones)
 * @return array		List of filter categories
 */
function ocf_get_filter_cats($only_exists_now=false)
{
	$filter_rows_a=$GLOBALS['FORUM_DB']->query('SELECT DISTINCT t_pt_from_category FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics WHERE t_pt_from='.strval((integer)get_member()));
	$filter_rows_b=$GLOBALS['FORUM_DB']->query('SELECT DISTINCT t_pt_to_category FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics WHERE t_pt_to='.strval((integer)get_member()));
	$filter_cats=array(''=>1);
	if (!$only_exists_now)
		$filter_cats[do_lang('TRASH')]=1;
	if ($GLOBALS['FORUM_DB']->query_value('f_special_pt_access','COUNT(*)',array('s_member_id'=>get_member()))>0)
	$filter_cats[do_lang('INVITED_TO_PTS')]=1;
	foreach ($filter_rows_a as $filter_row)
		$filter_cats[$filter_row['t_pt_from_category']]=1;
	foreach ($filter_rows_b as $filter_row)
		$filter_cats[$filter_row['t_pt_to_category']]=1;

	return array_keys($filter_cats);
}

/**
 * Find whether a member of a certain username is bound to HTTP authentication (an exceptional situation, only for sites that use it).
 *
 * @param  string		The username.
 * @return ?integer	The member ID, if it is (NULL: not bound).
 */
function ocf_authusername_is_bound_via_httpauth($authusername)
{
	$ret=$GLOBALS['FORUM_DB']->query_value_null_ok('f_members','id',array('m_password_compat_scheme'=>'httpauth','m_pass_hash_salted'=>$authusername));
	if (is_null($ret))
		$ret=$GLOBALS['FORUM_DB']->query_value_null_ok_full('SELECT id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_members WHERE '.db_string_not_equal_to('m_password_compat_scheme','').' AND '.db_string_equal_to('m_username',$authusername));
	return $ret;
}

/**
 * Find whether a member is bound to HTTP LDAP (an exceptional situation, only for sites that use it).
 *
 * @param  MEMBER	The member.
 * @return boolean	The answer.
 */
function ocf_is_ldap_member($member_id)
{
	global $LDAP_CONNECTION;
	if (is_null($LDAP_CONNECTION)) return false;

	$scheme=$GLOBALS['OCF_DRIVER']->get_member_row_field($member_id,'m_password_compat_scheme');
	return $scheme=='ldap';
}

/**
 * Find whether a member is bound to HTTP authentication (an exceptional situation, only for sites that use it).
 *
 * @param  MEMBER	The member.
 * @return boolean	The answer.
 */
function ocf_is_httpauth_member($member_id)
{
	$scheme=$GLOBALS['OCF_DRIVER']->get_member_row_field($member_id,'m_password_compat_scheme');
	return $scheme=='httpauth';
}

/**
 * Gets all the system custom fields that match certain parameters.
 *
 * @param  ?array		That are applicable only to one of the usergroups in this list (empty: CPFs with no restriction) (NULL: disregard restriction).
 * @param  ?BINARY	That are publicly viewable (NULL: don't care).
 * @param  ?BINARY	That are owner viewable (NULL: don't care).
 * @param  ?BINARY	That are owner settable (NULL: don't care).
 * @param  ?BINARY	That are required (NULL: don't care).
 * @param  ?BINARY	That are to be shown in posts (NULL: don't care).
 * @param  ?BINARY	That are to be shown in post previews (NULL: don't care).
 * @param  BINARY		That start 'ocp_'
 * @param  ?boolean	That are to go on the join form (NULL: don't care).
 * @return array		A list of rows of such fields.
 */
function ocf_get_all_custom_fields_match($groups,$public_view=NULL,$owner_view=NULL,$owner_set=NULL,$required=NULL,$show_in_posts=NULL,$show_in_post_previews=NULL,$special_start=0,$show_on_join_form=NULL)
{
	global $CUSTOM_FIELD_CACHE;
	$x=serialize(array($public_view,$owner_view,$owner_set,$required,$show_in_posts,$show_in_post_previews,$special_start));
	if (array_key_exists($x,$CUSTOM_FIELD_CACHE)) // ocPortal offers a wide array of features. It's multi dimensional. ocPortal.. entering the 6th dimension. hyper-hyper-time.
	{
		$result=$CUSTOM_FIELD_CACHE[$x];
	} else
	{
		// Load up filters
		$hooks=find_all_hooks('systems','ocf_cpf_filter');
		$to_keep=array();
		foreach (array_keys($hooks) as $hook)
		{
			require_code('hooks/systems/ocf_cpf_filter/'.$hook);
			$_hook=object_factory('Hook_ocf_cpf_filter_'.$hook,true);
			if (is_null($_hook)) continue;
			$to_keep+=$_hook->to_enable();
		}

		$where='WHERE 1=1 ';
		if (!is_null($public_view)) $where.=' AND cf_public_view='.strval((integer)$public_view);
		if (!is_null($owner_view)) $where.=' AND cf_owner_view='.strval((integer)$owner_view);
		if (!is_null($owner_set)) $where.=' AND cf_owner_set='.strval((integer)$owner_set);
		if (!is_null($required)) $where.=' AND cf_required='.strval((integer)$required);
		if (!is_null($show_in_posts)) $where.=' AND cf_show_in_posts='.strval((integer)$show_in_posts);
		if (!is_null($show_in_post_previews)) $where.=' AND cf_show_in_post_previews='.strval((integer)$show_in_post_previews);
		if ($special_start==1) $where.=' AND tx.text_original LIKE \''.db_encode_like('ocp_%').'\'';
		if (!is_null($show_on_join_form)) $where.=' AND cf_show_on_join_form='.strval((integer)$show_on_join_form);

		global $TABLE_LANG_FIELDS;
		$_result=$GLOBALS['FORUM_DB']->query('SELECT f.* FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_custom_fields f LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate tx ON (tx.id=f.cf_name AND '.db_string_equal_to('tx.language',get_site_default_lang()).') '.$where.' ORDER BY cf_order',NULL,NULL,false,false,array_key_exists('f_custom_fields',$TABLE_LANG_FIELDS)?$TABLE_LANG_FIELDS['f_custom_fields']:array());
		$result=array();
		foreach ($_result as $row)
		{
			$row['trans_name']=get_translated_text($row['cf_name'],$GLOBALS['FORUM_DB']);

			if ((substr($row['trans_name'],0,4)=='ocp_') && ($special_start==0))
			{
				// See if it gets filtered
				if (!array_key_exists(substr($row['trans_name'],4),$to_keep)) continue;

				require_lang('ocf');
				$test=do_lang('SPECIAL_CPF__'.$row['trans_name'],NULL,NULL,NULL,NULL,false);
				if (!is_null($test)) $row['trans_name']=$test;
			}
			$result[]=$row;
		}

		$CUSTOM_FIELD_CACHE[$x]=$result;
	}

	$result2=array();
	foreach ($result as $row)
	{
		if (($row['cf_only_group']=='') || (is_null($groups)) || (count(array_intersect(explode(',',$row['cf_only_group']),$groups))!=0)) $result2[]=$row;
	}

	return $result2;
}

/**
 * Gets all a member's custom fields that match certain parameters.
 *
 * @param  MEMBER		The member.
 * @param  ?BINARY	That are publicly viewable (NULL: don't care).
 * @param  ?BINARY	That are owner viewable (NULL: don't care).
 * @param  ?BINARY	That are owner settable (NULL: don't care).
 * @param  ?BINARY	That are encrypted (NULL: don't care).
 * @param  ?BINARY	That are required (NULL: don't care).
 * @param  ?BINARY	That are to be shown in posts (NULL: don't care).
 * @param  ?BINARY	That are to be shown in post previews (NULL: don't care).
 * @param  BINARY		That start 'ocp_'
 * @param  ?boolean	That are to go on the join form (NULL: don't care).
 * @return array		A mapping of field title to a map of details: 'RAW' as the raw field value, 'RENDERED' as the rendered field value.
 */
function ocf_get_all_custom_fields_match_member($member_id,$public_view=NULL,$owner_view=NULL,$owner_set=NULL,$encrypted=NULL,$required=NULL,$show_in_posts=NULL,$show_in_post_previews=NULL,$special_start=0,$show_on_join_form=NULL)
{
	$fields_to_show=ocf_get_all_custom_fields_match($GLOBALS['FORUM_DRIVER']->get_members_groups($member_id),$public_view,$owner_view,$owner_set,$required,$show_in_posts,$show_in_post_previews,$special_start,$show_on_join_form);
	$custom_fields=array();
	$member_mappings=ocf_get_custom_field_mappings($member_id);
	$member_value=mixed(); // Initialise type to mixed
	$all_cpf_permissions=((get_member()==$member_id)||$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member()))?/*no restricts if you are the member or a super-admin*/array():list_to_map('field_id',$GLOBALS['FORUM_DB']->query_select('f_member_cpf_perms',array('*'),array('member_id'=>$member_id)));

	require_code('fields');

	foreach ($fields_to_show as $i=>$field_to_show)
	{
		$member_value=$member_mappings['field_'.strval($field_to_show['id'])];

		// Decrypt the value if appropriate
		if ((array_key_exists('cf_encrypted',$field_to_show)) && ($field_to_show['cf_encrypted']==1))
		{
			require_code('encryption');
			if ((is_encryption_enabled()) && (!is_null(post_param('decrypt',NULL))))
			{
				$member_value=decrypt_data($member_value,post_param('decrypt'));
			}
		}

		$ob=get_fields_hook($field_to_show['cf_type']);
		list(,,$storage_type)=$ob->get_field_value_row_bits($field_to_show);

		if (strpos($storage_type,'_trans')!==false)
		{
			if ((is_null($member_value)) || ($member_value==0)) $member_value=''; else $member_value=get_translated_tempcode($member_value,$GLOBALS['FORUM_DB']); // This is meant to be '' for blank, not new ocp_tempcode()
			if ((is_object($member_value)) && ($member_value->is_empty())) $member_value='';
		}

		// get custom permissions for the current CPF
		$cpf_permissions=array_key_exists($field_to_show['id'],$all_cpf_permissions)?$all_cpf_permissions[$field_to_show['id']]:array();

		$display_cpf=true;

		// if there are custom permissions set and we are not showing to all
		if ((array_key_exists(0,$cpf_permissions)) && (!is_null($public_view)))
		{
			$display_cpf=false;

			// Negative ones
			if ($cpf_permissions[0]['guest_view']==1) $display_cpf=true;
			if (!is_guest())
			{
				if ($cpf_permissions[0]['member_view']==1) $display_cpf=true;
			}

			if (!$display_cpf) // Guard this, as the code will take some time to run
			{
				if ($cpf_permissions[0]['friend_view']==1)
				{
					if (!is_null($GLOBALS['SITE_DB']->query_value_null_ok('chat_buddies','member_liked',array('member_likes'=>$member_id,'member_liked'=>get_member()))))
						$display_cpf=true;
				}

				if (!is_guest())
				{
					if ($cpf_permissions[0]['group_view']=='all')
					{
						$display_cpf=true;
					} else
					{
						if (strlen($cpf_permissions[0]['group_view'])>0)
						{
							require_code('ocfiltering');

							$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,false,false,NULL,$member_id);

							$groups_to_search=array();
							foreach (array_keys($groups) as $group_id)
							{
								$groups_to_search[$group_id]=NULL;
							}
							$matched_groups=ocfilter_to_idlist_using_memory($cpf_permissions[0]['group_view'],$groups_to_search);

							if (count($matched_groups)>0)
							{
								$display_cpf=true;
							}
						}
					}
				}
			}
		}

		if ($display_cpf)
		{
			$rendered_value=$ob->render_field_value($field_to_show,$member_value,$i,NULL);

			$custom_fields[$field_to_show['trans_name']]=array('RAW'=>$member_value,'RENDERED'=>$rendered_value);
		}
	}

	return $custom_fields;
}

/**
 * Get the ID for a CPF if we only know the title. Warning: Only use this with custom code, never core code! It assumes a single language and that fields aren't renamed.
 *
 * @param  SHORT_TEXT	The title.
 * @return ?AUTO_LINK	The ID (NULL: could not find).
 */
function find_cpf_field_id($title)
{
	$fields_to_show=ocf_get_all_custom_fields_match(NULL);
	foreach ($fields_to_show as $field_to_show)
	{
		if ($field_to_show['trans_name']==$title)
		{
			return $field_to_show['id'];
		}
	}
	return NULL;
}

/**
 * Returns a list of all field values for user. Doesn't take translation into account. Doesn't take anything permissive into account.
 *
 * @param  MEMBER	The member.
 * @return array	The list.
 */
function ocf_get_custom_field_mappings($member_id)
{
	require_code('fields');

	global $MEMBER_CACHE_FIELD_MAPPINGS;
	if (!array_key_exists($member_id,$MEMBER_CACHE_FIELD_MAPPINGS))
	{
		$query=$GLOBALS['FORUM_DB']->query_select('f_member_custom_fields',array('*'),array('mf_member_id'=>$member_id),'',1);
		if (!array_key_exists(0,$query)) // Repair
		{
			$all_fields_regardless=$GLOBALS['FORUM_DB']->query_select('f_custom_fields',array('id','cf_type'));
			$row=array('mf_member_id'=>$member_id);
			foreach ($all_fields_regardless as $field)
			{
				$ob=get_fields_hook($field['cf_type']);
				list(,$default,$storage_type)=$ob->get_field_value_row_bits($field,false,'',$GLOBALS['FORUM_DB']);

				if (strpos($storage_type,'_trans')!==false)
				{
					$row['field_'.strval($field['id'])]=intval($default);
				} else
				{
					$row['field_'.strval($field['id'])]=$default;
				}
			}
			$GLOBALS['FORUM_DB']->query_insert('f_member_custom_fields',$row);
			$query=array($row);
		}
		$MEMBER_CACHE_FIELD_MAPPINGS[$member_id]=$query[0];
	}
	return $MEMBER_CACHE_FIELD_MAPPINGS[$member_id];
}

/**
 * Returns a mapping between field number and field value. Doesn't take translation into account. Doesn't take anything permissive into account.
 *
 * @param  MEMBER	The member.
 * @return array	The mapping.
 */
function ocf_get_custom_fields_member($member_id)
{
	$row=ocf_get_custom_field_mappings($member_id);
	$result=array();
	foreach ($row as $column=>$val)
	{
		if (substr($column,0,6)=='field_')
		{
			$result[intval(substr($column,6))]=$val;
		}
	}
	return $result;
}

/**
 * Get the primary of a member (supports consulting of LDAP).
 *
 * @param  MEMBER	The member.
 * @return GROUP	The primary.
 */
function ocf_get_member_primary_group($member_id)
{
	global $PRIMARY_GROUP_MEMBERS;
	if (array_key_exists($member_id,$PRIMARY_GROUP_MEMBERS)) return $PRIMARY_GROUP_MEMBERS[$member_id];

	if (ocf_is_ldap_member($member_id))
	{
		ocf_ldap_get_member_primary_group($member_id);
	} else
	{
		$PRIMARY_GROUP_MEMBERS[$member_id]=$GLOBALS['OCF_DRIVER']->get_member_row_field($member_id,'m_primary_group');
	}

	return $PRIMARY_GROUP_MEMBERS[$member_id];
}
