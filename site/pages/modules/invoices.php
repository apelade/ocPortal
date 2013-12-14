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
 * @package		ecommerce
 */

/**
 * Module page class.
 */
class Module_invoices
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
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_if_exists('invoices');
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		$GLOBALS['SITE_DB']->create_table('invoices',array(
			'id'=>'*AUTO', // linked to IPN with this
			'i_type_code'=>'ID_TEXT',
			'i_member_id'=>'USER',
			'i_state'=>'ID_TEXT', // new|pending|paid|delivered (pending means payment has been requested)
			'i_amount'=>'SHORT_TEXT', // can't always find this from i_type_code
			'i_special'=>'SHORT_TEXT', // depending on i_type_code, would trigger something special such as a key upgrade
			'i_time'=>'TIME',
			'i_note'=>'LONG_TEXT'
		));
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return ((is_guest()) || ($GLOBALS['SITE_DB']->query_value('invoices','COUNT(*)',array('i_member_id'=>get_member()))==0))?array():array('misc'=>'MY_INVOICES');
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		require_lang('ecommerce');
		require_code('ecommerce');
		require_css('ecommerce');

		// Kill switch
		if ((ecommerce_test_mode()) && (!$GLOBALS['IS_ACTUALLY_ADMIN']) && (!has_specific_permission(get_member(),'access_ecommerce_in_test_mode'))) warn_exit(do_lang_tempcode('PURCHASE_DISABLED'));

		if (is_guest()) access_denied('NOT_AS_GUEST');

		$type=get_param('type','misc');

		if ($type=='misc') return $this->my();
		if ($type=='pay') return $this->pay();
		return new ocp_tempcode();
	}

	/**
	 * Show my invoices.
	 *
	 * @return tempcode	The interface.
	 */
	function my()
	{
		$title=get_page_title('MY_INVOICES');

		$member_id=get_member();
		if (has_specific_permission(get_member(),'assume_any_member')) $member_id=get_param_integer('id',$member_id);

		$invoices=array();
		$rows=$GLOBALS['SITE_DB']->query_select('invoices',array('*'),array('i_member_id'=>$member_id));
		foreach ($rows as $row)
		{
			$product=$row['i_type_code'];
			$object=find_product($product);
			if (is_null($object)) continue;
			$products=$object->get_products(false,$product);

			$invoice_title=$products[$product][4];
			$time=get_timezoned_date($row['i_time'],true,false,false,true);
			$payable=($row['i_state']=='new');
			$deliverable=($row['i_state']=='paid');
			$state=do_lang('PAYMENT_STATE_'.$row['i_state']);
			if (perform_local_payment())
			{
				$transaction_button=hyperlink(build_url(array('page'=>'_SELF','type'=>'pay','id'=>$row['id']),'_SELF'),do_lang_tempcode('MAKE_PAYMENT'));
			} else
			{
				$transaction_button=make_transaction_button(substr(get_class($object),5),$invoice_title,strval($row['id']),floatval($row['i_amount']),get_option('currency'));
			}
			$invoices[]=array('TRANSACTION_BUTTON'=>$transaction_button,'INVOICE_TITLE'=>$invoice_title,'ID'=>strval($row['id']),'AMOUNT'=>$row['i_amount'],'TIME'=>$time,'STATE'=>$state,'DELIVERABLE'=>$deliverable,'PAYABLE'=>$payable,'NOTE'=>$row['i_note'],'TYPE_CODE'=>$row['i_type_code']);
		}
		if (count($invoices)==0) inform_exit(do_lang_tempcode('NO_ENTRIES'));

		return do_template('ECOM_INVOICES_SCREEN',array('_GUID'=>'144a893d93090c105eecc48fa58921a7','TITLE'=>$title,'CURRENCY'=>get_option('currency'),'INVOICES'=>$invoices));
	}

	/**
	 * Show my invoices.
	 *
	 * @return tempcode	The interface.
	 */
	function pay()
	{
		$id=get_param_integer('id');

		if (((ocp_srv('HTTPS')=='') || (ocp_srv('HTTPS')=='off')) && (!ecommerce_test_mode()))
		{
			warn_exit(do_lang_tempcode('NO_SSL_SETUP'));
		}

		$title=get_page_title('MAKE_PAYMENT');

		$post_url=build_url(array('page'=>'purchase','type'=>'finish'),get_module_zone('purchase'));

		$rows=$GLOBALS['SITE_DB']->query_select('invoices',array('*'),array('id'=>$id),'',1);
		if (!array_key_exists(0,$rows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$row=$rows[0];
		$product=$row['i_type_code'];
		$object=find_product($product);
		$products=$object->get_products(false,$product);
		$invoice_title=$products[$product][4];

		$fields=get_transaction_form_fields(NULL,strval($id),$invoice_title,float_to_raw_string($row['i_amount']),NULL,'');

		$text=do_lang_tempcode('TRANSACT_INFO');

		return do_template('FORM_SCREEN',array('_GUID'=>'e90a4019b37c8bf5bcb64086416bcfb3','TITLE'=>$title,'SKIP_VALIDATION'=>'1','FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>$text,'HIDDEN'=>'','SUBMIT_NAME'=>do_lang_tempcode('MAKE_PAYMENT')));
	}

}


