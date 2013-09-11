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
 * @package		core_rich_media
 */

class Hook_media_rendering_vimeo
{
	/**
	 * Get the label for this media rendering type.
	 *
	 * @return string		The label
	 */
	function get_type_label()
	{
		require_lang('comcode');
		return do_lang('MEDIA_TYPE_'.preg_replace('#^Hook_media_rendering_#','',__CLASS__));
	}

	/**
	 * Find the media types this hook serves.
	 *
	 * @return integer	The media type(s), as a bitmask
	 */
	function get_media_type()
	{
		return MEDIA_TYPE_VIDEO;
	}

	/**
	 * See if we can recognise this mime type.
	 *
	 * @param  ID_TEXT	The mime type
	 * @return integer	Recognition precedence
	 */
	function recognises_mime_type($mime_type)
	{
		return MEDIA_RECOG_PRECEDENCE_NONE;
	}

	/**
	 * See if we can recognise this URL pattern.
	 *
	 * @param  URLPATH	URL to pattern match
	 * @return integer	Recognition precedence
	 */
	function recognises_url($url)
	{
		if (preg_match('#^https?://vimeo\.com/(\d+)#',$url)!=0) return MEDIA_RECOG_PRECEDENCE_HIGH;
		return MEDIA_RECOG_PRECEDENCE_NONE;
	}

	/**
	 * If we can handle this URL, get the thumbnail URL.
	 *
	 * @param  URLPATH		Video URL
	 * @return ?string		The thumbnail URL (NULL: no match).
	 */
	function get_video_thumbnail($src_url)
	{
		$matches=array();
		if (preg_match('#^https?://vimeo\.com/(\d+)#',$src_url,$matches)!=0)
		{
			$test=get_long_value('vimeo_thumb_for__'.$matches[1]);
			if ($test!==NULL) return $test;

			// Vimeo API method
			if (is_file(get_file_base().'/sources_custom/gallery_syndication.php'))
			{
				require_code('hooks/modules/video_syndication/vimeo');
				$ob=object_factory('video_syndication_vimeo');
				if ($ob->is_active())
				{
					$result=$ob->get_remote_videos(NULL,$matches[1]);
					if (count($result)!=0)
					{
						foreach ($result as $r)
						{
							return $r['thumb_url'];
						}
					}
					return NULL;
				}
			}

			// Lame method (not so reliable)
			$html=http_download_file($src_url,NULL,false);
			if (is_null($html)) return NULL;
			$matches2=array();
			if (preg_match('#<meta property="og:image" content="([^"]+)"#',$html,$matches2)!=0)
			{
				//set_long_value('vimeo_thumb_for__'.$matches[1],$matches2[1]);		Actually this only happens occasionally (on add/edit), so not needed. Caching would bung up DB and make editing a pain.
				return $matches2[1];
			}
		}
		return NULL;
	}

	/**
	 * Provide code to display what is at the URL, in the most appropriate way.
	 *
	 * @param  mixed		URL to render
	 * @param  mixed		URL to render (no sessions etc)
	 * @param  array		Attributes (e.g. width, height, length)
	 * @param  boolean	Whether there are admin privileges, to render dangerous media types
	 * @param  ?MEMBER	Member to run as (NULL: current member)
	 * @return tempcode	Rendered version
	 */
	function render($url,$url_safe,$attributes,$as_admin=false,$source_member=NULL)
	{
		if (is_object($url)) $url=$url->evaluate();
		$url=preg_replace('#^https?://vimeo\.com/(\d+)#','${1}',$url);
		return do_template('MEDIA_VIMEO',array('HOOK'=>'vimeo')+_create_media_template_parameters($url,$attributes,$as_admin,$source_member));
	}

}
