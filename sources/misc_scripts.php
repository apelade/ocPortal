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

/**
 * Script to make a nice textual image, vertical writing.
 */
function gd_text_script()
{
	if (!function_exists('imagefontwidth')) return;

	$text=get_param('text');
	if (get_magic_quotes_gpc()) $text=stripslashes($text);

	$font_size=array_key_exists('size',$_GET)?intval($_GET['size']):8;

	$font=get_param('font',filter_naughty(get_param('font','Veranda')).'.ttf');
	if (strpos($font,'/')===false) $font=get_file_base().'/data/fonts/'.$font;
	if (substr($font,-4)!='.ttf') $font.='.ttf';

	if ((!function_exists('imagettftext')) || (!array_key_exists('FreeType Support',gd_info())) || (@imagettfbbox(26.0,0.0,get_file_base().'/data/fonts/Vera.ttf','test')===false) || (strlen($text)==0))
	{
		switch ($font_size)
		{
			case 1:
			case 2:
				$pfont=1;
				break;

			case 3:
			case 4:
				$pfont=2;
				break;

			case 5:
			case 6:
				$pfont=3;
				break;

			case 7:
			case 8:
				$pfont=4;
				break;

			default:
				$pfont=5;
		}
		$height=intval(imagefontwidth($pfont)*strlen($text)*1.05);
		$width=imagefontheight($pfont);
		$baseline_offset=0;
	} else
	{
		$scale=4;
		list(,,$height,,,,,$width)=imagettfbbox(floatval($font_size*$scale),0.0,$font,$text);
		$baseline_offset=8*intval(ceil(floatval($font_size)/8.0));
		$width=max($width,-$width);
		$width+=$baseline_offset;
		$height+=$font_size*$scale; // This is just due to inaccuracy in imagettfbbox, possibly due to italics not being computed correctly

		list(,,$real_height,,,,,$real_width)=imagettfbbox(floatval($font_size),0.0,$font,$text);
		$real_width=max($real_width,-$real_width);
		$real_width+=$baseline_offset/$scale;
		$real_height+=2;
	}
	if ($width==0) $width=1;
	if ($height==0) $height=1;
	$trans_color=array_key_exists('color',$_GET)?$_GET['color']:'FF00FF';
	$img=imagecreatetruecolor($width,$height+$baseline_offset);
	imagealphablending($img,false);
	$black_color=array_key_exists('fgcolor',$_GET)?$_GET['fgcolor']:'000000';
	$black=imagecolorallocate($img,hexdec(substr($black_color,0,2)),hexdec(substr($black_color,2,2)),hexdec(substr($black_color,4,2)));
	if ((!function_exists('imagettftext')) || (!array_key_exists('FreeType Support',gd_info())) || (@imagettfbbox(26.0,0.0,get_file_base().'/data/fonts/Vera.ttf','test')===false) || (strlen($text)==0))
	{
		$trans=imagecolorallocate($img,hexdec(substr($trans_color,0,2)),hexdec(substr($trans_color,2,2)),hexdec(substr($trans_color,4,2)));
		imagefill($img,0,0,$trans);
		imagecolortransparent($img,$trans);
		imagestringup($img,$pfont,0,$height-1-intval($height*0.02),$text,$black);
	} else
	{
		if (function_exists('imagecolorallocatealpha'))
		{
			$trans=imagecolorallocatealpha($img,hexdec(substr($trans_color,0,2)),hexdec(substr($trans_color,2,2)),hexdec(substr($trans_color,4,2)),127);
		} else
		{
			$trans=imagecolorallocate($img,hexdec(substr($trans_color,0,2)),hexdec(substr($trans_color,2,2)),hexdec(substr($trans_color,4,2)));
		}
		imagefilledrectangle($img,0,0,$width,$height,$trans);
		if (@$_GET['angle']!=90)
		{
			require_code('character_sets');
			$text=utf8tohtml(convert_to_internal_encoding($text,strtolower(get_param('charset',get_charset())),'utf-8'));
			if (strpos($text,'&#')===false)
			{
				$previous=mixed();
				$nxpos=0;
				for ($i=0;$i<strlen($text);$i++)
				{
					if (!is_null($previous)) // check for existing previous character
					{
						list(,,$rx1,,$rx2)=imagettfbbox(floatval($font_size*$scale),0.0,$font,$previous);
						$nxpos+=max($rx1,$rx2)+3;
					}
					imagettftext($img,floatval($font_size*$scale),270.0,$baseline_offset,$nxpos,$black,$font,$text[$i]);
					$previous=$text[$i];
				}
			} else
			{
				imagettftext($img,floatval($font_size*$scale),270.0,4,0,$black,$font,$text);
			}
		} else
		{
			imagettftext($img,floatval($font_size*$scale),90.0,$width-$baseline_offset,$height,$black,$font,$text);
		}
		$dest_img=imagecreatetruecolor($real_width+intval(ceil(floatval($baseline_offset)/floatval($scale))),$real_height);
		imagealphablending($dest_img,false);
		imagecopyresampled($dest_img,$img,0,0,0,0,$real_width+intval(ceil(floatval($baseline_offset)/floatval($scale))),$real_height,$width,$height); // Sizes down, for simple antialiasing-like effect
		imagedestroy($img);
		$img=$dest_img;
		if (function_exists('imagesavealpha')) imagesavealpha($img,true);
	}

	header('Content-Type: image/png');
	imagepng($img);
	imagedestroy($img);
}

/**
 * Script to track clicks to external sites.
 */
function simple_tracker_script()
{
	$url=get_param('url');
	if (strpos($url,'://')===false) $url=base64_decode($url);

	$GLOBALS['SITE_DB']->query_insert('link_tracker',array(
		'c_date_and_time'=>time(),
		'c_member_id'=>get_member(),
		'c_ip_address'=>get_ip_address(),
		'c_url'=>$url,
	));

	header('Location: '.$url);
}

/**
 * Script to show previews of content being added/edited.
 */
function preview_script()
{
	require_code('preview');
	list($output,$validation,$keyword_density,$spelling)=build_preview(true);

	$output=do_template('PREVIEW_SCRIPT',array('_GUID'=>'97bd8909e8b9983a0bbf7ab68fab92f3','OUTPUT'=>$output->evaluate(),'VALIDATION'=>$validation,'KEYWORD_DENSITY'=>$keyword_density,'SPELLING'=>$spelling,'HIDDEN'=>build_keep_post_fields()));

	$tpl=do_template('STANDALONE_HTML_WRAP',array('_GUID'=>'0a96e3b9be154e8b29bee5b1c1c7cc69','TITLE'=>do_lang_tempcode('PREVIEW'),'FRAME'=>true,'TARGET'=>'_top','CONTENT'=>$output));
	$tpl->handle_symbol_preprocessing();
	$tpl->evaluate_echo();
}

/**
 * Script to perform ocPortal CRON jobs called by the real CRON.
 *
 * @param  PATH  	File path of the cron_bridge.php script
 */
function cron_bridge_script($caller)
{
	if (function_exists('set_time_limit')) @set_time_limit(1000); // May get overridden lower later on

	// In query mode, ocPortal will just give advice on CRON settings to use
	if (get_param_integer('querymode',0)==1)
	{
		header('Content-Type: text/plain');
		@ini_set('ocproducts.xss_detect','0');
		require_code('files2');
		$php_path=find_php_path();
		echo $php_path.' -C -q --no-header '.$caller;
		exit();
	}

	// For multi-site installs, run for each install
	global $CURRENT_SHARE_USER,$SITE_INFO;
	if ((is_null($CURRENT_SHARE_USER)) && (array_key_exists('custom_share_domain',$SITE_INFO)))
	{
		require_code('files');

		foreach ($SITE_INFO as $key=>$val)
		{
			if (substr($key,0,12)=='custom_user_')
			{
				$url=preg_replace('#://[\w\.]+#','://'.substr($key,12).'.'.$SITE_INFO['custom_share_domain'],get_base_url()).'/data/cron_bridge.php';
				http_download_file($url);
			}
		}
	}

	decache('main_staff_checklist'); // So the block knows CRON has run

	$limit_hook=get_param('limit_hook','');

	// Call the hooks which do the real work
	set_value('last_cron',strval(time()));
	$cron_hooks=find_all_hooks('systems','cron');
	foreach (array_keys($cron_hooks) as $hook)
	{
		if (($limit_hook!='') && ($limit_hook!=$hook)) continue;

		require_code('hooks/systems/cron/'.$hook);
		$object=object_factory('Hook_cron_'.$hook,true);
		if (is_null($object)) continue;
		$object->run();
	}

	if (!headers_sent()) header('Content-type: text/plain');
}

/**
 * Script to handle iframe.
 */
function iframe_script()
{
	$zone=get_param('zone');
	$page=get_param('page');
	$ajax=(get_param_integer('ajax',0)==1);

	process_url_monikers($page);

	// AJAX prep
	if ($ajax) prepare_for_known_ajax_response();

	// Check permissions
	$zones=$GLOBALS['SITE_DB']->query_select('zones',array('*'),array('zone_name'=>$zone),'',1);
	if (!array_key_exists(0,$zones)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	if ($zones[0]['zone_require_session']==1) header('X-Frame-Options: SAMEORIGIN'); // Clickjacking protection
	if (($zones[0]['zone_name']!='') && (get_option('windows_auth_is_enabled')!='1') && ((get_session_id()==-1) || ($GLOBALS['SESSION_CONFIRMED_CACHE']==0)) && (!is_guest()) && ($zones[0]['zone_require_session']==1))
		access_denied('ZONE_ACCESS_SESSION');
	if (!has_actual_page_access(get_member(),$page,$zone))
		access_denied('ZONE_ACCESS');

	// Closed site
	$site_closed=get_option('site_closed');
	if (($site_closed=='1') && (!has_privilege(get_member(),'access_closed_site')) && (!$GLOBALS['IS_ACTUALLY_ADMIN']))
	{
		header('Content-Type: text/plain');
		@exit(get_option('closed'));
	}

	// SEO
	require_code('site');
	attach_to_screen_header('<meta name="robots" content="noindex" />'); // XHTMLXHTML

	// Load page
	$output=request_page($page,true);

	// Simple AJAX output?
	if ($ajax)
	{
		@ini_set('ocproducts.xss_detect','0');

		$output->handle_symbol_preprocessing();
		echo $output->evaluate();
		return;
	}

	// Normal output
	$tpl=do_template('STANDALONE_HTML_WRAP',array('_GUID'=>'04cf4ef7aac4201bb985327ec0e04c87','OPENS_BELOW'=>get_param_integer('opens_below',0)==1,'FRAME'=>true,'TARGET'=>'_top','CONTENT'=>$output));
	$tpl->handle_symbol_preprocessing();
	$tpl->evaluate_echo();
}

/**
 * Redirect the browser to where a pagelink specifies.
 */
function pagelink_redirect_script()
{
	$pagelink=get_param('id');
	$tpl=symbol_tempcode('PAGE_LINK',array($pagelink));

	$x=$tpl->evaluate();

	if ((strpos($x,"\n")!==false) || (strpos($x,"\r")!==false))
		log_hack_attack_and_exit('HEADER_SPLIT_HACK');

	header('Location: '.$x);
}

/**
 * Outputs the page-link chooser popup.
 */
function page_link_chooser_script()
{
	// Check we are allowed here
	if (!has_zone_access(get_member(),'adminzone'))
		access_denied('ZONE_ACCESS');

	require_lang('menus');

	require_javascript('javascript_ajax');
	require_javascript('javascript_tree_list');
	require_javascript('javascript_more');

	require_code('site');
	attach_to_screen_header('<meta name="robots" content="noindex" />'); // XHTMLXHTML

	// Display
	$content=do_template('PAGE_LINK_CHOOSER',array('_GUID'=>'235d969528d7b81aeb17e042a17f5537','NAME'=>'tree_list'));
	$echo=do_template('STANDALONE_HTML_WRAP',array('_GUID'=>'58768379196d6ad27d6298134e33fabd','TITLE'=>do_lang_tempcode('CHOOSE'),'CONTENT'=>$content,'POPUP'=>true));
	$echo->handle_symbol_preprocessing();
	$echo->evaluate_echo();
}

/**
 * Shows an HTML page of all emoticons clickably.
 */
function emoticons_script()
{
	if (get_forum_type()!='ocf') warn_exit(do_lang_tempcode('NO_OCF'));

	require_css('ocf');

	require_lang('ocf');
	require_javascript('javascript_editing');

	$extra=has_privilege(get_member(),'use_special_emoticons')?'':' AND e_is_special=0';
	$rows=$GLOBALS['FORUM_DB']->query('SELECT * FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_emoticons WHERE e_relevance_level<3'.$extra);

	// Work out what grid spacing to use
	$max_emoticon_width=0;
	require_code('images');
	foreach ($rows as $myrow)
	{
		list($_width,)=_symbol_image_dims(array(find_theme_image($myrow['e_theme_img_code'],true)));
		$max_emoticon_width=max($max_emoticon_width,intval($_width));
	}
	if ($max_emoticon_width==0) $max_emoticon_width=36;
	$padding=2;
	$window_width=300;
	$cols=intval(floor(floatval($window_width)/floatval($max_emoticon_width+$padding)));

	// Render UI
	$content=new ocp_tempcode();
	$current_row=new ocp_tempcode();
	foreach ($rows as $i=>$myrow)
	{
		if (($i%$cols==0) && ($i!=0))
		{
			$content->attach(do_template('OCF_EMOTICON_ROW',array('_GUID'=>'283bff0bb281039b94ff2d4dcaf79172','CELLS'=>$current_row)));
			$current_row=new ocp_tempcode();
		}

		$code_esc=$myrow['e_code'];
		$current_row->attach(do_template('OCF_EMOTICON_CELL',array('_GUID'=>'ddb838e6fa296df41299c8758db92f8d','COLS'=>strval($cols),'FIELD_NAME'=>get_param('field_name','post'),'CODE_ESC'=>$code_esc,'THEME_IMG_CODE'=>$myrow['e_theme_img_code'],'CODE'=>$myrow['e_code'])));
	}
	if (!$current_row->is_empty())
		$content->attach(do_template('OCF_EMOTICON_ROW',array('_GUID'=>'d13e74f7febc560dc5fc241dc7914a03','CELLS'=>$current_row)));

	$content=do_template('OCF_EMOTICON_TABLE',array('_GUID'=>'d3dd9bbfacede738e2aff4712b86944b','ROWS'=>$content));

	require_code('site');
	attach_to_screen_header('<meta name="robots" content="noindex" />'); // XHTMLXHTML

	$echo=do_template('STANDALONE_HTML_WRAP',array('_GUID'=>'8acac778b145bfe7b063317fbcae7fde','TITLE'=>do_lang_tempcode('EMOTICONS_POPUP'),'POPUP'=>true,'CONTENT'=>$content));
	$echo->handle_symbol_preprocessing();
	$echo->evaluate_echo();
}

/**
 * Allows conversion of a URL to a thumbnail via a simple script.
 */
function thumb_script()
{
	$url_full=get_param('url');
	if (strpos($url_full,'://')===false) $url_full=base64_decode($url_full);

	require_code('images');

	$new_name=url_to_filename($url_full);
	if (!is_saveable_image($new_name)) $new_name.='.png';
	if (is_null($new_name)) warn_exit(do_lang_tempcode('URL_THUMB_TOO_LONG'));
	$file_thumb=get_custom_file_base().'/uploads/auto_thumbs/'.$new_name;
	if (!file_exists($file_thumb))
	{
		convert_image($url_full,$file_thumb,-1,-1,intval(get_option('thumb_width')),false);
	}
	$url_thumb=get_custom_base_url().'/uploads/auto_thumbs/'.rawurlencode($new_name);

	if ((strpos($url_thumb,"\n")!==false) || (strpos($url_thumb,"\r")!==false))
		log_hack_attack_and_exit('HEADER_SPLIT_HACK');
	header('Location: '.$url_thumb);
}

/**
 * Outputs a modal question dialog.
 */
function question_ui_script()
{
	@ini_set('ocproducts.xss_detect','0');
	$GLOBALS['SCREEN_TEMPLATE_CALLED']='';

	$title=get_param('window_title',false,true);
	$_message=nl2br(escape_html(get_param('message',false,true)));
	if (function_exists('ocp_mark_as_escaped')) ocp_mark_as_escaped($_message);
	$button_set=explode(',',get_param('button_set',false,true));
	$_image_set=get_param('image_set',false,true);
	$image_set=($_image_set=='')?array():explode(',',$_image_set);
	$message=do_template('QUESTION_UI_BUTTONS',array('_GUID'=>'0c5a1efcf065e4281670426c8fbb2769','TITLE'=>$title,'IMAGES'=>$image_set,'BUTTONS'=>$button_set,'MESSAGE'=>$_message));

	require_code('site');
	attach_to_screen_header('<meta name="robots" content="noindex" />'); // XHTMLXHTML

	$echo=do_template('STANDALONE_HTML_WRAP',array('_GUID'=>'8d72daa4c9f922656b190b643a6fe61d','TITLE'=>escape_html($title),'POPUP'=>true,'CONTENT'=>$message));
	$echo->handle_symbol_preprocessing();
	$echo->evaluate_echo();
}

