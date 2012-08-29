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
 * @package		core_comcode_pages
 */

class Hook_search_comcode_pages
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		require_lang('zones');

		$info=array();
		$info['lang']=do_lang_tempcode('PAGES');
		$info['default']=false;
		$info['category']='the_zone';
		$info['integer_category']=false;

		return $info;
	}

	/**
	 * Get a list of entries for the content covered by this search hook. In hierarchical list selection format.
	 *
	 * @param  string			The default selected item
	 * @return tempcode		Tree structure
	 */
	function get_tree($selected)
	{
		require_code('zones3');
		$tree=nice_get_zones($selected);
		return $tree;
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
		$remapped_orderer='';
		switch ($sort)
		{
			case 'title':
				$remapped_orderer='the_page';
				break;

			case 'add_date':
				$remapped_orderer='the_zone'; // Stucked
				break;
		}

		load_up_all_self_page_permissions(get_member());

		$sq=build_search_submitter_clauses('p_submitter',$author_id,$author);
		if (is_null($sq)) return array(); else $where_clause.=$sq;

		if (!$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member()))
		{
			$where_clause.=' AND ';
			$where_clause.='z.zone_name IS NOT NULL';
		}
		if (strpos($content,'panel_')===false)
		{
			$where_clause.=' AND ';
			$where_clause.='(r.the_page NOT LIKE \''.db_encode_like('panel\_%').'\') AND (r.the_page NOT LIKE \''.db_encode_like('\_%').'\')';
		}
		if ((!is_null($search_under)) && ($search_under!='!'))
		{
			$where_clause.=' AND ';
			$where_clause.='('.db_string_equal_to('r.the_zone',$search_under).')';
		}

		if (!has_privilege(get_member(),'see_unvalidated'))
		{
			$where_clause.=' AND ';
			$where_clause.='p_validated=1';
		}

		require_lang('zones');
		$g_or=_get_where_clause_groups(get_member(),false);

		// Calculate and perform query
		if ($g_or=='')
		{
			$rows=get_search_rows('comcode_page','the_zone:the_page',$content,$boolean_search,$boolean_operator,$only_search_meta,$direction,$max,$start,$only_titles,'cached_comcode_pages r LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'comcode_pages q ON (q.the_zone=r.the_zone AND q.the_page=r.the_page)',array('r.cc_page_title','r.string_index'),$where_clause,$content_where,$remapped_orderer,'r.*');
		} else
		{
			$rows=get_search_rows('comcode_page','the_zone:the_page',$content,$boolean_search,$boolean_operator,$only_search_meta,$direction,$max,$start,$only_titles,'cached_comcode_pages r LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'comcode_pages q ON (q.the_zone=r.the_zone AND q.the_page=r.the_page) LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'group_zone_access z ON (z.zone_name=r.the_zone AND ('.str_replace('group_id','z.group_id',$g_or).'))',array('r.cc_page_title','r.string_index'),$where_clause,$content_where,$remapped_orderer,'r.*');
		}

		if (addon_installed('redirects_editor'))
		{
			$redirects=$GLOBALS['SITE_DB']->query_select('redirects',array('*'));
		} else $redirects=array();

		$out=array();
		$pages_found=array();
		foreach ($rows as $i=>$row)
		{
			foreach ($redirects as $redirect)
			{
				if (($redirect['r_from_page']==$row['the_page']) && ($redirect['r_from_zone']==$row['the_zone'])) continue 2;
			}

			if ($row['the_zone']=='!') continue;
			if (array_key_exists($row['the_zone'].':'.$row['the_page'],$pages_found)) continue;
			$pages_found[$row['the_zone'].':'.$row['the_page']]=1;
			$out[$i]['data']=$row+array('extra'=>array($row['the_zone'],$row['the_page'],$limit_to));
			if (($remapped_orderer!='') && (array_key_exists($remapped_orderer,$row))) $out[$i]['orderer']=$row[$remapped_orderer]; elseif (substr($remapped_orderer,0,7)=='_rating') $out[$i]['orderer']=$row['compound_rating'];

			if (!has_page_access(get_member(),$row['the_page'],$row['the_zone'])) $out[$i]['restricted']=true;
		}

		if ($author=='')
		{
			// Make sure we record that for all cached Comcode pages, we know of them (only those not cached would not have been under the scope of the current search)
			$all_pages=$GLOBALS['SITE_DB']->query_select('cached_comcode_pages',array('the_zone','the_page'));
			foreach ($all_pages as $row)
			{
				$pages_found[$row['the_zone'].':'.$row['the_page']]=1;
			}

			// Now, look on disk for non-cached comcode pages
			$zones=find_all_zones();
			$i=count($out);
			if ((!is_null($search_under)) && ($search_under!='!')) $zones=array($search_under);
			foreach ($zones as $zone)
			{
				if (!has_zone_access(get_member(),$zone)) continue;

				$pages=find_all_pages($zone,'comcode/'.user_lang(),'txt')+find_all_pages($zone,'comcode_custom/'.user_lang(),'txt')+find_all_pages($zone,'comcode/'.get_site_default_lang(),'txt')+find_all_pages($zone,'comcode_custom/'.get_site_default_lang(),'txt');
				foreach ($pages as $page=>$dir)
				{
					if (!is_string($page)) $page=strval($page);

					if (!array_key_exists($zone.':'.$page,$pages_found))
					{
						if (!has_page_access(get_member(),$page,$zone)) continue;

						if (strpos($content,'panel_')===false)
						{
							if (substr($page,0,6)=='panel_') continue;
						}
						if (substr($page,0,1)=='_') continue;

						foreach ($redirects as $redirect)
						{
							if (($redirect['r_from_page']==$page) && ($redirect['r_from_zone']==$zone)) continue 2;
						}

						$path=zone_black_magic_filterer((($dir=='comcode_custom')?get_custom_file_base():get_file_base()).'/'.$zone.'/pages/'.$dir.'/'.$page.'.txt');
						if ((!is_null($cutoff)) && (filemtime($path)<$cutoff)) continue;
						$contents=file_get_contents($path);

						if (in_memory_search_match(array('content'=>$content,'conjunctive_operator'=>$boolean_operator),$contents))
						{
							$out[$i]['data']=$row+array('extra'=>array($row['the_zone'],$row['the_page'],$limit_to));
							if ($remapped_orderer=='the_page') $out[$i]['orderer']=$page;
							elseif ($remapped_orderer=='the_zone') $out[$i]['orderer']=$zone;

							$i++;

							$GLOBALS['TOTAL_SEARCH_RESULTS']++;

							// Let it cache for next time
							if (get_option('is_on_comcode_page_cache')=='1')
								request_page($page,false,$zone,$dir,false,true);
						}
					}
				}
			}
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
		list($zone,$page,$limit_to)=$row['extra'];
		return $this->decide_template($zone,$page,$limit_to);
	}

	/**
	 * Decide how to show a comcode page in the search results.
	 *
	 * @param  ID_TEXT		The zone for the page
	 * @param  ID_TEXT		The page name
	 * @param  string			What search hooks the search is being limited to (blank: not limited)
	 * @return tempcode		The tempcode showing the comcode page
	 */
	function decide_template($zone,$page,$limit_to)
	{
		global $SEARCH__CONTENT_BITS;

		require_code('xhtml');

		$url=build_url(array('page'=>$page),$zone);

		$_summary=seo_meta_get_for('comcode_page',$zone.':'.$page);
		$summary=$_summary[1];

		if ($summary=='')
		{
			$comcode_file=zone_black_magic_filterer(get_custom_file_base().'/'.filter_naughty($zone).'/pages/comcode_custom/'.get_site_default_lang().'/'.filter_naughty($page).'.txt');
			if (!file_exists($comcode_file))
			{
				$comcode_file=zone_black_magic_filterer(get_file_base().'/'.filter_naughty($zone).'/pages/comcode/'.get_site_default_lang().'/'.filter_naughty($page).'.txt');
			}
			if (file_exists($comcode_file))
			{
				global $LAX_COMCODE;
				$LAX_COMCODE=true;
				/*$temp_summary=comcode_to_tempcode(file_get_contents($comcode_file),NULL,true); Tempcode compiler slowed things down so easier just to show full thing
				$_temp_summary=$temp_summary->evaluate();
				if (strlen($_temp_summary)<500)
				{
					$summary=$_temp_summary;
				} else
				{
					$entity='&hellip;';
					if (function_exists('ocp_mark_as_escaped')) ocp_mark_as_escaped($entity);
					$pos=false;//strpos($_temp_summary,'<span class="comcode_highlight">');
					if ($pos===false) $pos=0;
					$pos2=max(0,$pos-250);
					$summary=(($pos2==0)?'':$entity).xhtml_substr($_temp_summary,$pos2,500).$entity;
				}*/
				$GLOBALS['OVERRIDE_SELF_ZONE']=$zone;
				$backup_search__contents_bits=$SEARCH__CONTENT_BITS;
				$SEARCH__CONTENT_BITS=NULL; // We do not want highlighting, as it'll result in far too much Comcode being parsed (ok for short snippets, not many full pages!)
				$temp_summary=request_page($page,true,$zone,strpos($comcode_file,'/comcode_custom/')?'comcode_custom':'comcode',true);
				$SEARCH__CONTENT_BITS=$backup_search__contents_bits;
				$GLOBALS['OVERRIDE_SELF_ZONE']=NULL;
				$LAX_COMCODE=false;
				$_temp_summary=$temp_summary->evaluate();
				global $PAGES_CACHE;
				$PAGES_CACHE=array(); // Decache this, or we'll eat up a tonne of RAM

				$summary=generate_text_summary($_temp_summary,is_null($SEARCH__CONTENT_BITS)?array():$SEARCH__CONTENT_BITS);
			}
		}

		require_lang('comcode');
		$title=do_lang_tempcode('_SEARCH_RESULT_COMCODE_PAGE',escape_html($page));
		global $LAST_COMCODE_PARSED_TITLE;

		if ($LAST_COMCODE_PARSED_TITLE!='')
			$title=do_lang_tempcode('_SEARCH_RESULT_COMCODE_PAGE_NICE',$LAST_COMCODE_PARSED_TITLE);

		$breadcrumbs=comcode_breadcrumbs($page,$zone);

		return do_template('COMCODE_PAGE_BOX',array(
			'_GUID'=>'79cd9e7d0b63ee916c4cd74b26c2f652',
			'TITLE'=>$title,
			'BREADCRUMBS'=>$breadcrumbs,
			'PAGE'=>$page,
			'ZONE'=>$zone,
			'URL'=>$url,
			'SUMMARY'=>$summary,
			'GIVE_CONTEXT'=>true,
		));
	}

}


