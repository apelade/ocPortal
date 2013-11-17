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

class Hook_cron_tasks
{
	/**
	 * Standard modular run function for CRON hooks. Searches for tasks to perform.
	 */
	function run()
	{
		if (!GOOGLE_APPENGINE) // GAE has its own external task queue
		{
			require_code('tasks');

			$task_rows=$GLOBALS['SITE_DB']->query_select('task_queue',array('*'),array('t_locked'=>0));
			foreach ($task_rows as $task_row)
			{
				require_code('files');
				//$url=find_script('tasks').'?id='.strval($task_row['id']).'&secure_ref='.urlencode($task_row['t_secure_ref']);
				//http_download_file($url);
				execute_task_background($task_row);
			}
		}
	}
}


