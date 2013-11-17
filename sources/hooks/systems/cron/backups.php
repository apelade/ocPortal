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
 * @package		backup
 */

class Hook_cron_backups
{
	/**
	 * Standard modular run function for CRON hooks. Searches for tasks to perform.
	 */
	function run()
	{
		if (!addon_installed('backup')) return;

		$backup_schedule_time=intval(get_value('backup_schedule_time'));

		if ($backup_schedule_time!=0)
		{
			$backup_recurrance_days=intval(get_value('backup_recurrance_days'));

			$time=time();
			$last_time=intval(get_value('last_backup'));
			if ($time>=$backup_schedule_time)
			{
				decache('main_staff_checklist');
				require_lang('backups');
				require_code('backup');
				$max_size=get_value('backup_max_size');
				$b_type=get_value('backup_b_type');
				global $MB2_FILE,$MB2_B_TYPE,$MB2_MAX_SIZE;
				$end=((get_option('backup_overwrite')!='1') || ($b_type=='incremental'))?uniqid('',true):'scheduled';
				if ($b_type=='full') $file='restore_'.$end;
				elseif ($b_type=='incremental') $file='dif_'.$end;
				elseif ($b_type=='sql') $file='database_'.$end;
				$MB2_FILE=$file;
				$MB2_B_TYPE=$b_type;
				$MB2_MAX_SIZE=$max_size;
				register_shutdown_function('make_backup_2');
				if ($backup_recurrance_days==0)
				{
					delete_value('backup_schedule_time');
				} else
				{
					set_value('backup_schedule_time',strval($backup_schedule_time+$backup_recurrance_days*60*60*24));
				}
			}
		}
	}
}


