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
function init__zones()
{
	global $CACHE_ON,$CLASS_CACHE;
	$CACHE_ON=NULL;
	$CLASS_CACHE=array();

	global $ARB_COUNTER;
	$ARB_COUNTER=1;

	global $DO_NOT_CACHE_THIS;
	$DO_NOT_CACHE_THIS=false;

	global $MODULES_ZONES,$MODULES_ZONES_DEFAULT;
	$MODULES_ZONES=function_exists('persistent_cache_get')?persistent_cache_get('MODULES_ZONES'):NULL;
	global $SITE_INFO;
	$hardcoded=(isset($SITE_INFO['hardcode_common_module_zones'])) && ($SITE_INFO['hardcode_common_module_zones']=='1');
	if (get_forum_type()=='ocf')
	{
		if ($hardcoded)
		{
			$MODULES_ZONES_DEFAULT=array( // Breaks redirects etc, but handy optimisation if you have a vanilla layout
				'forumview'=>'forum',
				'topicview'=>'forum',
				'topics'=>'forum',
				'vforums'=>'forum',
				'points'=>'site',
				'members'=>'site',
				'catalogues'=>'site',
				'join'=>'',
				'login'=>'',
				'recommend'=>'',
			);
		} else
		{
			$MODULES_ZONES_DEFAULT=array(
				'join'=>'',
				'login'=>'',
			);
		}
	} else
	{
		$MODULES_ZONES_DEFAULT=array(
			'join'=>'',
		);
	}

	global $VIRTUALISED_ZONES;
	$VIRTUALISED_ZONES=NULL;
	if (is_null($MODULES_ZONES))
	{
		foreach ($MODULES_ZONES_DEFAULT as $key=>$val)
		{
			if ((!$hardcoded) && (!is_file(get_file_base().'/'.$val.'/pages/modules/'.$key.'.php')))
			{
				unset($MODULES_ZONES_DEFAULT[$key]);
			}
		}
		$MODULES_ZONES=$MODULES_ZONES_DEFAULT;
	}

	global $ALL_ZONES,$ALL_ZONES_TITLED;
	$ALL_ZONES=NULL;
	$ALL_ZONES_TITLED=NULL;

	global $MODULE_INSTALLED_CACHE;
	$MODULE_INSTALLED_CACHE=array();

	global $HOOKS_CACHE;
	$HOOKS_CACHE=function_exists('persistent_cache_get')?persistent_cache_get('HOOKS'):array();
	if ($HOOKS_CACHE===NULL) $HOOKS_CACHE=array();

	define('FIND_ALL_PAGES__PERFORMANT',0);
	define('FIND_ALL_PAGES__NEWEST',1);
	define('FIND_ALL_PAGES__ALL',2);

	global $BLOCKS_AT_CACHE;
	$BLOCKS_AT_CACHE=function_exists('persistent_cache_get')?persistent_cache_get('BLOCKS_AT'):array();
	if ($BLOCKS_AT_CACHE===NULL) $BLOCKS_AT_CACHE=array();
}

/**
 * Consider virtual zone merging, where paths are not necessarily where you'd expect for pages in zones.
 *
 * @param  PATH			The path, assuming in the obvious place.
 * @param  boolean		Where the passed path is relative.
 * @return PATH			The fixed path.
 */
function zone_black_magic_filterer($path,$relative=false)
{
	static $no_collapse_zones=NULL;
	if ($no_collapse_zones===NULL) $no_collapse_zones=(get_option('collapse_user_zones',true)!=='1');
	if ($no_collapse_zones) return $path;

	static $zbmf_cache=array();
	if (isset($zbmf_cache[$path])) return $zbmf_cache[$path];

	if ($relative)
	{
		$stripped=$path;
	} else
	{
		$cfb=get_custom_file_base();
		if (substr($path,0,strlen($cfb))==$cfb)
		{
			$stripped=substr($path,strlen($cfb)+1);
		} else
		{
			$fb=get_file_base();
			$stripped=substr($path,strlen($fb)+1);
		}
	}

	if ($stripped!='')
	{
		if ($stripped[0]=='/') $stripped=substr($stripped,1);

		if (($stripped[0]=='p') && (substr($stripped,0,6)=='pages/')) // Ah, need to do some checks as we are looking in the welcome zone
		{
			$full=$relative?(get_file_base().'/'.$path):$path;
			if (!is_file($full))
			{
				$site_equiv=get_file_base().'/site/'.$stripped;

				if (is_file($site_equiv))
				{
					$ret=$relative?('site/'.$stripped):$site_equiv;
					$zbmf_cache[$path]=$ret;
					return $ret;
				}
			}
		}
	}

	$zbmf_cache[$path]=$path;
	return $path;
}

/**
 * Get the name of the zone the current page request is coming from.
 *
 * @return ID_TEXT		The current zone
 */
function get_zone_name()
{
	global $ZONE,$RELATIVE_PATH,$SITE_INFO,$VIRTUALISED_ZONES;
	if ($ZONE!==NULL) return $ZONE['zone_name'];
	if ($VIRTUALISED_ZONES!==false)
	{
		$VIRTUALISED_ZONES=false;
		$url_path=dirname(ocp_srv('REQUEST_URI'));
		foreach ($SITE_INFO as $key=>$val)
		{
			if (($key[0]=='Z') && (substr($key,0,13))=='ZONE_MAPPING_')
			{
				$VIRTUALISED_ZONES=true;
				if ((preg_replace('#:\d+$#','',ocp_srv('HTTP_HOST'))==$val[0]) && (preg_match('#^'.(($val[1]=='')?'':('/'.preg_quote($val[1]))).'(/|$)#',$url_path)!=0))
					return substr($key,13);
			}
		}
	}
	$real_zone=(($RELATIVE_PATH=='data') || ($RELATIVE_PATH=='data_custom'))?get_param('zone',''):$RELATIVE_PATH;

	return $real_zone;
}

/**
 * Find the zone a page is in.
 *
 * @param  ID_TEXT		The page name to find
 * @param  ID_TEXT		The type of the page we are looking for
 * @param  ?string		The special subcategorisation of page we are looking for (e.g. 'EN' for a comcode page) (NULL: none)
 * @param  string			The file extension for the page type
 * @param  boolean		Whether ocPortal should bomb out if the page was not found
 * @return ?ID_TEXT		The zone the page is in (NULL: not found)
 */
function get_module_zone($module_name,$type='modules',$dir2=NULL,$ftype='php',$error=true)
{
	global $MODULES_ZONES;
	if ((isset($MODULES_ZONES[$module_name])) || ((!$error) && (array_key_exists($module_name,$MODULES_ZONES)) && ($type=='modules')/*don't want to look at cached failure for different page type*/))
	{
		return $MODULES_ZONES[$module_name];
	}

	$error=false; // hack for now

	$zone=get_zone_name();
	if (($module_name==get_page_name()) && (running_script('index')) && ($module_name!='login'))
	{
		$MODULES_ZONES[$module_name]=$zone;
		return $zone;
	}

	if (get_value('allow_admin_in_other_zones')!=='1')
	{
		if (($type=='modules') && (substr($module_name,0,6)=='admin_'))
		{
			$zone='adminzone';
			$MODULES_ZONES[$module_name]=$zone;
			return $zone;
		}
		if (($type=='modules') && (substr($module_name,0,4)=='cms_'))
		{
			$zone='cms';
			$MODULES_ZONES[$module_name]=$zone;
			return $zone;
		}
	}

	global $REDIRECT_CACHE;
	$first_zones=array((substr($module_name,0,6)=='admin_')?'adminzone':$zone);
	if ($zone!='') $first_zones[]='';
	if (($zone!='site')/* && (is_file(get_file_base().'/site/index.php'))*/) $first_zones[]='site';
	foreach ($first_zones as $zone)
	{
		if ((isset($REDIRECT_CACHE[$zone][$module_name])) && ($REDIRECT_CACHE[$zone][$module_name]['r_is_transparent']==1)) // Only needs to actually look for redirections in first zones until end due to the way precedences work (we know the current zone will be in the first zones)
		{
			$MODULES_ZONES[$module_name]=$zone;
			if (function_exists('persistent_cache_set')) persistent_cache_set('MODULES_ZONES',$MODULES_ZONES);
			return $zone;
		}

		if ((is_file(zone_black_magic_filterer(get_file_base().'/'.$zone.'/pages/'.$type.'/'.(($dir2===NULL)?'':($dir2.'/')).$module_name.'.'.$ftype)))
			|| (is_file(zone_black_magic_filterer(get_file_base().'/'.$zone.'/pages/'.$type.'_custom/'.(($dir2===NULL)?'':($dir2.'/')).$module_name.'.'.$ftype))))
		{
			if ((isset($REDIRECT_CACHE[$zone][$module_name])) && ($REDIRECT_CACHE[$zone][$module_name]['r_is_transparent']==0) && ($REDIRECT_CACHE[$zone][$module_name]['r_to_page']==$module_name)) $zone=$REDIRECT_CACHE[$zone][$module_name]['r_to_zone'];
			$MODULES_ZONES[$module_name]=$zone;
			if (function_exists('persistent_cache_set')) persistent_cache_set('MODULES_ZONES',$MODULES_ZONES);
			return $zone;
		}
	}
	$zones=find_all_zones();
	foreach ($zones as $zone)
	{
		if (!in_array($zone,$first_zones))
		{
			if ((is_file(zone_black_magic_filterer(get_file_base().'/'.$zone.'/pages/'.$type.'/'.(($dir2===NULL)?'':($dir2.'/')).$module_name.'.'.$ftype)))
				|| (is_file(zone_black_magic_filterer(get_file_base().'/'.$zone.'/pages/'.$type.'_custom/'.(($dir2===NULL)?'':($dir2.'/')).$module_name.'.'.$ftype))))
			{
				if ((isset($REDIRECT_CACHE[$zone][$module_name])) && ($REDIRECT_CACHE[$zone][$module_name]['r_is_transparent']==0) && ($REDIRECT_CACHE[$zone][$module_name]['r_to_page']==$module_name)) $zone=$REDIRECT_CACHE[$zone][$module_name]['r_to_zone'];
				$MODULES_ZONES[$module_name]=$zone;
				if (function_exists('persistent_cache_set')) persistent_cache_set('MODULES_ZONES',$MODULES_ZONES);
				return $zone;
			}
		}
	}

	foreach ($zones as $zone) // Okay, finally check for redirects
	{
		if ((isset($REDIRECT_CACHE[$zone][$module_name])) && ($REDIRECT_CACHE[$zone][$module_name]['r_is_transparent']==1))
		{
			$MODULES_ZONES[$module_name]=$zone;
			if (function_exists('persistent_cache_set')) persistent_cache_set('MODULES_ZONES',$MODULES_ZONES);
			return $zone;
		}
	}

	if (!$error)
	{
		$MODULES_ZONES[$module_name]=NULL;
		return NULL;
	}
	warn_exit(do_lang_tempcode('MISSING_MODULE_REFERENCED',$module_name));
	return NULL;
}

/**
 * Find the zone a comcode page is in.
 *
 * @param  ID_TEXT		The comcode page name to find
 * @param  boolean		Whether ocPortal should bomb out if the page was not found
 * @return ?ID_TEXT		The zone the comcode page is in (NULL: missing)
 */
function get_comcode_zone($page_name,$error=true)
{
	$test=get_module_zone($page_name,'comcode',user_lang(),'txt',false);
	if (!is_null($test)) return $test;
	$test=get_module_zone($page_name,'comcode',get_site_default_lang(),'txt',false);
	if (!is_null($test)) return $test;
	$test=get_module_zone($page_name,'comcode',fallback_lang(),'txt',false);
	if (!is_null($test)) return $test;
	if ($error) warn_exit(do_lang_tempcode('MISSING_MODULE_REFERENCED',$page_name));
	return NULL;
}

/**
 * Find the zone a page is in.
 *
 * @param  ID_TEXT		The page name to find
 * @param  boolean		Whether ocPortal should bomb out if the page was not found
 * @return ?ID_TEXT		The zone the page is in (NULL: missing)
 */
function get_page_zone($page_name,$error=true)
{
	$test=get_module_zone($page_name,'modules',NULL,'php',false);
	if (!is_null($test)) return $test;
	$test=get_module_zone($page_name,'comcode',get_site_default_lang(),'txt',false);
	if (!is_null($test)) return $test;
	$test=get_module_zone($page_name,'comcode',fallback_lang(),'txt',false);
	if (!is_null($test)) return $test;
	$test=get_module_zone($page_name,'html',get_site_default_lang(),'htm',false);
	if (!is_null($test)) return $test;
	$test=get_module_zone($page_name,'html',fallback_lang(),'htm',false);
	if (!is_null($test)) return $test;
	$test=get_module_zone($page_name,'minimodules',NULL,'php',false);
	if (!is_null($test)) return $test;
	if ($error) warn_exit(do_lang_tempcode('MISSING_MODULE_REFERENCED',$page_name));
	return NULL;
}

/**
 * Runs the specified mini-module.
 * The module result is returned.
 *
 * @param  PATH			The relative path to the module file
 * @return tempcode		The result of executing the module
 */
function load_minimodule_page($string)
{
	global $PAGE_STRING;
	if (is_null($PAGE_STRING)) $PAGE_STRING=$string;

	require_code('developer_tools');
	destrictify();

	ob_start();
	require_code(filter_naughty($string));
	$out=new ocp_tempcode();
	$out->attach(ob_get_contents());
	ob_end_clean();

	restrictify();

	return $out;
}

/**
 * Runs the specified module, but also update any stats for the module, and check to see if it needs upgrading or reinstalling.
 * The module result is returned.
 *
 * @param  PATH			The relative path to the module file
 * @param  ID_TEXT		The page name to load
 * @return tempcode		The result of executing the module
 */
function load_module_page($string,$codename)
{
	global $PAGE_STRING;
	if (is_null($PAGE_STRING)) $PAGE_STRING=$string;

	require_code(filter_naughty($string));
	if (class_exists('Mx_'.filter_naughty_harsh($codename)))
	{
		$object=object_factory('Mx_'.filter_naughty_harsh($codename));
	} else
	{
		$object=object_factory('Module_'.filter_naughty_harsh($codename));
	}

	// Get info about what is installed and what is on disk
	$rows=$GLOBALS['SITE_DB']->query_select('modules',array('*'),array('module_the_name'=>$codename),'',1);
	if (array_key_exists(0,$rows))
	{
		$info=$object->info();
		$installed_version=$rows[0]['module_version'];
		$installed_hack_version=$rows[0]['module_hack_version'];
		$installed_hacked_by=$rows[0]['module_hacked_by'];
		if (is_null($installed_hacked_by)) $installed_hacked_by='';
		$this_version=$info['version'];
		$this_hack_version=$info['hack_version'];
		$this_hacked_by=$info['hacked_by'];
		if (is_null($this_hacked_by)) $this_hacked_by='';

		// See if we need to do an upgrade
		if (($installed_version<$this_version) && (array_key_exists('update_require_upgrade',$info)))
		{
			require_code('database_action');
			require_code('config2');
			require_code('menus2');
			$GLOBALS['SITE_DB']->query_update('modules',array('module_version'=>$this_version,'module_hack_version'=>$this_hack_version,'module_hacked_by'=>$this_hacked_by),array('module_the_name'=>$codename),'',1); // Happens first so if there is an error it won't loop (if we updated install code manually there will be an error)
			$object->install($installed_version,$installed_hack_version,$installed_hacked_by);
		}
		elseif (($installed_hack_version<$this_hack_version) && (array_key_exists('hack_require_upgrade',$info)))
		{
			require_code('database_action');
			require_code('config2');
			require_code('menus2');
	/*		if (($installed_hacked_by!=$this_hacked_by) && (!is_null($installed_hacked_by)))
			{
				fatal_exit();
			} Probably better we leave the solution to this to modders rather than just block the potential for there even to be a solution	*/

			$GLOBALS['SITE_DB']->query_update('modules',array('module_version'=>$this_version,'module_hack_version'=>$this_hack_version,'module_hacked_by'=>$this_hacked_by),array('module_the_name'=>$codename),'',1);
			$object->install($installed_version,$installed_hack_version,$installed_hacked_by);
		}
	} else
	{
		require_code('zones2');
		$zone=substr($string,0,strpos($string,'/'));
		if ($zone=='pages') $zone='';
		reinstall_module($zone,$codename);
	}

	return $object->run();
}

/**
 * Find the installed zones, up to the first $max installed
 *
 * @param  boolean		Whether to search the file system and return zones that might not be fully in the system (otherwise will just use the database)
 * @param  boolean		Whether to get titles for the zones as well. Only if !$search
 * @param  boolean		Whether to insist on getting all zones (there could be thousands in theory...)
 * @param  integer		Start position to get results from
 * @param  integer		Maximum zones to get
 * @return array			A list of zone names / a list of quartets (name, title, show in menu, default page)
 */
function find_all_zones($search=false,$get_titles=false,$force_all=false,$start=0,$max=50)
{
	if ($search)
	{
		$out=array('');

		$dh=opendir(get_file_base());
		while (($file=readdir($dh))!==false)
		{
			if (($file!='.') && ($file!='..') && (is_dir($file)) && (is_readable(get_file_base().'/'.$file)) && (is_file(get_file_base().'/'.$file.'/index.php')) && (is_dir(get_file_base().'/'.$file.'/pages/modules')))
			{
				if ((get_option('collapse_user_zones',true)==='1') && ($file=='site')) continue;

				$out[]=$file;

				if ((!$force_all) && (count($out)==$max)) break;
			}
		}
		closedir($dh);

		return $out;
	}

	global $ALL_ZONES,$ALL_ZONES_TITLED,$SITE_INFO;

	if ($get_titles)
	{
		if ($ALL_ZONES_TITLED===NULL) $ALL_ZONES_TITLED=function_exists('persistent_cache_get')?persistent_cache_get('ALL_ZONES_TITLED'):NULL;
		if ($ALL_ZONES_TITLED!==NULL) return $ALL_ZONES_TITLED;
	} else
	{
		if ($ALL_ZONES===NULL) $ALL_ZONES=function_exists('persistent_cache_get')?persistent_cache_get('ALL_ZONES'):NULL;
		if ($ALL_ZONES!==NULL) return $ALL_ZONES;
	}

	$rows=$GLOBALS['SITE_DB']->query_select('zones',array('*','zone_title AS _zone_title'),NULL,'ORDER BY zone_name',$force_all?NULL:$max,$start);
	if ((!$force_all) && (count($rows)==$max))
	{
		$rows=$GLOBALS['SITE_DB']->query_select('zones',array('*','zone_title AS _zone_title'),NULL,'ORDER BY zone_title',$max/*reasonable limit; zone_title is sequential for default zones*/);
	}
	$zones_titled=array();
	$zones=array();
	foreach ($rows as $zone)
	{
		if ((get_option('collapse_user_zones',true)==='1') && ($zone['zone_name']=='site')) continue;

		$zone['zone_title']=get_translated_text($zone['_zone_title']);

		$folder=get_file_base().'/'.$zone['zone_name'].'/pages';
		if (((isset($SITE_INFO['no_disk_sanity_checks'])) && ($SITE_INFO['no_disk_sanity_checks']=='1')) || (is_file(get_file_base().'/'.$zone['zone_name'].'/index.php')))
		{
			$zones[]=$zone['zone_name'];
			$zones_titled[$zone['zone_name']]=array($zone['zone_name'],$zone['zone_title'],array_key_exists('zone_displayed_in_menu',$zone)?$zone['zone_displayed_in_menu']:1,$zone['zone_default_page'],$zone);
		}
	}

	$ALL_ZONES_TITLED=$zones_titled;
	if (function_exists('persistent_cache_set')) persistent_cache_set('ALL_ZONES_TITLED',$ALL_ZONES_TITLED);
	$ALL_ZONES=$zones;
	if (function_exists('persistent_cache_set')) persistent_cache_set('ALL_ZONES',$ALL_ZONES);

	return $get_titles?$zones_titled:$zones;
}

/**
 * Look up and remember what modules are installed.
 */
function cache_module_installed_status()
{
	global $MODULE_INSTALLED_CACHE;
	$rows=$GLOBALS['SITE_DB']->query_select('modules',array('module_the_name'));
	foreach ($rows as $row)
	{
		$MODULE_INSTALLED_CACHE[$row['module_the_name']]=true;
	}
}

/**
 * Check to see if a module is installed.
 *
 * @param  ID_TEXT		The module name
 * @return boolean		Whether it is
 */
function module_installed($module)
{
	global $MODULE_INSTALLED_CACHE;
	if (array_key_exists($module,$MODULE_INSTALLED_CACHE)) return $MODULE_INSTALLED_CACHE[$module];
	$test=$GLOBALS['SITE_DB']->query_value_null_ok('modules','module_the_name',array('module_the_name'=>$module));
	$answer=!is_null($test);
	$MODULE_INSTALLED_CACHE[$module]=$answer;
	return $answer;
}

/**
 * Get the path to a module known to be in a certain zone.
 *
 * @param  ID_TEXT		The zone name
 * @param  ID_TEXT		The module name
 * @return PATH			The module path
 */
function _get_module_path($zone,$module)
{
	$module_path=zone_black_magic_filterer(($zone=='')?('pages/modules_custom/'.filter_naughty_harsh($module).'.php'):(filter_naughty($zone).'/pages/modules_custom/'.filter_naughty_harsh($module).'.php'),true);
	if (!is_file(get_file_base().'/'.$module_path))
	{
		$module_path=zone_black_magic_filterer(($zone=='')?('pages/modules/'.filter_naughty_harsh($module).'.php'):(filter_naughty($zone).'/pages/modules/'.filter_naughty_harsh($module).'.php'),true);
	}
	return $module_path;
}

/**
 * Get an array of all the hook implementations for a hook class.
 *
 * @param  ID_TEXT		The type of hook
 * @set    blocks modules systems
 * @param  ID_TEXT		The hook class to find hook implementations for (e.g. the name of a module)
 * @return array			A map of hook implementation name to [sources|sources_custom]
 */
function find_all_hooks($type,$entry)
{
	global $HOOKS_CACHE;
	if (isset($HOOKS_CACHE[$type.'/'.$entry])) return $HOOKS_CACHE[$type.'/'.$entry];

	$out=array();

	$dir=get_file_base().'/sources/hooks/'.filter_naughty($type).'/'.filter_naughty($entry);
	$dh=@opendir($dir);
	if ($dh!==false)
	{
		while (($file=readdir($dh))!==false)
		{
			$basename=basename($file,'.php');
			if (($file==$basename.'.php') && (preg_match('#^[\w\-]*$#',$basename)!=0))
			{
//				if ((filesize($dir.'/'.$file)>0) && (substr($file,0,4)!='ocf_'))
					$out[$basename]='sources';
			}
		}
		closedir($dh);
	}

	if ((!isset($GLOBALS['DOING_USERS_INIT'])) && (!in_safe_mode())) // The !isset is because of if the user init causes a DB query to load sessions which loads DB hooks which checks for safe mode which leads to a permissions check for safe mode and thus a failed user check (as sessions not loaded yet)
	{
		$dir=get_file_base().'/sources_custom/hooks/'.filter_naughty($type).'/'.filter_naughty($entry);
		$dh=@opendir($dir);
		if ($dh!==false)
		{
			while (($file=readdir($dh))!==false)
			{
				$basename=basename($file,'.php');
				if (($file==$basename.'.php') && (preg_match('#^[\w\-]*$#',$basename)!=0))
				{
	//				if ((filesize($dir.'/'.$file)>0) && (substr($file,0,4)!='ocf_'))
						$out[$basename]='sources_custom';
				}
			}
			closedir($dh);
		}
	}

	// Optimisation, so that hooks with same name as our page get loaded first
	$page=get_param('page','',true);
	if (array_key_exists($page,$out))
	{
		$_out=array($page=>$out[$page]);
		unset($out[$page]);
		$out=array_merge($_out,$out);
	}

	if (!isset($GLOBALS['DOING_USERS_INIT']))
		$HOOKS_CACHE[$type.'/'.$entry]=$out;

	if (function_exists('persistent_cache_set')) persistent_cache_set('HOOKS',$HOOKS_CACHE,true);

	return $out;
}

/**
 * Get the processed tempcode for the specified block. Please note that you pass multiple parameters in as an array, but single parameters go in as a string or other flat variable.
 *
 * @param  ID_TEXT		The block name
 * @param  ?array			The block parameter map (NULL: no parameters)
 * @param  ?integer		The TTL to use in minutes (NULL: block default)
 * @return tempcode		The generated tempcode
 */
function do_block($codename,$map=NULL,$ttl=NULL)
{
	global $LANGS_REQUESTED,$JAVASCRIPTS,$CSSS,$DO_NOT_CACHE_THIS;

	$DO_NOT_CACHE_THIS=false;

	if ((cron_installed()) && (running_script('index')))
	{
		if ($codename=='side_weather' || $codename=='side_rss' || $codename=='main_rss') // Special cases to stop external dependencies causing issues
		{
			if (!array_key_exists('cache',$map))
				$map['cache']='2';
		}
	}

	$object=NULL;
	if (((get_option('is_on_block_cache')=='1') || (get_param_integer('keep_cache',0)==1) || (get_param_integer('cache',0)==1) || (get_param_integer('cache_blocks',0)==1)) && ((get_param_integer('keep_cache',NULL)!==0) && (get_param_integer('cache_blocks',NULL)!==0) && (get_param_integer('cache',NULL)!==0)) && (strpos(get_param('special_page_type',''),'t')===false))
	{
		// See if the block may be cached (else cannot, or is yet unknown)
		if (($map!==NULL) && (isset($map['cache'])) && ($map['cache']=='0'))
		{
			$row=NULL;
		} else // We may allow it to be cached but not store the cache signature, as it is too complex
		{
			$row=find_cache_on($codename);
			if ($row===NULL)
			{
				$object=do_block_hunt_file($codename,$map);
				if ((is_object($object)) && (method_exists($object,'cacheing_environment')))
				{
					$info=$object->cacheing_environment($map);
					if ($info!==NULL)
						$row=array('cached_for'=>$codename,'cache_on'=>$info['cache_on'],'cache_ttl'=>$info['ttl']);
				}
			}
			if (($row===NULL) && ((isset($map['cache'])) && ($map['cache']=='1') || (isset($map['quick_cache'])) && ($map['quick_cache']=='1')))
			{
				$row=array('cached_for'=>$codename,'cache_on'=>'$map','cache_ttl'=>60);
			}
		}
		if ($row!==NULL)
		{
			$cache_identifier=do_block_get_cache_identifier($row['cache_on'],$map);

			// See if it actually is cached
			if ($cache_identifier!==NULL)
			{
				if ($ttl===NULL) $ttl=$row['cache_ttl'];
				$cache=get_cache_entry($codename,$cache_identifier,$ttl,true,(array_key_exists('cache',$map)) && ($map['cache']=='2'),$map);
				if ($cache===NULL)
				{
					$nql_backup=$GLOBALS['NO_QUERY_LIMIT'];
					$GLOBALS['NO_QUERY_LIMIT']=true;

					if ($object!==NULL) $object=do_block_hunt_file($codename,$map);
					if (!is_object($object))
					{
						// This probably happened as we uninstalled a block, and now we're getting a "missing block" message back.

						if (!defined('HIPHOP_PHP'))
						{
							// Removed outdated cache-on information
							$GLOBALS['SITE_DB']->query_delete('cache_on',array('cached_for'=>$codename),'',1);
							persistent_cache_delete('CACHE_ON');
						}

						$out=new ocp_tempcode();
						$out->attach($object);
						return $out;
					}
					$backup_langs_requested=$LANGS_REQUESTED;
					$backup_javascripts=$JAVASCRIPTS;
					$backup_csss=$CSSS;
					$LANGS_REQUESTED=array();
					$JAVASCRIPTS=array('javascript'=>1,'javascript_transitions'=>1);
					$CSSS=array('no_cache'=>1,'global'=>1);
					if ((isset($map['quick_cache'])) && ($map['quick_cache']=='1')) // because we know we will not do this often we can allow this to work as a vector for doing highly complex activity
					{
						global $MEMORY_OVER_SPEED;
						$MEMORY_OVER_SPEED=true; // Let this eat up some CPU in order to let it save RAM,
						disable_php_memory_limit();
						if (function_exists('set_time_limit')) @set_time_limit(200);
					}
					$cache=$object->run($map);
					$cache->handle_symbol_preprocessing();
					if (!$DO_NOT_CACHE_THIS)
					{
						require_code('caches2');
						if ((isset($map['quick_cache'])) && ($map['quick_cache']=='1') && (has_cookies())) $cache=make_string_tempcode(preg_replace('#((\?)|(&(amp;)?))keep\_[^="]*=[^&"]*#','\2',$cache->evaluate()));
						put_into_cache($codename,$ttl,$cache_identifier,$cache,array_keys($LANGS_REQUESTED),array_keys($JAVASCRIPTS),array_keys($CSSS),true);
					} elseif (($ttl!=-1) && ($cache->is_empty())) // Try again with no TTL, if we currently failed but did impose a TTL
					{
						$LANGS_REQUESTED+=$backup_langs_requested;
						$JAVASCRIPTS+=$backup_javascripts;
						$CSSS+=$backup_csss;
						return do_block($codename,$map,-1);
					}
					$LANGS_REQUESTED+=$backup_langs_requested;
					$JAVASCRIPTS+=$backup_javascripts;
					$CSSS+=$backup_csss;

					$GLOBALS['NO_QUERY_LIMIT']=$nql_backup;
				}
				return $cache;
			}
		}
	}

	// NB: If we've got this far cache="2" is ignored. But later on (for normal expiries, different contexts, etc) cache_on will be known so not an issue.

	// We will need to load the actual file
	if (is_null($object)) $object=do_block_hunt_file($codename,$map);
	if (is_object($object))
	{
		if ($map===NULL) $map=array();
		$nql_backup=$GLOBALS['NO_QUERY_LIMIT'];
		$GLOBALS['NO_QUERY_LIMIT']=true;
		$backup_langs_requested=$LANGS_REQUESTED;
		$backup_javascripts=$JAVASCRIPTS;
		$backup_csss=$CSSS;
		$LANGS_REQUESTED=array();
		$JAVASCRIPTS=array('javascript'=>1,'javascript_transitions'=>1);
		$CSSS=array('no_cache'=>1,'global'=>1);
		$cache=$object->run($map);
		$GLOBALS['NO_QUERY_LIMIT']=$nql_backup;
	} else
	{
		$out=new ocp_tempcode();
		$out->attach($object);
		return $out;
	}

	// May it be added to cache_on?
	if ((!$DO_NOT_CACHE_THIS) && (method_exists($object,'cacheing_environment')) && ((get_option('is_on_block_cache')=='1') || (get_param_integer('keep_cache',0)==1) || (get_param_integer('cache_blocks',0)==1) || (get_param_integer('cache',0)==1)) && ((get_param_integer('keep_cache',NULL)!==0) && (get_param_integer('cache_blocks',NULL)!==0) && (get_param_integer('cache',NULL)!==0)))
	{
		$info=$object->cacheing_environment($map);
		if ($info!==NULL)
		{
			$cache_identifier=do_block_get_cache_identifier($info['cache_on'],$map);
			if ($cache_identifier!==NULL)
			{
				require_code('caches2');
				put_into_cache($codename,$info['ttl'],$cache_identifier,$cache,array_keys($LANGS_REQUESTED),array_keys($JAVASCRIPTS),array_keys($CSSS),true);
				if ((!defined('HIPHOP_PHP')) && (!is_array($info['cache_on'])))
				{
					$GLOBALS['SITE_DB']->query_insert('cache_on',array('cached_for'=>$codename,'cache_on'=>$info['cache_on'],'cache_ttl'=>$info['ttl']),false,true); // Allow errors in case of race conditions
				}
			}
		}
	}
	$LANGS_REQUESTED+=$backup_langs_requested;
	$JAVASCRIPTS+=$backup_javascripts;
	$CSSS+=$backup_csss;

	return $cache;
}

/**
 * Convert a parameter set from a an array (for PHP code) to a string (for templates).
 *
 * @param  array			The parameters / acceptable parameter pattern
 * @return string			The parameters / acceptable parameter pattern, as template safe parameter
 */
function block_params_arr_to_str($map)
{
	ksort($map);

	$_map='';

	foreach ($map as $key=>$val)
	{
		if ($_map!='') $_map.=',';
		$_map.=$key.'='.str_replace(',','\,',$val);
	}

	return $_map;
}

/**
 * Get the block object for a given block codename.
 *
 * @param  ID_TEXT		The block name
 * @param  ?array			The block parameter map (NULL: no parameters)
 * @return mixed			Either the block object, or the string output of a miniblock
 */
function do_block_hunt_file($codename,$map=NULL)
{
	global $BLOCKS_AT_CACHE;

	$codename=filter_naughty_harsh($codename);

	$file_base=get_file_base();

	global $_REQUIRED_CODE;
	if (((isset($BLOCKS_AT_CACHE[$codename])) && ($BLOCKS_AT_CACHE[$codename]=='sources_custom/blocks')) || ((!isset($BLOCKS_AT_CACHE[$codename])) && (is_file($file_base.'/sources_custom/blocks/'.$codename.'.php'))))
	{
		if (!isset($_REQUIRED_CODE['blocks/'.$codename])) require_once($file_base.'/sources_custom/blocks/'.$codename.'.php');
		$_REQUIRED_CODE['blocks/'.$codename]=1;

		if (!isset($BLOCKS_AT_CACHE[$codename]))
		{
			$BLOCKS_AT_CACHE[$codename]='sources_custom/blocks';
			if (function_exists('persistent_cache_set')) persistent_cache_set('BLOCKS_AT',$BLOCKS_AT_CACHE,true);
		}
	}
	elseif (((isset($BLOCKS_AT_CACHE[$codename])) && ($BLOCKS_AT_CACHE[$codename]=='sources/blocks')) || ((!isset($BLOCKS_AT_CACHE[$codename])) && (is_file($file_base.'/sources/blocks/'.$codename.'.php'))))
	{
		if (!isset($_REQUIRED_CODE['blocks/'.$codename])) require_once($file_base.'/sources/blocks/'.$codename.'.php');
		$_REQUIRED_CODE['blocks/'.$codename]=1;

		if (!isset($BLOCKS_AT_CACHE[$codename]))
		{
			$BLOCKS_AT_CACHE[$codename]='sources/blocks';
			if (function_exists('persistent_cache_set')) persistent_cache_set('BLOCKS_AT',$BLOCKS_AT_CACHE,true);
		}
	}
	else
	{
		if (((isset($BLOCKS_AT_CACHE[$codename])) && ($BLOCKS_AT_CACHE[$codename]=='sources_custom/miniblocks')) || ((!isset($BLOCKS_AT_CACHE[$codename])) && (is_file($file_base.'/sources_custom/miniblocks/'.$codename.'.php'))))
		{
			require_code('developer_tools');
			destrictify();
			ob_start();
			if (defined('HIPHOP_PHP'))
			{
				require('sources_custom/miniblocks/'.$codename.'.php');
			} else
			{
				require($file_base.'/sources_custom/miniblocks/'.$codename.'.php');
			}
			$object=ob_get_contents();
			ob_end_clean();
			restrictify();

			if (!isset($BLOCKS_AT_CACHE[$codename]))
			{
				$BLOCKS_AT_CACHE[$codename]='sources_custom/miniblocks';
				if (function_exists('persistent_cache_set')) persistent_cache_set('BLOCKS_AT',$BLOCKS_AT_CACHE,true);
			}
		}
		elseif (((isset($BLOCKS_AT_CACHE[$codename])) && ($BLOCKS_AT_CACHE[$codename]=='sources/miniblocks')) || ((!isset($BLOCKS_AT_CACHE[$codename])) && (is_file($file_base.'/sources/miniblocks/'.$codename.'.php'))))
		{
			require_code('developer_tools');
			destrictify();
			ob_start();
			if (defined('HIPHOP_PHP'))
			{
				require('sources/miniblocks/'.$codename.'.php');
			} else
			{
				require($file_base.'/sources/miniblocks/'.$codename.'.php');
			}
			$object=ob_get_contents();
			ob_end_clean();
			restrictify();

			if (!isset($BLOCKS_AT_CACHE[$codename]))
			{
				$BLOCKS_AT_CACHE[$codename]='sources/miniblocks';
				if (function_exists('persistent_cache_set')) persistent_cache_set('BLOCKS_AT',$BLOCKS_AT_CACHE,true);
			}
		} elseif ((is_null($map)) || (!array_key_exists('failsafe',$map)) || ($map['failsafe']!='1'))
		{
			$temp=do_template('WARNING_BOX',array('WARNING'=>do_lang_tempcode('MISSING_BLOCK_FILE',escape_html($codename))));
			return $temp->evaluate();
		} else $object='';
		return $object;
	}

	$_object=object_factory('Block_'.$codename);
	return $_object;
}

/**
 * Takes a string which is a PHP expression over $map (parameter map), and returns a derived identifier.
 * We see if we have something cached by looking for a matching identifier.
 *
 * @param  mixed			PHP expression over $map (the parameter map of the block) OR a call_user_func specifier that will return a result (which will be used if cacheing is really very important, even for Hip Hop PHP)
 * @param  ?array			The block parameter map (NULL: no parameters)
 * @return ?LONG_TEXT	The derived cache identifier (NULL: the identifier is CURRENTLY null meaning cannot be cached)
 */
function do_block_get_cache_identifier($cache_on,$map)
{
	$_cache_identifier=array();
	if (is_array($cache_on))
	{
		$_cache_identifier=call_user_func($cache_on[0],$map);
	} else
	{
		if (($cache_on!='') && (!defined('HIPHOP_PHP')))
		{
			$_cache_on=eval('return '.$cache_on.';'); // NB: This uses $map, as $map is referenced inside $cache_on
			if ($_cache_on===NULL) return NULL;
			foreach ($_cache_on as $on)
			{
				$_cache_identifier[]=$on;
			}
		} elseif (defined('HIPHOP_PHP')) return NULL;
	}

	$_cache_identifier[]=get_users_timezone(get_member());
	$_cache_identifier[]=(get_bot_type()===NULL);

	$cache_identifier=serialize($_cache_identifier);

	return $cache_identifier;
}

/**
 * Gets the path to a block code file for a block code name
 *
 * @param  ID_TEXT		The name of the block
 * @return PATH			The path to the block
 */
function _get_block_path($block)
{
	$block_path=get_file_base().'/sources_custom/blocks/'.filter_naughty_harsh($block).'.php';
	if (!is_file($block_path))
	{
		$block_path=get_file_base().'/sources/blocks/'.filter_naughty_harsh($block).'.php';
		if (!is_file($block_path))
		{
			$block_path=get_file_base().'/sources_custom/miniblocks/'.filter_naughty_harsh($block).'.php';
		}
	}
	return $block_path;
}

/**
 * Check to see if a block is installed.
 *
 * @param  ID_TEXT		The module name
 * @return boolean		Whether it is
 */
function block_installed($block)
{
	$test=$GLOBALS['SITE_DB']->query_value_null_ok('blocks','block_name',array('block_name'=>$block));
	return !is_null($test);
}

/**
 * Get an array of all the pages everywhere in the zone, limited by the selection algorithm (for small sites everything will be returned, for larger ones it depends on the show method).
 *
 * @param  ID_TEXT		The zone name
 * @param  boolean		Whether to leave file extensions on the page name
 * @param  boolean		Whether to take redirects into account
 * @param  integer		Selection algorithm constant
 * @set 0 1 2
 * @param  ?ID_TEXT		Page type to show (NULL: all)
 * @return array			A map of page name to type (modules_custom, etc)
 */
function find_all_pages_wrap($zone,$keep_ext_on=false,$consider_redirects=false,$show_method=0,$page_type=NULL)
{
	require_code('zones2');
	return _find_all_pages_wrap($zone,$keep_ext_on,$consider_redirects,$show_method,$page_type);
}

/**
 * Get an array of all the pages of the specified type (module, etc) and extension (for small sites everything will be returned, for larger ones it depends on the show method).
 *
 * @param  ID_TEXT		The zone name
 * @param  ID_TEXT		The type
 * @set    modules modules_custom comcode comcode_custom html html_custom
 * @param  string			The file extension to limit us to (without a dot)
 * @param  boolean		Whether to leave file extensions on the page name
 * @param  ?TIME			Only show pages newer than (NULL: no restriction)
 * @param  integer		Selection algorithm constant
 * @set 0 1 2
 * @return array			A map of page name to type (modules_custom, etc)
 */
function find_all_pages($zone,$type,$ext='php',$keep_ext_on=false,$cutoff_time=NULL,$show_method=0)
{
	require_code('zones2');
	return _find_all_pages($zone,$type,$ext,$keep_ext_on,$cutoff_time,$show_method);
}

/**
 * Get an array of all the modules.
 *
 * @param  ID_TEXT		The zone name
 * @return array			A map of page name to type (modules_custom, etc)
 */
function find_all_modules($zone)
{
	require_code('zones2');
	return _find_all_modules($zone);
}

/**
 * Extract code to execute the requested functions with the requested parameters from the module at the given path.
 * We used to actually load up the module, but it ate all our RAM when we did!
 *
 * @param  PATH			The path to the module
 * @param  array			Array of functions to be executing
 * @param  ?array			A list of parameters to pass to our functions (NULL: none)
 * @return array			A list of pieces of code to do the equivalent of executing the requested functions with the requested parameters
 */
function extract_module_functions($path,$functions,$params=NULL)
{
	if ($params===NULL) $params=array();

	global $SITE_INFO;
	$prefer_direct_code_call=(isset($SITE_INFO['prefer_direct_code_call'])) && ($SITE_INFO['prefer_direct_code_call']=='1');
	$hphp=defined('HIPHOP_PHP');
	if ((($hphp) && (!function_exists('quercus_version'))) || ($prefer_direct_code_call))
	{
		global $CLASS_CACHE;
		if (array_key_exists($path,$CLASS_CACHE))
		{
			$new_classes=$CLASS_CACHE[$path];
		} else
		{
			if (!$hphp) $classes_before=get_declared_classes();
			require_code(preg_replace('#^'.preg_quote(get_file_base()).'/#','',preg_replace('#^'.preg_quote(get_file_base()).'/((sources)|(sources\_custom))/(.*)\.php#','${4}',$path)));
			if (!$hphp) $classes_after=get_declared_classes();
			$new_classes=$hphp?array():array_values(array_diff($classes_after,$classes_before));
			if (count($new_classes)==0) // Ah, AllVolatile is probably not enabled
			{
				$matches=array();
				if (preg_match('#^\s*class (\w+)#m',file_get_contents($path),$matches)!=0)
					$new_classes=array($matches[1]);
			}
			$CLASS_CACHE[$path]=$new_classes;
		}
		if ((array_key_exists(0,$new_classes)) && ($new_classes[0]=='standard_aed_module')) array_shift($new_classes);
		if (array_key_exists(0,$new_classes))
		{
			$c=$new_classes[0];
			$new_ob=new $c;
		} else
		{
			$new_ob=NULL;
		}
		$ret=array();
		foreach ($functions as $function)
		{
			if (method_exists($new_ob,$function))
			{
				$ret[]=array(array(&$new_ob,$function),$params);
			} else
			{
				$ret[]=NULL;
			}
		}
		return $ret;
	}

	if (!is_file($path)) return array(NULL);
	$file=unixify_line_format(file_get_contents($path),NULL,false,true);
	global $ARB_COUNTER;

	$r=preg_replace('#[^\w]#','',basename($path,'.php')).strval(mt_rand(0,100000)).'_'.strval($ARB_COUNTER);
	$ARB_COUNTER++;
	$out=array();
	$_params='';
	$pre=substr($file,5,strpos($file,'class ')-5); // FUDGEFUDGE. We assume any functions we need to pre-load precede any classes in the file
	$pre=preg_replace('#(^|\n)function (\w+)\(.*#s','if (!function_exists(\'${1}\')) { ${0} }',$pre); // In case we end up extracting from this file more than once across multiple calls to extract_module_functions
	if ($params!==NULL)
	{
		foreach ($params as $param)
		{
			if ($_params!='') $_params.=',';
			if (is_string($param)) $_params.='\''.str_replace('\'','\\\'',$param).'\'';
			elseif ($param===NULL) $_params.='NULL';
			elseif (is_bool($param)) $_params.=$param?'true':'false';
			else $_params.=strval($param);
		}
	}
	foreach ($functions as $function)
	{
		$start=strpos($file,'function '.$function.'(');

		$spaces=1;
		if ($start===false)
		{
			$out[]=NULL;
		} else
		{
			while ($file[$start-$spaces-1]!=chr(10))
				$spaces++;

			$end1=strpos($file,chr(10).str_repeat(' ',$spaces).'}'.chr(10),$start);
			$end2=strpos($file,chr(10).str_repeat("\t",$spaces).'}'.chr(10),$start);
			if ($end1===false) $end1=$end2;
			if ($end2===false) $end2=$end1;
			$end=min($end1,$end2)+2+$spaces;
			$func=substr($file,$start,$end-$start);

			/*if (strpos($func,'function '.$function.'()')===false)			Fails for default parameters (e.g. $a=NULL in function definition)
			{
				$new_func=preg_replace('#function '.preg_quote($function).'\(([^\n]*)\)#','list(${1})=array('.$_params.');',$func);
			} else
			{
				$new_func=preg_replace('#function '.preg_quote($function).'\(\)#','',$func);
			}*/
			$new_func=str_replace('function '.$function.'(','function '.$function.$r.'(',$func).'return '.filter_naughty_harsh($function).$r.'('.$_params.'); ';
			$out[]=$pre."\n\n".$new_func;

			$pre=''; // Can only load that bit once
		}
	}

	return $out;
}

