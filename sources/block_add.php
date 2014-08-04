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
 * Shows an HTML page for making block Comcode.
 */
function block_helper_script()
{
	require_lang('comcode');
	require_lang('blocks');
	require_code('zones2');
	require_code('zones3');
	require_code('addons');

	check_privilege('comcode_dangerous');

	$title=get_screen_title('BLOCK_HELPER');

	require_code('form_templates');
	require_all_lang();

	$type_wanted=get_param('block_type','main');

	$type=get_param('type','step1');

	$content=new ocp_tempcode();

	if ($type=='step1') // Ask for block
	{
		// Find what addons all our block files are in, and icons if possible
		$hooks=find_all_hooks('systems','addon_registry');
		$hook_keys=array_keys($hooks);
		$hook_files=array();
		foreach ($hook_keys as $hook)
		{
			$path=get_custom_file_base().'/sources_custom/hooks/systems/addon_registry/'.filter_naughty_harsh($hook).'.php';
			if (!file_exists($path))
			{
				$path=get_file_base().'/sources/hooks/systems/addon_registry/'.filter_naughty_harsh($hook).'.php';
			}
			$hook_files[$hook]=file_get_contents($path);
		}
		unset($hook_keys);
		$addon_icons=array();
		$addons_blocks=array();
		foreach ($hook_files as $addon_name=>$hook_file)
		{
			$matches=array();
			if (preg_match('#function get_file_list\(\)\s*\{([^\}]*)\}#',$hook_file,$matches)!=0)
			{
				if (!HIPHOP_PHP)
				{
					$addon_files=eval($matches[1]);
					if ($addon_files===false) $addon_files=array(); // Some kind of PHP error
				} else
				{
					require_code('hooks/systems/addon_registry/'.$addon_name);
					$hook_ob=object_factory('Hook_addon_registry_'.$addon_name);
					$addon_files=$hook_ob->get_file_list();
				}
				foreach ($addon_files as $file)
				{
					if ((substr($file,0,21)=='sources_custom/blocks/') || (substr($file,0,15)=='sources/blocks/'))
					{
						if ($addon_name=='staff_messaging') $addon_name='core_feedback_features';

						$addons_blocks[basename($file,'.php')]=$addon_name;
					}
				}
			}
			$addon_icons[$addon_name]=find_addon_icon($addon_name);
		}

		// Find where blocks have been used
		$block_usage=array();
		$zones=find_all_zones(false,true);
		foreach ($zones as $_zone)
		{
			$zone=$_zone[0];
			$pages=find_all_pages_wrap($zone,true);
			foreach ($pages as $filename=>$type)
			{
				if (substr(strtolower($filename),-4)=='.txt')
				{
					$matches=array();
					$contents=file_get_contents(zone_black_magic_filterer(((substr($type,0,15)=='comcode_custom/')?get_custom_file_base():get_file_base()).'/'.(($zone=='')?'':($zone.'/')).'pages/'.$type.'/'.$filename));
					$num_matches=preg_match_all('#\[block[^\]]*\](.*)\[/block\]#U',$contents,$matches);
					for ($i=0;$i<$num_matches;$i++)
					{
						$block_used=$matches[1][$i];
						if (!array_key_exists($block_used,$block_usage)) $block_usage[$block_used]=array();
						$block_usage[$block_used][]=$zone.':'.basename($filename,'.txt');
					}
				}
			}
		}

		// Show block list
		$links=new ocp_tempcode();
		$blocks=find_all_blocks();
		$dh=@opendir(get_file_base().'/sources_custom/miniblocks');
		if ($dh!==false)
		{
			while (($file=readdir($dh))!==false)
				if ((substr($file,-4)=='.php') && (preg_match('#^[\w\-]*$#',substr($file,0,strlen($file)-4))!=0))
					$blocks[substr($file,0,strlen($file)-4)]='sources_custom';
			closedir($dh);
		}
		$block_types=array();
		$block_types_icon=array();
		$keep=symbol_tempcode('KEEP');
		foreach (array_keys($blocks) as $block)
		{
			if (array_key_exists($block,$addons_blocks))
			{
				$addon_name=$addons_blocks[$block];
				$addon_icon=array_key_exists($addon_name,$addon_icons)?$addon_icons[$addon_name]:NULL;
				$addon_name=preg_replace('#^core\_#','',$addon_name);
			} else
			{
				$addon_name=NULL;
				$addon_icon=NULL;
			}
			$this_block_type=(is_null($addon_name) || (strpos($addon_name,'block')!==false) || ($addon_name=='core'))?substr($block,0,(strpos($block,'_')===false)?strlen($block):strpos($block,'_')):$addon_name;
			if (!array_key_exists($this_block_type,$block_types)) $block_types[$this_block_type]=new ocp_tempcode();
			if (!is_null($addon_icon)) $block_types_icon[$this_block_type]=$addon_icon;

			$block_description=do_lang('BLOCK_'.$block.'_DESCRIPTION',NULL,NULL,NULL,NULL,false);
			$block_use=do_lang('BLOCK_'.$block.'_USE',NULL,NULL,NULL,NULL,false);
			if (is_null($block_description)) $block_description='';
			if (is_null($block_use)) $block_use='';
			$descriptiont=($block_description=='' && $block_use=='')?new ocp_tempcode():do_lang_tempcode('BLOCK_HELPER_1X',$block_description,$block_use);

			$url=find_script('block_helper').'?type=step2&block='.urlencode($block).'&field_name='.urlencode(get_param('field_name')).$keep->evaluate();
			if (get_param('utheme','')!='') $url.='&utheme='.get_param('utheme');
			$url.='&block_type='.$type_wanted;
			if (get_param('save_to_id','')!='')
			{
				$url.='&save_to_id='.urlencode(get_param('save_to_id'));
			}
			$link_caption=do_lang_tempcode('NICE_BLOCK_NAME',escape_html(cleanup_block_name($block)),$block);
			$usage=array_key_exists($block,$block_usage)?$block_usage[$block]:array();

			$block_types[$this_block_type]->attach(do_template('BLOCK_HELPER_BLOCK_CHOICE',array('_GUID'=>'079e9b37fc142d292d4a64940243178a','USAGE'=>$usage,'DESCRIPTION'=>$descriptiont,'URL'=>$url,'LINK_CAPTION'=>$link_caption)));
		}
		/*if (array_key_exists($type_wanted,$block_types)) We don't do this now, as we structure by addon name
		{
			$x=$block_types[$type_wanted];
			unset($block_types[$type_wanted]);
			$block_types=array_merge(array($type_wanted=>$x),$block_types);
		}*/
		ksort($block_types); // We sort now instead
		$move_after=$block_types['adminzone_dashboard'];
		unset($block_types['adminzone_dashboard']);
		$block_types['adminzone_dashboard']=$move_after;
		foreach ($block_types as $block_type=>$_links)
		{
			switch ($block_type)
			{
				case 'side':
				case 'main':
				case 'bottom':
					$type_title=do_lang_tempcode('BLOCKS_TYPE_'.$block_type);
					$img=NULL;
					break;
				default:
					$type_title=do_lang_tempcode('BLOCKS_TYPE_ADDON',escape_html(cleanup_block_name($block_type)));
					$img=array_key_exists($block_type,$block_types_icon)?$block_types_icon[$block_type]:NULL;
					break;
			}
			$links->attach(do_template('BLOCK_HELPER_BLOCK_GROUP',array('_GUID'=>'975a881f5dbd054ced9d2e3b35ed59bf','IMG'=>$img,'TITLE'=>$type_title,'LINKS'=>$_links)));
		}
		$content=do_template('BLOCK_HELPER_START',array('_GUID'=>'1d58238a6d00eb7f79d5a4f0e85fb1a4','GET'=>true,'TITLE'=>$title,'LINKS'=>$links));
	}

	if ($type=='step2') // Ask for block fields
	{
		require_code('comcode_compiler');
		$defaults=parse_single_comcode_tag(get_param('parse_defaults','',true),'block');

		$keep=symbol_tempcode('KEEP');
		$back_url=find_script('block_helper').'?type=step1&field_name='.get_param('field_name').$keep->evaluate();
		if (get_param('utheme','')!='') $back_url.='&utheme='.get_param('utheme');
		if (get_param('save_to_id','')!='')
		{
			$back_url.='&save_to_id='.urlencode(get_param('save_to_id'));
		}

		$block=trim(get_param('block'));
		$title=get_screen_title('_BLOCK_HELPER',true,array(escape_html($block),escape_html($back_url)));
		$fields=new ocp_tempcode();

		// Load up renderer hooks
		$block_ui_renderers=array();
		$_block_ui_renderers=find_all_hooks('systems','block_ui_renderers');
		foreach (array_keys($_block_ui_renderers) as $_block_ui_renderer)
		{
			require_code('hooks/systems/block_ui_renderers/'.filter_naughty($_block_ui_renderer));
			$block_ui_renderers[]=object_factory('Hook_block_ui_renderers_'.$_block_ui_renderer);
		}

		// Work out parameters involved, and their sets ("classes")
		$parameters=get_block_parameters($block);
		$parameters[]='failsafe';
		$parameters[]='cache';
		$parameters[]='quick_cache';
		$parameters[]='defer';
		$parameters[]='block_id';
		if (!isset($defaults['cache'])) $defaults['cache']=block_cache_default($block);
		if (is_null($parameters)) $parameters=array();
		$advanced_ind=do_lang('BLOCK_IND_ADVANCED');
		$param_classes=array('normal'=>array(),'advanced'=>array());
		foreach ($parameters as $parameter)
		{
			$param_class='normal';
			if (($parameter=='cache') || ($parameter=='quick_cache') || ($parameter=='failsafe') || ($parameter=='defer') || ($parameter=='block_id') || (strpos(do_lang('BLOCK_'.$block.'_PARAM_'.$parameter),$advanced_ind)!==false))
				$param_class='advanced';
			$param_classes[$param_class][]=$parameter;
		}

		// Go over each set of parameters
		foreach ($param_classes as $param_class=>$parameters)
		{
			if (count($parameters)==0)
			{
				if ($param_class=='normal')
				{
					$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('_GUID'=>'e50ed41cc58bc234ccd314127583a1f2','SECTION_HIDDEN'=>false,'TITLE'=>do_lang_tempcode('PARAMETERS'),'HELP'=>protect_from_escaping(paragraph(do_lang_tempcode('BLOCK_HELPER_NO_PARAMETERS'),'','nothing_here')))));
				}

				continue;
			}

			if ($param_class=='advanced')
			{
				$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('_GUID'=>'3d9642b17f6be2067f4fd6e102c344bf','SECTION_HIDDEN'=>true,'TITLE'=>do_lang_tempcode('ADVANCED'))));
			}

			foreach ($parameters as $parameter)
			{
				// Work out and cleanup the description
				$matches=array();
				switch ($parameter)
				{
					case 'quick_cache':
					case 'cache':
					case 'defer':
					case 'block_id':
					case 'failsafe':
						$description=do_lang('BLOCK_PARAM_'.$parameter,get_brand_base_url());
						break;
					default:
						$description=do_lang('BLOCK_'.$block.'_PARAM_'.$parameter,get_brand_base_url());
						break;
				}
				$description=str_replace(do_lang('BLOCK_IND_STRIPPABLE_1'),'',$description);
				$description=trim(str_replace(do_lang('BLOCK_IND_ADVANCED'),'',$description));

				// Work out default value for field
				$default='';
				if (preg_match('#'.do_lang('BLOCK_IND_DEFAULT').': ["\']([^"]*)["\']#Ui',$description,$matches)!=0)
				{
					$default=$matches[1];
					$has_default=true;
					$description=preg_replace('#\s*'.do_lang('BLOCK_IND_DEFAULT').': ["\']([^"]*)["\'](?-U)\.?(?U)#Ui','',$description);
				} else $has_default=false;

				if (isset($defaults[$parameter]))
				{
					$default=$defaults[$parameter];
					$has_default=true;
				}

				// Show field
				foreach ($block_ui_renderers as $block_ui_renderer)
				{
					$test=$block_ui_renderer->render_block_ui($block,$parameter,$has_default,$default,$description);
					if (!is_null($test))
					{
						$fields->attach($test);
						continue 2;
					}
				}
				if ($block.':'.$parameter=='menu:type') // special case for menus
				{
					$matches=array();
					$dh=opendir(get_file_base().'/themes/default/templates/');
					$options=array();
					while (($file=readdir($dh))!==false)
						if (preg_match('^MENU\_([a-z]+)\.tpl$^',$file,$matches)!=0)
							$options[]=$matches[1];
					closedir($dh);
					$dh=opendir(get_custom_file_base().'/themes/default/templates_custom/');
					while (($file=readdir($dh))!==false)
						if ((preg_match('^MENU\_([a-z]+)\.tpl$^',$file,$matches)!=0) && (!file_exists(get_file_base().'/themes/default/templates/'.$file)))
							$options[]=$matches[1];
					closedir($dh);
					sort($options);
					$list=new ocp_tempcode();
					foreach ($options as $option)
						$list->attach(form_input_list_entry($option,$has_default && $option==$default));
					$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false));
				}
				/*elseif ($block.':'.$parameter=='menu:param') // special case for menus		Disabled so Sitemap nodes may be entered
				{
					$list=new ocp_tempcode();
					$rows=$GLOBALS['SITE_DB']->query_select('menu_items',array('DISTINCT i_menu'),NULL,'ORDER BY i_menu');
					foreach ($rows as $row)
					{
						$list->attach(form_input_list_entry($row['i_menu'],$has_default && $row['i_menu']==$default));
					}
					$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false));
				}*/
				elseif ($parameter=='zone') // zone list
				{
					$list=new ocp_tempcode();
					$list->attach(form_input_list_entry('_SEARCH',($default=='')));
					$list->attach(create_selection_list_zones(($default=='')?NULL:$default));
					$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false));
				}
				elseif ((($default=='') || (is_numeric(str_replace(',','',$default)))) && ((($parameter=='forum') || (($parameter=='param') && (in_array($block,array('main_forum_topics'))))) && (get_forum_type()=='ocf'))) // OCF forum list
				{
					require_code('ocf_forums');
					require_code('ocf_forums2');
					if (!addon_installed('ocf_forum')) warn_exit(do_lang_tempcode('NO_FORUM_INSTALLED'));
					$list=ocf_get_forum_tree_secure(NULL,NULL,true,explode(',',$default));
					$fields->attach(form_input_multi_list(titleify($parameter),escape_html($description),$parameter,$list));
				}
				elseif ($parameter=='font') // font choice
				{
					$fonts=array();
					$dh=opendir(get_file_base().'/data/fonts');
					while (($f=readdir($dh)))
					{
						if (substr($f,-4)=='.ttf') $fonts[]=substr($f,0,strlen($f)-4);
					}
					closedir($dh);
					$dh=opendir(get_custom_file_base().'/data_custom/fonts');
					while (($f=readdir($dh)))
					{
						if (substr($f,-4)=='.ttf') $fonts[]=substr($f,0,strlen($f)-4);
					}
					closedir($dh);
					$fonts=array_unique($fonts);
					sort($fonts);
					$list=new ocp_tempcode();
					foreach ($fonts as $font)
					{
						$list->attach(form_input_list_entry($font,$font==$default));
					}
					$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false));
				}
				elseif (preg_match('#'.do_lang('BLOCK_IND_EITHER').' (.+)#i',$description,$matches)!=0) // list
				{
					$description=preg_replace('# \('.do_lang('BLOCK_IND_EITHER').'.*\)#U','',$description); // predefined selections

					$list=new ocp_tempcode();
					$matches2=array();
					$num_matches=preg_match_all('#\'([^\']*)\'="([^"]*)"#',$matches[1],$matches2);
					if ($num_matches!=0)
					{
						for ($i=0;$i<$num_matches;$i++)
							$list->attach(form_input_list_entry($matches2[1][$i],$matches2[1][$i]==$default,$matches2[2][$i]));
					} else
					{
						$num_matches=preg_match_all('#\'([^\']*)\'#',$matches[1],$matches2);
						for ($i=0;$i<$num_matches;$i++)
							$list->attach(form_input_list_entry($matches2[1][$i],$matches2[1][$i]==$default));
					}
					$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false));
				}
				elseif (preg_match('#\('.do_lang('BLOCK_IND_HOOKTYPE').': \'([^\'/]*)/([^\'/]*)\'\)#i',$description,$matches)!=0) // hook list
				{
					$description=preg_replace('#\s*\('.do_lang('BLOCK_IND_HOOKTYPE').': \'([^\'/]*)/([^\'/]*)\'\)#i','',$description);

					$list=new ocp_tempcode();
					$hooks=find_all_hooks($matches[1],$matches[2]);
					ksort($hooks);
					if (($default=='') && ($has_default))
						$list->attach(form_input_list_entry('',true));
					foreach (array_keys($hooks) as $hook)
					{
						$list->attach(form_input_list_entry($hook,$hook==$default));
					}
					if ((($block=='main_search') && ($parameter=='limit_to')) || ($block=='side_tag_cloud'))
					{
						$fields->attach(form_input_multi_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,0));
					} else
					{
						$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false));
					}
				}
				elseif ((($default=='0') || ($default=='1') || (strpos($description,'\'0\'')!==false) || (strpos($description,'\'1\'')!==false)) && (do_lang('BLOCK_IND_WHETHER')!='') && (strpos(strtolower($description),do_lang('BLOCK_IND_WHETHER'))!==false)) // checkbox
				{
					$fields->attach(form_input_tick(titleify($parameter),escape_html($description),$parameter,$default=='1'));
				} elseif ((do_lang('BLOCK_IND_NUMERIC')!='') && (strpos($description,do_lang('BLOCK_IND_NUMERIC'))!==false)) // numeric
				{
					$fields->attach(form_input_integer(titleify($parameter),escape_html($description),$parameter,($default=='')?NULL:intval($default),false));
				} else // normal
				{
					$fields->attach(form_input_line(titleify($parameter),escape_html($description),$parameter,$default,false));
				}
			}
		}
		$post_url=find_script('block_helper').'?type=step3&field_name='.urlencode(get_param('field_name')).$keep->evaluate();
		if (get_param('utheme','')!='') $post_url.='&utheme='.get_param('utheme');
		$post_url.='&block_type='.$type_wanted;
		if (get_param('save_to_id','')!='')
		{
			$post_url.='&save_to_id='.urlencode(get_param('save_to_id'));
			$submit_name=do_lang_tempcode('SAVE');

			// Allow remove option
			$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('_GUID'=>'9fafd87384a20a8ccca561b087cbe1fc','SECTION_HIDDEN'=>false,'TITLE'=>do_lang_tempcode('ACTIONS'),'HELP'=>'')));
			$fields->attach(form_input_tick(do_lang_tempcode('REMOVE'),'','_delete',false));
		} else
		{
			$submit_name=do_lang_tempcode('USE');
		}
		$block_description=do_lang('BLOCK_'.$block.'_DESCRIPTION',NULL,NULL,NULL,NULL,false);
		if (is_null($block_description)) $block_description='';
		$block_use=do_lang('BLOCK_'.$block.'_USE',NULL,NULL,NULL,NULL,false);
		if (is_null($block_use)) $block_use='';
		if (($block_description=='') && ($block_use==''))
		{
			$text=new ocp_tempcode();
		} else
		{
			$text=do_lang_tempcode('BLOCK_HELPER_2',escape_html(cleanup_block_name($block)),escape_html($block_description),escape_html($block_use));
		}
		$hidden=form_input_hidden('block',$block);
		$content=do_template('FORM_SCREEN',array('_GUID'=>'62f8688bf0ae4223a2ba1f76fef3b0b4','TITLE'=>$title,'TARGET'=>'_self','SKIP_VALIDATION'=>true,'FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>$text,'SUBMIT_ICON'=>'buttons__proceed','SUBMIT_NAME'=>$submit_name,'HIDDEN'=>$hidden,'PREVIEW'=>true,'THEME'=>$GLOBALS['FORUM_DRIVER']->get_theme()));

		if ($fields->is_empty()) $type='step3';
	}

	if ($type=='step3') // Close off, and copy in Comcode to browser
	{
		require_javascript('javascript_posting');
		require_javascript('javascript_editing');

		$field_name=get_param('field_name');

		$bparameters='';
		$bparameters_xml='';
		$bparameters_tempcode='';
		$block=trim(either_param('block'));
		$parameters=get_block_parameters($block);
		$parameters[]='failsafe';
		$parameters[]='cache';
		$parameters[]='block_id';
		$parameters[]='quick_cache';
		if (in_array('param',$parameters))
		{
			$_parameters=array('param');
			unset($parameters[array_search('param',$parameters)]);
			$parameters=array_merge($_parameters,$parameters);
		}
		foreach ($parameters as $parameter)
		{
			$value=post_param($parameter,NULL);
			if (is_null($value))
			{
				if (post_param_integer('tick_on_form__'.$parameter,NULL)===NULL) continue; // If not on form, continue, otherwise must be 0
				$value='0';
			}
			if (($value!='') && (($parameter!='block_id') || ($value!='')) && (($parameter!='failsafe') || ($value=='1')) && (($parameter!='defer') || ($value=='1')) && (($parameter!='cache') || ($value!=block_cache_default($block))) && (($parameter!='quick_cache') || ($value=='1')))
			{
				if ($parameter=='param')
				{
					$bparameters.='="'.str_replace('"','\"',$value).'"';
				} else
				{
					$bparameters.=' '.$parameter.'="'.str_replace('"','\"',$value).'"';
				}
				$bparameters_xml='<blockParam key="'.escape_html($parameter).'" val="'.escape_html($value).'" />';
				$bparameters_tempcode.=','.$parameter.'='.str_replace(',','\,',$value);
			}
		}

		$comcode='[block'.$bparameters.']'.$block.'[/block]';
		$tempcode='{$BLOCK,block='.$block.$bparameters_tempcode.'}';
		if ($type_wanted=='template') $comcode=$tempcode; // This is what will be written in

		$comcode_semihtml=comcode_to_tempcode($comcode,NULL,false,60,NULL,NULL,true,false,false);

		$content=do_template('BLOCK_HELPER_DONE',array('_GUID'=>'575d6c8120d6001c8156560be518f296','TITLE'=>$title,'FIELD_NAME'=>$field_name,'BLOCK'=>$block,'COMCODE'=>$comcode,'COMCODE_SEMIHTML'=>$comcode_semihtml));
	}

	require_code('site');
	attach_to_screen_header('<meta name="robots" content="noindex" />'); // XHTMLXHTML

	$echo=do_template('STANDALONE_HTML_WRAP',array('_GUID'=>'ccb57d45d593eb8aabc2a5e99ea7711f','TITLE'=>do_lang_tempcode('BLOCK_HELPER'),'POPUP'=>true,'CONTENT'=>$content));
	$echo->handle_symbol_preprocessing();
	$echo->evaluate_echo();
}

