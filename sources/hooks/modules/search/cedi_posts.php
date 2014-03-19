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
 * @package		cedi
 */

class Hook_search_cedi_posts
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		if (!module_installed('cedi')) return NULL; // TODO: Update in v10

		if (!has_actual_page_access(get_member(),'cedi')) return NULL;
		if ($GLOBALS['SITE_DB']->query_value('seedy_posts','COUNT(*)')==0) return NULL;

		require_lang('cedi');

		$info=array();
		$info['lang']=do_lang_tempcode('CEDI_POSTS');
		$info['default']=false;
		$info['category']='page_id';
		$info['integer_category']=true;

		return $info;
	}

	/**
	 * Get details for an ajax-tree-list of entries for the content covered by this search hook.
	 *
	 * @return array			A pair: the hook, and the options
	 */
	function ajax_tree()
	{
		return array('choose_cedi_page',array('compound_list'=>true));
	}

	/**
	 * Standard modular run function for search results.
	 *
	 * @param  string			Search string
	 * @param  boolean		Whether to only do a META (tags) search
	 * @param  ID_TEXT		Order direction
	 * @param  integer		Start position in total results
	 * @param  integer		Maximum results to return in total
	 * @param  boolean		Whether only to search titles (as opposed to both titles and content)
	 * @param  string			Where clause that selects the content according to the main search string (SQL query fragment) (blank: full-text search)
	 * @param  SHORT_TEXT	Username/Author to match for
	 * @param  ?MEMBER		Member-ID to match for (NULL: unknown)
	 * @param  TIME			Cutoff date
	 * @param  string			The sort type (gets remapped to a field in this function)
	 * @set    title add_date
	 * @param  integer		Limit to this number of results
	 * @param  string			What kind of boolean search to do
	 * @set    or and
	 * @param  string			Where constraints known by the main search code (SQL query fragment)
	 * @param  string			Comma-separated list of categories to search under
	 * @param  boolean		Whether it is a boolean search
	 * @return array			List of maps (template, orderer)
	 */
	function run($content,$only_search_meta,$direction,$max,$start,$only_titles,$content_where,$author,$author_id,$cutoff,$sort,$limit_to,$boolean_operator,$where_clause,$search_under,$boolean_search)
	{
		unset($limit_to);

		$remapped_orderer='';
		switch ($sort)
		{
			case 'rating':
				$remapped_orderer='_rating:seedy_post:id';
				break;

			case 'title':
				$remapped_orderer='page_id'; // No good fit
				break;

			case 'add_date':
				$remapped_orderer='date_and_time';
				break;
		}

		require_code('cedi');
		require_lang('cedi');

		// Calculate our where clause (search)
		$sq=build_search_submitter_clauses('the_user',$author_id,$author);
		if (is_null($sq)) return array(); else $where_clause.=$sq;
		if (!is_null($cutoff))
		{
			$where_clause.=' AND ';
			$where_clause.='date_and_time>'.strval($cutoff);
		}

		if (!has_specific_permission(get_member(),'see_unvalidated'))
		{
			$where_clause.=' AND ';
			$where_clause.='validated=1';
		}

		// Calculate and perform query
		$rows=get_search_rows(NULL,NULL,$content,$boolean_search,$boolean_operator,$only_search_meta,$direction,$max,$start,$only_titles,'seedy_posts r',array('','r.the_message'),$where_clause,$content_where,$remapped_orderer,'r.*',NULL,'seedy_page','page_id');

		$out=array();
		foreach ($rows as $i=>$row)
		{
			$out[$i]['data']=$row;
			unset($rows[$i]);
			if (($remapped_orderer!='') && (array_key_exists($remapped_orderer,$row))) $out[$i]['orderer']=$row[$remapped_orderer]; elseif (substr($remapped_orderer,0,7)=='_rating') $out[$i]['orderer']=$row['compound_rating'];
		}

		return $out;
	}

	/**
	 * Standard modular run function for rendering a search result.
	 *
	 * @param  array		The data row stored when we retrieved the result
	 * @return tempcode	The output
	 */
	function render($row)
	{
		return get_cedi_post_html($row);
	}

}


