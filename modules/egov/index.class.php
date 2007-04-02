<?php
/**
 * E-Government
 * @copyright   CONTREXX CMS - ASTALAVISTA IT AG
 * @author      Astalavista Development Team <thun@astalvista.ch>
 * @version     1.0.0
 * @package     contrexx
 * @subpackage  module_egov
 * @todo        Edit PHP DocBlocks!
 */

$_ARRAYLANG["TXT_EGOV_PAYMENT_NOT_VALID"] = 'Zahlung fehlgeschlagen.';
$_ARRAYLANG['TXT_EGOV_WORLDPAY_CANCEL'] = 'Worldpayzahlung wurde abgebrochen.';

/**
 * Includes
 */
require_once dirname(__FILE__).'/lib/eGovLibrary.class.php';
require_once dirname(__FILE__).'/lib/paypal.class.php';
/**
 * E-Government
 * @copyright   CONTREXX CMS - ASTALAVISTA IT AG
 * @author      Astalavista Development Team <thun@astalvista.ch>
 * @access      public
 * @version     1.0.0
 * @package     contrexx
 * @subpackage  module_egov
 */
class eGov extends eGovLibrary
{
	var $_arrFormFieldTypes;
	var $arrCheckTypes;

	function eGov($pageContent)
    {
    	$this->initContactForms();
    	$this->initCheckTypes();
	    $this->__construct($pageContent);

	}

	function __construct($pageContent)
	{
		$this->pageContent = $pageContent;
		$this->objTemplate = &new HTML_Template_Sigma('.');
		$this->objTemplate->setErrorHandling(PEAR_ERROR_DIE);
	    $this->objTemplate->setTemplate($this->pageContent, true, true);
	}

	function getPage()
	{
		switch($_GET['cmd']){
    		case 'detail':
				$this->_ProductDetail();
    		break;
    		case 'ipncheck':
    			require_once ASCMS_LIBRARY_PATH."/paypal/paypal.class.php";
				$PaymentObj = new PayPal();
				$PaymentObj->ipnCheck();
					
    		break;
    		default:
                $this->_ProductsList();
    	}
    	return $this->objTemplate->get();
	}


	function _saveOrder(){
		global $objDatabase, $_ARRAYLANG;

		$product_id 	= intval($_REQUEST["id"]);
		$datum_db 		= date("Y")."-".date("m")."-".date("d")." ".date("H").":".date("i").":".date("s");
		$ip_adress 		= $_SERVER['REMOTE_ADDR'];
		
		// ------------------------------------------------------
		// Payment
		// ------------------------------------------------------
		$Payment = $this->GetProduktValue("product_paymant", $product_id);
		$Payment = intval($_REQUEST["payment"]);

		
		if($Payment > 0){
			
			// -------------------------------------------------------------
			// Paypal
			// -------------------------------------------------------------
			if($Payment==1){

				if($this->GetProduktValue("product_per_day", $product_id)=="yes"){
					$Addname 	= ' '.$_REQUEST["contactFormField_1000"];
				}
				
				$FormFields		= 'id='.$product_id.'&send=exe&';
				$arrFields = $this->getFormFields($product_id);
				$FormValue = '';
				foreach ($arrFields as $fieldId => $arrField) {
					$FormFields .= 'contactFormField_'.$fieldId.'='.strip_tags(contrexx_addslashes($_REQUEST["contactFormField_".$fieldId])).'&';
				}
				if($this->GetProduktValue("product_per_day", $product_id)=="yes"){
					$FormFields .= 'contactFormField_1000='.$_REQUEST["contactFormField_1000"]."&";
					$FormFields .= 'contactFormField_Quantity='.$_REQUEST["contactFormField_Quantity"]."";
				}
				
				$query 		= "INSERT INTO ".DBPREFIX."module_egov_paypal SET paypal_status=0 ";
				$objDatabase->Execute($query);
				$paypal_id 	= $objDatabase->Insert_ID();
				
				require_once ASCMS_LIBRARY_PATH."/paypal/paypal.class.php";
				$PaymentObj 				= new PayPal();
				$PaymentObj->orderid 		= '';
				$PaymentObj->business 		= $this->GetSettings("set_paypal_email");
				$PaymentObj->item_name		= $this->GetProduktValue('product_name', $product_id).$Addname;
				$PaymentObj->currency_code	= $this->GetSettings("set_paypal_currency");
				$PaymentObj->amount			= $this->GetProduktValue('product_price', $product_id);
				$PaymentObj->quantity		= ($this->GetProduktValue("product_per_day", $product_id)=="yes") ? $_REQUEST["contactFormField_Quantity"] : 1;
				$PaymentObj->host			= ASCMS_PROTOCOL."://".$_SERVER['HTTP_HOST'].ASCMS_PATH_OFFSET;
				$PaymentObj->return			= $PaymentObj->host.'/index.php?section=egov&payment=success&id='.$product_id.'&'.$FormFields.'&paypal_id='.$paypal_id.'&code='.md5($paypal_id."success");
				$PaymentObj->cancel_return	= $PaymentObj->host.'/index.php?section=egov&cmd=detail&id='.$product_id.'&'.$FormFields.'&payment=cancel&paypal_id='.$paypal_id;
				$PaymentObj->notify_url		= $PaymentObj->host.'/index.php?section=egov&cmd=ipncheck&id='.$product_id.'&'.$FormFields.'&payment=ipncheck&paypal_id='.$paypal_id;
				echo($PaymentObj->GetPaypal());
			}
			
			// -------------------------------------------------------------
			// Worldpay
			// -------------------------------------------------------------
			if($Payment==2){
				
				if($this->GetProduktValue("product_per_day", $product_id)=="yes"){
					$Addname 	= ' '.$_REQUEST["contactFormField_1000"];
				}
				
				require_once ASCMS_LIBRARY_PATH."/worldpay/worldpay.class.php";
				$PaymentObj = new worldpay();
				$PaymentObj->instId 		= $this->GetSettings("set_worldpay_id");
				$PaymentObj->cartId 		= "12345";
				$PaymentObj->amount 		= $this->GetProduktValue('product_price', $product_id);
				$PaymentObj->currency 		= $this->GetSettings("set_worldpay_currency");
				$PaymentObj->desc 			= $this->GetProduktValue('product_name', $product_id).$Addname;
				$PaymentObj->lang 			= $this->GetSettings("set_worldpay_lang");
				echo($PaymentObj->GetWorldPay());
			}
			
		}else{
		
			$Order = true;
			if(isset($_REQUEST["payment"])){
				if($_REQUEST["code"]!=md5($_REQUEST["paypal_id"]."success")){
					$Order 			= false;
					$ReturnValue 	= 'alert('.$_ARRAYLANG["TXT_EGOV_PAYMENT_NOT_VALID"].');'.chr(10);
				}
			}
			if(isset($_REQUEST["transStatus"])){
				if($_REQUEST["transStatus"]!="Y"){
					$Order 			= false;
					$ReturnValue 	= 'alert('.$_ARRAYLANG["TXT_EGOV_PAYMENT_NOT_VALID"].');'.chr(10);
				}
			}
			
			
			if($Order){
		
				
				
				$arrFields = $this->getFormFields($product_id);
				$FormValue = '';
				$FormValue4Mail = '';
				foreach ($arrFields as $fieldId => $arrField) {
					$FormValue .= $arrField['name'].'::'.strip_tags(contrexx_addslashes($_REQUEST["contactFormField_".$fieldId])).';;';
					$FormValue4Mail .= html_entity_decode($arrField['name']).': '.html_entity_decode($_REQUEST["contactFormField_".$fieldId]).chr(10);
				}
		
				if($this->GetProduktValue("product_per_day", $product_id)=="yes"){
					$FormValue = $this->GetSettings("set_calendar_date_label")."::".strip_tags(contrexx_addslashes($_REQUEST["contactFormField_1000"])).";;".$FormValue;
					$FormValue = $_ARRAYLANG['TXT_EGOV_QUANTITY']."::".strip_tags(contrexx_addslashes($_REQUEST["contactFormField_Quantity"])).";;".$FormValue;
					$FormValue4Mail = html_entity_decode($this->GetSettings("set_calendar_date_label")).": ".$_REQUEST["contactFormField_1000"].chr(10).$FormValue4Mail;
					$FormValue4Mail = $_ARRAYLANG['TXT_EGOV_QUANTITY'].": ".$_REQUEST["contactFormField_Quantity"].chr(10).$FormValue4Mail;
				}
		
				$objDatabase->Execute("INSERT INTO ".DBPREFIX."module_egov_orders
							(`order_date`, `order_ip`,`order_product`, `order_values`)
							 VALUES ('".$datum_db."', '".$ip_adress."', '".$product_id."', '".$FormValue."')");
				$order_id = $objDatabase->Insert_ID();
		
		
				if($this->GetProduktValue("product_per_day", $product_id)=="yes"){
					list ($calD, $calM, $calY) = split('[.]', $_REQUEST["contactFormField_1000"]);
		
					for($x=1; $x<=intval($_REQUEST["contactFormField_Quantity"]); $x++){
						$objDatabase->Execute("INSERT INTO ".DBPREFIX."module_egov_product_calendar
						(`calendar_product`, `calendar_order`,`calendar_day`, `calendar_month`, `calendar_year`)
						 VALUES ('".$product_id."', '".$order_id."', '".$calD."', '".$calM."', '".$calY."')");
					}
				}
		
				if($this->GetProduktValue("product_message", $product_id)!=""){
					$AlertMessageTxt = html_entity_decode($this->GetProduktValue("product_message", $product_id));
					$ReturnValue = 'alert("'.$AlertMessageTxt.'");'.chr(10);
				}
				if($this->GetProduktValue("product_target_url", $product_id)!=""){
					$ReturnValue .= 'document.location.href="'.$this->GetProduktValue("product_target_url", $product_id).'";'.chr(10);
				}
		
				// Bestelleingang-Benachrichtigung || Mail f�r den Administrator
				// -------------------------------------------------------------------
				if($this->GetProduktValue("product_target_email", $product_id)!=""){
		
					$recipient = $this->GetProduktValue("product_target_email", $product_id);
					if($recipient==''){
						$recipient = $this->GetSettings("set_orderentry_recipient");
					}
		
					$SubjectText = str_replace("[[PRODUCT_NAME]]", html_entity_decode($this->GetProduktValue("product_name", $product_id)), $this->GetSettings("set_orderentry_subject"));
					$SubjectText = html_entity_decode($SubjectText);
		
					$BodyText = str_replace("[[ORDER_VALUE]]", $FormValue4Mail, $this->GetSettings("set_orderentry_email"));
					$BodyText = html_entity_decode($BodyText);
		
					$replyAddress = $this->GetEmailAdress($order_id);
					if (empty($replyAddress)) {
						$replyAddress = $this->GetSettings("set_orderentry_sender");
					}
		
					if (@include_once ASCMS_LIBRARY_PATH.'/phpmailer/class.phpmailer.php') {
						$objMail = new phpmailer();
						$objMail->From 		= $this->GetSettings("set_orderentry_sender");
						$objMail->FromName 	= $this->GetSettings("set_orderentry_name");
						$objMail->AddReplyTo($replyAddress);
						$objMail->Subject 	= $SubjectText;
						$objMail->Priority 	= 3;
						$objMail->IsHTML(false);
						$objMail->Body 		= $BodyText;
						$objMail->AddAddress($recipient);
						$objMail->Send();
					}
				}
		
				// Update 29.10.2006 Statusmail automatisch abschicken || Produktdatei
				// -------------------------------------------------------------------
				if($this->GetProduktValue('product_electro', $product_id)==1){
		
					$query = "UPDATE ".DBPREFIX."module_egov_orders
									 SET order_state=1
									 WHERE order_id=".$order_id."";
					$objDatabase->Execute($query);
		
					$query = "UPDATE ".DBPREFIX."module_egov_product_calendar
									 SET calendar_act=1
									 WHERE calendar_order=".$order_id."";
					$objDatabase->Execute($query);
		
					$TargetMail = $this->GetEmailAdress($order_id);
		
					$FromEmail 	= $this->GetProduktValue("product_sender_email", $product_id);
					if($FromEmail==''){
						$FromEmail = $this->GetSettings("set_sender_email");
					}
		
					$FromName 	= $this->GetProduktValue("product_sender_name", $product_id);
					if($FromName==''){
						$FromName = $this->GetSettings("set_sender_name");
					}
		
					$SubjectDB = $this->GetProduktValue("product_target_subject", $product_id);
					if($SubjectDB == ''){
						$SubjectDB = $this->GetSettings("set_state_subject");
					}
		
					$SubjectText = str_replace("[[PRODUCT_NAME]]", html_entity_decode($this->GetProduktValue("product_name", $product_id)), $SubjectDB);
					$SubjectText = html_entity_decode($SubjectText);
		
					$BodyDB = $this->GetProduktValue("product_target_body", $product_id);
					if($BodyDB == ''){
						$BodyDB = $this->GetSettings("set_state_email");
					}
		
					$BodyText = str_replace("[[ORDER_VALUE]]", $FormValue4Mail, $BodyDB);
					$BodyText = str_replace("[[PRODUCT_NAME]]", html_entity_decode($this->GetProduktValue("product_name", $product_id)), $BodyText);
					$BodyText = html_entity_decode($BodyText);
		
					if($TargetMail!=''){
						if (@include_once ASCMS_LIBRARY_PATH.'/phpmailer/class.phpmailer.php') {
							$objMail = new phpmailer();
							$objMail->From 		= $FromEmail;
							$objMail->FromName 	= $FromName;
							$objMail->AddReplyTo($FromEmail);
							$objMail->Subject 	= $SubjectText;
							$objMail->Priority 	= 3;
							$objMail->IsHTML(false);
							$objMail->AddAttachment(ASCMS_PATH.$this->GetProduktValue("product_file", $product_id));
							$objMail->Body 		= $BodyText;
							$objMail->AddAddress($TargetMail);
							$objMail->Send();
		
						}
					}
		
				}elseif ($this->GetProduktValue('product_autostatus', $product_id)==1){
		
					$query = "UPDATE ".DBPREFIX."module_egov_orders
									 SET order_state=1
									 WHERE order_id=".$order_id."";
					$objDatabase->Execute($query);
		
					$query = "UPDATE ".DBPREFIX."module_egov_product_calendar
									 SET calendar_act=1
									 WHERE calendar_order=".$order_id."";
					$objDatabase->Execute($query);
		
					$SubjectDB = $this->GetProduktValue("product_target_subject", $product_id);
					if($SubjectDB == ''){
						$SubjectDB = $this->GetSettings("set_state_subject");
					}
		
					$SubjectText = str_replace("[[PRODUCT_NAME]]", html_entity_decode($this->GetProduktValue("product_name", $product_id)), $SubjectDB);
					$SubjectText = html_entity_decode($SubjectText);
		
					$BodyDB = $this->GetProduktValue("product_target_body", $product_id);
					if($BodyDB == ''){
						$BodyDB = $this->GetSettings("set_state_email");
					}
		
					$BodyText = str_replace("[[ORDER_VALUE]]", $FormValue4Mail, $BodyDB);
					$BodyText = str_replace("[[PRODUCT_NAME]]", html_entity_decode($this->GetProduktValue("product_name", $product_id)), $BodyText);
					$BodyText = html_entity_decode($BodyText);
		
					$FromEmail 	= $this->GetProduktValue("product_sender_email", $product_id);
					if($FromEmail==''){
						$FromEmail = $this->GetSettings("set_sender_email");
					}
		
					$FromName 	= $this->GetProduktValue("product_sender_name", $product_id);
					if($FromName==''){
						$FromName = $this->GetSettings("set_sender_name");
					}
		
					$TargetMail = $this->GetEmailAdress($order_id);
					if($TargetMail!=''){
						if (@include_once ASCMS_LIBRARY_PATH.'/phpmailer/class.phpmailer.php') {
							$objMail = new phpmailer();
							$objMail->From 		= $FromEmail;
							$objMail->FromName 	= $FromName;
							$objMail->AddReplyTo($FromEmail);
							$objMail->Subject 	= $SubjectText;
							$objMail->Priority 	= 3;
							$objMail->IsHTML(false);
							$objMail->Body 		= $BodyText;
							$objMail->AddAddress($TargetMail);
							$objMail->Send();
						}
					}
				}
				
				$ReturnValue .= 'history.go(-1);'.chr(10);
				$ReturnValue .= 'document.location.href="'.$_SERVER['PHP_SELF'].'?section=egov";'.chr(10);
			}
		
		}
		return $ReturnValue;

	}

	function _ProductsList()
	{
		global $objDatabase, $_ARRAYLANG, $_CONFIG;
		if($_REQUEST["send"]=="exe"){
			$SaveReturn = '<script language="JavaScript" type="text/javascript">'.chr(10);
			$SaveReturn .= '<!--'.chr(10);
			$SaveReturn .= $this->_saveOrder();
			$SaveReturn .= '// -->'.chr(10);
			$SaveReturn .= '</script>'.chr(10);
		}else{
			$SaveReturn = '';
		}

		$query = "SELECT product_id, product_name, product_desc
			       FROM ".DBPREFIX."module_egov_products where product_status=1 order by product_orderby, product_name";
		$objResult = $objDatabase->Execute($query );

		$this->objTemplate->setVariable(array(
				'EGOV_JS' 		        => $SaveReturn,
			));

		if($objResult !== false){
			while (!$objResult->EOF){
				$this->objTemplate->setVariable(array(
					'EGOV_PRODUCT_TITLE' 		        => $objResult->fields['product_name'],
					'EGOV_PRODUCT_ID'		            => $objResult->fields['product_id'],
					'EGOV_PRODUCT_DESC'					=> $objResult->fields['product_desc'],
					'EGOV_PRODUCT_LINK'				    => "index.php?section=egov&cmd=detail&id=".$objResult->fields['product_id']
				));
				$this->objTemplate->parse('egovProducts');
				$objResult->MoveNext();
			}
		}else{
			$this->objTemplate->hideBlock('egovProducts');
		}

	}

	function _ProductDetail()
	{
		global $objDatabase, $_ARRAYLANG, $_CONFIG;
		
		if(intval($_REQUEST["id"])){
			$query = "SELECT product_id, product_name, product_desc, product_price, product_per_day, product_quantity, product_target_email, product_target_url, product_message
			          FROM ".DBPREFIX."module_egov_products
			          WHERE product_id=".$_REQUEST["id"]."";
			$objResult = $objDatabase->Execute($query);
			
			if(isset($_REQUEST["payment"])){
				if($_REQUEST["payment"]=="cancel"){
					$ReturnValue = 'alert("'.$_ARRAYLANG['TXT_EGOV_PAYPAL_CANCEL'].'");'.chr(10);
				}
				if($_REQUEST["transStatus"]=="C"){
					$ReturnValue = 'alert("'.$_ARRAYLANG['TXT_EGOV_WORLDPAY_CANCEL'].'");'.chr(10);
				}
				$Return = chr(10).chr(10).'<script language="JavaScript" type="text/javascript">'.chr(10);
				$Return .= '<!--'.chr(10);
				$Return .= $ReturnValue;
				$Return .= '// -->'.chr(10);
				$Return .= '</script>'.chr(10);
				
				$AddSource = $Return;
			}
			
			if ($objResult->RecordCount() != 0) {
				$product_id = $objResult->fields['product_id'];
				$FormSource = $this->getSourceCode($product_id);
				$this->objTemplate->setVariable(array(
					'EGOV_PRODUCT_TITLE'				=> $objResult->fields['product_name'],
					'EGOV_PRODUCT_ID'					=> $objResult->fields['product_id'],
					'EGOV_PRODUCT_DESC'					=> $objResult->fields['product_desc'],
					'EGOV_PRODUCT_PRICE'				=> $objResult->fields['product_price'],
					'EGOV_FORM'							=> $FormSource.$AddSource,
				));
			}

			if($this->objTemplate->blockExists('egov_price')){
				if(intval($objResult->fields['product_price']) > 0){
					$this->objTemplate->touchBlock('egov_price');
				}else{
					$this->objTemplate->hideBlock('egov_price');
				}
			}
		}
	}



}
?>
