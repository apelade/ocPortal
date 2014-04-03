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

class Hook_check_functions_needed
{
	/**
	 * Check various input var restrictions.
	 *
	 * @return	array		List of warnings
	 */
	function run()
	{
		$warning=array();

		$needed_functions=<<<END
			abs addslashes array_count_values array_diff array_flip array_key_exists array_keys
			array_intersect array_merge array_pop array_push array_reverse array_search array_shift
			array_slice array_splice array_unique array_values arsort asort base64_decode base64_encode
			call_user_func ceil chdir checkdate chmod chr chunk_split class_exists clearstatcache closedir
			constant copy cos count crypt current date dechex decoct define defined dirname disk_free_space
			deg2rad error_log error_reporting eval exit explode fclose feof fgets file file_exists
			file_get_contents filectime filegroup filemtime fileowner fileperms filesize floatval floor
			get_defined_vars get_declared_classes get_defined_functions fopen fread fseek ftell
			function_exists fwrite gd_info get_class get_html_translation_table get_magic_quotes_gpc getcwd
			getdate getenv gmdate gzclose gzopen gzwrite header headers_sent hexdec highlight_string
			htmlentities imagealphablending imagecolorallocate imagecolortransparent imagecopy
			imagecopyresampled imagecopyresized imagecreate imagecreatefromstring imagecreatefrompng
			imagecreatefromjpeg imagecreatetruecolor imagecolorat imagecolorsforindex
			imagedestroy imagefill imagefontheight imagefontwidth imagejpeg imagepng imagesavealpha
			imagesetpixel imagestring imagesx imagesy imagestringup imagettfbbox imagettftext imagetypes
			imagearc imagefilledarc imagecopymergegray imageline imageellipse imagefilledellipse
			imagechar imagefilledpolygon imagepolygon imagefilledrectangle imagerectangle imagefilltoborder
			imagegammacorrect imageinterlace imageloadfont imagepalettecopy imagesetbrush
			imagesetstyle imagesetthickness imagesettile imagetruecolortopalette
			imagecharup imagecolorclosest imagecolorclosestalpha imagecolorclosesthwb
			imagecolordeallocate imagecolorexact imagecolorexactalpha imagecolorresolve
			imagecolorresolvealpha imagecolorset imagecolorstotal imagecopymerge
			implode in_array include include_once ini_get ini_set intval is_a is_array is_bool is_dir is_file is_float
			is_integer is_null is_numeric is_object is_readable is_resource is_string is_uploaded_file is_writable
			isset krsort ksort localeconv ltrim mail max md5 method_exists microtime min
			mkdir mktime move_uploaded_file mt_getrandmax mt_rand mt_srand number_format ob_end_clean
			ob_end_flush ob_get_contents ob_start octdec opendir ord pack parse_url pathinfo
			preg_match preg_grep preg_match_all
			preg_replace preg_replace_callback preg_split print_r putenv rawurldecode
			rawurlencode readdir realpath register_shutdown_function rename require require_once reset rmdir
			round rsort rtrim serialize set_error_handler set_magic_quotes_runtime
			setcookie setlocale sha1 sin sort fprintf sprintf srand str_pad str_repeat str_replace
			strcmp strftime strip_tags stripslashes strlen strpos strrpos strstr strtok strtolower
			strtotime strtoupper strtr strval substr substr_count time trim trigger_error
			uasort ucfirst ucwords uksort uniqid unlink unserialize unset urldecode urlencode usort
			utf8_decode utf8_encode wordwrap xml_error_string xml_get_current_byte_index xml_get_current_line_number
			xml_get_error_code xml_parse xml_parser_create_ns xml_parser_free xml_parser_set_option
			xml_set_character_data_handler xml_set_element_handler xml_set_end_namespace_decl_handler xml_set_object
			xml_set_start_namespace_decl_handler xmlrpc_encode_request acos array_rand array_unshift asin assert
			assert_options atan base_convert basename bin2hex bindec call_user_func_array
			connection_aborted connection_status crc32 decbin each empty fflush fileatime flock flush
			get_current_user gethostbyaddr getrandmax gmmktime gmstrftime ip2long
			levenshtein log log10 long2ip md5_file money_format pow preg_quote prev rad2deg
			range readfile shuffle similar_text sqrt strcasecmp strcoll strcspn stristr strnatcasecmp
			strnatcmp strncasecmp strncmp strrchr strrev strspn substr_replace tan unpack version_compare
			gettype zend_version zend_logo_guid xml_get_current_column_number xml_parser_create
			xml_parser_get_option xml_parse_into_struct xml_set_default_handler xml_set_external_entity_ref_handler
			xml_set_notation_decl_handler xml_set_processing_instruction_handler xml_set_unparsed_entity_decl_handler
			var_dump vprintf vsprintf touch tanh sinh sleep soundex sscanf stripcslashes
			readgzfile restore_error_handler rewind rewinddir quoted_printable_decode
			quotemeta exp ezmlm_hash lcg_value localtime addcslashes
			array_filter array_map array_merge_recursive array_multisort array_pad array_reduce array_walk
			atan2 fgetc fgetcsv fgetss filetype fscanf fstat ftp_cdup ftp_fget ftp_get ftp_pasv
			ftp_pwd ftp_rawlist ftp_systype ftruncate func_get_arg func_get_args func_num_args
			parse_ini_file parse_str is_executable
			is_scalar is_subclass_of metaphone natcasesort natsort nl2br ob_get_length ob_gzhandler
			ob_iconv_handler ob_implicit_flush ob_clean
			php_uname printf convert_cyr_string cosh count_chars
			gethostbynamel getimagesize getlastmod fpassthru
			gettimeofday get_cfg_var get_magic_quotes_runtime get_meta_tags get_parent_class
			get_included_files get_resource_type gzcompress gzdeflate gzencode gzfile gzinflate
			gzuncompress hypot ignore_user_abort hebrev hebrevc array_intersect_assoc
			is_link is_callable debug_print_backtrace stream_context_create next usleep array_sum create_function
			gzclose gzopen gzwrite ftp_chdir ftp_close ftp_connect ftp_delete ftp_fput ftp_chmod
			ftp_login ftp_mkdir ftp_nlist ftp_put ftp_rename ftp_rmdir ftp_site ftp_size
			file_get_contents str_word_count html_entity_decode array_combine array_diff_uassoc array_udiff
			array_udiff_assoc array_udiff_uassoc array_walk_recursive array_uintersect_assoc array_uintersect_uassoc
			array_uintersect str_split strpbrk substr_compare file_put_contents get_headers headers_list
			http_build_query image_type_to_extension imagefilter scandir str_shuffle image_type_to_mime_type sha1
			exif_imagetype ob_get_clean array_diff_assoc glob debug_backtrace date_default_timezone_set
			date_default_timezone_get array_diff_key inet_pton array_product array_diff_ukey array_intersect_ukey
			libxml_get_errors inet_ntop fputcsv
			is_nan is_finite is_infinite ob_flush array_chunk array_fill array_change_key_case
			exif_read_data var_export
END;
		foreach (preg_split('#\s+#',$needed_functions) as $function)
		{
			if (trim($function)=='') continue;
			if (@preg_match('#(\s|,|^)'.preg_quote($function,'#').'(\s|$|,)#',strtolower(@ini_get('disable_functions').','.ini_get('suhosin.executor.func.blacklist').','.ini_get('suhosin.executor.include.blacklist').','.ini_get('suhosin.executor.eval.blacklist')))!=0)
				$warning[]=do_lang_tempcode('DISABLED_FUNCTION',escape_html($function));
		}

		return $warning;
	}
}