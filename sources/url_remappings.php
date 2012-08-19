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
 * Find the list of URL remappings
 *
 * @param  boolean		Whether to use the old URL remapping style
 * @return array			The list of URL remappings
 */
function get_remappings($old_style=false)
{
	// The target mapping... upper case means variable substitution, lower case means constant-string
	// The source mapping... NULL means 'anything' (we'll use it in a variable substitution), else we require a certain value
	// These have to be in longest to shortest number of bindings order, to reduce the potential for &'d attributes
	if ($old_style)
	{
		return array(
			array(array('page'=>'wiki','type'=>'misc','id'=>NULL),'pg/s/ID',false),
			array(array('page'=>'galleries','type'=>'image','id'=>NULL,'wide'=>1),'pg/galleries/image/ID',false),
			array(array('page'=>'galleries','type'=>'video','id'=>NULL,'wide'=>1),'pg/galleries/video/ID',false),
			array(array('page'=>'iotds','type'=>'view','id'=>NULL,'wide'=>1),'pg/iotds/view/ID',false),
			array(array('page'=>'blogs','news_filter'=>NULL),'pg/blogs/view/NEWS_FILTER',false),
			array(array('page'=>NULL,'type'=>NULL,'id'=>NULL),'pg/PAGE/TYPE/ID',false),
			array(array('page'=>NULL,'type'=>NULL),'pg/PAGE/TYPE',false),
			array(array('page'=>NULL),'pg/PAGE',false),
			array(array('page'=>''),'pg',false),
			array(array(),'',true),
		);
	} else
	{
		return array(
			array(array('page'=>'wiki','type'=>'misc','id'=>NULL),'s/ID.htm',false),
			array(array('page'=>'galleries','type'=>'image','id'=>NULL,'wide'=>1),'galleries/image/ID.htm',false),
			array(array('page'=>'galleries','type'=>'video','id'=>NULL,'wide'=>1),'galleries/video/ID.htm',false),
			array(array('page'=>'iotds','type'=>'view','id'=>NULL,'wide'=>1),'iotds/view/ID.htm',false),
			array(array('page'=>'blogs','news_filter'=>NULL),'blogs/view/NEWS_FILTER.htm',false),
			array(array('page'=>NULL,'type'=>NULL,'id'=>NULL),'PAGE/TYPE/ID.htm',false),
			array(array('page'=>NULL,'type'=>NULL),'PAGE/TYPE.htm',false),
			array(array('page'=>NULL),'PAGE.htm',false),
			array(array('page'=>''),'',false),
			array(array(),'',true),
		);
	}
}


