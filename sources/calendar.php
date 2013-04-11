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
 * @package		calendar
 */

/**
 * Standard code module init function.
 */
function init__calendar()
{
	require_code('temporal2');
}

/**
 * Get the week number for a time.
 *
 * @param  TIME				The week timestamp
 * @param  boolean			Whether to do it contextually to the year, rather than including the year
 * @return string				The week number
 */
function get_week_number_for($timestamp,$no_year=false)
{
	$format=$no_year?'W':'o-W';
	$w=intval(date('w',$timestamp));
	if (get_option('ssw')=='0')
	{
		return date($format,$timestamp);
	}
	return date($format,$timestamp-60*60*24); // For SSW: week starts one day earlier, so first day of week actually pushes back onto end of previous one
}

/**
 * Converts year+week to year+month+day. This is really complex. The first week of a year may actually start in December. The first day of the first week is a Monday or a Sunday, depending on configuration.
 *
 * @param  integer			Year #
 * @param  integer			Week #
 * @return array				Month #,Day #,Year #
 */
function date_from_week_of_year($year,$week)
{
	$basis=strval($year).'-'.str_pad(strval($week),2,'0',STR_PAD_LEFT);
	$time=mktime(0,0,0,1,1,$year);
	for ($i=($week==52)?300/*conditional to stop it finding week as previous year overlap week of same number*/:0;$i<366;$i++)
	{
		$new_time=$time+60*60*24*$i;
		if (((date('w',$new_time)=='1') && (get_option('ssw')=='0')) || ((date('w',$new_time)=='0') && (get_option('ssw')=='1')))
		{
			$test=get_week_number_for($new_time);
			if ($test==$basis)
			{
				$exploded=explode('-',date('m-d-Y',$new_time));
				return array(intval($exploded[0]),intval($exploded[1]),intval($exploded[2]));
			}
		}
	}
	return array(NULL,NULL,NULL);
}

/**
 * Find a list of pairs specifying the times the event occurs, for 20 years into the future, in user-time.
 *
 * @param  ID_TEXT		The timezone for the event (NULL: current user's timezone)
 * @param  BINARY			Whether the time should be converted to the viewer's own timezone
 * @param  integer		The year the event starts at. This and the below are in server time
 * @param  integer		The month the event starts at
 * @param  integer		The day the event starts at
 * @param  integer		The hour the event starts at
 * @param  integer		The minute the event starts at
 * @param  ?integer		The year the event ends at (NULL: not a multi day event)
 * @param  ?integer		The month the event ends at (NULL: not a multi day event)
 * @param  ?integer		The day the event ends at (NULL: not a multi day event)
 * @param  ?integer		The hour the event ends at (NULL: not a multi day event / all day event)
 * @param  ?integer		The minute the event ends at (NULL: not a multi day event / all day event)
 * @param  string			The event recurrence
 * @param  ?integer		The number of recurrences (NULL: none/infinite)
 * @param  ?TIME			The timestamp that found times must exceed. In user-time (NULL: now)
 * @param  ?TIME			The timestamp that found times must not exceed. In user-time (NULL: 20 years time)
 * @return array			A list of pairs for period times (timestamps, in user-time). Actually a series of pairs, 'window-bound timestamps' is first pair, then 'true coverage timestamps', then 'true coverage timestamps without timezone conversions'
 */
function find_periods_recurrence($timezone,$do_timezone_conv,$start_year,$start_month,$start_day,$start_hour,$start_minute,$end_year,$end_month,$end_day,$end_hour,$end_minute,$recurrence,$recurrences,$period_start=NULL,$period_end=NULL)
{
	if ($recurrences===0) return array();

	if (is_null($period_start)) $period_start=utctime_to_usertime(time());
	if (is_null($period_end)) $period_end=utctime_to_usertime(time()+60*60*24*360*20);

	$times=array();
	$i=0;
	$parts=explode(' ',$recurrence);
	if (count($parts)!=1)
	{
		$recurrence=$parts[0];
		$mask=$parts[1];
		$mask_len=strlen($mask);
	} else
	{
		$mask='1';
		$mask_len=1;
	}

	$a=0;

	$dif_day=0;
	$dif_month=0;
	$dif_year=0;
	$dif=utctime_to_usertime()-utctime_to_usertime(mktime($start_hour,$start_minute,0,$start_month,$start_day,$start_year));
	switch ($recurrence) // If a long way out of range, accelerate forward before steadedly looping forward till we might find a match (doesn't jump fully forward, due to possibility of timezones complicating things)
	{
		case 'daily':
			$dif_day=1;
			if (($dif>60*60*24*10) && ($mask_len==0))
			{
				$zoom=$dif_day*intval(floor(floatval($dif)/(60.0*60.0*24.0)));
				$start_day+=$zoom;
				if (!is_null($end_day)) $end_day+=$zoom;
			}
			break;
		case 'weekly':
			$dif_day=7;
			if (($dif>60*60*24*70) && ($mask_len==0))
			{
				$zoom=$dif_day*intval(floor(floatval($dif)/(60.0*60.0*24.0)))-70;
				$start_day+=$zoom;
				if (!is_null($end_day)) $end_day+=$zoom;
			}
			break;
		case 'monthly':
			$dif_month=1;
			if (($dif>60*60*24*31*10) && ($mask_len==0))
			{
				$zoom=$dif_month*intval(floor(floatval($dif)/(60.0*60.0*24.0*31.0)))-10;
				$start_month+=$zoom;
				if (!is_null($end_day)) $end_day+=$zoom;
			}
			break;
		case 'yearly':
			$dif_year=1;
			if (($dif>60*60*24*365*10) && ($mask_len==0))
			{
				$zoom=$dif_year*intval(floor(floatval($dif)/(60.0*60.0*24.0*365.0)))-1;
				$start_year+=$zoom;
				if (!is_null($end_day)) $end_day+=$zoom;
			}
			break;
	}

	$_b=mixed();
	$b=mixed();

	do
	{
		/*
		Consider this scenario...

		An event ends at end of 1/1/2012 (23:59), which is 22:59 in UTC if they are in +1 timezone

		Therefore the event, which is stored in UTC, needs a server time of 22:59 before going through cal_utctime_to_usertime

		The server already has the day stored UTC which may be different to the day stored for the +1 timezone (in fact either the start or end day will be stored differently, assuming there is an end day)
		*/

		$_a=cal_get_start_utctime_for_event($timezone,$start_year,$start_month,$start_day,$start_hour,$start_minute,$do_timezone_conv==1);
		$a=cal_utctime_to_usertime(
			$_a,
			$timezone,
			$do_timezone_conv==1
		);
		if ((is_null($start_hour)) && (is_null($end_year) || is_null($end_month) || is_null($end_day))) // All day event with no end date, should be same as start date
		{
			// Should not be needed, but normalise possible database error
			$start_minute=NULL;
			$start_hour=NULL;
			$end_minute=NULL;
			$end_hour=NULL;

			$end_day=$start_day;
			$end_month=$start_month;
			$end_year=$start_year;
		}
		if (is_null($end_year) || is_null($end_month) || is_null($end_day))
		{
			$_b=NULL;
			$b=NULL;
		} else
		{
			$_b=cal_get_end_utctime_for_event($timezone,$end_year,$end_month,$end_day,$end_hour,$end_minute,$do_timezone_conv==1);

			$b=cal_utctime_to_usertime(
				$_b,
				$timezone,
				$do_timezone_conv==1
			);
		}
		$starts_within=(($a>=$period_start) && ($a<$period_end));
		$ends_within=(($b>$period_start) && ($b<=$period_end));
		$spans=(($a<$period_start) && ($b>$period_end));
		if (($starts_within || $ends_within || $spans) && (in_array($mask[$i%$mask_len],array('1','y'))))
		{
			$times[]=array(max($period_start,$a),min($period_end,$b),$a,$b,$_a,$_b);
		}
		$i++;

		$start_day+=$dif_day;
		$start_month+=$dif_month;
		$start_year+=$dif_year;
		if (!is_null($end_year) && !is_null($end_month) && !is_null($end_day))
		{
			$end_day+=$dif_day;
			$end_month+=$dif_month;
			$end_year+=$dif_year;
		}

		if ($i==300) break; // Let's be reasonable
	}
	while (($recurrence!='') && ($recurrence!='none') && ($a<$period_end) && ((is_null($recurrences)) || ($i<$recurrences)));

	return $times;
}

/**
 * Get a list of event types, taking security into account against the current member.
 *
 * @param  ?AUTO_LINK		The event type to select by default (NULL: none)
 * @return tempcode			The list
 */
function nice_get_event_types($it=NULL)
{
	$type_list=new ocp_tempcode();
	$types=$GLOBALS['SITE_DB']->query_select('calendar_types',array('id','t_title'));
	$first_type=NULL;
	foreach ($types as $type)
	{
		if (!has_category_access(get_member(),'calendar',strval($type['id']))) continue;
		if (!has_submit_permission('low',get_member(),get_ip_address(),'cms_calendar',array('calendar',$type['id']))) continue;

		if ($type['id']!=db_get_first_id())
			$type_list->attach(form_input_list_entry(strval($type['id']),$type['id']==$it,get_translated_text($type['t_title'])));
		else $first_type=$type;
	}
	if ((has_actual_page_access(get_member(),'admin_occle')) && (!is_null($first_type)) && (is_null($GLOBALS['CURRENT_SHARE_USER'])))
		$type_list->attach(form_input_list_entry(strval(db_get_first_id()),db_get_first_id()==$it,get_translated_text($first_type['t_title'])));

	return $type_list;
}

/**
 * Regenerate all the calendar jobs for reminders for next occurance of an event (because the event was added or edited).
 *
 * @param  AUTO_LINK		The ID of the event
 * @param  boolean		Force evaluation even if it's in the past. Only valid for code events
 */
function regenerate_event_reminder_jobs($id,$force=false)
{
	$events=$GLOBALS['SITE_DB']->query_select('calendar_events',array('*'),array('id'=>$id),'',1);

	if (!array_key_exists(0,$events)) return;
	$event=$events[0];

	$GLOBALS['SITE_DB']->query_delete('calendar_jobs',array('j_event_id'=>$id));

	$period_start=$force?0:NULL;
	$recurrences=find_periods_recurrence($event['e_timezone'],$event['e_do_timezone_conv'],$event['e_start_year'],$event['e_start_month'],$event['e_start_day'],is_null($event['e_start_hour'])?0:$event['e_start_hour'],is_null($event['e_start_minute'])?0:$event['e_start_minute'],$event['e_end_year'],$event['e_end_month'],$event['e_end_day'],is_null($event['e_end_hour'])?23:$event['e_end_hour'],is_null($event['e_end_minute'])?0:$event['e_end_minute'],$event['e_recurrence'],min(1,$event['e_recurrences']),$period_start);
	if ((array_key_exists(0,$recurrences)) && ($recurrences[0][0]==$recurrences[0][2]/*really starts in window, not just spanning it*/))
	{
		if ($event['e_type']==db_get_first_id()) // Add system command job if necessary
		{
			$GLOBALS['SITE_DB']->query_insert('calendar_jobs',array(
				'j_time'=>usertime_to_utctime($recurrences[0][0]),
				'j_reminder_id'=>NULL,
				'j_member_id'=>NULL,
				'j_event_id'=>$id
			));
		} else
		{
			if (function_exists('set_time_limit')) @set_time_limit(0);

			$start=0;
			do
			{
				$reminders=$GLOBALS['SITE_DB']->query_select('calendar_reminders',array('*'),array('e_id'=>$id),'',500,$start);

				foreach ($reminders as $reminder)
				{
					$GLOBALS['SITE_DB']->query_insert('calendar_jobs',array(
						'j_time'=>usertime_to_utctime($recurrences[0][0])-$reminder['n_seconds_before'],
						'j_reminder_id'=>$reminder['id'],
						'j_member_id'=>$reminder['n_member_id'],
						'j_event_id'=>$event['id']
					));
				}
				$start+=500;
			}
			while (array_key_exists(0,$reminders));
		}
	}
}

/**
 * Create a neatly human-readable date range, using various user-friendly readability tricks.
 *
 * @param  TIME				From time in user time
 * @param  TIME				To time in user time
 * @param  boolean			Whether time is included in this date range
 * @return string				Textual specially-formatted range
 */
function date_range($from,$to,$do_time=true)
{
	if (($to-$from>60*60*24) || (!$do_time))
	{
		$_length=do_lang('DAYS',integer_format(intval(ceil(($to-$from)/(60*60*24.0)))));
		if (!$do_time) return $_length;
		$date=locale_filter(date(do_lang(($to-$from>60*60*24*5)?'calendar_date_range_single_long':'calendar_date_range_single'),$from));
		$date2=locale_filter(date(do_lang(($to-$from>60*60*24*5)?'calendar_date_range_single_long':'calendar_date_range_single'),$to));
	} else
	{
		// Duration between times
		$_length=display_time_period($to-$from);
		$pm_a=date('a',$from);
		$pm_b=date('a',$to);
		if ($pm_a==$pm_b)
		{
			$date=str_replace(do_lang('calendar_minute_no_seconds'),'',locale_filter(my_strftime(do_lang('calendar_minute_ampm_known'),$from)));
			$date2=str_replace(do_lang('calendar_minute_no_seconds'),'',locale_filter(my_strftime(do_lang('calendar_minute'),$to)));
		} else
		{
			$date=str_replace(do_lang('calendar_minute_no_seconds'),'',locale_filter(my_strftime(do_lang('calendar_minute'),$from)));
			$date2=str_replace(do_lang('calendar_minute_no_seconds'),'',locale_filter(my_strftime(do_lang('calendar_minute'),$to)));
		}
	}

	return do_lang('EVENT_TIME_RANGE',$date,$date2,$_length);
}

/**
 * Detect calendar matches in a time period, in user-time.
 *
 * @param  MEMBER			The member to detect conflicts for
 * @param  boolean		Whether to restrict only to viewable events for the current member
 * @param  ?TIME			The timestamp that found times must exceed. In user-time (NULL: use find_periods_recurrence default)
 * @param  ?TIME			The timestamp that found times must not exceed. In user-time (NULL: use find_periods_recurrence default)
 * @param  ?array			The type filter (NULL: none)
 * @param  boolean		Whether to include RSS events in the results
 * @return array			A list of events happening, with time details
 */
function calendar_matches($member_id,$restrict,$period_start,$period_end,$filter=NULL,$do_rss=true)
{
	if (is_null($period_start)) $period_start=utctime_to_usertime(time());
	if (is_null($period_end)) $period_end=utctime_to_usertime(time()+60*60*24*360*20);

	$matches=array();
	$where='';
	if ($restrict)
	{
		if ($where!='') $where.=' AND ';
		$where.='(e_submitter='.strval((integer)$member_id).' OR e_is_public=1)';
	}
	if (!is_null($filter))
	{
		foreach ($filter as $a=>$b)
		{
			if ($b==0)
			{
				if ($where!='') $where.=' AND ';
				$where.='e_type<>'.strval((integer)substr($a,4));
			}
		}
	}
	if ($where!='') $where.=' AND ';
	$where.='(validated=1 OR e_is_public=0)';

	if (addon_installed('syndication_blocks'))
	{
		// Determine what feeds to overlay
		$feed_urls_todo=array();
		for ($i=0;$i<10;$i++)
		{
			$feed_url=post_param('feed_'.strval($i),ocp_admirecookie('feed_'.strval($i),''));
			require_code('users_active_actions');
			ocp_setcookie('feed_'.strval($i),$feed_url);
			if (($feed_url!='') && (preg_match('#^[\w\d\-\_]*$#',$feed_url)==0))
				$feed_urls_todo[$feed_url]=NULL;
		}
		$_event_types=list_to_map('id',$GLOBALS['SITE_DB']->query_select('calendar_types',array('id','t_title','t_logo','t_external_feed')));
		foreach ($_event_types as $j=>$_event_type)
		{
			if (($_event_type['t_external_feed']!='') && ((is_null($filter)) || (!array_key_exists($_event_type['id'],$filter)) || ($filter[$_event_type['id']]==1)) && (has_category_access(get_member(),'calendar',strval($_event_type['id']))))
				$feed_urls_todo[$_event_type['t_external_feed']]=$_event_type['id'];

			$_event_types[$j]['text_original']=get_translated_text($_event_type['t_title']);
		}
		$event_types=collapse_2d_complexity('text_original','t_logo',$_event_types);

		// Overlay it
		foreach ($feed_urls_todo as $feed_url=>$event_type)
		{
			$temp_file_path=ocp_tempnam('feed');
			require_code('files');
			$write_to_file=fopen($temp_file_path,'wb');
			http_download_file($feed_url,1024*512,false,false,'ocPortal',NULL,NULL,NULL,NULL,NULL,$write_to_file);

			if (($GLOBALS['HTTP_DOWNLOAD_MIME_TYPE']=='text/calendar') || ($GLOBALS['HTTP_DOWNLOAD_MIME_TYPE']=='application/octet-stream'))
			{
				$data=file_get_contents($temp_file_path);

				require_code('calendar_ical');

				$whole=end(explode('BEGIN:VCALENDAR',$data));

				$events=explode('BEGIN:VEVENT',$whole);

				$calendar_nodes=array();

				foreach ($events as $key=>$items)
				{		
					$nodes=explode("\n",$items);

					foreach ($nodes as $childs)
					{
						if (preg_match('#^[^"]*:#',$childs)!=0)
							$child=explode(':',$childs,2);
						else $child=array($childs);

						$matches2=array();
						if (preg_match('#;TZID=(.*)#',$child[0],$matches2))
							$calendar_nodes[$key]['TZID']=$matches2[1];
						$child[0]=preg_replace('#;.*#','',$child[0]);

						if (array_key_exists("1",$child) && $child[0]!=='PRODID' &&  $child[0]!=='VERSION' && $child[0]!=='END')
							$calendar_nodes[$key][$child[0]]=str_replace(array('\n','\,'),array("\n",','),trim($child[1]," \t\""));
					}
					if ($key!=0)
					{
						list($full_url,$type,$recurrence,$recurrences,$seg_recurrences,$title,$content,$priority,$is_public,$start_year,$start_month,$start_day,$start_hour,$start_minute,$end_year,$end_month,$end_day,$end_hour,$end_minute,$timezone,$validated,$allow_rating,$allow_comments,$allow_trackbacks,$notes)=get_event_data_ical($calendar_nodes[$key]);
						$is_public=1;

						$event=array('e_recurrence'=>$recurrence,'e_content'=>$content,'e_title'=>$title,'e_id'=>$feed_url,'e_priority'=>$priority,'t_logo'=>'calendar/rss','e_recurrences'=>$recurrences,'e_seg_recurrences'=>$seg_recurrences,'e_is_public'=>$is_public,'e_start_year'=>$start_year,'e_start_month'=>$start_month,'e_start_day'=>$start_day,'e_start_hour'=>$start_hour,'e_start_minute'=>$start_minute,'e_end_year'=>$end_year,'e_end_month'=>$end_month,'e_end_day'=>$end_day,'e_end_hour'=>$end_hour,'e_end_minute'=>$end_minute,'e_timezone'=>$timezone);
						if (!is_null($event_type)) $event['t_logo']=$_event_types[$event_type]['t_logo'];
						if (!is_null($type))
						{
							$event['t_title']=$type;
							if (array_key_exists($type,$event_types))
								$event['t_logo']=$event_types[$type];
						}

						$their_times=find_periods_recurrence($timezone,0,$start_year,$start_month,$start_day,$start_hour,$start_minute,$end_year,$end_month,$end_day,$end_hour,$end_minute,$recurrence,$recurrences,$period_start,$period_end);

						// Now search every combination to see if we can get a hit
						foreach ($their_times as $their)
						{
							$matches[]=array($full_url,$event,$their[0],$their[1],$their[2],$their[3],$their[4],$their[5]);
						}
					}
				}
			} else
			{
				require_code('rss');

				$rss=new rss($temp_file_path,true);

				$content=new ocp_tempcode();
				foreach ($rss->gleamed_items as $item)
				{
					if (array_key_exists('guid',$item)) $full_url=$item['guid'];
					elseif (array_key_exists('comment_url',$item)) $full_url=$item['comment_url'];
					elseif (array_key_exists('full_url',$item)) $full_url=$item['full_url'];
					else $full_url='';
					if ((array_key_exists('title',$item)) && (array_key_exists('clean_add_date',$item)) && ($full_url!=''))
					{
						$event=array('e_recurrence'=>'none','e_content'=>array_key_exists('news',$item)?$item['news']:'','e_title'=>$item['title'],'e_id'=>$full_url,'e_priority'=>'na','t_logo'=>'calendar/rss','e_recurrences'=>1,'e_seg_recurrences'=>'','e_is_public'=>1,'e_timezone'=>get_users_timezone());
						if (!is_null($event_type)) $event['t_logo']=$_event_types[$event_type]['t_logo'];
						if (array_key_exists('category',$item))
						{
							$event['t_title']=$item['category'];
							if (array_key_exists($item['category'],$event_types))
								$event['t_logo']=$event_types[$item['category']];
						}
						$from=utctime_to_usertime($item['clean_add_date']);
						if (($from>=$period_start) && ($from<$period_end))
						{
							$event+=array('e_start_year'=>date('Y',$from),'e_start_month'=>date('m',$from),'e_start_day'=>date('D',$from),'e_start_hour'=>date('H',$from),'e_start_minute'=>date('i',$from),'e_end_year'=>NULL,'e_end_month'=>NULL,'e_end_day'=>NULL,'e_end_hour'=>NULL,'e_end_minute'=>NULL);
							$matches[]=array($full_url,$event,$from,NULL,$from,NULL,$from,NULL);
						}
					}
				}
			}

			@unlink($temp_file_path);
		}
	}

	if ($where!='') $where.=' AND ';
	$where.='(((e_start_month>='.strval(intval(date('m',$period_start))-1).' AND e_start_year='.date('Y',$period_start).' OR e_start_year>'.date('Y',$period_start).') AND (e_end_month<='.strval(intval(date('m',$period_end))+1).' AND e_end_year='.date('Y',$period_end).' OR e_end_year<'.date('Y',$period_end).')) OR '.db_string_not_equal_to('e_recurrence','').')';

	$where=' WHERE '.$where;
	$event_count=$GLOBALS['SITE_DB']->query_value_null_ok_full('SELECT COUNT(*) FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'calendar_events e LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'calendar_types t ON e.e_type=t.id'.$where);
	if ($event_count>2000)
	{
		attach_message(do_lang_tempcode('TOO_MANY_TO_CHOOSE_FROM'),'inform');
		return array();
	}
	$events=$GLOBALS['SITE_DB']->query('SELECT *,e.id AS e_id FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'calendar_events e LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'calendar_types t ON e.e_type=t.id'.$where);
	foreach ($events as $event)
	{
		if (!has_category_access(get_member(),'calendar',strval($event['e_type']))) continue;

		$their_times=find_periods_recurrence($event['e_timezone'],$event['e_do_timezone_conv'],$event['e_start_year'],$event['e_start_month'],$event['e_start_day'],$event['e_start_hour'],$event['e_start_minute'],$event['e_end_year'],$event['e_end_month'],$event['e_end_day'],$event['e_end_hour'],$event['e_end_minute'],$event['e_recurrence'],$event['e_recurrences'],$period_start,$period_end);

		// Now search every combination to see if we can get a hit
		foreach ($their_times as $their)
		{
			$matches[]=array($event['e_id'],$event,$their[0],$their[1],$their[2],$their[3],$their[4],$their[5]);
		}
	}

	global $M_SORT_KEY;
	$M_SORT_KEY=2;
	usort($matches,'multi_sort');

	return $matches;
}

/**
 * Get a list of events to edit.
 *
 * @param  ?MEMBER			Only show events owned by this member (NULL: no such limitation)
 * @param  ?AUTO_LINK		Event to select by default (NULL: no specific default)
 * @param  boolean			Whether owned public events should be shown
 * @return tempcode			The list
 */
function nice_get_events($only_owned,$it,$edit_viewable_events=true)
{
	$where=array();
	if (!is_null($only_owned)) $where['e_submitter']=$only_owned;
	if (!$edit_viewable_events) $where['e_is_public']=0;
	if ($GLOBALS['SITE_DB']->query_value('calendar_events','COUNT(*)')>500) warn_exit(do_lang_tempcode('TOO_MANY_TO_CHOOSE_FROM'));
	$events=$GLOBALS['SITE_DB']->query_select('calendar_events',array('id','e_title','e_type'),$where);
	$list=new ocp_tempcode();
	foreach ($events as $event)
	{
		if (!has_category_access(get_member(),'calendar',strval($event['e_type']))) continue;

		$list->attach(form_input_list_entry(strval($event['id']),$event['id']==$it,get_translated_text($event['e_title'])));
	}

	return $list;
}

/**
 * Detect conflicts with an event at a certain time.
 *
 * @param  MEMBER			The member to detect conflicts for
 * @param  AUTO_LINK		The event ID that we are detecting conflicts with (we need this so we don't think we conflict with ourself)
 * @param  ?integer		The year the event starts at. This and the below are in server time (NULL: default)
 * @param  ?integer		The month the event starts at (NULL: default)
 * @param  ?integer		The day the event starts at (NULL: default)
 * @param  ?integer		The hour the event starts at (NULL: default)
 * @param  ?integer		The minute the event starts at (NULL: default)
 * @param  ?integer		The year the event ends at (NULL: not a multi day event)
 * @param  ?integer		The month the event ends at (NULL: not a multi day event)
 * @param  ?integer		The day the event ends at (NULL: not a multi day event)
 * @param  ?integer		The hour the event ends at (NULL: not a multi day event)
 * @param  ?integer		The minute the event ends at (NULL: not a multi day event)
 * @param  string			The event recurrence
 * @param  ?integer		The number of recurrences (NULL: none/infinite)
 * @return ?tempcode		Information about conflicts (NULL: none)
 */
function detect_conflicts($member_id,$skip_id,$start_year,$start_month,$start_day,$start_hour,$start_minute,$end_year,$end_month,$end_day,$end_hour,$end_minute,$recurrence,$recurrences)
{
	$our_times=find_periods_recurrence(get_users_timezone(),1,$start_year,$start_month,$start_day,$start_hour,$start_minute,$end_year,$end_month,$end_day,$end_hour,$end_minute,$recurrence,$recurrences);

	$conflicts=detect_happening_at($member_id,$skip_id,$our_times,!has_specific_permission(get_member(),'sense_personal_conflicts'));

	$out=new ocp_tempcode();
	$found_ids=array();
	foreach ($conflicts as $conflict)
	{
		list($id,$event,,)=$conflict;

		// Only show a conflict once
		if (array_key_exists($id,$found_ids)) continue;
		$found_ids[$id]=1;

		$url=build_url(array('page'=>'_SELF','type'=>'view','id'=>$id),'_SELF');
		$conflict=(($event['e_is_public']==1) || ($event['e_submitter']==get_member()))?make_string_tempcode(escape_html(get_translated_text($event['e_title']))):do_lang_tempcode('PRIVATE_HIDDEN');
		$out->attach(do_template('CALENDAR_EVENT_CONFLICT',array('_GUID'=>'2e209eae2dfe2ee74df61c0f4ffe1651','URL'=>$url,'ID'=>strval($id),'TITLE'=>$conflict)));
	}

	if (!$out->is_empty()) return $out;
	return NULL;
}

/**
 * Find first hour in day for a timezone.
 *
 * @param  ID_TEXT			Timezone
 * @param  integer			Year
 * @param  integer			Month
 * @param  integer			Day
 * @return integer			Hour
 */
function find_timezone_start_hour_in_utc($timezone,$year,$month,$day)
{
	$t1=mktime(0,0,0,$month,$day,$year);
	$t2=tz_time($t1,$timezone);
	$t2-=2*($t2-$t1);
	$ret=intval(date('H',$t2));
	return $ret;
}

/**
 * Find first minute in day for a timezone. Usually 0, but some timezones have 30 min offsets.
 *
 * @param  ID_TEXT			Timezone
 * @param  integer			Year
 * @param  integer			Month
 * @param  integer			Day
 * @return integer			Hour
 */
function find_timezone_start_minute_in_utc($timezone,$year,$month,$day)
{
	$t1=mktime(0,0,0,$month,$day,$year);
	$t2=tz_time($t1,$timezone);
	$t2-=2*($t2-$t1);
	$ret=intval(date('i',$t2));
	return $ret;
}

/**
 * Find last hour in day for a timezone.
 *
 * @param  ID_TEXT			Timezone
 * @param  integer			Year
 * @param  integer			Month
 * @param  integer			Day
 * @return integer			Hour
 */
function find_timezone_end_hour_in_utc($timezone,$year,$month,$day)
{
	$t1=mktime(23,59,0,$month,$day,$year);
	$t2=tz_time($t1,$timezone);
	$t2-=2*($t2-$t1);
	$ret=intval(date('H',$t2));
	return $ret;
}

/**
 * Find last minute in day for a timezone. Usually 59, but some timezones have 30 min offsets.
 *
 * @param  ID_TEXT			Timezone
 * @param  integer			Year
 * @param  integer			Month
 * @param  integer			Day
 * @return integer			Hour
 */
function find_timezone_end_minute_in_utc($timezone,$year,$month,$day)
{
	$t1=mktime(23,59,0,$month,$day,$year);
	$t2=tz_time($t1,$timezone);
	$t2-=2*($t2-$t1);
	$ret=intval(date('i',$t2));
	return $ret;
}

/**
 * Get the UTC start time for a specified UTC time event.
 *
 * @param  ID_TEXT			The timezone it is in; used to derive $hour and $minute if those are NULL, such that they start the day correctly for this timezone
 * @param  integer			Year
 * @param  integer			Month
 * @param  integer			Day
 * @param  ?integer			Hour (NULL: start hour of day in the timezone expressed as UTC, for whatever day the given midnight day/month/year shifts to after timezone conversion)
 * @param  ?integer			Minute (NULL: start minute of day in the timezone expressed as UTC, for whatever day the given midnight day/month/year shifts to after timezone conversion)
 * @param  boolean			Whether the time should be converted to the viewer's own timezone instead.
 * @return TIME				Timestamp
 */
function cal_get_start_utctime_for_event($timezone,$year,$month,$day,$hour,$minute,$show_in_users_timezone)
{
	$_hour=is_null($hour)?0:$hour;
	$_minute=is_null($minute)?0:$minute;

	$timestamp=mktime(
		$_hour,
		$_minute,
		0,
		$month,
		$day,
		$year
	);

	if (is_null($hour))
	{
		$timestamp_day_end=mktime(
			23,
			59,
			0,
			$month,
			$day,
			$year
		);

		$timezoned_timestamp=tz_time($timestamp_day_end,$timezone);

		$timestamp_day_start=mktime(
			0,
			0,
			0,
			$month,
			$day,
			$year
		);

		if (!$show_in_users_timezone) return $timestamp_day_start;

		return $timestamp_day_start+($timestamp_day_end-$timezoned_timestamp);
	}

	if (!$show_in_users_timezone) // Move into timezone, as if that is UTC, as it won't get converted later
	{
		$timestamp=tz_time($timestamp,$timezone);
	}

	return $timestamp;
}

/**
 * Get the UTC end time for a specified UTC time event.
 *
 * @param  ID_TEXT			Timezone
 * @param  integer			Year
 * @param  integer			Month
 * @param  integer			Day
 * @param  ?integer			Hour (NULL: end hour of day in the timezone expressed as UTC, for whatever day the given midnight day/month/year shifts to after timezone conversion)
 * @param  ?integer			Minute (NULL: end minute of day in the timezone expressed as UTC, for whatever day the given midnight day/month/year shifts to after timezone conversion)
 * @param  boolean			Whether the time should be converted to the viewer's own timezone instead.
 * @return TIME				Timestamp
 */
function cal_get_end_utctime_for_event($timezone,$year,$month,$day,$hour,$minute,$show_in_users_timezone)
{
	$_hour=is_null($hour)?23:$hour;
	$_minute=is_null($minute)?59:$minute;

	$timestamp=mktime(
		$_hour,
		$_minute,
		0,
		$month,
		$day,
		$year
	);

	if (is_null($hour))
	{
		$timestamp_day_start=mktime(
			0,
			0,
			0,
			$month,
			$day,
			$year
		);

		$timezoned_timestamp=tz_time($timestamp_day_start,$timezone);

		$timestamp_day_end=mktime(
			23,
			59,
			0,
			$month,
			$day,
			$year
		);

		if (!$show_in_users_timezone) return $timestamp_day_end;

		return $timestamp_day_end+($timestamp_day_start-$timezoned_timestamp);
	}

	if (!$show_in_users_timezone) // Move into timezone, as if that is UTC, as it won't get converted later
	{
		$timestamp=tz_time($timestamp,$timezone);
	}

	return $timestamp;
}

/**
 * Put a timestamp into the correct timezone for being reported onto the calendar.
 *
 * @param  TIME			Timestamp (proper UTC timestamp, not in user time)
 * @param  ID_TEXT		The timezone associated with the event (the passed $utc_timestamp should NOT be relative to this timezone, that must be UTC)
 * @param  boolean		Whether the time should be converted to the viewer's own timezone instead
 * @return TIME			Altered timestamp
 */
function cal_utctime_to_usertime($utc_timestamp,$default_timezone,$show_in_users_timezone)
{
	if (!$show_in_users_timezone) return $utc_timestamp;
	return tz_time($utc_timestamp,get_users_timezone());
}

/**
 * Detect conflicts with an event in certain time periods.
 *
 * @param  MEMBER			The member to detect conflicts for
 * @param  AUTO_LINK		The event ID that we are detecting conflicts with (we need this so we don't think we conflict with ourself)
 * @param  array			List of pairs specifying our happening time (in time order)
 * @param  boolean		Whether to restrict only to viewable events for the current member
 * @param  ?TIME			The timestamp that found times must exceed. In user-time (NULL: use find_periods_recurrence default)
 * @param  ?TIME			The timestamp that found times must not exceed. In user-time (NULL: use find_periods_recurrence default)
 * @return array			A list of events happening, with time details
 */
function detect_happening_at($member_id,$skip_id,$our_times,$restrict=true,$period_start=NULL,$period_end=NULL)
{
	if (count($our_times)==0) return array();

	$conflicts=array();
	$where=is_null($skip_id)?'':('id<>'.strval($skip_id));
	if ($restrict)
	{
		if ($where!='') $where.=' AND ';
		$where.='(e_submitter='.strval((integer)$member_id).' OR e_is_public=1)';
	}
	if ($where!='') $where.=' AND ';
	$where.='(validated=1 OR e_is_public=0)';
	$where.=' AND (((e_start_month>='.strval(intval(date('m',$our_times[0][0]))-1).' OR e_start_year>'.date('Y',$our_times[0][0]).') AND (e_end_month<='.strval(intval(date('m',$our_times[0][1]))+1).' OR e_end_year<'.date('Y',$our_times[0][1]).')) OR '.db_string_not_equal_to('e_recurrence','').')';
	$where=' WHERE '.$where;
	$table='calendar_events e';
	$events=$GLOBALS['SITE_DB']->query('SELECT *,e.id AS e_id FROM '.$GLOBALS['SITE_DB']->get_table_prefix().$table.$where);
	foreach ($events as $event)
	{
		if (!has_category_access(get_member(),'calendar',strval($event['e_type']))) continue;

		$their_times=find_periods_recurrence(
			$event['e_timezone'],
			1,
			$event['e_start_year'],
			$event['e_start_month'],
			$event['e_start_day'],
			is_null($event['e_start_hour'])?find_timezone_start_hour_in_utc($event['e_timezone'],$event['e_start_year'],$event['e_start_month'],$event['e_start_day']):$event['e_start_hour'],
			is_null($event['e_start_minute'])?find_timezone_start_minute_in_utc($event['e_timezone'],$event['e_start_year'],$event['e_start_month'],$event['e_start_day']):$event['e_start_minute'],
			$event['e_end_year'],
			$event['e_end_month'],
			$event['e_end_day'],
			is_null($event['e_end_hour'])?find_timezone_end_hour_in_utc($event['e_timezone'],$event['e_end_year'],$event['e_end_month'],$event['e_end_day']):$event['e_end_hour'],
			is_null($event['e_end_minute'])?find_timezone_end_minute_in_utc($event['e_timezone'],$event['e_end_year'],$event['e_end_month'],$event['e_end_day']):$event['e_end_minute'],
			$event['e_recurrence'],
			$event['e_recurrences'],
			$period_start,
			$period_end
		);

		// Now search every combination to see if we can get a hit
		foreach ($our_times as $our)
		{
			foreach ($their_times as $their)
			{
				$conflict=false;

				if ((is_null($our[3])) && (is_null($their[3]))) // Has to be exactly the same
				{
					if ($our[2]==$their[2]) $conflict=true;
				}

				elseif ((is_null($our[3])) && (!is_null($their[3]))) // Ours has to occur within their period
				{
					if (($our[2]>=$their[2]) && ($our[2]<$their[3])) $conflict=true;
				}

				elseif ((!is_null($our[3])) && (is_null($their[3]))) // Theirs has to occur within our period
				{
					if (($their[2]>=$our[2]) && ($their[2]<$our[3])) $conflict=true;
				}

				elseif ((!is_null($our[3])) && (!is_null($their[3]))) // The two periods need to overlap
				{
					if (($our[2]>=$their[2]) && ($our[2]<$their[3])) $conflict=true;
					if (($their[2]>=$our[2]) && ($their[2]<$our[3])) $conflict=true;
				}

				if ($conflict)
				{
					$conflicts[]=array($event['e_id'],$event,$their[2],$their[3]);
					break 2;
				}
			}
		}
	}

	return $conflicts;
}
