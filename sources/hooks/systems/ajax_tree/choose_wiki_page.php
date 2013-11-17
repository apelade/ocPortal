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
 * @package		wiki
 */

class Hook_choose_wiki_page
{
	/**
	 * Standard modular run function for ajax-tree hooks. Generates XML for a tree list, which is interpreted by Javascript and expanded on-demand (via new calls).
	 *
	 * @param  ?ID_TEXT		The ID to do under (NULL: root)
	 * @param  array			Options being passed through
	 * @param  ?ID_TEXT		The ID to select by default (NULL: none)
	 * @return string			XML in the special category,entry format
	 */
	function run($id,$options,$default=NULL)
	{
		require_code('wiki');
		require_lang('wiki');

		$wiki_seen=array();
		$tree=get_wiki_page_tree($wiki_seen,is_null($id)?NULL:intval($id),NULL,NULL,true,false,is_null($id)?0:1);

		$stripped_id=$id;

		$out='';

		if (!has_actual_page_access(NULL,'wiki')) $tree=array();

		foreach ($tree as $t)
		{
			$_id=strval($t['id']);

			if ($stripped_id===$_id) continue; // Possible when we look under as a root

			$title=$t['title'];
			$has_children=($t['child_count']!=0);
			$selectable=true;

			$tag='category'; // category
			$out.='<'.$tag.' id="'.$_id.'" title="'.xmlentities($title).'" has_children="'.($has_children?'true':'false').'" selectable="'.($selectable?'true':'false').'"></'.$tag.'>';
		}

		if (is_null($id))
		{
			if (!db_has_subqueries($GLOBALS['SITE_DB']->connection_read))
			{
				$where='';
				$wiki_seen=array();
				get_wiki_page_tree($wiki_seen,is_null($id)?NULL:intval($id)); // To build up $wiki_seen
				foreach ($wiki_seen as $seen)
				{
					if ($where!='') $where.=' AND ';
					$where.='p.id<>'.strval($seen);
				}

				$orphans=$GLOBALS['SITE_DB']->query('SELECT p.id,text_original,p.title FROM '.get_table_prefix().'wiki_pages p LEFT JOIN '.get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND t.id=p.title WHERE '.$where.' ORDER BY add_date DESC',50/*reasonable limit*/,NULL,false,true);
			} else
			{
				$orphans=$GLOBALS['SITE_DB']->query('SELECT p.id,text_original,p.title FROM '.get_table_prefix().'wiki_pages p LEFT JOIN '.get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND t.id=p.title WHERE NOT EXISTS(SELECT * FROM '.get_table_prefix().'wiki_children WHERE child_id=p.id) ORDER BY add_date DESC',50/*reasonable limit*/);
				if (count($orphans)<50)
				{
					sort_maps_by($orphans,'text_original');
				}
			}

			foreach ($orphans as $orphan)
			{
				if (!has_category_access(get_member(),'wiki_page',strval($orphan['id']))) continue;

				if ($orphan['id']==db_get_first_id()) continue;

				if ($GLOBALS['RECORD_LANG_STRINGS_CONTENT'] || is_null($orphan['text_original'])) $orphan['text_original']=get_translated_text($orphan['title']);

				$_id=strval($orphan['id']);
				$title=$orphan['text_original'];
				$has_children=($GLOBALS['SITE_DB']->query_select_value('wiki_children','COUNT(*)',array('parent_id'=>$orphan['id']))!=0);
				$selectable=true;

				$tag='category'; // category
				$out.='<'.$tag.' id="'.$_id.'" title="'.xmlentities($title).'" has_children="'.($has_children?'true':'false').'" selectable="'.($selectable?'true':'false').'"></'.$tag.'>';
			}
		}

		$tag='result'; // result
		return '<'.$tag.'>'.$out.'</'.$tag.'>';
	}

	/**
	 * Standard modular simple function for ajax-tree hooks. Returns a normal <select> style <option>-list, for fallback purposes
	 *
	 * @param  ?ID_TEXT		The ID to do under (NULL: root) - not always supported
	 * @param  array			Options being passed through
	 * @param  ?ID_TEXT		The ID to select by default (NULL: none)
	 * @return tempcode		The nice list
	 */
	function simple($id,$options,$it=NULL)
	{
		require_lang('wiki');
		require_code('wiki');

		return wiki_show_tree(is_null($it)?NULL:intval($it));
	}
}


