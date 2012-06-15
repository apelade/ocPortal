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
 * @package		core_language_editing
 */

/**
 * Module page class.
 */
class Module_admin_lang
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Graham';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=2;
		$info['locked']=false;
		return $info;
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array('misc'=>'TRANSLATE_CODE','content'=>'TRANSLATE_CONTENT','criticise'=>'CRITICISE_LANGUAGE_PACK');
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$langs=find_all_langs(true);
		foreach (array_keys($langs) as $lang)
		{
			deldir_contents(get_custom_file_base().'/lang_cached/'.$lang,true);
			// lang_custom purposely left
		}
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		require_javascript('javascript_translate');

		require_code('lang2');
		require_code('lang_compile');
		require_lang('lang');

		$type=get_param('type','misc');

		if ($type=='content') return $this->interface_content();
		if ($type=='_content') return $this->set_lang_content();
		if ($type=='criticise') return $this->criticise();
		if ($type=='misc') return $this->interface_code();
		if ($type=='_code') return $this->set_lang_code();
		if ($type=='_code2') return $this->set_lang_code_2(); // This is a lang string setter called from an external source. Strings may be from many different files
		if ($type=='export_po') return $this->export_po();

		return new ocp_tempcode();
	}

	/**
	 * The UI to choose a language.
	 *
	 * @param  tempcode		The title to show when choosing a language
	 * @param  boolean		Whether to also choose a language file
	 * @param  boolean		Whether the user may add a language
	 * @param  mixed			Text message to show (Tempcode or string)
	 * @param  boolean		Whether to provide an N/A choice
	 * @param  ID_TEXT		The name of the parameter for specifying language
	 * @return tempcode		The UI
	 */
	function choose_lang($title,$choose_lang_file=false,$add_lang=false,$text='',$provide_na=true,$param_name='lang')
	{
		$GLOBALS['HELPER_PANEL_PIC']='pagepics/language';
		$GLOBALS['HELPER_PANEL_TUTORIAL']='tut_intl';

		require_code('form_templates');
		$langs=new ocp_tempcode();
		if ($provide_na) $langs->attach(form_input_list_entry('',false,do_lang_tempcode('NA')));
		$langs->attach(nice_get_langs(NULL,$add_lang));
		$fields=form_input_list(do_lang_tempcode('LANGUAGE'),do_lang_tempcode('DESCRIPTION_LANGUAGE'),$param_name,$langs,NULL,false,false);

		$javascript='';

		if ($add_lang)
		{
			$fields->attach(form_input_codename(do_lang_tempcode('ALT_FIELD',do_lang_tempcode('LANGUAGE')),do_lang_tempcode('DESCRIPTION_NEW_LANG'),'lang_new','',false));
			$javascript.='standardAlternateFields(\'lang\',\'lang_new\');';
		}

		if ($choose_lang_file)
		{
			$lang_files=new ocp_tempcode();
			$lang_files->attach(form_input_list_entry('',false,do_lang_tempcode('NA_EM')));
			$lang_files->attach(nice_get_lang_files());
			$fields->attach(form_input_list(do_lang_tempcode('LANGUAGE_FILE'),do_lang_tempcode('DESCRIPTION_LANGUAGE_FILE'),'lang_file',$lang_files,NULL,true));

			$fields->attach(form_input_line(do_lang_tempcode('ALT_FIELD',do_lang('SEARCH')),'','search','',false));

			$javascript.='standardAlternateFields(\'lang_file\',\'search\');';
		}

		$post_url=get_self_url(false,false,NULL,false,true);

		return do_template('FORM_SCREEN',array('_GUID'=>'ee6bdea3661cb4736173cac818a769e5','GET'=>true,'SKIP_VALIDATION'=>true,'HIDDEN'=>'','SUBMIT_NAME'=>do_lang_tempcode('CHOOSE'),'TITLE'=>$title,'FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>$text,'JAVASCRIPT'=>$javascript));
	}

	/**
	 * Finds equivalents for a given string, in a different language, by automatic searching of codes and content.
	 *
	 * @param  string				The language string we are searching for the equivalent of
	 * @param  LANGUAGE_NAME	The language we want an equivalent in
	 * @return string				The match (or blank if no match can be found)
	 */
	function find_lang_matches($old,$lang)
	{
		// Search for pretranslated content
		$potentials=$GLOBALS['SITE_DB']->query_select('translate',array('id'),array('text_original'=>$old,'language'=>get_site_default_lang()));
		foreach ($potentials as $potential)
		{
			$test=$GLOBALS['SITE_DB']->query_value_null_ok('translate','text_original',array('id'=>$potential['id'],'language'=>$lang));
			if (!is_null($test)) return $test;
		}

		// Search code strings
		global $LANGUAGE;

		if (!array_key_exists(user_lang(),$LANGUAGE)) return '';
		$finds=array_keys($LANGUAGE[user_lang()],$old);
		foreach ($finds as $find)
		{
			if ((array_key_exists($lang,$LANGUAGE)) && (array_key_exists($find,$LANGUAGE[$lang]))) return $LANGUAGE[$lang][$find];
		}

		return '';
	}

	/**
	 * The UI to criticise a language pack.
	 *
	 * @return tempcode		The UI
	 */
	function criticise()
	{
		$GLOBALS['HELPER_PANEL_TUTORIAL']='tut_intl';
		$GLOBALS['HELPER_PANEL_PIC']='pagepics/criticise_language';

		$title=get_page_title('CRITICISE_LANGUAGE_PACK');

		$lang=get_param('crit_lang','');
		if ($lang=='') return $this->choose_lang($title,false,false,do_lang_tempcode('CHOOSE_CRITICISE_LIST_LANG_FILE'),false,'crit_lang');

		$files='';

		$missing=array();

		if (fallback_lang()==$lang) warn_exit(do_lang_tempcode('CANNOT_CRITICISE_BASE_LANG'));

		$lang_files_base=get_lang_files(fallback_lang());
		$lang_files_criticise=get_lang_files($lang);

		foreach (array_keys($lang_files_base) as $file_base)
		{
			$file=new ocp_tempcode();

			if (array_key_exists($file_base,$lang_files_criticise))
			{
				// Process this file
				$base_map=get_lang_file_map(fallback_lang(),$file_base,true);
				$criticise_map=get_lang_file_map($lang,$file_base);

				foreach ($base_map as $key=>$val)
				{
					if (array_key_exists($key,$criticise_map))
					{
						$is=$criticise_map[$key];

						// Perhaps we have a parameter mismatch?
						if (strpos($val,'{3}')!==false) $num=3;
						elseif (strpos($val,'{2}')!==false) $num=2;
						elseif (strpos($val,'{1}')!==false) $num=1;
						else $num=0;

						if (strpos($is,'{3}')!==false) $num_is=3;
						elseif (strpos($is,'{2}')!==false) $num_is=2;
						elseif (strpos($is,'{1}')!==false) $num_is=1;
						else $num_is=0;

						if ($num_is!=$num)
						{
							$crit=do_template('TRANSLATE_LANGUAGE_CRITICISM',array('_GUID'=>'424388712f07bde0a04d89b0f349a0de','CRITICISM'=>do_lang_tempcode('CRITICISM_PARAMETER_COUNT_MISMATCH',escape_html($key),escape_html($val))));
							$file->attach($crit);
						}

						unset($criticise_map[$key]);
					} elseif (trim($val)!='')
					{
						$crit=do_template('TRANSLATE_LANGUAGE_CRITICISM',array('_GUID'=>'1c06d1d7c26ed73787eef6bfd912f57a','CRITICISM'=>do_lang_tempcode('CRITICISM_MISSING_STRING',escape_html($key),escape_html($val))));
						$file->attach($crit);
					}
				}

				foreach ($criticise_map as $key=>$val)
				{
					$crit=do_template('TRANSLATE_LANGUAGE_CRITICISM',array('_GUID'=>'550018f24c0f677c50cd1bba96f24cc8','CRITICISM'=>do_lang_tempcode('CRITICISM_EXTRA_STRING',escape_html($key))));
					$file->attach($crit);
				}

			} else $missing[]=$file_base;

			if (!$file->is_empty())
			{
				$file_result=do_template('TRANSLATE_LANGUAGE_CRITICISE_FILE',array('_GUID'=>'925ae4a8dc34fed864c3072734a9abe5','COMPLAINTS'=>$file,'FILENAME'=>$file_base));
				//$files->attach($file_result);
				$files.=$file_result->evaluate();/*FUDGEFUDGE*/
			}
		}

		if (count($missing)!=0)
		{
			foreach ($missing as $missed)
			{
				$crit=do_template('TRANSLATE_LANGUAGE_CRITICISM',array('_GUID'=>'c19b1e83b5119495b52baf942e829336','CRITICISM'=>do_lang_tempcode('CRITICISM_MISSING_FILE',escape_html($missed))));
				$file->attach($crit);
			}
			$file_result=do_template('TRANSLATE_LANGUAGE_CRITICISE_FILE',array('_GUID'=>'4ffab9265ea8c5a5e99a7b9fb23d15e1','COMPLAINTS'=>$file,'FILENAME'=>do_lang_tempcode('NA_EM')));
			//$files->attach($file_result);
			$files.=$file_result->evaluate();/*FUDGEFUDGE*/
		}

		return do_template('TRANSLATE_LANGUAGE_CRITICISE_SCREEN',array('_GUID'=>'62d6f40ca69609a8fd33704a8a38fb6f','TITLE'=>$title,'FILES'=>$files));
	}

	/**
	 * The UI to translate content.
	 *
	 * @return tempcode		The UI
	 */
	function interface_content()
	{
		$title=get_page_title('TRANSLATE_CONTENT');

		if (!multi_lang()) warn_exit(do_lang_tempcode('MULTILANG_OFF'));

		$max=get_param_integer('max',100);

		$lang=choose_language($title);
		if (is_object($lang)) return $lang;

		// Fiddle around in order to find what we haven't translated. Subqueries and self joins don't work well enough across different db's
		if (!db_has_subqueries($GLOBALS['SITE_DB']->connection_read))
		{
			$_done_id_list=collapse_2d_complexity('id','text_original',$GLOBALS['SITE_DB']->query_select('translate',array('id','text_original'),array('language'=>$lang,'broken'=>0)));
			$done_id_list='';
			foreach (array_keys($_done_id_list) as $done_id)
			{
				if ($done_id_list!='') $done_id_list.=',';
				$done_id_list.=strval($done_id);
			}
			$and_clause=($done_id_list=='')?'':'AND id NOT IN ('.$done_id_list.')';
			$query='FROM '.get_table_prefix().'translate WHERE '.db_string_not_equal_to('language',$lang).' '.$and_clause.' AND '.db_string_not_equal_to('text_original','').' ORDER BY importance_level';
			$to_translate=$GLOBALS['SITE_DB']->query('SELECT * '.$query,$max/*reasonable limit*/);
		} else
		{
			$query='FROM '.get_table_prefix().'translate a LEFT JOIN '.get_table_prefix().'translate b ON a.id=b.id AND b.broken=0 AND '.db_string_equal_to('b.language',$lang).' WHERE b.id IS NULL AND '.db_string_not_equal_to('a.language',$lang).' AND '.db_string_not_equal_to('a.text_original','');
			$to_translate=$GLOBALS['SITE_DB']->query('SELECT a.* '.$query.(can_arbitrary_groupby()?' GROUP BY a.id':'').' ORDER BY a.importance_level',$max/*reasonable limit*/);
		}
		$total=$GLOBALS['SITE_DB']->query_value_null_ok_full('SELECT COUNT(*) '.$query);
		if (count($to_translate)==0) inform_exit(do_lang_tempcode('NOTHING_TO_TRANSLATE'));

		require_all_lang($lang,true);
		require_all_open_lang_files($lang);

		// Make our translation page
		require_code('lang2');
		$lines='';
		$intertrans=$this->get_intertran_conv($lang);
		$actions=make_string_tempcode('&nbsp;');
		$last_level=NULL;
		$too_many=(count($to_translate)==$max);
		$ids_to_lookup=array();
		foreach ($to_translate as $it)
		{
			$ids_to_lookup[]=$it['id'];
		}
		$names=find_lang_content_names($ids_to_lookup);
		foreach ($to_translate as $i=>$it)
		{
			if ($it['importance_level']==0) continue; // Corrupt data

			$id=$it['id'];
			$old=$it['text_original'];
			$current=$this->find_lang_matches($old,$lang);
			$priority=($last_level===$it['importance_level'])?NULL:do_lang('PRIORITY_'.strval($it['importance_level']));

			$name=$names[$id];
			if (is_null($name)) continue; // Orphaned string

			if ($intertrans!='') $actions=do_template('TRANSLATE_ACTION',array('_GUID'=>'f625cf15c9db5e5af30fc772a7f0d5ff','LANG_FROM'=>$it['language'],'LANG_TO'=>$lang,'NAME'=>'trans_'.strval($id),'OLD'=>$old));

			$line=do_template('TRANSLATE_LINE_CONTENT',array('_GUID'=>'87a0f5298ce9532839f3206cd0e06051','NAME'=>$name,'ID'=>strval($id),'OLD'=>$old,'CURRENT'=>$current,'ACTIONS'=>$actions,'PRIORITY'=>$priority));

			$lines.=$line->evaluate(); /*XHTMLXHTML*/

			$last_level=$it['importance_level'];
		}

		$url=build_url(array('page'=>'_SELF','type'=>'_content','lang'=>$lang),'_SELF');

		require_code('lang2');

		return do_template('TRANSLATE_SCREEN_CONTENT_SCREEN',array('_GUID'=>'af732c5e595816db1c6f025c4b8fa6a2','MAX'=>integer_format($max),'TOTAL'=>integer_format($total-$max),'LANG_ORIGINAL_NAME'=>get_site_default_lang(),'LANG_NICE_ORIGINAL_NAME'=>lookup_language_full_name(get_site_default_lang()),'LANG_NICE_NAME'=>lookup_language_full_name($lang),'TOO_MANY'=>$too_many,'INTERTRANS'=>$intertrans,'LANG'=>$lang,'LINES'=>$lines,'TITLE'=>$title,'URL'=>$url));
	}

	/**
	 * The actualiser to translate content.
	 *
	 * @return tempcode		The UI
	 */
	function set_lang_content()
	{
		$title=get_page_title('TRANSLATE_CONTENT');

		$lang=choose_language($title);
		if (is_object($lang)) return $lang;

		foreach ($_POST as $key=>$val)
		{
			if (!is_string($val)) continue;
			if (substr($key,0,6)!='trans_') continue;

			$lang_id=intval(substr($key,6));

			if (get_magic_quotes_gpc()) $val=stripslashes($val);

			if ($val!='')
			{
				$GLOBALS['SITE_DB']->query_delete('translate',array('language'=>$lang,'id'=>$lang_id),'',1);
				$importance_level=$GLOBALS['SITE_DB']->query_value_null_ok('translate','importance_level',array('id'=>$lang_id));
				if (!is_null($importance_level))
					$GLOBALS['SITE_DB']->query_insert('translate',array('id'=>$lang_id,'source_user'=>get_member(),'language'=>$lang,'importance_level'=>$importance_level,'text_original'=>$val,'text_parsed'=>'','broken'=>0));
			}
		}

		log_it('TRANSLATE_CONTENT');

		require_code('view_modes');
		erase_tempcode_cache();
		persistant_cache_empty();

		if (get_param_integer('contextual',0)==1)
		{
			return inform_screen($title,do_lang_tempcode('SUCCESS'));
		}

		// Show it worked / Refresh
		$url=post_param('redirect',NULL);
		if (is_null($url))
		{
			$_url=build_url(array('page'=>'_SELF','type'=>'content'),'_SELF');
			$url=$_url->evaluate();
		}
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * The actualiser to create a .po TAR.
	 *
	 * @return tempcode		The UI
	 */
	function export_po()
	{
		$lang=filter_naughty(get_param('id'));

		// Send header
		header('Content-Type: application/octet-stream'.'; authoritative=true;');
		if (strstr(ocp_srv('HTTP_USER_AGENT'),'MSIE')!==false)
			header('Content-Disposition: filename="ocportal-'.$lang.'.tar"');
		else
			header('Content-Disposition: attachment; filename="ocportal-'.$lang.'.tar"');

		require_code('tar');
		require_code('lang_compile');
		require_code('character_sets');

		$tempfile=ocp_tempnam('po');

		$tar=tar_open($tempfile,'wb');

		$dh=@opendir(get_custom_file_base().'/lang_custom/'.$lang);
		if ($dh!==false)
		{
			$charset=do_lang('charset',NULL,NULL,NULL,$lang);
			$english_charset=do_lang('charset',NULL,NULL,NULL,fallback_lang());

			while (($f=readdir($dh))!==false)
			{
				if (substr($f,-4)=='.ini') // don't export .po, esp as could overwrite .ini file
				{
					$path=get_custom_file_base().'/lang_custom/'.$lang.'/'.$f;
					$entries=array();
					_get_lang_file_map($path,$entries,false,false);
					$mtime=filemtime($path);
					$data='
msgid ""
msgstr ""
"Project-Id-Version: ocportal\n"
"PO-Revision-Date: '.gmdate('Y-m-d H:i',$mtime).'+0000\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
"Language-Team: FULL NAME <EMAIL@ADDRESS>\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"X-ocPortal-Export-Date: '.gmdate('Y-m-d H:i',$mtime).'+0000\n"
"X-Generator: ocPortal ('.ocp_version_full().')\n"

';
					$entries2=array();
					$en_seen_before=array();
					foreach ($entries as $key=>$val)
					{
						$english=do_lang($key,NULL,NULL,NULL,fallback_lang(),false);
						if (is_null($english)) continue;
						if ($english=='') continue;
						$val=convert_to_internal_encoding($val,$charset,'utf-8');
						$val=str_replace(chr(10),'\n',$val);
						$english=convert_to_internal_encoding($english,$english_charset,'utf-8');
						$english=str_replace(chr(10),'\n',$english);

						$seen_before=false;
						if (isset($en_seen_before[$val]))
						{
							$seen_before=true;
							foreach ($entries2 as $_key=>$_val)
							{
								if ($entries2[$_key][2]==$val)
									$entries2[$_key][1]=true;
							}
						}
						$entries2[$key]=array($val,$seen_before,$english);
						$en_seen_before[$val]=1;
					}
					require_code('support2');
					foreach ($entries2 as $key=>$_val)
					{
						list($val,$seen_before,$english)=$_val;
						$data.='#: [strings]'.$key.chr(10);
						if ($seen_before) $data.='msgctxt "[strings]'.$key.'"'.chr(10);
						$wrapped=preg_replace('#"\n"$#','',ocp_mb_chunk_split(str_replace('"','\"',$english),76,'"'.chr(10).'"'));
						if (strpos($wrapped,chr(10))!==false)
						{
							$data.='msgid ""'.chr(10).'"'.$wrapped.'"'.chr(10);
						} else
						{
							$data.='msgid "'.$wrapped.'"'.chr(10);
						}
						$wrapped=preg_replace('#"\n"$#','',ocp_mb_chunk_split(str_replace('"','\"',$val),76,'"'.chr(10).'"'));
						if (strpos($wrapped,chr(10))!==false)
						{
							$data.='msgstr ""'.chr(10).'"'.$wrapped.'"'.chr(10);
						} else
						{
							$data.='msgstr "'.$wrapped.'"'.chr(10);
						}
						$data.=chr(10);
					}
					tar_add_file($tar,basename($f,'.ini').'/'.basename($f,'.ini').'-'.strtolower($lang).'.po',$data,0666,$mtime);
				}
			}
		}
		tar_close($tar);
		readfile($tempfile);
		@unlink($tempfile);

		$GLOBALS['SCREEN_TEMPLATE_CALLED']='';
		exit();

		return new ocp_tempcode(); // For code quality checker
	}

	/**
	 * The UI to translate code.
	 *
	 * @return tempcode		The UI
	 */
	function interface_code()
	{
		$lang=filter_naughty_harsh(get_param('lang',''));
		$lang_new=get_param('lang_new',$lang);
		if ($lang_new!='')
		{
			if (strlen($lang_new)>5)
			{
				warn_exit(do_lang_tempcode('INVALID_LANG_CODE'));
			}
			$lang=$lang_new;
		}
		if ($lang=='')
		{
			$title=get_page_title('TRANSLATE_CODE');
			$GLOBALS['HELPER_PANEL_TEXT']=comcode_lang_string('DOC_FIND_LANG_STRING_TIP');
			return $this->choose_lang($title,true,true,do_lang_tempcode('CHOOSE_EDIT_LIST_LANG_FILE'));
		}

		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('CHOOSE'))));
		breadcrumb_set_self(do_lang_tempcode('TRANSLATE_CODE'));

		$base_lang=fallback_lang();

		$map_a=get_file_base().'/lang/langs.ini';
		$map_b=get_custom_file_base().'/lang_custom/langs.ini';

		$search=get_param('search','',true);
		if ($search!='')
		{
			$title=get_page_title('TRANSLATE_CODE');

			require_code('form_templates');
			$fields=new ocp_tempcode();
			global $LANGUAGE;
			foreach ($LANGUAGE[user_lang()] as $key=>$value)
			{
				if (strpos(strtolower($value),strtolower($search))!==false)
				{
					$fields->attach(form_input_text($key,'','l_'.$key,str_replace('\n',chr(10),$value),false));
				}
			}
			if ($fields->is_empty()) inform_exit(do_lang_tempcode('NO_ENTRIES'));
			$post_url=build_url(array('page'=>'_SELF','type'=>'_code2'),'_SELF');
			$hidden=form_input_hidden('redirect',get_self_url(true));
			$hidden=form_input_hidden('lang',$lang);
			return do_template('FORM_SCREEN',array('_GUID'=>'2d7356fd2c4497ceb19450e65331c9c5','TITLE'=>$title,'HIDDEN'=>$hidden,'FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>'','SUBMIT_NAME'=>do_lang('TRANSLATE_CODE')));
		}
		$lang_file=get_param('lang_file');
		if (!file_exists($map_b)) $map_b=$map_a;
		$map=better_parse_ini_file($map_b);
		$title=get_page_title('_TRANSLATE_CODE',true,array(escape_html($lang_file),escape_html(array_key_exists($lang,$map)?$map[$lang]:$lang)));

		// Upgrade to custom if not there yet (or maybe we are creating a new lang - same difference)
		$custom_dir=get_custom_file_base().'/lang_custom/'.$lang;
		if (!file_exists($custom_dir))
		{
			require_code('abstract_file_manager');
			force_have_afm_details();

			afm_make_directory('lang_custom/'.$lang,true);

			$cached_dir=get_custom_file_base().'/lang_cached/'.$lang;
			if (!file_exists($cached_dir))
			{
				afm_make_directory('lang_cached/'.$lang,true);
			}

			// Make comcode page dirs
			$zones=find_all_zones();
			foreach ($zones as $zone)
			{
				$_special_dir=get_custom_file_base().'/'.$zone.'/pages/comcode_custom/'.$lang;
				if (!file_exists($_special_dir))
				{
					afm_make_directory($zone.(($zone=='')?'':'/').'pages/comcode_custom/'.$lang,true);
				}
				$_special_dir=get_custom_file_base().'/'.$zone.'/pages/html_custom/'.$lang;
				if (!file_exists($_special_dir))
				{
					afm_make_directory($zone.(($zone=='')?'':'/').'pages/html_custom/'.$lang,true);
				}
			}

			// Make templates_cached dirs
			require_code('themes2');
			$themes=find_all_themes();
			foreach (array_keys($themes) as $theme)
			{
				$_special_dir=get_custom_file_base().'/themes/'.$theme.'/templates_cached/'.$lang;
				if (!file_exists($_special_dir))
				{
					afm_make_directory('themes/'.$theme.'/templates_cached/'.$lang,true);
				}
			}
		}

		// Get some stuff
		$for_lang=get_lang_file_map($lang,$lang_file);
		$for_base_lang=get_lang_file_map($base_lang,$lang_file,true);
		$descriptions=get_lang_file_descriptions($base_lang,$lang_file);

		// Make our translation page
		$lines='';
		$intertrans=$this->get_intertran_conv($lang);
		$actions=new ocp_tempcode();
		$next=0;
		$trans_lot='';
		$delimit=chr(10).'=-=-=-=-=-=-=-=-'.chr(10);
		foreach ($for_base_lang as $name=>$old)
		{
			if (array_key_exists($name,$for_lang))
			{
				$current=$for_lang[$name];
			} else
			{
				$current='';//$this->find_lang_matches($old,$lang); Too slow / useless for code translation
			}
			if (($current=='') && (strtolower($name)!=$name))
			{
				$trans_lot.=str_replace('\n',chr(10),str_replace(array('{','}'),array('(((',')))'),$old)).$delimit;
			}
		}

		$translated_stuff=array();
		if (($trans_lot!='') && ($intertrans!=''))
		{
			$result=http_download_file('http://translate.google.com/translate_t',NULL,false,false,'ocPortal',array('text'=>$trans_lot,'langpair'=>'en|'.$intertrans));
			if (!is_null($result))
			{
				require_code('character_sets');

				$result=convert_to_internal_encoding($result);

				$matches=array();
				if (preg_match('#<div id=result_box dir="ltr">(.*)</div>#Us',convert_to_internal_encoding($result),$matches)!=0)
				{
					$result2=$matches[1];
					$result2=@html_entity_decode($result2,ENT_QUOTES,get_charset());
					$result2=preg_replace('#\s?<br>\s?#',chr(10),$result2);
					$result2=str_replace('> ','>',str_replace(' <',' <',str_replace('</ ','</',str_replace(array('(((',')))'),array('{','}'),$result2))));
					$translated_stuff=explode(trim($delimit),$result2.chr(10));
				}
			}
		}
		foreach ($for_base_lang+$for_lang as $name=>$old)
		{
			if (array_key_exists($name,$for_lang))
			{
				$current=$for_lang[$name];
			} else
			{
				$current='';//$this->find_lang_matches($old,$lang); Too slow / useless for code translation
			}
			$description=array_key_exists($name,$descriptions)?$descriptions[$name]:'';
			if (($current=='') && (strtolower($name)!=$name) && (array_key_exists($next,$translated_stuff)))
			{
				$_current='';
				$translate_auto=trim($translated_stuff[$next]);
				$next++;
			} else
			{
				$_current=str_replace('\n',chr(10),$current);
				$translate_auto=NULL;
			}
			if ($_current=='') $_current=str_replace('\n',chr(10),$old);

			if (($intertrans!='') && (get_value('google_translate_api_key')!==NULL))
			{
				$actions=do_template('TRANSLATE_ACTION',array('_GUID'=>'9e9a68cb2c1a1e23a901b84c9af2280b','LANG_FROM'=>get_site_default_lang(),'LANG_TO'=>$lang,'NAME'=>'trans_'.$name,'OLD'=>$_current));
			}

			$temp=do_template('TRANSLATE_LINE',array('_GUID'=>'9cb331f5852ee043e6ad30b45aedc43b','TRANSLATE_AUTO'=>$translate_auto,'DESCRIPTION'=>$description,'NAME'=>$name,'OLD'=>str_replace('\n',chr(10),$old),'CURRENT'=>$_current,'ACTIONS'=>$actions));
			$lines.=$temp->evaluate();
		}

		$url=build_url(array('page'=>'_SELF','type'=>'_code','lang_file'=>$lang_file,'lang'=>$lang),'_SELF');

		return do_template('TRANSLATE_SCREEN',array('_GUID'=>'b3429f8bd0b4eb79c33709ca43e3207c','PAGE'=>$lang_file,'INTERTRANS'=>(get_value('google_translate_api_key')!==NULL)?$intertrans:'','LANG'=>$lang,'LINES'=>$lines,'TITLE'=>$title,'URL'=>$url));
	}

	/**
	 * Convert a standard language code to an intertran code.
	 *
	 * @param  LANGUAGE_NAME	The code to convert
	 * @return string				The converted code (or blank if none can be found)
	 */
	function get_intertran_conv($in)
	{
		if ($in==fallback_lang()) return '';
		return strtolower($in); // Actually google now

		/*$conv=array(
					"BG"=>"bul",
					"CS"=>"che",
					"DA"=>"dan",
					"NL"=>"dut",
					"ES"=>"spa",
					"FI"=>"fin",
					"FR"=>"fre",
					"DE"=>"ger",
					"EL"=>"grk",
					"HU"=>"hun",
					"IS"=>"ice",
					"IT"=>"ita",
					"JA"=>"jpn",
					"NO"=>"nor",
					"TL"=>"tag",
					"PL"=>"pol",
					"PT"=>"poe",
					"RO"=>"rom",
					"RU"=>"rus",
					"SH"=>"sel",
					"SL"=>"slo",
					"SV"=>"swe",
					"CY"=>"wel",
					"TR"=>"tur"
		);
		if (array_key_exists($in,$conv)) return $conv[$in];
		return '';*/
	}

	/**
	 * The actualiser to translate code (called from this module).
	 *
	 * @return tempcode		The UI
	 */
	function set_lang_code()
	{
		decache('side_language');
		require_code('view_modes');
		erase_tempcode_cache();

		$lang=get_param('lang');
		$lang_file=get_param('lang_file');

		$for_base_lang=get_lang_file_map(fallback_lang(),$lang_file,true);
		$for_base_lang_2=get_lang_file_map($lang,$lang_file,false);
		$descriptions=get_lang_file_descriptions(fallback_lang(),$lang_file);

		// Just to make sure the posted data is at least partially there, before we wipe out the old file
		foreach (array_unique(array_merge(array_keys($for_base_lang),array_keys($for_base_lang_2))) as $key)
		{
			$val=post_param($key);
		}

		$path=get_custom_file_base().'/lang_custom/'.filter_naughty($lang).'/'.filter_naughty($lang_file).'.ini';
		$path_backup=$path.'.'.strval(time());
		if (file_exists($path))
		{
			@copy($path,$path_backup) OR intelligent_write_error($path_backup);
			sync_file($path_backup);
		}
		$myfile=@fopen($path,'wt');
		if ($myfile===false) intelligent_write_error($path);
		fwrite($myfile,"[descriptions]\n");
		foreach ($descriptions as $key=>$description)
		{
			fwrite($myfile,$key.'='.$description."\n");
		}
		fwrite($myfile,"\n"); // Weird bug with IIS 'wt' writing needs this to be on a separate line
		fwrite($myfile,"[strings]\n");
		foreach (array_unique(array_merge(array_keys($for_base_lang),array_keys($for_base_lang_2))) as $key)
		{
			$val=post_param($key);
			if (($val!='') && ((!array_key_exists($key,$for_base_lang)) || (str_replace(chr(10),'\n',$val)!=$for_base_lang[$key])))
			{
				if (fwrite($myfile,$key.'='.str_replace(chr(10),'\n',$val)."\n")==0) warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
			}
		}
		fclose($myfile);
		fix_permissions($path);
		sync_file($path);
		$path_backup2=$path.'.latest_in_ocp_edit';
		@copy($path,$path_backup2) OR intelligent_write_error($path_backup2);
		sync_file($path_backup2);

		$title=get_page_title('TRANSLATE_CODE');

		log_it('TRANSLATE_CODE');

		require_code('view_modes');
		erase_cached_language();
		erase_cached_templates();

		// Show it worked / Refresh
		$url=build_url(array('page'=>'_SELF','type'=>'misc'),'_SELF');
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * The actualiser to translate code (called externally, and may operate on many lang files).
	 *
	 * @return tempcode		The UI
	 */
	function set_lang_code_2()
	{
		$lang=post_param('lang');

		$lang_files=get_lang_files(fallback_lang());

		foreach (array_keys($lang_files) as $lang_file)
		{
			$for_base_lang=get_lang_file_map(fallback_lang(),$lang_file,true);
			$for_base_lang_2=get_lang_file_map($lang,$lang_file,false);
			$descriptions=get_lang_file_descriptions(fallback_lang(),$lang_file);

			$out='';

			foreach ($for_base_lang_2+$for_base_lang as $key=>$now_val)
			{
				$val=post_param('l_'.$key,array_key_exists($key,$for_base_lang_2)?$for_base_lang_2[$key]:$now_val);
				if ((str_replace(chr(10),'\n',$val)!=$now_val) || (!array_key_exists($key,$for_base_lang)) || ($for_base_lang[$key]!=$val) || (!file_exists(get_file_base().'/lang/'.fallback_lang().'/'.$lang_file.'.ini'))) // if it's changed from default ocPortal, or not in default ocPortal, or was already changed in language file, or whole file is not in default ocPortal
					$out.=$key.'='.str_replace(chr(10),'\n',$val)."\n";
			}

			if ($out!='')
			{
				$path=get_custom_file_base().'/lang_custom/'.filter_naughty($lang).'/'.filter_naughty($lang_file).'.ini';
				$path_backup=$path.'.'.strval(time());
				if (file_exists($path))
				{
					@copy($path,$path_backup) OR intelligent_write_error($path_backup);
					sync_file($path_backup);
				}
				$myfile=@fopen($path,'wt');
				if ($myfile===false) intelligent_write_error($path);
				fwrite($myfile,"[descriptions]\n");
				foreach ($descriptions as $key=>$description)
				{
					if (fwrite($myfile,$key.'='.$description."\n")==0) warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
				}
				fwrite($myfile,"\n[strings]\n");
				fwrite($myfile,$out);
				fclose($myfile);
				fix_permissions($path);
				sync_file($path);
				$path_backup2=$path.'.latest_in_ocp_edit';
				@copy($path,$path_backup2) OR intelligent_write_error($path_backup2);
				sync_file($path_backup2);
			}
		}


		$title=get_page_title('TRANSLATE_CODE');

		log_it('TRANSLATE_CODE');

		require_code('view_modes');
		erase_cached_language();
		erase_cached_templates();

		// Show it worked / Refresh
		$url=post_param('redirect','');
		if ($url=='')
		{
			return inform_screen($title,do_lang_tempcode('SUCCESS'));
		}
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
	}

}


