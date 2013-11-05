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
 * @package		downloads
 */

/**
 * Module page class.
 */
class Module_downloads
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
		$info['version']=7;
		$info['update_require_upgrade']=1;
		$info['locked']=false;
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_table_if_exists('download_categories');
		$GLOBALS['SITE_DB']->drop_table_if_exists('download_downloads');
		$GLOBALS['SITE_DB']->drop_table_if_exists('download_logging');
		$GLOBALS['SITE_DB']->drop_table_if_exists('download_licences');

		delete_privilege('download');

		$GLOBALS['SITE_DB']->query_delete('group_category_access',array('module_the_name'=>'downloads'));

		$GLOBALS['SITE_DB']->query_delete('trackbacks',array('trackback_for_type'=>'downloads'));

		delete_value('download_bandwidth');
		delete_value('archive_size');
		delete_value('num_archive_downloads');
		delete_value('num_downloads_downloaded');

		require_code('files');
		deldir_contents(get_custom_file_base().'/uploads/downloads',true);
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		if (is_null($upgrade_from))
		{
			require_lang('downloads');

			$GLOBALS['SITE_DB']->create_table('download_categories',array(
				'id'=>'*AUTO',
				'category'=>'SHORT_TRANS',
				'parent_id'=>'?AUTO_LINK',
				'add_date'=>'TIME',
				'notes'=>'LONG_TEXT',
				'description'=>'LONG_TRANS',	// Comcode
				'rep_image'=>'URLPATH'
			));

			$lang_key=lang_code_to_default_content('DOWNLOADS_HOME');
			$id=$GLOBALS['SITE_DB']->query_insert('download_categories',array('rep_image'=>'','parent_id'=>NULL,'add_date'=>time(),'notes'=>'','description'=>insert_lang_comcode('',3),'category'=>$lang_key),true);
			$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);
			$GLOBALS['SITE_DB']->query_delete('group_category_access',array('module_the_name'=>'downloads'));
			foreach (array_keys($groups) as $group_id)
				$GLOBALS['SITE_DB']->query_insert('group_category_access',array('module_the_name'=>'downloads','category_name'=>strval($id),'group_id'=>$group_id));

			$GLOBALS['SITE_DB']->create_index('download_categories','child_find',array('parent_id'));

			$GLOBALS['SITE_DB']->create_table('download_downloads',array(
				'id'=>'*AUTO',
				'category_id'=>'AUTO_LINK',
				'name'=>'SHORT_TRANS',
				'url'=>'URLPATH',
				'description'=>'LONG_TRANS',	// Comcode
				'author'=>'ID_TEXT',
				'additional_details'=>'LONG_TRANS',	// Comcode
				'num_downloads'=>'INTEGER',
				'out_mode_id'=>'?AUTO_LINK',
				'add_date'=>'TIME',
				'edit_date'=>'?TIME',
				'validated'=>'BINARY',
				'default_pic'=>'INTEGER',
				'file_size'=>'?INTEGER',
				'allow_rating'=>'BINARY',
				'allow_comments'=>'SHORT_INTEGER',
				'allow_trackbacks'=>'BINARY',
				'notes'=>'LONG_TEXT',
				'download_views'=>'INTEGER',
				'download_cost'=>'INTEGER',
				'download_submitter_gets_points'=>'BINARY',
				'submitter'=>'MEMBER',
				'original_filename'=>'SHORT_TEXT',
				'rep_image'=>'URLPATH',
				'download_licence'=>'?AUTO_LINK',
				'download_data_mash'=>'LONG_TEXT'
			));
			$GLOBALS['SITE_DB']->create_index('download_downloads','download_views',array('download_views'));
			$GLOBALS['SITE_DB']->create_index('download_downloads','category_list',array('category_id'));
			$GLOBALS['SITE_DB']->create_index('download_downloads','recent_downloads',array('add_date'));
			$GLOBALS['SITE_DB']->create_index('download_downloads','top_downloads',array('num_downloads'));
			$GLOBALS['SITE_DB']->create_index('download_downloads','downloadauthor',array('author'));
			$GLOBALS['SITE_DB']->create_index('download_downloads','dds',array('submitter'));
			$GLOBALS['SITE_DB']->create_index('download_downloads','ddl',array('download_licence')); // For when deleting a download license
			$GLOBALS['SITE_DB']->create_index('download_downloads','dvalidated',array('validated'));

			$GLOBALS['SITE_DB']->create_index('download_downloads','ftjoin_dname',array('name'));
			$GLOBALS['SITE_DB']->create_index('download_downloads','ftjoin_ddescrip',array('description'));
			$GLOBALS['SITE_DB']->create_index('download_downloads','ftjoin_dadditional',array('additional_details'));
			$GLOBALS['SITE_DB']->create_index('download_categories','ftjoin_dccat',array('category'));
			$GLOBALS['SITE_DB']->create_index('download_categories','ftjoin_dcdescrip',array('description'));

			$GLOBALS['SITE_DB']->create_index('download_downloads','#download_data_mash',array('download_data_mash'));
			$GLOBALS['SITE_DB']->create_index('download_downloads','#original_filename',array('original_filename'));

			$GLOBALS['SITE_DB']->create_table('download_logging',array(
				'id'=>'*AUTO_LINK',
				'member_id'=>'*MEMBER',
				'ip'=>'IP',
				'date_and_time'=>'TIME'
			));
			$GLOBALS['SITE_DB']->create_index('download_logging','calculate_bandwidth',array('date_and_time'));

			$GLOBALS['SITE_DB']->create_table('download_licences',array(
				'id'=>'*AUTO',
				'l_title'=>'SHORT_TEXT',
				'l_text'=>'LONG_TEXT'
			));
		}

		if ((is_null($upgrade_from)) || ($upgrade_from<7))
		{
			add_privilege('_SECTION_DOWNLOADS','download',true);
		}

		if ((!is_null($upgrade_from)) && ($upgrade_from<7))
		{
			$GLOBALS['SITE_DB']->alter_table_field('download_downloads','comments','LONG_TRANS','additional_details');

			$GLOBALS['SITE_DB']->alter_table_field('download_logging','the_user','*MEMBER','member_id');

			delete_config_option('show_dload_trees');
		}
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @param  boolean	Whether to check permissions.
	 * @param  ?MEMBER	The member to check permissions as (NULL: current user).
	 * @param  boolean	Whether to allow cross links to other modules (identifiable via a full-pagelink rather than a screen-name).
	 * @return ?array		A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (NULL: disabled).
	 */
	function get_entry_points($check_perms=true,$member_id=NULL,$support_crosslinks=true)
	{
		return array('misc'=>'DOWNLOADS_HOME');
	}

	var $title;
	var $category_id;
	var $category;
	var $id;
	var $myrow;
	var $name;
	var $root;
	var $images_details;
	var $num_images;

	/**
	 * Standard modular pre-run function, so we know meta-data for <head> before we start streaming output.
	 *
	 * @return ?tempcode		Tempcode indicating some kind of exceptional output (NULL: none).
	 */
	function pre_run()
	{
		$type=get_param('type','misc');

		require_code('downloads');
		require_lang('downloads');

		set_feed_url('?mode=downloads&filter=');

		if ($type=='index')
		{
			set_feed_url('?mode=downloads&filter=');
		}

		if ($type=='misc')
		{
			$category_id=get_param_integer('id',db_get_first_id());

			$root=get_param_integer('keep_download_root',db_get_first_id());

			set_feed_url('?mode=downloads&filter='.strval($category_id));

			// Get details
			$rows=$GLOBALS['SITE_DB']->query_select('download_categories',array('*'),array('id'=>$category_id),'',1);
			if (!array_key_exists(0,$rows))
			{
				return warn_screen(get_screen_title('DOWNLOAD_CATEGORY'),do_lang_tempcode('MISSING_RESOURCE'));
			}
			$category=$rows[0];

			// Check access
			if (!has_category_access(get_member(),'downloads',strval($category_id))) access_denied('CATEGORY_ACCESS');

			// Title
			$title_to_use=get_translated_text($category['category']);
			if ((get_value('no_awards_in_titles')!=='1') && (addon_installed('awards')))
			{
				require_code('awards');
				$awards=find_awards_for('download_category',strval($category_id));
			} else $awards=array();
			$this->title=get_screen_title('_DOWNLOAD_CATEGORY',true,array(make_fractionable_editable('download_category',$category_id,$title_to_use)),NULL,$awards);

			// Breadcrumbs
			$breadcrumbs=download_breadcrumbs($category_id,$root,true,get_zone_name(),true);
			if (!$breadcrumbs->is_empty())
			{
				$breadcrumbs->attach(do_template('BREADCRUMB_SEPARATOR'));
			}
			if (has_privilege(get_member(),'open_virtual_roots'))
			{
				$url=get_self_url(false,false,array('keep_download_root'=>$category_id));
				$breadcrumbs->attach(hyperlink($url,escape_html($title_to_use),false,false,do_lang_tempcode('VIRTUAL_ROOT')));
			} else $breadcrumbs->attach($title_to_use);
			breadcrumb_add_segment($breadcrumbs);

			// Meta data
			seo_meta_load_for('downloads_category',strval($category_id),$title_to_use);
			set_extra_request_metadata(array(
				'created'=>date('Y-m-d',$category['add_date']),
				'creator'=>'',
				'publisher'=>'', // blank means same as creator
				'modified'=>'',
				'type'=>'Download category',
				'title'=>$title_to_use,
				'identifier'=>'_SEARCH:downloads:misc:'.strval($category_id),
				'description'=>get_translated_text($category['description']),
				//'category'=>???,
			));
			$main_rep_image=$category['rep_image'];
			if ($main_rep_image!='')
			{
				set_extra_request_metadata(array(
					'image'=>(url_is_local($main_rep_image)?(get_custom_base_url().'/'):'').$main_rep_image,
				));
			}

			$this->category_id=$category_id;
			$this->category=$category;
			$this->root=$root;
		}

		if ($type=='entry')
		{
			$id=get_param_integer('id');

			$root=get_param_integer('keep_download_root',db_get_first_id(),true);

			if (addon_installed('content_privacy'))
			{
				require_code('content_privacy');
				check_privacy('download',strval($id));
			}

			// Load from database
			$rows=$GLOBALS['SITE_DB']->query_select('download_downloads',array('*'),array('id'=>$id),'',1);
			if (!array_key_exists(0,$rows))
			{
				return warn_screen(get_screen_title('SECTION_DOWNLOADS'),do_lang_tempcode('MISSING_RESOURCE'));
			}
			$myrow=$rows[0];
			set_feed_url('?mode=downloads&filter='.strval($myrow['category_id']));

			// Permissions
			if (!has_category_access(get_member(),'downloads',strval($myrow['category_id']))) access_denied('CATEGORY_ACCESS');

			$name=get_translated_text($myrow['name']);

			// Title
			$title_to_use_tempcode=do_lang_tempcode('DOWNLOAD_TITLE',make_fractionable_editable('download',$id,$name));
			$title_to_use=do_lang('DOWNLOAD_TITLE',$name);
			if ((get_value('no_awards_in_titles')!=='1') && (addon_installed('awards')))
			{
				require_code('awards');
				$awards=find_awards_for('download',strval($id));
			} else $awards=array();
			$this->title=get_screen_title($title_to_use_tempcode,false,NULL,NULL,$awards);

			// SEO
			seo_meta_load_for('downloads_download',strval($id),$title_to_use);

			// Breadcrumbs
			$breadcrumbs=download_breadcrumbs($myrow['category_id'],$root,false,get_zone_name());
			breadcrumb_add_segment($breadcrumbs,protect_from_escaping('<span>'.$title_to_use_tempcode->evaluate().'</span>'));

			// Images in associated gallery
			$images_details=new ocp_tempcode();
			$image_url='';
			$counter=0;
			if (addon_installed('galleries'))
			{
				// Images
				require_lang('galleries');
				$cat='download_'.strval($id);
				$map=array('cat'=>$cat);
				if ((!has_privilege(get_member(),'see_unvalidated')) && (addon_installed('unvalidated'))) $map['validated']=1;
				$rows=$GLOBALS['SITE_DB']->query_select('images',array('*'),$map,'ORDER BY id',200/*Stop sillyness, could be a DOS attack*/);
				$div=2;
				$_out=new ocp_tempcode();
				$_row=new ocp_tempcode();
				require_code('images');
				while (array_key_exists($counter,$rows))
				{
					$row=$rows[$counter];

					$view_url=$row['url'];
					if ($image_url=='') $image_url=$row['url'];
					if (url_is_local($view_url)) $view_url=get_custom_base_url().'/'.$view_url;
					$thumb_url=ensure_thumbnail($row['url'],$row['thumb_url'],'galleries','images',$row['id']);
					$image_description=get_translated_tempcode($row['description']);
					$thumb=do_image_thumb($thumb_url,'');
					if ((has_actual_page_access(NULL,'cms_galleries',NULL,NULL)) && (has_edit_permission('mid',get_member(),$row['submitter'],'cms_galleries',array('galleries','download_'.strval($id)))))
					{
						$iedit_url=build_url(array('page'=>'cms_galleries','type'=>'_ed','id'=>$row['id']),get_module_zone('cms_galleries'));
					} else $iedit_url=new ocp_tempcode();
					$_content=do_template('DOWNLOAD_SCREEN_IMAGE',array('_GUID'=>'fba0e309aa0ae04891e32c65a625b177','ID'=>strval($row['id']),'VIEW_URL'=>$view_url,'EDIT_URL'=>$iedit_url,'THUMB'=>$thumb,'DESCRIPTION'=>$image_description));

					$_row->attach(do_template('DOWNLOAD_GALLERY_IMAGE_CELL',array('_GUID'=>'8400a832dbed64bb63f264eb3a038895','CONTENT'=>$_content)));

					if (($counter%$div==1) && ($counter!=0))
					{
						$_out->attach(do_template('DOWNLOAD_GALLERY_ROW',array('_GUID'=>'205c4f5387e98c534d5be1bdfcccdd7d','CELLS'=>$_row)));
						$_row=new ocp_tempcode();
					}

					$counter++;
				}
				if (!$_row->is_empty())
					$_out->attach(do_template('DOWNLOAD_GALLERY_ROW',array('_GUID'=>'e9667ca2545ac72f85a873f236cbbd6f','CELLS'=>$_row)));
				$images_details=$_out;
			}

			// Meta data
			set_extra_request_metadata(array(
				'created'=>date('Y-m-d',$myrow['add_date']),
				'creator'=>$myrow['author'],
				'publisher'=>$GLOBALS['FORUM_DRIVER']->get_username($myrow['submitter']),
				'modified'=>is_null($myrow['edit_date'])?'':date('Y-m-d',$myrow['edit_date']),
				'type'=>'Download',
				'title'=>get_translated_text($myrow['name']),
				'identifier'=>'_SEARCH:downloads:view:'.strval($id),
				'description'=>get_translated_text($myrow['description']),
				'image'=>$image_url,
				//'category'=>???,
			));

			$this->id=$id;
			$this->myrow=$myrow;
			$this->name=$name;
			$this->images_details=$images_details;
			$this->num_images=$counter;
			$this->root=$root;
		}

		if ($type=='index')
		{
			$this->title=get_screen_title('SECTION_DOWNLOADS');
		}

		return NULL;
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		require_code('feedback');
		require_css('downloads');
		require_code('files');

		$type=get_param('type','misc');

		// Decide what to do
		if ($type=='misc') return $this->view_category_screen();
		if ($type=='index') return $this->show_all_downloads();
		if ($type=='entry') return $this->view_download_screen();

		return new ocp_tempcode();
	}

	/**
	 * The UI to view a download category.
	 *
	 * @return tempcode		The UI
	 */
	function view_category_screen()
	{
		$category_id=$this->category_id;
		$root=$this->root;
		$category=$this->category;

		$cat_sort=get_param('cat_sort','t1.text_original ASC');
		$sort=get_param('sort',get_option('downloads_default_sort_order'));

		$description=get_translated_tempcode($category['description']);

		// Sorting
		if (is_null($sort))
		{
			$sort=get_param('sort',NULL);
			if (is_null($sort))
			{
				$sort='t.text_original ASC';
			}
		}
		if ((strtoupper($sort)!=strtoupper('t.text_original ASC')) && (strtoupper($sort)!=strtoupper('t.text_original DESC'))
			&& (strtoupper($sort)!=strtoupper('file_size ASC')) && (strtoupper($sort)!=strtoupper('file_size DESC'))
			&& (strtoupper($sort)!=strtoupper('num_downloads DESC')) && (strtoupper($sort)!=strtoupper('add_date ASC'))
			&& (strtoupper($sort)!=strtoupper('compound_rating DESC')) && (strtoupper($sort)!=strtoupper('compound_rating ASC'))
			&& (strtoupper($sort)!=strtoupper('average_rating DESC')) && (strtoupper($sort)!=strtoupper('average_rating ASC'))
			&& (strtoupper($sort)!=strtoupper('fixed_random ASC'))
			&& (strtoupper($sort)!=strtoupper('add_date DESC'))) log_hack_attack_and_exit('sortBY_HACK');
		$_selectors=array(
			't.text_original ASC'=>'TITLE',
			'file_size ASC'=>'SMALLEST_FIRST',
			'file_size DESC'=>'LARGEST_FIRST',
			'num_downloads DESC'=>'MOST_DOWNLOADED',
			'average_rating DESC'=>'RATING',
			'compound_rating DESC'=>'POPULARITY',
			'add_date ASC'=>'OLDEST_FIRST',
			'add_date DESC'=>'NEWEST_FIRST',
			'fixed_random ASC'=>'RANDOM',
		);
		$selectors=new ocp_tempcode();
		foreach ($_selectors as $selector_value=>$selector_name)
		{
			$selected=($sort==$selector_value);
			$selectors->attach(do_template('PAGINATION_SORTER',array('_GUID'=>'af660c0ebf014bb296d576b2854aa911','SELECTED'=>$selected,'NAME'=>do_lang_tempcode($selector_name),'VALUE'=>$selector_value)));
		}
		$sort_url=get_self_url(false,false,array('sort'=>NULL),false,true);
		$sorting=do_template('PAGINATION_SORT',array('_GUID'=>'f4112dcd72d1dd04afbe7277a3871399','SORT'=>'sort','URL'=>$sort_url,'SELECTORS'=>$selectors));

		// Get category contents
		$subcategories=do_block('main_multi_content',array('param'=>'download_category','filter'=>strval($category_id).'>','efficient'=>'0','zone'=>'_SELF','sort'=>'title','max'=>get_option('download_subcats_per_page'),'no_links'=>'1','pagination'=>'1','give_context'=>'0','include_breadcrumbs'=>'0','render_if_empty'=>'0'));
		if (get_option('downloads_subcat_narrowin')=='1')
		{
			$filter=strval($category_id).'*';
		} else
		{
			$filter=strval($category_id);
		}
		$ocselect=either_param('active_filter','');
		$entries=do_block('main_multi_content',array('param'=>'download','filter'=>$filter,'efficient'=>'0','zone'=>'_SELF','sort'=>$sort,'max'=>get_option('download_entries_per_page'),'no_links'=>'1','pagination'=>'1','give_context'=>'0','include_breadcrumbs'=>'0','attach_to_url_filter'=>'1','ocselect'=>$ocselect,'block_id'=>'module'));

		// Management links
		if (has_actual_page_access(NULL,'cms_downloads',NULL,array('downloads',strval($category_id)),'submit_midrange_content'))
		{
			$submit_url=build_url(array('page'=>'cms_downloads','type'=>'ad','cat'=>$category_id),get_module_zone('cms_downloads'));
		} else $submit_url=new ocp_tempcode();
		if (has_actual_page_access(NULL,'cms_downloads',NULL,array('downloads',strval($category_id)),'submit_cat_midrange_content'))
		{
			$add_cat_url=build_url(array('page'=>'cms_downloads','type'=>'ac','parent_id'=>$category_id),get_module_zone('cms_downloads'));
		} else $add_cat_url=new ocp_tempcode();
		if (has_actual_page_access(NULL,'cms_downloads',NULL,array('downloads',strval($category_id)),'edit_cat_midrange_content'))
		{
			$edit_cat_url=build_url(array('page'=>'cms_downloads','type'=>'_ec','id'=>$category_id),get_module_zone('cms_downloads'));
		} else $edit_cat_url=new ocp_tempcode();

		// Render
		return do_template('DOWNLOAD_CATEGORY_SCREEN',array(
			'_GUID'=>'ebb3c8708695f6a30dbd4a03f8632047',
			'ID'=>strval($category_id),
			'TAGS'=>get_loaded_tags('download_categories'),
			'TITLE'=>$this->title,
			'SUBMIT_URL'=>$submit_url,
			'ADD_CAT_URL'=>$add_cat_url,
			'EDIT_CAT_URL'=>$edit_cat_url,
			'DESCRIPTION'=>$description,
			'SUBCATEGORIES'=>$subcategories,
			'DOWNLOADS'=>$entries,
			'SORTING'=>$sorting,
		));
	}

	/**
	 * The UI to view a download index.
	 *
	 * @return tempcode		The UI
	 */
	function show_all_downloads()
	{
		$id=get_param('id',strval(db_get_first_id()));

		require_code('ocfiltering');
		$sql_filter=ocfilter_to_sqlfragment(is_numeric($id)?($id.'*'):$id,'p.category_id','download_categories','parent_id','p.category_id','id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)

		if ($GLOBALS['SITE_DB']->query_select_value('download_downloads','COUNT(*)')>1000)
			warn_exit(do_lang_tempcode('TOO_MANY_TO_CHOOSE_FROM'));

		$privacy_join='';
		$privacy_where='';
		if (addon_installed('content_privacy'))
		{
			require_code('content_privacy');
			list($privacy_join,$privacy_where)=get_privacy_where_clause('download','p');
		}

		// Load up all data
		$cats=array();
		$rows=$GLOBALS['SITE_DB']->query('SELECT p.*,text_original FROM '.get_table_prefix().'download_downloads p'.$privacy_join.' LEFT JOIN '.get_table_prefix().'translate t ON t.id=p.name AND '.db_string_equal_to('language',user_lang()).' WHERE '.(addon_installed('unvalidated')?'validated=1 AND ':'').'('.$sql_filter.')'.$privacy_where.' ORDER BY text_original ASC',NULL,NULL,false,true);
		foreach ($rows as $row)
		{
			if ($GLOBALS['RECORD_LANG_STRINGS_CONTENT'] || is_null($row['text_original'])) $row['text_original']=get_translated_text($row['name']);
			$letter=strtoupper(substr($row['text_original'],0,1));

			if (!has_category_access(get_member(),'downloads',strval($row['category_id']))) continue;

			if (!array_key_exists($letter,$cats)) $cats[$letter]=array();
			$cats[$letter][]=$row;
		}
		unset($rows);

		// Show all letters
		// Not done via main_multi_content block due to complex organisation
		$subcats=array();
		foreach ($cats as $letter=>$rows)
		{
			if (!is_string($letter)) $letter=strval($letter); // Numbers come out as numbers not strings, even if they went in as strings- darned PHP

			$has_download=false;

			$data=array();
			$data['CAT_TITLE']=$letter;
			$data['LETTER']=$letter;

			$out=new ocp_tempcode();

			foreach ($rows as $myrow)
			{
				$out->attach(render_download_box($myrow,true,true/*breadcrumbs?*/,NULL,NULL,false/*context?*/));
				$has_download=true;
			}

			$data['DOWNLOADS']=$out;

			$subcats[]=$data;
		}

		// Management links
		if ((is_numeric($id)) && (has_actual_page_access(NULL,'cms_downloads',NULL,array('downloads',$id),'submit_midrange_content')))
		{
			$submit_url=build_url(array('page'=>'cms_downloads','type'=>'ad','cat'=>$id),get_module_zone('cms_downloads'));
		} else $submit_url=new ocp_tempcode();
		if ((is_numeric($id)) && (has_actual_page_access(NULL,'cms_downloads',NULL,array('downloads',$id),'submit_cat_midrange_content')))
		{
			$add_cat_url=build_url(array('page'=>'cms_downloads','type'=>'ac','parent_id'=>$id),get_module_zone('cms_downloads'));
		} else $add_cat_url=new ocp_tempcode();
		if ((is_numeric($id)) && (has_actual_page_access(NULL,'cms_downloads',NULL,array('downloads',$id),'edit_cat_midrange_content')))
		{
			$edit_cat_url=build_url(array('page'=>'cms_downloads','type'=>'_ec','id'=>$id),get_module_zone('cms_downloads'));
		} else $edit_cat_url=new ocp_tempcode();

		// Render
		return do_template('DOWNLOAD_ALL_SCREEN',array(
			'_GUID'=>'ed853b11e66917aeff3eb23767f70011',
			'TITLE'=>$this->title,
			'SUBMIT_URL'=>$submit_url,
			'ADD_CAT_URL'=>$add_cat_url,
			'EDIT_CAT_URL'=>$edit_cat_url,
			'SUB_CATEGORIES'=>$subcats,
		));
	}

	/**
	 * The UI to view a download.
	 *
	 * @return tempcode		The UI
	 */
	function view_download_screen()
	{
		$id=$this->id;
		$myrow=$this->myrow;
		$name=$this->name;
		$images_details=$this->images_details;
		$num_images=$this->num_images;
		$root=$this->root;

		// Feedback
		list($rating_details,$comment_details,$trackback_details)=embed_feedback_systems(
			get_page_name(),
			strval($id),
			$myrow['allow_rating'],
			$myrow['allow_comments'],
			$myrow['allow_trackbacks'],
			$myrow['validated'],
			$myrow['submitter'],
			build_url(array('page'=>'_SELF','type'=>'entry','id'=>$id),'_SELF',NULL,false,false,true),
			$name,
			find_overridden_comment_forum('downloads',strval($myrow['category_id'])),
			$myrow['add_date']
		);

		// Views
		if ((get_db_type()!='xml') && (get_value('no_view_counts')!=='1'))
		{
			$myrow['download_views']++;
			if (!$GLOBALS['SITE_DB']->table_is_locked('download_downloads'))
				$GLOBALS['SITE_DB']->query_update('download_downloads',array('download_views'=>$myrow['download_views']),array('id'=>$id),'',1,NULL,false,true);
		}

		$warning_details=new ocp_tempcode();

		// Validation
		if (($myrow['validated']==0) && (addon_installed('unvalidated')))
		{
			if (!has_privilege(get_member(),'jump_to_unvalidated'))
				access_denied('PRIVILEGE','jump_to_unvalidated');

			$warning_details->attach(do_template('WARNING_BOX',array('_GUID'=>'5b1781b8fbb1ef9b8f47693afcff02b9','WARNING'=>do_lang_tempcode((get_param_integer('redirected',0)==1)?'UNVALIDATED_TEXT_NON_DIRECT':'UNVALIDATED_TEXT'))));
		}

		// Cost warning
		if (($myrow['download_cost']!=0) && (addon_installed('points')))
		{
			require_lang('points');
			$warning_details->attach(do_template('WARNING_BOX',array('_GUID'=>'05fc448bf79b373385723c5af5ec93af','WARNING'=>do_lang_tempcode('WILL_COST',integer_format($myrow['download_cost'])))));
		}

		// Management links
		$edit_url=new ocp_tempcode();
		$add_img_url=new ocp_tempcode();
		if ((has_actual_page_access(NULL,'cms_downloads',NULL,NULL)) && (has_edit_permission('mid',get_member(),$myrow['submitter'],'cms_downloads',array('downloads',$myrow['category_id']))))
		{
			$edit_url=build_url(array('page'=>'cms_downloads','type'=>'_ed','id'=>$id),get_module_zone('cms_downloads'));
		}
		if (addon_installed('galleries'))
		{
			if ((has_actual_page_access(NULL,'cms_galleries',NULL,NULL)) && (has_edit_permission('mid',get_member(),$myrow['submitter'],'cms_galleries',array('galleries','download_'.strval($id)))))
			{
				require_lang('galleries');
				$add_img_url=build_url(array('page'=>'cms_galleries','type'=>'ad','cat'=>'download_'.strval($id)),get_module_zone('cms_galleries'));
			}
		}

		// Outmoding
		if (!is_null($myrow['out_mode_id']))
		{
			$outmode_url=build_url(array('page'=>'_SELF','type'=>'entry','id'=>$myrow['out_mode_id']),'_SELF');
		} else $outmode_url=new ocp_tempcode();

		// Stats
		$add_date=get_timezoned_date($myrow['add_date'],false);

		// Additional information
		$additional_details=get_translated_tempcode($myrow['additional_details']);

		// Edit date
		if (!is_null($myrow['edit_date']))
		{
			$edit_date=make_string_tempcode(get_timezoned_date($myrow['edit_date'],false));
		} else $edit_date=new ocp_tempcode();

		// Download link
		$author=$myrow['author'];
		$author_url=addon_installed('authors')?build_url(array('page'=>'authors','type'=>'misc','id'=>$author),get_module_zone('authors')):new ocp_tempcode();

		// Licence
		$licence_title=NULL;
		$licence_url=NULL;
		$licence_hyperlink=NULL;
		$licence=$myrow['download_licence'];
		if (!is_null($licence))
		{
			$licence_title=$GLOBALS['SITE_DB']->query_select_value_if_there('download_licences','l_title',array('id'=>$licence));
			if (!is_null($licence_title))
			{
				$keep=symbol_tempcode('KEEP');
				$licence_url=find_script('download_licence').'?id='.strval($licence).$keep->evaluate();
				$licence_hyperlink=do_template('HYPERLINK_POPUP_WINDOW',array('_GUID'=>'10582f28c37ee7e9e462fdbd6a2cb8dd','TITLE'=>'','CAPTION'=>$licence_title,'URL'=>$licence_url,'WIDTH'=>'600','HEIGHT'=>'500','REL'=>'license'));
			} else
			{
				$licence=NULL; // Orphaned
			}
		}

		$may_download=has_privilege(get_member(),'download','downloads',array(strval($myrow['category_id'])));

		// Render
		return do_template('DOWNLOAD_SCREEN',array(
			'_GUID'=>'a9af438f84783d0d38c20b5f9a62dbdb',
			'ORIGINAL_FILENAME'=>$myrow['original_filename'],
			'URL'=>$myrow['url'],
			'NUM_IMAGES'=>strval($num_images),
			'TAGS'=>get_loaded_tags('downloads'),
			'LICENCE'=>is_null($licence)?NULL:strval($licence),
			'LICENCE_TITLE'=>$licence_title,
			'LICENCE_HYPERLINK'=>$licence_hyperlink,
			'SUBMITTER'=>strval($myrow['submitter']),
			'EDIT_DATE'=>$edit_date,
			'EDIT_DATE_RAW'=>is_null($myrow['edit_date'])?'':strval($myrow['edit_date']),
			'VIEWS'=>integer_format($myrow['download_views']),
			'NAME'=>$name,
			'DATE'=>$add_date,
			'DATE_RAW'=>strval($myrow['add_date']),
			'NUM_DOWNLOADS'=>integer_format($myrow['num_downloads']),
			'TITLE'=>$this->title,
			'OUTMODE_URL'=>$outmode_url,
			'WARNING_DETAILS'=>$warning_details,
			'EDIT_URL'=>$edit_url,
			'ADD_IMG_URL'=>$add_img_url,
			'DESCRIPTION'=>get_translated_tempcode($myrow['description']),
			'ADDITIONAL_DETAILS'=>$additional_details,
			'IMAGES_DETAILS'=>$images_details,
			'ID'=>strval($id),
			'FILE_SIZE'=>clean_file_size($myrow['file_size']),
			'AUTHOR_URL'=>$author_url,
			'AUTHOR'=>$author,
			'TRACKBACK_DETAILS'=>$trackback_details,
			'RATING_DETAILS'=>$rating_details,
			'COMMENT_DETAILS'=>$comment_details,
			'MAY_DOWNLOAD'=>$may_download,
		));
	}

}


