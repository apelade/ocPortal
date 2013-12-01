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
function init__css_and_js()
{
	global $CSS_COMPILE_ACTIVE_THEME;
	$CSS_COMPILE_ACTIVE_THEME='default';
}

/**
 * Inherit from a CSS file to create a string for a (possibly theme-gen) modified version of that file.
 *
 * @param  ID_TEXT		Source CSS file
 * @param  ID_TEXT		Source theme
 * @param  ID_TEXT		Destination theme
 * @param  ?ID_TEXT		Seed (NULL: do not re-seed)
 * @param  boolean		Whether it is a dark theme
 * @param  ID_TEXT		The algorithm to use
 * @set equations hsv
 * @return string			The sheet
 */
function css_inherit($css_file,$theme,$destination_theme,$seed,$dark,$algorithm)
{
	// Find source
	$fullpath=get_custom_file_base().'/themes/'.$theme.'/css_custom/'.$css_file.'.css';
	if (!is_file($fullpath))
	{
		$fullpath=get_custom_file_base().'/themes/'.$theme.'/css/'.$css_file.'.css';
		if (!is_null($GLOBALS['CURRENT_SHARE_USER']))
		{
			$fullpath=get_file_base().'/themes/'.$theme.'/css_custom/'.$css_file.'.css';
			if (!is_file($fullpath))
				$fullpath=get_file_base().'/themes/'.$theme.'/css/'.$css_file.'.css';
		}
		if (!is_file($fullpath))
		{
			$theme='default';
			$fullpath=get_file_base().'/themes/'.$theme.'/css_custom/'.$css_file.'.css';
			if (!is_file($fullpath))
				$fullpath=get_file_base().'/themes/'.$theme.'/css/'.$css_file.'.css';
		}
	}

	// Read a raw
	$sheet=file_get_contents($fullpath);

	// Re-seed
	if (!is_null($seed))
	{
		// Not actually needed
		$sheet=preg_replace('#\{\$THEME_WIZARD_COLOR,\#[A-Fa-f0-9]{6},seed,100% [A-Fa-f0-9]{6}\}#','{$THEME_WIZARD_COLOR,#'.$seed.',seed,100% '.$seed.'}',$sheet);
		$sheet=preg_replace('#\{\$THEME_WIZARD_COLOR,\#[A-Fa-f0-9]{6},WB,100% [A-Fa-f0-9]{6}\}#','{$THEME_WIZARD_COLOR,#'.$seed.',WB,100% '.($dark?'000000':'FFFFFF').'}',$sheet);
		$sheet=preg_replace('#\{\$THEME_WIZARD_COLOR,\#[A-Fa-f0-9]{6},BW,100% [A-Fa-f0-9]{6}\}#','{$THEME_WIZARD_COLOR,#'.$seed.',BW,100% '.($dark?'FFFFFF':'000000').'}',$sheet);

		require_code('themewizard');
		list($colours,$landscape)=calculate_theme($seed,$theme,$algorithm,'colours',$dark);

		// The main thing (THEME_WIZARD_COLOR is not executed in full by Tempcode, so we need to sub it according to our theme wizard landscape)
		foreach ($landscape as $peak)
		{
			$from=$peak[2];
			$to=preg_replace('#\{\$THEME_WIZARD_COLOR,\#[\da-fA-F]{6},#','{$THEME_WIZARD_COLOR,#'.$peak[3].',',$peak[2]);
			$sheet=str_replace($from,$to,$sheet);
		}
	}

	// Copy to tmp file
	$temp_file=get_custom_file_base().'/themes/'.$destination_theme.'/css_custom/'.basename($fullpath,'.css').'__tmp_copy.css';
	$myfile=@fopen($temp_file,'at') OR intelligent_write_error($temp_file);
	flock($myfile,LOCK_EX);
	ftruncate($myfile,0);
	fwrite($myfile,$sheet);
	flock($myfile,LOCK_UN);
	fclose($myfile);
	fix_permissions($temp_file);

	// Load up as Tempcode
	$_sheet=_css_compile($destination_theme,$destination_theme,$css_file.'__tmp_copy',$temp_file,false);
	@unlink($temp_file);
	sync_file($temp_file);
	$sheet=$_sheet[1];

	return $sheet;
}

/**
 * Compile a Javascript file.
 *
 * @param  ID_TEXT		Name of the JS file
 * @param  PATH			Full path to the JS file
 * @param  boolean		Whether to also do minification
 */
function js_compile($j,$js_cache_path,$minify=true)
{
	require_lang('javascript');
	global $KEEP_MARKERS,$SHOW_EDIT_LINKS;
	$temp_keep_markers=$KEEP_MARKERS;
	$temp_show_edit_links=$SHOW_EDIT_LINKS;
	$KEEP_MARKERS=false;
	$SHOW_EDIT_LINKS=false;
	$tpl_params=array();
	if ($j=='javascript_staff')
	{
		$url_patterns=array();
		$cma_hooks=find_all_hooks('systems','content_meta_aware');
		$award_hooks=find_all_hooks('systems','awards');
		$common=array_intersect(array_keys($cma_hooks),array_keys($award_hooks));
		foreach ($common as $hook)
		{
			require_code('hooks/systems/content_meta_aware/'.$hook);
			$hook_ob=object_factory('Hook_content_meta_aware_'.$hook);
			$info=$hook_ob->info();
			list($zone,$attributes,)=page_link_decode($info['view_pagelink_pattern']);
			$url=build_url($attributes,$zone,NULL,false,false,true);
			$url_patterns[$url->evaluate()]=array(
				'PATTERN'=>$url->evaluate(),
				'HOOK'=>$hook,
			);
			list($zone,$attributes,)=page_link_decode($info['edit_pagelink_pattern']);
			$url=build_url($attributes,$zone,NULL,false,false,true);
			$url_patterns[$url->evaluate()]=array(
				'PATTERN'=>$url->evaluate(),
				'HOOK'=>$hook,
			);
		}
		$tpl_params['URL_PATTERNS']=array_values($url_patterns);
	}
	$js=do_template(strtoupper($j),$tpl_params);
	$KEEP_MARKERS=$temp_keep_markers;
	$SHOW_EDIT_LINKS=$temp_show_edit_links;
	global $ATTACHED_MESSAGES_RAW;
	$num_msgs_before=count($ATTACHED_MESSAGES_RAW);
	$out=$js->evaluate();
	$num_msgs_after=count($ATTACHED_MESSAGES_RAW);
	$success_status=($num_msgs_before==$num_msgs_after);
	if ($minify)
		$out=js_minify($out);

	if ($out=='')
	{
		$contents=$out;
	} else
	{
		$contents='/* DO NOT EDIT. THIS IS A CACHE FILE AND WILL GET OVERWRITTEN RANDOMLY.'.chr(10).'INSTEAD EDIT THE TEMPLATE FROM WITHIN THE ADMIN ZONE, OR BY MANUALLY EDITING A TEMPLATES_CUSTOM OVERRIDE. */'.chr(10).chr(10).$out;
	}
	$js_file=@fopen($js_cache_path,'at');
	if ($js_file===false) intelligent_write_error($js_cache_path);
	flock($js_file,LOCK_EX);
	ftruncate($js_file,0);
	if (fwrite($js_file,$contents)<strlen($contents)) warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
	flock($js_file,LOCK_UN);
	fclose($js_file);
	fix_permissions($js_cache_path);
	sync_file($js_cache_path);
	if (!$success_status)
	{
		touch($js_cache_path,time()-60*60*24); // Fudge it so it's going to auto expire. We do have to write the file as it's referenced, but we want it to expire instantly so that any errors will reshow.
	}
}

/**
 * Compile a CSS file.
 *
 * @param  ID_TEXT		The theme the file is being loaded for
 * @param  ID_TEXT		The theme the file is in
 * @param  ID_TEXT		Name of the CSS file
 * @param  PATH			Full path to the CSS file
 * @param  PATH			Full path to where the cached CSS file will go
 * @param  boolean		Whether to also do minification
 */
function css_compile($active_theme,$theme,$c,$fullpath,$css_cache_path,$minify=true)
{
	if ($c!='global') // We need to make sure the global.css file is parsed, as it contains some shared THEME_WIZARD_COLOR variables that Tempcode will pick up on
	{
		$found=find_template_place('global','',$active_theme,'.css','css');
		$d_theme=$found[0];
		$global_fullpath=get_custom_file_base().'/themes/'.$d_theme.$found[1].'global.css';
		if (!is_file($global_fullpath))
			$global_fullpath=get_file_base().'/themes/'.$d_theme.$found[1].'global.css';

		_css_compile($active_theme,$d_theme,'global',$global_fullpath,false);
	}

	list($success_status,$out)=_css_compile($active_theme,$theme,$c,$fullpath,$minify);
	$css_file=@fopen($css_cache_path,'at');
	if ($css_file===false) intelligent_write_error($css_cache_path);
	flock($css_file,LOCK_EX);
	ftruncate($css_file,0);
	if (fwrite($css_file,$out)<strlen($out)) warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
	flock($css_file,LOCK_UN);
	fclose($css_file);
	fix_permissions($css_cache_path);
	sync_file($css_cache_path);
	if (!$success_status)
	{
		touch($css_cache_path,time()-60*60*24); // Fudge it so it's going to auto expire. We do have to write the file as it's referenced, but we want it to expire instantly so that any errors will reshow.
	}
}

/**
 * preg_replace callback, to handle CSS file inclusion.
 *
 * @param  array			Matched variables
 * @return array			A pair: success status, The text of the compiled file
 */
function _css_ocp_include($matches)
{
	global $CSS_COMPILE_ACTIVE_THEME;

	$theme=$matches[1];
	$c=$matches[3];
	if (($theme=='default') && ($matches[2]=='css'))
	{
		$fullpath=get_file_base().'/themes/'.filter_naughty($theme).'/'.filter_naughty($matches[2]).'/'.filter_naughty($c).'.css';
	} else
	{
		$fullpath=get_custom_file_base().'/themes/'.filter_naughty($theme).'/'.filter_naughty($matches[2]).'/'.filter_naughty($c).'.css';
		if (!is_file($fullpath))
			$fullpath=get_file_base().'/themes/'.filter_naughty($theme).'/'.filter_naughty($matches[2]).'/'.filter_naughty($c).'.css';
	}
	if (!is_file($fullpath)) return array(false,'');
	return _css_compile($CSS_COMPILE_ACTIVE_THEME,$theme,$c,$fullpath);
}

/**
 * Return a specific compiled CSS file.
 *
 * @param  ID_TEXT		The theme the file is being loaded for
 * @param  string			Theme name
 * @param  string			The CSS file required
 * @param  PATH			Full path to CSS file (file is in uncompiled Tempcode format)
 * @param  boolean		Whether to also do minification
 * @return array			A pair: success status, The text of the compiled file
 */
function _css_compile($active_theme,$theme,$c,$fullpath,$minify=true)
{
	global $KEEP_MARKERS,$SHOW_EDIT_LINKS;
	$keep_markers=$KEEP_MARKERS;
	$show_edit_links=$SHOW_EDIT_LINKS;
	$KEEP_MARKERS=false;
	$SHOW_EDIT_LINKS=false;
	if (($theme!='default') && (!is_file($fullpath))) $theme='default';
	if ($GLOBALS['RECORD_TEMPLATES_USED'])
	{
		global $RECORDED_TEMPLATES_USED;
		$RECORDED_TEMPLATES_USED[]=$c.'.css';
	}
	require_code('tempcode_compiler');
	global $ATTACHED_MESSAGES_RAW;
	$num_msgs_before=count($ATTACHED_MESSAGES_RAW);
	$css=_do_template($theme,(strpos($fullpath,'/css_custom/')!==false)?'/css_custom/':'/css/',$c,$c,user_lang(),'.css',$active_theme);
	$out=$css->evaluate();
	$num_msgs_after=count($ATTACHED_MESSAGES_RAW);
	global $CSS_COMPILE_ACTIVE_THEME;
	$CSS_COMPILE_ACTIVE_THEME=$active_theme;
	$out=preg_replace_callback('#\@ocp\_include\(\'?(\w+)/(\w+)/(\w+)\'?\);#','_css_ocp_include',$out);
	$out=preg_replace('#/\*\s*\*/#','',$out); // strip empty comments (would have encapsulated Tempcode comments)
	if (get_custom_file_base()!=get_file_base())
	{
		$out=preg_replace('#'.preg_quote(str_replace('#','\#',get_base_url(true).'/themes/')).'#','../../../../../../themes/',$out); // make URLs relative. For SSL and myocp
		$out=preg_replace('#'.preg_quote(str_replace('#','\#',get_base_url(false).'/themes/')).'#','../../../../../../themes/',$out); // make URLs relative. For SSL and myocp
		$out=preg_replace('#'.preg_quote(str_replace('#','\#',get_custom_base_url(true).'/themes/')).'#','../../../../themes/',$out); // make URLs relative. For SSL and myocp
		$out=preg_replace('#'.preg_quote(str_replace('#','\#',get_custom_base_url(false).'/themes/')).'#','../../../../themes/',$out); // make URLs relative. For SSL and myocp
	} else
	{
		$out=preg_replace('#'.preg_quote(str_replace('#','\#',get_base_url(true))).'#','../../../..',$out); // make URLs relative. For SSL and myocp
		$out=preg_replace('#'.preg_quote(str_replace('#','\#',get_base_url(false))).'#','../../../..',$out); // make URLs relative. For SSL and myocp
	}
	$cdn=get_value('cdn');
	if (!is_null($cdn))
	{
		$cdn_parts=explode(',',$cdn);
		foreach ($cdn_parts as $cdn_part)
		{
			$out=preg_replace('#'.preg_quote(str_replace('#','\#',preg_replace('#://'.str_replace('#','\#',preg_quote(get_domain())).'([/:])#','://'.$cdn_part.'${1}',get_base_url(true)))).'#','../../../..',$out); // make URLs relative. For SSL and myocp
			$out=preg_replace('#'.preg_quote(str_replace('#','\#',preg_replace('#://'.str_replace('#','\#',preg_quote(get_domain())).'([/:])#','://'.$cdn_part.'${1}',get_base_url(false)))).'#','../../../..',$out); // make URLs relative. For SSL and myocp
		}
	}
	if ($minify)
		$out=css_minify($out);
	$KEEP_MARKERS=$keep_markers;
	$SHOW_EDIT_LINKS=$show_edit_links;
	if ($c!='no_cache')
	{
		if ($out!='')
		{
			$out='/* DO NOT EDIT. THIS IS A CACHE FILE AND WILL GET OVERWRITTEN RANDOMLY.'.chr(10).'INSTEAD EDIT THE CSS FROM WITHIN THE ADMIN ZONE, OR BY MANUALLY EDITING A CSS_CUSTOM OVERRIDE. */'.chr(10).chr(10).$out;
		}
	}
	if ($num_msgs_after>$num_msgs_before) // Was an error (e.g. missing theme image), so don't cache so that the error will be visible on refresh and hence debugged
	{
		return array(false,$out);
	}
	return array(true,$out);
}

/**
 * Minimise the given Javascript
 *
 * @param 	string		Javascript to minimise
 * @return 	string		Minimised Javascript
 */
function js_minify($js)
{
	if (strpos(substr($js,0,1000),'no minify')!==false) return $js;

	require_code('jsmin');

	if (!class_exists('JSMin')) return $js;

	$jsmin=new JSMin($js);
	return $jsmin->min();
}

/**
 * cssmin.php - A simple CSS minifier.
 * --
 * 
 * <code>
 * include("cssmin.php");
 * file_put_contents("path/to/target.css", cssmin::minify(file_get_contents("path/to/source.css")));
 * </code>
 * --
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING 
 * BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND 
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, 
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 * --
 *
 * @author 		Joe Scylla <joe.scylla@gmail.com>
 * @copyright 	2008 Joe Scylla <joe.scylla@gmail.com>
 * @license 	http://opensource.org/licenses/mit-license.php MIT License
 * @version 	1.0 (2008-01-31)
 * @package		core
 */

/**
 * Minifies stylesheet definitions
 *
 * @param 	string		Stylesheet definitions as string
 * @return 	string		Minified stylesheet definitions
 */
function css_minify($v) 
{
	$search=array('/\/\*[\d\D]*?\*\/|\t+/', '/\s+/');
	$replace=array('', ' ');
	$v=preg_replace($search, $replace, $v);
	$search=array('/\\;\s/', '/\s+\{\\s+/', '/\\:\s+\\#/', '/,\s+/i', '/\\:\s+\\\'/i', '/\\:\s+([0-9]+|[A-F]+)/i');
	$replace=array(';', '{', ':#', ',', ':\'', ':$1');
	$v=preg_replace($search, $replace, $v);
	$v=str_replace("\n", '', $v);
	return trim($v);	
}

