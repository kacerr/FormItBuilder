<?php
require_once 'FormItBuilderCore.class.php';

class FormItBuilder extends FormItBuilderCore{
	/**
	 * A reference to the modX instance
	 * @var modX $modx
	 */
	private $modx;

	private $_method;
	private $_id;
	private $_redirectDocument;
	private $_hooks;
	private $_jqueryValidation;
	private $_formElements;
	private $_postHookName;
	private $_headHtml;
	private $_emailFromAddress;
	private $_emailSubject;
	private $_emailToAddress;
	private $_emailFontSize;
	private $_emailFontFamily;
	private $_emailHeadHtml;
	private $_rules;
	private $_emailTpl;
	private $_validate;
	private $_customValidators; 
	private $_databaseTableObjectName;
	private $_databaseTableFieldMapping;
	private $_store;
	private $_placeholderJavascript;
	
	private $_emailFromName;
	private $_emailToName;
	private $_emailReplyToAddress;
	private $_emailReplyToName;
	private $_emailCCAddress;
	private $_emailCCName;
	private $_emailBCCAddress;
	private $_emailBCCName;

	/**
	*
	* @param modX $modx Reference to the core modX object
	* @param string $id Id of the form
	*/
	function __construct(modX &$modx, string $id) {
	    $this->modx = &$modx;
		$this->_method = 'post'; 
		$this->_id = $id;
		$this->_store = 1;
		$this->_formElements=array();
		$this->_rules=array();
		$this->_redirectDocument=$this->modx->resource->get('id');
		$this->_jqueryValidation=false;
		$this->_emailTpl='FormItBuilderEmailTpl';
		
		$this->_emailFontSize='13px';
		$this->_emailFontFamily='Helvetica,Arial,sans-serif';
		
		//test that required snippets are available
		$snippet_formIt = $this->modx->getObject('modSnippet',array('name'=>'FormIt'));
		if($snippet_formIt===NULL){
			FormItBuilder::throwError('FormIt snippet does not appear to be installed. Please install FormIt package.');
		}
	}
	
	public function addRule(FormRule $formRule){
		if(is_a($formRule,'FormRule')===false){
			FormItBuilder::throwError('Form rule "'.$formRule.'" is not a valid FormRule type. Recommend using FormRuleType constants to define rule type.');
		}else{
			$this->_rules[]=$formRule;
		}
	}
	
	public function addRules($rules){
		foreach($rules as $rule){
			$this->addRule($rule);
		}
	}
	
	//getters & setters
	public function getMethod() { return $this->_method; } 
	public function getId() { return $this->_id; } 
	public function getRedirectDocument() { return $this->_redirectDocument; } 
	public function getJqueryValidation() { return $this->_jqueryValidation; } 
	public function getPostHookName() { return $this->_postHookName; }
	public function getEmailFromAddress() { return $this->_emailFromAddress; }
	public function getEmailToAddress() { return $this->_emailToAddress; }
	public function getEmailSubject() { return $this->_emailSubject; }
	public function getEmailHeadHtml() { return $this->_emailHeadHtml; }
	public function getHooks() { return $this->_hooks; }
	public function getEmailTpl() { return $this->_emailTpl; }
	public function getValidate() { return $this->_validate; }
	public function getCustomValidators() { return $this->_customValidators; }
	public function getEmailFromName() { return $this->_emailFromName; }
	public function getEmailToName() { return $this->_emailToName; }
	public function getEmailReplyToAddress() { return $this->_emailReplyToAddress; }
	public function getEmailReplyToName() { return $this->_emailReplyToName; }
	public function getEmailCCAddress() { return $this->_emailCCAddress; }
	public function getEmailCCName() { return $this->_emailCCName; }
	public function getEmailBCCAddress() { return $this->_emailBCCAddress; }
	public function getEmailBCCName() { return $this->_emailBCCName; }
	public function getStore() { return $this->_store; }
	public function getPlaceholderJavascript() { return $this->_placeholderJavascript; } 
	
	public function setMethod($v) { $this->_method = $v; } 
	public function setRedirectDocument($v) { $this->_redirectDocument = $v; } 
	public function setJqueryValidation($v) { $this->_jqueryValidation = self::forceBool($v); }
	public function setPostHookName($v) { $this->_postHookName = $v; }
	public function setEmailFromAddress($v) { $this->_emailFromAddress = $v; }
	public function setEmailToAddress($v) { $this->_emailToAddress = $v; }
	public function setEmailFromName($v) { $this->_emailFromName = $v; }
	public function setEmailToName($v) { $this->_emailToName = $v; }
	public function setEmailReplyToAddress($v) { $this->_emailReplyToAddress = $v; }
	public function setEmailReplyToName($v) { $this->_emailReplyToName = $v; }
	public function setEmailCCAddress($v) { $this->_emailCCAddress = $v; }
	public function setEmailCCName($v) { $this->_emailCCName = $v; }
	public function setEmailBCCAddress($v) { $this->_emailBCCAddress = $v; }
	public function setEmailBCCName($v) { $this->_emailBCCName = $v; }
	public function setEmailSubject($v) { $this->_emailSubject = $v; }
	public function setEmailHeadHtml($v) { $this->_emailHeadHtml = $v; }
	public function setHooks($v){$this->_hooks = self::forceArray($v);}
	public function setEmailTpl($v){$this->_emailTpl = $v;}
	public function setValidate($v) { $this->_validate = $v; }
	public function setCustomValidators($v) { $this->_customValidators = $v; }
	public function setDatabaseObjectForInsert($s_objName,$a_mapping){
		$this->_databaseTableObjectName=$s_objName;
		$this->_databaseTableFieldMapping=$a_mapping;
	}
	public function setStore($v) { $this->_store = $v; }
	public function setPlaceholderJavascript($v) { $this->_placeholderJavascript = $v; }
    
	public function addElement(FormItBuilder_element $o_formElement){
		$this->_formElements[]=$o_formElement;
	}
	public function addElements($a_elements){
		foreach($a_elements as $o_formElement){
			$this->addElement($o_formElement);
		}
	}
	
	private function addToDatabase($s_ObjName,$a_mapping){
		//inspired by http://bobsguides.com/custom-db-tables.html
		$fields = array();
		foreach($a_mapping as $a){
			$o_formObj = $a[0];
			$s_keyName = $a[1];
			
			$fields[$s_keyName]=$_POST[$o_formObj->getId()];
		};
		$newObj = $this->modx->newObject($s_ObjName, $fields);
		$res = $newObj->save();
		if($res===true){
			return true;
		}else{
			return false;
		}
	}
	
	public function processHooks($a_hookCommands){
		//called from the FormItBuilder_hooks snippet. Not intended to be called publically in any other way.
		$i_okCount=0;
		foreach($a_hookCommands as $a_cmd){
			$b_res=false;
			if(isset($a_cmd['name'],$a_cmd['value'])===true){
				switch($a_cmd['name']){
					case 'dbEntry':
						if(isset($a_cmd['value']['tableObj'],$a_cmd['value']['mapping'])===true){
							$b_res = $this->addToDatabase($a_cmd['value']['tableObj'],$a_cmd['value']['mapping']);
						}else{
							FormItBuilder::throwError('FormItBuilder processHooks failed. The tableObj or mapping attributes were not set for "'.$a_cmd['name'].'".');		
						}
						break;
				}
				if($b_res===true){
					$i_okCount++;
				}
			}else{
				FormItBuilder::throwError('FormItBuilder processHooks failed. The name and value pair is not set.');
			}
		}
		if($i_okCount==count($a_hookCommands)){
			return true;
		}else{
			return false;
		}
	}
	
	private function getPostHookString(){
		$NL="\r\n";
		$s_style = 'font-size:'.$this->_emailFontSize.'; font-family:'.$this->_emailFontFamily.';';
		
		$s_ret='<div style="'.$s_style.'">'.$NL.$this->_emailHeadHtml.$NL
		.'<table cellpadding="4" cellspacing="0" style="'.$s_style.'">'.$NL;
		
		$bgCol1="#FFFFFF";
		$bgCol2="#e4edf9";
		$rowCount=0;
		foreach($this->_formElements as $o_el){
			if(get_class($o_el)=='FormItBuilder_htmlBlock'){
				//do nothing
			}else{
				if($o_el->showInEmail()===true){
					
					$bgCol=$bgCol1;
					if($rowCount%2==0){
						$bgCol=$bgCol2;
					}

					$elType=get_class($o_el);
					$elId = $o_el->getId();
					$s_val='[[+'.htmlspecialchars($o_el->getId()).':nl2br]]';
					if($elType=='FormItBuilder_elementFile'){
						if(isset($_FILES[$elId])){
							if($_FILES[$elId]['size']==0){
								$s_val='None';
							}
						}
					}
					
					$s_ret.='<tr valign="top" bgcolor="'.$bgCol.'"><td><b>'.htmlspecialchars($o_el->getLabel()).':</b></td><td>'.$s_val.'</td></tr>'.$NL;
					$rowCount++;
					
				}
			}
		}

		$s_ret.='</table>'.$NL
		.'<p>You can use this link to reply: <a href="mailto:'.htmlspecialchars($this->_emailFromAddress).'?subject=RE:'.htmlspecialchars($this->_emailSubject).'">'.htmlspecialchars($this->_emailFromAddress).'</a></p>'.$NL
		.'</div>';
		return $s_ret;
	}
	public function postHook(){
		return $this->getPostHookString();
	}
	public function postHookRaw(){
		echo $this->getPostHookString();
		exit();
	}
	private function jqueryValidateJSON($jqFieldProps,$jqFieldMessages,$jqFormRules,$jqFormMessages){
		$a_ruleSegs = array();
		$a_msgSegs = array();
		foreach($jqFieldProps as $fieldID=>$a_fieldProp){
			if(count($a_fieldProp)>0){
				$a_ruleSegs[]=$fieldID.':{'.implode(',',$a_fieldProp).'}';
			}
		}
		foreach($jqFieldMessages as $fieldID=>$a_fieldMsg){
			if(count($a_fieldMsg)>0){
				$a_msgSegs[]=$fieldID.':{'.implode(',',$a_fieldMsg).'}';
			}
		}
		$s_js=
		'rules:{  '."\r\n  ".implode(",\r\n  ",$a_ruleSegs)."\r\n".'},'.
		'messages:{  '."\r\n  ".implode(",\r\n  ",$a_msgSegs)."\r\n".'}'
		;
		
		return $s_js;
	}
	
	private function getFormItBuilderOutput(){
		$s_submitVar = 'submitVar_'.$this->_id;
		$b_posted = false;
		if(isset($_REQUEST[$s_submitVar])===true){
			$b_posted=true;
		}
		$nl="\r\n";

		//process and add form rules
		$a_fieldProps=array();
		$a_fieldProps_jqValidate=array();
		$a_fieldProps_errstringFormIt=array();
		$a_fieldProps_errstringJq=array();
		
		$a_formProps=array();
		$a_formProps_custValidate=array();
		$a_formProps_jqValidate=array();
		$a_formPropsFormItErrorStrings=array();
		$a_formPropsJqErrorStrings=array();

		foreach($this->_rules as $rule){
			$o_elFull = $rule->getElement();
			if(is_array($o_elFull)===true){
				$o_el = $o_elFull[0];
			}else{
				$o_el = $o_elFull;
			}
			$elID = $o_el->getId();
			if(isset($a_fieldProps[$elID])===false){
				$a_fieldProps[$elID]=array();
			}
			if(isset($a_fieldProps[$elID])===false){
				$a_fieldProps[$elID]=array();
			}
			if(isset($a_fieldProps_jqValidate[$elID])===false){
				$a_fieldProps_jqValidate[$elID]=array();
			}
			if(isset($a_fieldProps_errstringFormIt[$elID])===false){
				$a_fieldProps_errstringFormIt[$elID]=array();
			}
			if(isset($a_fieldProps_errstringJq[$elID])===false){
				$a_fieldProps_errstringJq[$elID]=array();
			}
			if(isset($a_formProps_custValidate[$elID])===false){
				$a_formProps_custValidate[$elID]=array();
			}
			
			
			switch($rule->getType()){
				case FormRuleType::email:
					$a_fieldProps[$elID][] = 'email';
					$a_fieldProps_jqValidate[$elID][] = 'email:true';
					$a_fieldProps_errstringFormIt[$elID][] = 'vTextEmailInvalid=`'.$rule->getValidationMessage().'`';
					$a_fieldProps_errstringJq[$elID][] = 'email:"'.$rule->getValidationMessage().'"';
					break;
				case FormRuleType::fieldMatch:
					$a_fieldProps[$elID][] = 'password_confirm=^'.$o_elFull[1]->getId().'^';
					$a_fieldProps_jqValidate[$elID][] = 'equalTo:"#'.$o_elFull[1]->getId().'"';
					$a_fieldProps_errstringFormIt[$elID][] = 'vTextPasswordConfirm=`'.$rule->getValidationMessage().'`';
					$a_fieldProps_errstringJq[$elID][] = 'equalTo:"'.$rule->getValidationMessage().'"';
					break;
				case FormRuleType::maximumLength:
					$a_fieldProps[$elID][] = 'maxLength=^'.$rule->getValue().'^';
					$a_fieldProps_jqValidate[$elID][] = 'maxlength:'.$rule->getValue();
					$a_fieldProps_errstringFormIt[$elID][] = 'vTextMaxLength=`'.$rule->getValidationMessage().'`';
					$a_fieldProps_errstringJq[$elID][] = 'maxlength:"'.$rule->getValidationMessage().'"';
					break;
				case FormRuleType::maximumValue:
					$a_fieldProps[$elID][] = 'maxValue=^'.$rule->getValue().'^';
					$a_fieldProps_jqValidate[$elID][] = 'max:'.$rule->getValue();
					$a_fieldProps_errstringFormIt[$elID][] = 'vTextMaxValue=`'.$rule->getValidationMessage().'`';
					$a_fieldProps_errstringJq[$elID][] = 'max:"'.$rule->getValidationMessage().'"';
					break;
				case FormRuleType::minimumLength:
					$a_fieldProps[$elID][] = 'minLength=^'.$rule->getValue().'^';
					$a_fieldProps_jqValidate[$elID][] = 'minlength:'.$rule->getValue();
					$a_fieldProps_errstringFormIt[$elID][] = 'vTextMinLength=`'.$rule->getValidationMessage().'`';
					$a_fieldProps_errstringJq[$elID][] = 'minlength:"'.$rule->getValidationMessage().'"';
					break;
				case FormRuleType::minimumValue:
					$a_fieldProps[$elID][] = 'minValue=^'.$rule->getValue().'^';
					$a_fieldProps_jqValidate[$elID][] = 'min:'.$rule->getValue();
					$a_fieldProps_errstringFormIt[$elID][] = 'vTextMinValue=`'.$rule->getValidationMessage().'`';
					$a_fieldProps_errstringJq[$elID][] = 'min:"'.$rule->getValidationMessage().'"';
					break;
				case FormRuleType::numeric:
					$a_fieldProps[$elID][] = 'isNumber';
					$a_fieldProps_jqValidate[$elID][] = 'digits:true';
					$a_fieldProps_errstringFormIt[$elID][] = 'vTextIsNumber=`'.$rule->getValidationMessage().'`';
					$a_fieldProps_errstringJq[$elID][] = 'digits:"'.$rule->getValidationMessage().'"';
					break;
				case FormRuleType::required:
					$a_fieldProps[$elID][] = 'required';
					$a_fieldProps_jqValidate[$elID][] = 'required:true';
					$a_fieldProps_errstringFormIt[$elID][] = 'vTextRequired=`'.$rule->getValidationMessage().'`';
					$a_fieldProps_errstringJq[$elID][] = 'required:"'.$rule->getValidationMessage().'"';
					break;
				case FormRuleType::date:
					$s_thisVal = $rule->getValue();
					$s_thisErrorMsg = str_replace('===dateformat===',$s_thisVal,$rule->getValidationMessage());
					
					$a_formProps_custValidate[$elID][]=array('type'=>'date','fieldFormat'=>$s_thisVal,'errorMessage'=>$s_thisErrorMsg);
					$a_fieldProps[$elID][] = 'FormItBuilder_customValidation';
					//$a_fieldProps[$elID][] = 'isDate=^'.$rule->getValue().'^';
					$a_fieldProps_jqValidate[$elID][] = 'dateFormat:\''.$s_thisVal.'\'';
					//$a_fieldProps_errstringFormIt[$elID][] = 'vTextIsDate=`'.$s_thisErrorMsg.'`';
					$a_fieldProps_errstringJq[$elID][] = 'dateFormat:"'.$s_thisErrorMsg.'"';
					break;
			}
		}
		//if some custom validation optiosn were found (date etc) then add formItBuilder custom validate snippet to the list
		if(count($a_formProps_custValidate)>0){
			$GLOBALS['FormItBuilder_customValidation']=$a_formProps_custValidate;
			if(empty($this->_customValidators)===false){
				$this->_customValidators.=',';
			}
			$this->_customValidators.='FormItBuilder_customValidation';
		}
		
		//build inner form html
		$b_attachmentIncluded=false;
		$s_form='<div>'.$nl
		.$nl.'<div class="process_errors_wrap"><div class="process_errors">[[!+fi.error_message:notempty=`[[!+fi.error_message]]`]]</div></div>'
		.$nl.'<input type="hidden" name="'.$s_submitVar.'" value="1" />'
		.$nl.'<input type="hidden" name="fke'.date('Y').'Sp'.date('m').'Blk:blank" value="" /><!-- additional crude spam block. If this field ends up with data it will fail to submit -->'
		.$nl;
		foreach($this->_formElements as $o_el){
			$s_elClass=get_class($o_el);
			if($s_elClass=='FormItBuilder_elementFile'){
				$b_attachmentIncluded=true;
			}
			if(is_a($o_el,'FormItBuilder_htmlBlock')){
				$s_form.=$o_el->outputHTML();
			}else{
				$forId=$o_el->getId();
				if(is_a($o_el,'FormItBuilder_elementRadioGroup')===true){
					$forId=$o_el->getId().'_0';
				}
				
				$s_form.='<div class="formSegWrap formSegWrap_'.htmlspecialchars($o_el->getId()).'">';
					if($o_el->showLabel()===true){

						$s_form.=$nl.'  <label class="mainElLabel" for="'.htmlspecialchars($forId).'">'.$o_el->getLabel().'</label>';
					}
					$s_form.=$nl.'  <div class="elWrap">'.$nl.'    '.$o_el->outputHTML();
					if($o_el->showLabel()===true){
						$s_form.=$nl.'  <div class="errorContainer"><label class="formiterror" for="'.htmlspecialchars($forId).'">[[+fi.error.'.htmlspecialchars($o_el->getId()).']]</label></div>';
					}
					$s_form.=$nl.'  </div>';
				$s_form.=$nl.'</div>'.$nl;
			}
		}
		$s_form.=$nl.'</div>';

		//wrap form elements in form tags
		$s_form='<form action="[[~[[*id]]]]" method="'.htmlspecialchars($this->_method).'"'.($b_attachmentIncluded?' enctype="multipart/form-data"':'').' class="form" id="'.htmlspecialchars($this->_id).'">'.$nl
		.$s_form.$nl
		.'</form>';
		
		//add all formit validation rules together in one array for easy implode
		$a_formItCmds=array();
		$a_formItErrorMessage=array();
		foreach($a_fieldProps as $fieldID=>$a_fieldProp){
			if(count($a_fieldProp)>0){
				$a_formItCmds[]=$fieldID.':'.implode(':',$a_fieldProp);
			}
		}
		//add formIT error messages
		foreach($a_fieldProps_errstringFormIt as $fieldID=>$msgArray){
			foreach($msgArray as $msg){
				$a_formItErrorMessage[]='&'.$fieldID.'.'.$msg;
			}
		}
		
		for($i=0; $i<count($a_formProps); $i++){
			$a_formItCmds[]=$a_formProps[$i];
			if(empty($a_formPropsFormItErrorStrings[$i])===false){
				$a_formItErrorMessage[]=$a_formPropsFormItErrorStrings[$i];
			}
		}
		
		//if using database table then add call to final hook
		$b_addFinalHooks=false;
		$GLOBALS['FormItBuilder_hookCommands']=array('formObj'=>&$this,'commands'=>array());
		if(empty($this->_databaseTableObjectName)===false){
			$GLOBALS['FormItBuilder_hookCommands']['commands'][]=array('name'=>'dbEntry','value'=>array('tableObj'=>$this->_databaseTableObjectName,'mapping'=>$this->_databaseTableFieldMapping));
			$b_addFinalHooks=true;
		}
		if($b_addFinalHooks===true){
			$this->_hooks[]='FormItBuilder_hooks';
		}
		
		
		$s_formItCmd='[[!FormIt?'
		.$nl.'&hooks=`'.$this->_postHookName.(count($this->_hooks)>0?','.implode(',',$this->_hooks):'').'`'
		.(empty($this->_emailTpl)===false?$nl.'&emailTpl=`'.$this->_emailTpl.'`':'')
			
		.(empty($this->_emailToAddress)===false?$nl.'&emailTo=`'.$this->_emailToAddress.'`':'')
		.(empty($this->_emailToName)===false?$nl.'&emailToName=`'.$this->_emailToName.'`':'')
			
		.(empty($this->_emailFromAddress)===false?$nl.'&emailFrom=`'.$this->_emailFromAddress.'`':'')
		.(empty($this->_emailFromName)===false?$nl.'&emailFromName=`'.$this->_emailFromName.'`':'')
			
		.(empty($this->_emailReplyToAddress)===false?$nl.'&emailReplyTo=`'.$this->_emailReplyToAddress.'`':'')
		.(empty($this->_emailReplyToName)===false?$nl.'&emailReplyToName=`'.$this->_emailReplyToName.'`':'')
		
		.(empty($this->_emailCCAddress)===false?$nl.'&emailCC=`'.$this->_emailCCAddress.'`':'')
		.(empty($this->_emailCCName)===false?$nl.'&emailCCName=`'.$this->_emailCCName.'`':'')
			
		.(empty($this->_emailBCCAddress)===false?$nl.'&emailBCC=`'.$this->_emailBCCAddress.'`':'')
		.(empty($this->_emailBCCName)===false?$nl.'&emailBCCName=`'.$this->_emailBCCName.'`':'')
			
		.(empty($this->_customValidators)===false?$nl.'&customValidators=`'.$this->_customValidators.'`':'')
			
		.$nl.'&emailSubject=`'.$this->_emailSubject.'`'
		.$nl.'&emailUseFieldForSubject=`1`'
		.$nl.'&redirectTo=`'.$this->_redirectDocument.'`'
		.$nl.'&store=`'.$this->_store.'`'
		.$nl.'&submitVar=`'.$s_submitVar.'`'
		.$nl.implode($nl,$a_formItErrorMessage)
		.$nl.'&validate=`'.(isset($this->_validate)?$this->_validate.',':'').implode(','.$nl.' ',$a_formItCmds).','.$nl.'`]]'.$nl;
		
		if($this->_jqueryValidation===true){
			$s_js='	
$().ready(function() {

jQuery.validator.addMethod("dateFormat", function(value, element, format) {
	var b_retStatus=false;
	var s_retValue="";
	var n_retTimestamp=0;
	if(value.length==format.length){
		var separator_only = format;
		separator_only = separator_only.replace(/m|d|y/g,"");
		var separator = separator_only.charAt(0)

		if(separator && separator_only.length==2){
			var dayPos; var day; var monthPos; var month; var yearPos; var year;
			var s_testYear;
			var newStr = format;
			
			dayPos = format.indexOf("dd");
			day = parseInt(value.substr(dayPos,2));
			newStr=newStr.replace("dd",day);

			monthPos = format.indexOf("mm");
			month = parseInt(value.substr(monthPos,2));
			newStr=newStr.replace("mm",month);

			yearPos = format.indexOf("yyyy");
			year = parseInt(value.substr(yearPos,4));
			newStr=newStr.replace("yyyy",year);
			
			var testDate = new Date(year, month-1, day);
			if (testDate.getDate()==day && (testDate.getMonth()+1)==month && testDate.getFullYear()==year) {
				b_retStatus = true;
				$(element).val(newStr);
			}
		} 
	}
	return this.optional(element) || b_retStatus;
}, "Please enter a valid date.");

//Main validate call
var thisFormEl=$("#'.$this->_id.'");
thisFormEl.validate({errorPlacement:function(error, element) {
	var labelEl = element.parents(".formSegWrap").find(".errorContainer");
	error.appendTo( labelEl );
},success: function(element) {
	element.addClass("valid");
	var formSegWrapEl = element.parents(".formSegWrap");
	formSegWrapEl.children(".mainElLabel").removeClass("mainLabelError");
},highlight: function(el, errorClass, validClass) {
	var element= $(el);
	element.addClass(errorClass).removeClass(validClass);
	element.parents(".formSegWrap").children(".mainElLabel").addClass("mainLabelError");
},invalidHandler: function(form, validator){
	//make nice little animation to scroll to the first invalid element instead of an instant jump
	var jumpEl = $("#"+validator.errorList[0].element.id).parents(".formSegWrap");
	$("html,body").animate({scrollTop: jumpEl.offset().top});
},ignore:":hidden",'.
					
$this->jqueryValidateJSON(
	$a_fieldProps_jqValidate,
	$a_fieldProps_errstringJq,
	$a_formProps_jqValidate,
	$a_formPropsJqErrorStrings
).'});
	
'.
//Force validation on load if already posted
($b_posted===true?'thisFormEl.valid();':'')
.'
	
});
';
		}
		
		//Allows output of the javascript into a paceholder so in can be inserted elsewhere in html (head etc)
		if(empty($this->_placeholderJavascript)===false){
			$this->modx->setPlaceholder($this->_placeholderJavascript,$s_js);
			return $s_formItCmd.$s_form;
		}else{
			return $s_formItCmd.$s_form.
'<script type="text/javascript">
// <![CDATA[
'.$s_js.'
// ]]>
</script>';
		}
	}

	public function output(){
		return $this->getFormItBuilderOutput();
	}
	
	public function outputRaw(){
		echo $this->getFormItBuilderOutput(); exit();
	}
}
?>