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
 * @package		core_rich_media
 */

class Hook_media_rendering_image_websafe
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
		return MEDIA_TYPE_IMAGE;
	}

	/**
	 * See if we can recognise this mime type.
	 *
	 * @param  ID_TEXT	The mime type
	 * @return integer	Recognition precedence
	 */
	function recognises_mime_type($mime_type)
	{
		if ($mime_type=='image/png') return MEDIA_RECOG_PRECEDENCE_HIGH;
		if ($mime_type=='image/gif') return MEDIA_RECOG_PRECEDENCE_HIGH;
		if ($mime_type=='image/jpeg') return MEDIA_RECOG_PRECEDENCE_HIGH;
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
		return MEDIA_RECOG_PRECEDENCE_NONE;
	}

	/**
	 * Provide code to display what is at the URL, in the most appropriate way.
	 *
	 * @param  mixed		URL to render
	 * @param  mixed		URL to render (no sessions etc)
	 * @param  array		Attributes (e.g. width, height, length)
	 * @param  boolean	Whether there are admin privileges, to render dangerous media types
	 * @param  ?MEMBER	Member to run as (NULL: current member)
	 * @param  ?URLPATH	Direct URL (not via a script) (NULL: just use the normal URL)
	 * @return tempcode	Rendered version
	 */
	function render($url,$url_safe,$attributes,$as_admin=false,$source_member=NULL,$url_direct_filesystem=NULL)
	{
		$_url=is_object($url)?$url->evaluate():$url;
		$_url_safe=is_object($url_safe)?$url_safe->evaluate():$url_safe;
		if ($url_direct_filesystem===NULL) $url_direct_filesystem=$_url;

		// Put in defaults
		$blank_thumbnail=(!array_key_exists('thumb_url',$attributes)) || ((is_object($attributes['thumb_url'])) && ($attributes['thumb_url']->is_empty()) || (is_string($attributes['thumb_url'])) && ($attributes['thumb_url']==''));
		if ((!array_key_exists('width',$attributes)) || (!is_numeric($attributes['width'])))
		{
			if ($blank_thumbnail)
				$attributes['width']=get_option('thumb_width');
			// else: media_renderer will derive from the provided thumbnail
			$auto_width=true;
		} else $auto_width=false;
		if ((!array_key_exists('height',$attributes)) || (!is_numeric($attributes['height'])))
		{
			if ($blank_thumbnail)
				$attributes['height']=get_option('thumb_width');
			// else: media_renderer will derive from the provided thumbnail
			$auto_height=true;
		} else $auto_height=false;
		$use_thumb=(!array_key_exists('thumb',$attributes)) || ($attributes['thumb']=='1');
		if ((!$use_thumb) || (!function_exists('imagetypes')))
		{
			$attributes['thumb_url']=$url;
		}
		if ($blank_thumbnail)
		{
			if ($use_thumb)
			{
				$new_name=$attributes['width'].'__'.url_to_filename($_url_safe);
				require_code('images');
				if (!is_saveable_image($new_name)) $new_name.='.png';
				$file_thumb=get_custom_file_base().'/uploads/auto_thumbs/'.$new_name;
				if (function_exists('imagecreatefromstring'))
				{
					if (!file_exists($file_thumb))
					{
						convert_image($url_direct_filesystem,$file_thumb,-1,-1,intval($attributes['width']),false);
					}
					$attributes['thumb_url']=get_custom_base_url().'/uploads/auto_thumbs/'.rawurlencode($new_name);
					if (($auto_width) || ($auto_height))
					{
						if (function_exists('getimagesize'))
						{
							$size=@getimagesize($file_thumb);
							if ($size!==false)
							{
								list($width,$height)=$size;
								if ($auto_width) $attributes['width']=strval($width);
								if ($auto_height) $attributes['height']=strval($height);
							}
						}
					}
				}
			} else
			{
				if ((function_exists('getimagesize')) && (($auto_width) || ($auto_height)))
				{
					require_code('images');
					list($_width,$_height)=_symbol_image_dims(array($_url));
					if ($auto_width) $attributes['width']=$_width;
					if ($auto_height) $attributes['height']=$_height;
				}
			}
		}

		return do_template('MEDIA_IMAGE_WEBSAFE',array('_GUID'=>'4dbc2c00dd049f9951c27d198065a4c2','HOOK'=>'image_websafe')+_create_media_template_parameters($url,$attributes,$as_admin,$source_member));
	}
}
