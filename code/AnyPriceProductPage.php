<?php
/**
 *
 *@author nicolaas [at] sunnysideup.co.nz
 *@package ecommerce
 *@subpackage products
 *@requires ecommerce
 *
 *
 */


class AnyPriceProductPage extends Product {

	public static $db = array(
		"AmountFieldLabel" => "Varchar(255)",
		"ActionFieldLabel" => "Varchar(255)",
		"MinimumAmount" => "Decimal(9,2)",
		"MaximumAmount" => "Decimal(9,2)",
		"RecommendedAmounts" => "Varchar(255)"
	);

	public static $defaults = array(
		"AmountFieldLabel" => "Enter Amount",
		"ActionFieldLabel" => "Add to cart",
		"MinimumAmount" => 1,
		"MaximumAmount" => 100,
		"AllowPurchase" => false,
		"Price" => 0
	);

	public static $singular_name = "Any Price Product";
		function i18n_singular_name() { return _t("AnyPriceProductPage.ANYPRICEPRODUCT", "Any Price Product");}

	public static $plural_name = "Any Price Products";
		function i18n_plural_name() { return _t("AnyPriceProductPage.ANYPRICEPRODUCT", "Any Price Products");}

	static $icon = 'ecommerce_anypriceproduct/images/treeicons/AnyPriceProductPage';

	function canCreate($member = null) {
		return !DataObject::get_one("SiteTree", "ClassName = 'AnyPriceProductPage'");
	}

	function canPurchase($member = null) {
		return true;
	}

	function getCMSFields() {
		$fields = parent::getCMSFields();
		$exampleLink = Director::absoluteURL($this->Link("setamount"))."/123.45";
		$exampleLinkExplanation = sprintf(_t("AnyPriceProductPage.EXPLANATION", '<br /><br /><h5>How to preset the amount?</h5><p>The link <a href="%1$s">%1$s</a> will pre-set the amount to 123.45. You can use this link (and vary the amount as needed) to cutomers to receive payments.</p>.'), $exampleLink);
		$fields->addFieldsToTab(
			"Root.Content.AddAmountForm",
			array(
				new TextField("AmountFieldLabel", "Amount Field Label (what amount would you like to pay?)"),
				new TextField("ActionFieldLabel", "Action Field Label (e.g. pay entered amount now)"),
				new NumericField("MinimumAmount", "Minimum Amount"),
				new NumericField("MaximumAmount", "Maximum Amount"),
				new TextField("RecommendedAmounts", "Hinted amounts, separated by commas (e.g. <i>5,10,100</i>)"),
				new LiteralField("ExampleLinkExplanation", $exampleLinkExplanation)
			)
		);
		// Standard product detail fields
		$fields->removeFieldsFromTab(
			'Root.Content.Main',
			array(
				'Weight',
				'Price',
				'Model'
			)
		);


		// Flags for this product which affect it's behaviour on the site
		$fields->removeFieldsFromTab(
			'Root.Content.Main',
			array(
				'FeaturedProduct'
			)
		);

		return $fields;
	}



}

class AnyPriceProductPage_Controller extends Product_Controller {

	function init() {
		parent::init();
	}

	function AddNewPriceForm() {
		$amount = $this->MinimumAmount;
		if($newAmount = Session::get("AnyPriceProductPageAmount")) {
			$amount = $newAmount;
		}
		$fields = new FieldSet(
			new CurrencyField("Amount", $this->AmountFieldLabel, $amount)
		);

		$actions = new FieldSet(
			new FormAction("doAddNewPriceForm", $this->ActionFieldLabel)
		);

		$requiredFields = new RequiredFields(array("Amount"));
		return new Form(
			$controller = $this,
			$name = "AddNewPriceForm",
			$fields,
			$actions,
			$requiredFields
		);
	}

	function doAddNewPriceForm($data, $form) {
		$amount = $this->parseFloat($data["Amount"]);
		if($this->MinimumAmount && ($amount < $this->MinimumAmount)) {
			$form->sessionMessage(_t("AnyPriceProductPage.ERRORINFORMTOOLOW", "Please enter a higher amount."), "bad");
			Director::redirectBack();
			return;
		}
		elseif($this->MaximumAmount && ($amount > $this->MaximumAmount)) {
			$form->sessionMessage(_t("AnyPriceProductPage.ERRORINFORMTOOHIGH", "Please enter a lower amount."), "bad");
			Director::redirectBack();
			return;
		}
		Session::clear("AnyPriceProductPageAmount");
		$obj = DataObject::get_one(
			"AnyPriceProductPage_ProductVariation",
			"\"ProductID\" = ".$this->ID." AND \"Price\" = ".$amount
		);
		//create new one if needed
		if(!$obj) {
			Currency::setCurrencySymbol(Payment::site_currency());
			$titleDescriptor = new Currency("titleDescriptor");
			$titleDescriptor->setValue($amount);
			$obj = new AnyPriceProductPage_ProductVariation();
			$obj->Title = _t("AnyPriceProductPage.PAYMENTFOR", "Payment for: ").$titleDescriptor->Nice();
			$obj->Price = $amount;
			$obj->AllowPurchase = true;
			$obj->ProductID = $this->ID;
			$obj->write("Stage");
			$obj->writeToStage("Stage");
			// line below does not work - suspected bug in Sapphire Versioning System
			//$componentSet->add($obj);
		}
		//check if we have one now
		if(!$obj) {
			$obj = DataObject::get_one(
				"AnyPriceProductPage_ProductVariation",
				"\"ProductID\" = ".$this->ID." AND \"Price\" = ".$amount
			);
		}
		if($obj) {
			$shoppingCart = ShoppingCart::singleton();
			$shoppingCart->addBuyable($obj);
		}
		else {
			$form->sessionMessage(_t("AnyPriceProductPage.ERROROTHER", "Sorry, we could not add our entry."), "bad");
			Director::redirectBack();
			return;
		}
		$checkoutPage = DataObject::get_one("CheckoutPage");
		if($checkoutPage) {
			Director::redirect($checkoutPage->Link());
		}
		return;
	}

	function setamount($request) {
		if($amount = floatval($request->param("ID"))) {
			Session::set("AnyPriceProductPageAmount", $amount);
		}
		Director::redirect($this->Link());
		return array();
	}

	protected function parseFloat($floatString){
		//hack to clean up currency symbols, etc....
		$LocaleInfo = localeconv();
		$floatString = str_replace($LocaleInfo["mon_decimal_point"] , ".", $floatString);
		$titleDescriptor = new Currency("titleDescriptor");
		$titleDescriptor->setValue(1111111);
		$titleDescriptorString = $titleDescriptor->Nice();
		$titleDescriptorString = str_replace("1", "", $titleDescriptorString);
		//HACK!
		$titleDescriptorString = str_replace(".00", "", $titleDescriptorString);
		for($i = 0; $i < strlen($titleDescriptorString); $i++){
			$char =substr($titleDescriptorString, $i, 1);
			if($char != $LocaleInfo["mon_decimal_point"]) {
				$floatString = str_replace($char, "", $floatString);
			}
		}
		return round(floatval($floatString - 0), 2);
	}

	function Variations(){
		$options = explode(",", $this->RecommendedAmounts);
		if(is_array($options)  && count($options)) {
			foreach($options as $key => $option) {
				if(!$option) {
					unset($options[$key]);
				}
			}
		}
		if(is_array($options)  && count($options)) {
			return DataObject::get("AnyPriceProductPage_ProductVariation", "\"ProductID\" = ".$this->ID." AND \"Price\" IN (".implode(",", $options).")");
		}
		elseif(floatval($options) == $options){
			return DataObject::get("AnyPriceProductPage_ProductVariation", "\"ProductID\" = ".$this->ID." AND \"Price\" = ".floatval($options).")");
		}
	}

}

class AnyPriceProductPage_ProductVariation extends ProductVariation {

}

class AnyPriceProductPage_ProductVariationOrderItem extends ProductVariation_OrderItem {

}

