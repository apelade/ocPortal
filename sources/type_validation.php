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
 * @package		core
 */

/**
 * Standard code module initialisation function.
 */
function init__type_validation()
{
	if (!function_exists('is_alphanumeric'))
	{
		/**
		 * Find whether the specified string is alphanumeric or not.
		 *
		 * @param  string			The string to test
		 * @param  boolean		Whether to check stricter identifier-validity
		 * @return boolean		Whether the string is alphanumeric or not
		 */
		function is_alphanumeric($string,$strict=false)
		{
			if ($strict)
				return preg_match('#^[\w\-]*$#',$string)!=0;

			$test=@preg_match('#^[\pL\w\-\.]*$#u',$string)!=0; // unicode version, may fail on some servers
			if ($test!==false) return $test;
			return preg_match('#^[\w\-\.]*$#',$string)!=0;
		}
	}
}

/**
 * Find whether the specified address is a valid e-mail address or not.
 *
 * @param  string			The string to test (Note: This is typed string, not e-mail, because it has to function on failure + we could make an infinite loop)
 * @return boolean		Whether the string is an email address or not
 */
function is_valid_email_address($string)
{
	if ($string=='') return false;

	return (preg_match('#^[\w\.\-\+]+@[\w\.\-]+$#',$string)!=0); // Put "\.[a-zA-Z0-9_\-]+" before $ to ensure a two+ part domain
}


