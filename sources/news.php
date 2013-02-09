<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		news
 */

/**
 * Show a news entry box.
 *
 * @param  array			The news row
 * @param  ID_TEXT		The zone our news module is in
 * @param  boolean		Whether to include context (i.e. say WHAT this is, not just show the actual content)
 * @param  boolean		Whether to use the brief styling
 * @param  ID_TEXT		Overridden GUID to send to templates (blank: none)
 * @return tempcode		The box
 */
function render_news_box($row,$zone='_SEARCH',$give_context=true,$brief=false,$guid='')
{
	require_lang('news');
	require_css('news');

	$url=build_url(array('page'=>'news','type'=>'view','id'=>$row['id']),$zone);

	$title=get_translated_tempcode($row['title']);
	$title_plain=get_translated_text($row['title']);

	global $NEWS_CATS_CACHE;
	if (!isset($NEWS_CATS_CACHE)) $NEWS_CATS_CACHE=array();
	if (!array_key_exists($row['news_category'],$NEWS_CATS_CACHE))
	{
		$_news_cats=$GLOBALS['SITE_DB']->query_select('news_categories',array('*'),array('id'=>$row['news_category']),'',1);
		if (array_key_exists(0,$_news_cats))
			$NEWS_CATS_CACHE[$row['news_category']]=$_news_cats[0];
	}
	if ((!array_key_exists($row['news_category'],$NEWS_CATS_CACHE)) || (!array_key_exists('nc_title',$NEWS_CATS_CACHE[$row['news_category']])))
		$row['news_category']=db_get_first_id();
	$news_cat_row=$NEWS_CATS_CACHE[$row['news_category']];
	$img=find_theme_image($news_cat_row['nc_img']);
	if (is_null($img)) $img='';
	if ($row['news_image']!='')
	{
		$img=$row['news_image'];
		if (url_is_local($img)) $img=get_base_url().'/'.$img;
	}
	$category=get_translated_text($news_cat_row['nc_title']);

	$news=get_translated_tempcode($row['news']);
	if ($news->is_empty())
	{
		$news=get_translated_tempcode($row['news_article']);
		$truncate=true;
	} else $truncate=false;

	$author_url=addon_installed('authors')?build_url(array('page'=>'authors','type'=>'misc','id'=>$row['author']),get_module_zone('authors')):new ocp_tempcode();
	$author=$row['author'];

	$seo_bits=(get_value('no_tags')==='1')?array('',''):seo_meta_get_for('news',strval($row['id']));

	$map=array(
		'_GUID'=>($guid!='')?$guid:'jd89f893jlkj9832gr3uyg2u',
		'GIVE_CONTEXT'=>$give_context,
		'TAGS'=>(get_option('show_content_tagging_inline')=='1')?get_loaded_tags('news',explode(',',$seo_bits[0])):NULL,
		'TRUNCATE'=>$truncate,
		'AUTHOR'=>$author,
		'BLOG'=>false,
		'AUTHOR_URL'=>$author_url,
		'CATEGORY'=>$category,
		'IMG'=>$img,
		'NEWS'=>$news,
		'ID'=>strval($row['id']),
		'SUBMITTER'=>strval($row['submitter']),
		'DATE'=>get_timezoned_date($row['date_and_time']),
		'DATE_RAW'=>strval($row['date_and_time']),
		'FULL_URL'=>$url,
		'NEWS_TITLE'=>$title,
		'NEWS_TITLE_PLAIN'=>$title_plain,
	);

	if ((get_option('is_on_comments')=='1') && (!has_no_forum()) && ($row['allow_comments']>=1)) $map['COMMENT_COUNT']='1';

	return do_template($brief?'NEWS_BRIEF':'NEWS_BOX',$map);
}

/**
 * Get tempcode for a news category 'feature box' for the given row
 *
 * @param  array			The database field row of it
 * @param  ID_TEXT		The zone to use
 * @param  boolean		Whether to include context (i.e. say WHAT this is, not just show the actual content)
 * @param  boolean		Whether to copy through any filter parameters in the URL, under the basis that they are associated with what this box is browsing
 * @param  ?integer		What to show (NULL: news and blogs, 0: news, 1: blogs)
 * @param  ID_TEXT		Overridden GUID to send to templates (blank: none)
 * @return tempcode		A box for it, linking to the full page
 */
function render_news_category_box($row,$zone='_SEARCH',$give_context=true,$attach_to_url_filter=false,$blogs=NULL,$guid='')
{
	require_lang('news');

	// URL
	$map=array('page'=>'news','type'=>'misc','id'=>$row['id']);
	if ($attach_to_url_filter)
	{
		if (get_param('type','misc')=='cat_select') $map['blog']='0';
		elseif (get_param('type','misc')=='blog_select') $map['blog']='1';

		$map+=propagate_ocselect();
	}
	$url=build_url($map,$zone);

	// Title
	$_title=get_translated_text($row['nc_title']);
	$title=$give_context?do_lang('CONTENT_IS_OF_TYPE',do_lang('NEWS_CATEGORY'),$_title):$_title;

	// Meta-data
	$num_entries=$GLOBALS['SITE_DB']->query_select_value('news','COUNT(*)',array('validated'=>1));
	$entry_details=do_lang_tempcode('CATEGORY_SUBORDINATE_2',escape_html(integer_format($num_entries)));

	// Image
	$img=($row['nc_img']=='')?'':find_theme_image($row['nc_img']);
	if ($blogs===1)
	{
		$_img=$GLOBALS['FORUM_DRIVER']->get_member_avatar_url($row['nc_owner']);
		if ($_img!='') $img=$_img;
	}
	$rep_image=mixed();
	$_rep_image=mixed();
	if ($img!='')
	{
		require_code('images');
		$_rep_image=$img;
		$rep_image=do_image_thumb($img,$_title,false);
	}

	// Render
	return do_template('SIMPLE_PREVIEW_BOX',array(
		'_GUID'=>($guid!='')?$guid:'49e9c7022f9171fdff02d84ee968bb52',
		'ID'=>strval($row['id']),
		'TITLE'=>$title,
		'TITLE_PLAIN'=>$_title,
		'_REP_IMAGE'=>$_rep_image,
		'REP_IMAGE'=>$rep_image,
		'OWNER'=>is_null($row['nc_owner'])?'':strval($row['nc_owner']),
		'SUMMARY'=>'',
		'ENTRY_DETAILS'=>$entry_details,
		'URL'=>$url,
		'FRACTIONAL_EDIT_FIELD_NAME'=>$give_context?NULL:'title',
		'FRACTIONAL_EDIT_FIELD_URL'=>$give_context?NULL:'_SEARCH:cms_news:type=__ec:id='.strval($row['id']),
	));
}

/**
 * Get a nice formatted XHTML list of news categories.
 *
 * @param  ?mixed			The selected news category. Array or AUTO_LINK (NULL: personal)
 * @param  boolean		Whether to add all personal categories into the list (for things like the adminzone, where all categories must be shown, regardless of permissions)
 * @param  boolean		Whether to only show for what may be added to by the current member
 * @param  boolean		Whether to limit to only existing cats (otherwise we dynamically add unstarted blogs)
 * @param  ?boolean		Whether to limit to only show blog categories (NULL: don't care, true: blogs only, false: no blogs)
 * @param  boolean		Whether to prefer to choose a non-blog category as the default
 * @return tempcode		The tempcode for the news category select list
 */
function nice_get_news_categories($it=NULL,$show_all_personal_categories=false,$addable_filter=false,$only_existing=false,$only_blogs=NULL,$prefer_not_blog_selected=false)
{
	if (!is_array($it)) $it=array($it);

	if ($only_blogs===true)
	{
		$where='WHERE nc_owner IS NOT NULL';
	}
	elseif ($only_blogs===false)
	{
		$where='WHERE nc_owner IS NULL';
	} else
	{
		$where='WHERE 1=1';
	}
	$count=$GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(*) FROM '.get_table_prefix().'news_categories '.$where.' ORDER BY id');
	if ($count>500) // Uh oh, loads, need to limit things more
	{
		$where.=' AND (nc_owner IS NULL OR nc_owner='.strval(get_member()).')';
	}
	$_cats=$GLOBALS['SITE_DB']->query('SELECT *,c.id as n_id FROM '.get_table_prefix().'news_categories c '.$where.' ORDER BY c.id',NULL,NULL,false,false,array('nc_title'));

	foreach ($_cats as $i=>$cat)
	{
		$_cats[$i]['nice_title']=get_translated_text($cat['nc_title']);
	}
	sort_maps_by($_cats,'nice_title');

	// Sort so blogs go after news
	$title_ordered_cats=$_cats;
	$_cats=array();
	foreach ($title_ordered_cats as $cat)
		if (is_null($cat['nc_owner'])) $_cats[]=$cat;
	foreach ($title_ordered_cats as $cat)
		if (!is_null($cat['nc_owner'])) $_cats[]=$cat;

	$categories=new ocp_tempcode();
	$add_cat=true;

	foreach ($_cats as $cat)
	{
		if ($cat['nc_owner']==get_member()) $add_cat=false;

		if (!has_category_access(get_member(),'news',strval($cat['n_id']))) continue;
		if (($addable_filter) && (!has_submit_permission('high',get_member(),get_ip_address(),'cms_news',array('news',$cat['n_id'])))) continue;

		if (is_null($cat['nc_owner']))
		{
			$li=form_input_list_entry(strval($cat['n_id']),($it!=array(NULL)) && in_array($cat['n_id'],$it),$cat['nice_title'].' (#'.strval($cat['n_id']).')');
			$categories->attach($li);
		} else
		{
			if ((((!is_null($cat['nc_owner'])) && (has_privilege(get_member(),'can_submit_to_others_categories'))) || (($cat['nc_owner']==get_member()) && (!is_guest()))) || ($show_all_personal_categories))
				$categories->attach(form_input_list_entry(strval($cat['n_id']),(($cat['nc_owner']==get_member()) && ((!$prefer_not_blog_selected) && (in_array(NULL,$it)))) || (in_array($cat['n_id'],$it)),$cat['nice_title']/*Performance do_lang('MEMBER_CATEGORY',$GLOBALS['FORUM_DRIVER']->get_username($cat['nc_owner']))*/.' (#'.strval($cat['n_id']).')'));
		}
	}

	if ((!$only_existing) && (has_privilege(get_member(),'have_personal_category','cms_news')) && ($add_cat) && (!is_guest()))
	{
		$categories->attach(form_input_list_entry('personal',(!$prefer_not_blog_selected) && in_array(NULL,$it),do_lang_tempcode('MEMBER_CATEGORY',do_lang_tempcode('_NEW',escape_html($GLOBALS['FORUM_DRIVER']->get_username(get_member()))))));
	}

	return $categories;
}

/**
 * Get a nice formatted XHTML list of news.
 *
 * @param  ?AUTO_LINK	The selected news entry (NULL: none)
 * @param  ?MEMBER		Limit news to those submitted by this member (NULL: show all)
 * @param  boolean		Whether to only show for what may be edited by the current member
 * @param  boolean		Whether to only show blog posts
 * @return tempcode		The list
 */
function nice_get_news($it,$only_owned=NULL,$editable_filter=false,$only_in_blog=false)
{
	$where=is_null($only_owned)?'1':'submitter='.strval($only_owned);
	if ($only_in_blog)
	{
		$rows=$GLOBALS['SITE_DB']->query('SELECT n.* FROM '.get_table_prefix().'news n JOIN '.get_table_prefix().'news_categories c ON c.id=n.news_category AND '.$where.' AND nc_owner IS NOT NULL ORDER BY date_and_time DESC',300/*reasonable limit*/);
	} else
	{
		$rows=$GLOBALS['SITE_DB']->query('SELECT * FROM '.get_table_prefix().'news WHERE '.$where.' ORDER BY date_and_time DESC',300/*reasonable limit*/);
	}

	if (count($rows)==300) attach_message(do_lang_tempcode('TOO_MUCH_CHOOSE__RECENT_ONLY',escape_html(integer_format(300))),'warn');

	$out=new ocp_tempcode();
	foreach ($rows as $myrow)
	{
		if (!has_category_access(get_member(),'news',strval($myrow['news_category']))) continue;
		if (($editable_filter) && (!has_edit_permission('high',get_member(),$myrow['submitter'],'cms_news',array('news',$myrow['news_category'])))) continue;

		$selected=($myrow['id']==$it);

		$out->attach(form_input_list_entry(strval($myrow['id']),$selected,get_translated_text($myrow['title'])));
	}

	return $out;
}

