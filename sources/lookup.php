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
 * @package		securitylogging
 */

/**
 * Standard code module initialisation function.
 */
function init__lookup()
{
	require_code('submit'); // For the wrap_probe_ip function
}

/**
 * Get information about the specified member.
 *
 * @param  mixed			The member for whom we are getting the page
 * @param  ?string		The member's name (by reference) (NULL: unknown)
 * @param  ?AUTO_LINK 	The member's ID (by reference) (NULL: unknown)
 * @param  ?string		The member's IP (by reference) (NULL: unknown)
 * @return array			The member's stats rows
 */
function lookup_member_page($member,&$name,&$id,&$ip)
{
	if (is_numeric($member))
	{
		// From member ID
		$name=$GLOBALS['FORUM_DRIVER']->get_username(intval($member));
		if (is_null($name)) return array();
		$id=intval($member);
		$ip=$GLOBALS['FORUM_DRIVER']->get_member_ip($id);
		if (is_null($ip)) $ip='127.0.0.1';
	}
	elseif ((strpos($member,'.')!==false) || (strpos($member,':')!==false))
	{
		// From IP
		$ids=wrap_probe_ip($member);
		$ip=$member;
		if (is_null($ip)) $ip='127.0.0.1';
		if (count($ids)==0) return array(); else $id=$ids[0]['id'];
		if (count($ids)!=1)
		{
			$also=new ocp_tempcode();
			foreach ($ids as $t=>$_id)
			{
				if ($t!=0)
				{
					if (!$also->is_empty()) $also->attach(do_lang('LIST_SEP'));
					$also->attach($GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($_id['id'],false,'',false));
				}
			}
			attach_message(do_lang_tempcode('MEMBERS_ALSO_ON_IP',$also),'inform');
		}
		$name=$GLOBALS['FORUM_DRIVER']->get_username($id);
		if (is_null($name)) $name=do_lang('UNKNOWN');
	} else
	{
		// From name
		$id=$GLOBALS['FORUM_DRIVER']->get_member_from_username($member);
		$name=$member;
		if (is_null($id)) return array();
		$ip=$GLOBALS['FORUM_DRIVER']->get_member_ip($id);
		if (is_null($ip)) $ip='127.0.0.1';
	}

	return $GLOBALS['SITE_DB']->query_select('stats',array('ip','MAX(date_and_time) AS date_and_time'),array('member_id'=>$id),'GROUP BY ip ORDER BY date_and_time DESC');
}

/**
 * Get a results table showing info about the member's travels around the site.
 *
 * @param  MEMBER			The member we are getting travel stats for
 * @param  IP				The IP address of the member
 * @param  integer		The current position in the browser
 * @param  integer		The maximum number of rows to show per browser page
 * @param  ?ID_TEXT		The current sortable (NULL: none)
 * @param  ?ID_TEXT		The order we are sorting in (NULL: none)
 * @set    ASC DESC
 * @return tempcode		The results table
 */
function get_stats_track($member,$ip,$start=0,$max=50,$sortable='date_and_time',$sort_order='DESC')
{
	$sortables=array('date_and_time'=>do_lang_tempcode('DATE'),'the_page'=>do_lang_tempcode('PAGE'));
	if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
		log_hack_attack_and_exit('ORDERBY_HACK');
	inform_non_canonical_parameter('sort');

	$query='';
	if (!is_guest($member))
		$query.='member_id='.strval($member).' OR ';
	if (strpos($ip,'*')===false)
	{
		$query.=db_string_equal_to('ip',$ip);
	} else
	{
		$query.='ip LIKE \''.db_encode_like(str_replace('*','%',$ip)).'\'';
	}
	$max_rows=$GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(*) FROM '.get_table_prefix().'stats WHERE '.$query,false,true);
	$rows=$GLOBALS['SITE_DB']->query('SELECT the_page,date_and_time,get,post,browser,operating_system FROM '.get_table_prefix().'stats WHERE '.$query.' ORDER BY '.$sortable.' '.$sort_order,$max,$start,false,true);

	$out=new ocp_tempcode();
	require_code('templates_results_table');
	$fields_title=results_field_title(array(do_lang_tempcode('PAGE'),do_lang_tempcode('DATE'),do_lang_tempcode('PARAMETERS'),do_lang_tempcode('USER_AGENT'),do_lang_tempcode('USER_OS')),$sortables,'sort',$sortable.' '.$sort_order);
	foreach ($rows as $myrow)
	{
		$date=get_timezoned_date($myrow['date_and_time']);
		$page=$myrow['the_page'];

		$page_converted=preg_replace('#/pages/[^/]*/#','/',$page);
		if ($page_converted[0]=='/') $page_converted=substr($page_converted,1);
		if ((substr($page_converted,-4)=='.php') || (substr($page_converted,-4)=='.htm') || (substr($page_converted,-4)=='.txt'))
		{
			$page_converted=substr($page_converted,0,strlen($page_converted)-4);
		}
		$page_converted=str_replace('/',': ',$page_converted);

		if (!is_null($myrow['get']))
		{
			$get=$myrow['get'];
			if (strpos($page_converted,':')!==false)
				$get=str_replace('<param>page='.substr($page_converted,strpos($page_converted,':')+1).'</param>'.chr(10),'',$get);
			$data=escape_html($get).(($myrow['post']=='')?'':', ').escape_html($myrow['post']);
			$data=str_replace('&lt;param&gt;','',str_replace('&lt;/param&gt;',', ',$data));
			if (substr($data,-3)==', '.chr(10)) $data=substr($data,0,strlen($data)-3);
			$parameters=symbol_truncator(array($data,35,'1','1'),'left');
		} else $parameters='?';

		$out->attach(results_entry(array(escape_html($page_converted),escape_html($date),$parameters,escape_html($myrow['browser']),escape_html($myrow['operating_system'])),false));
	}
	return results_table(do_lang_tempcode('_RESULTS'),$start,'start',$max,'max',$max_rows,$fields_title,$out,$sortables,$sortable,$sort_order,'sort');
}

/**
 * Get a results table showing security alerts matching WHERE constraints.
 *
 * @param  ?array			WHERE constraints (NULL: none)
 * @return tempcode		The results table
 */
function find_security_alerts($where)
{
	// Alerts
	$start=get_param_integer('alert_start',0);
	$max=get_param_integer('alert_max',50);
	$sortables=array('date_and_time'=>do_lang_tempcode('DATE_TIME'),'ip'=>do_lang_tempcode('IP_ADDRESS'));
	$test=explode(' ',get_param('alert_sort','date_and_time DESC'));
	if (count($test)==1) $test[1]='DESC';
	list($sortable,$sort_order)=$test;
	if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
		log_hack_attack_and_exit('ORDERBY_HACK');
	inform_non_canonical_parameter('alert_sort');
	$_fields=array(do_lang_tempcode('FROM'),do_lang_tempcode('DATE_TIME'),do_lang_tempcode('IP_ADDRESS'),do_lang_tempcode('REASON'));
	if (has_js()) $_fields[]=new ocp_tempcode();
	$fields_title=results_field_title($_fields,$sortables,'alert_sort',$sortable.' '.$sort_order);
	$max_rows=$GLOBALS['SITE_DB']->query_select_value('hackattack','COUNT(*)',$where);
	$rows=$GLOBALS['SITE_DB']->query_select('hackattack',array('*'),$where,'ORDER BY '.$sortable.' '.$sort_order,$max,$start);
	$fields=new ocp_tempcode();
	foreach ($rows as $row)
	{
		$time=get_timezoned_date($row['date_and_time']);
		$lookup_url=build_url(array('page'=>'admin_lookup','param'=>$row['ip']),'_SELF');
		$member_url=build_url(array('page'=>'admin_lookup','param'=>$row['member_id']),'_SELF');
		$full_url=build_url(array('page'=>'admin_security','type'=>'view','id'=>$row['id']),'_SELF');
		$reason=do_lang($row['reason'],$row['reason_param_a'],$row['reason_param_b'],NULL,NULL,false);
		if (is_null($reason)) $reason=$row['reason'];
		$reason=symbol_truncator(array($reason,'50','1'),'left');

		$username=$GLOBALS['FORUM_DRIVER']->get_username($row['member_id']);
		if (is_null($username)) $username=do_lang('UNKNOWN');

		$_row=array(hyperlink($member_url,$username),hyperlink($full_url,$time),hyperlink($lookup_url,$row['ip']),$reason);
		if (has_js())
		{
			$deletion_tick=do_template('RESULTS_TABLE_TICK',array('_GUID'=>'9d310a90afa8bd1817452e476385bc57','ID'=>strval($row['id'])));
			$_row[]=$deletion_tick;
		}

		$fields->attach(results_entry($_row));
	}
	return results_table(do_lang_tempcode('SECURITY_ALERTS'),$start,'alert_start',$max,'alert_max',$max_rows,$fields_title,$fields,$sortables,$sortable,$sort_order,'alert_sort');
}


