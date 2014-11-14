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

	private static $db = array(
		"AmountFieldLabel" => "Varchar(255)",
		"ActionFieldLabel" => "Varchar(255)",
		"MinimumAmount" => "Decimal(9,2)",
		"MaximumAmount" => "Decimal(9,2)",
		"RecommendedAmounts" => "Varchar(255)"
	);

	private static $defaults = array(
		"AmountFieldLabel" => "Enter Amount",
		"ActionFieldLabel" => "Add to cart",
		"MinimumAmount" => 1,
		"MaximumAmount" => 100,
		"AllowPurchase" => false,
		"Price" => 0
	);

	private static $singular_name = "Any Price Product";
		function i18n_singular_name() { return _t("AnyPriceProductPage.ANYPRICEPRODUCT", "Any Price Product");}

	private static $plural_name = "Any Price Products";
		function i18n_plural_name() { return _t("AnyPriceProductPage.ANYPRICEPRODUCT", "Any Price Products");}

	private static $icon = 'ecommerce_anypriceproduct/images/treeicons/AnyPriceProductPage';

	/**
	 * @config
	 * @var String Description of the class functionality, typically shown to a user
	 * when selecting which page type to create. Translated through {@link provideI18nEntities()}.
	 */
	private static $description = 'Generic product that can be used to allow customers to choose a specific amount to pay.';

	function canCreate($member = null) {
		return !SiteTree::get()->filter(array("ClassName" => 'AnyPriceProductPage'))->count();
	}

	function canPurchase(Member $member = null, $checkPrice = true) {
		return true;
	}

	function getCMSFields() {
		$fields = parent::getCMSFields();
		$exampleLink = Director::absoluteURL($this->Link("setamount"))."/123.45";
		$exampleLinkExplanation = sprintf(_t("AnyPriceProductPage.EXPLANATION", '<br /><br /><h5>How to preset the amount?</h5><p>The link <a href="%1$s">%1$s</a> will pre-set the amount to 123.45. You can use this link (and vary the amount as needed) to cutomers to receive payments.</p>.'), $exampleLink);
		$fields->addFieldsToTab(
			"Root.AddAmountForm",
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
			'Root.Details',
			array(
				'Weight',
				'Price',
				'Model'
			)
		);


		// Flags for this product which affect it's behaviour on the site
		$fields->removeFieldsFromTab(
			'Root.Details',
			array(
				'FeaturedProduct'
			)
		);

		return $fields;
	}



}

class AnyPriceProductPage_Controller extends Product_Controller {

	private static $allowed_actions = array(
		"AddNewPriceForm",
		"setamount",

	);

	function init() {
		parent::init();
	}

	function AddNewPriceForm() {
		$amount = $this->MinimumAmount;
		if($newAmount = Session::get("AnyPriceProductPageAmount")) {
			$amount = $newAmount;
		}
		$fields = new FieldList(
			new CurrencyField("Amount", $this->AmountFieldLabel, $amount)
		);

		$actions = new FieldList(
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
			$this->redirectBack();
			return;
		}
		elseif($this->MaximumAmount && ($amount > $this->MaximumAmount)) {
			$form->sessionMessage(_t("AnyPriceProductPage.ERRORINFORMTOOHIGH", "Please enter a lower amount."), "bad");
			$this->redirectBack();
			return;
		}
		Session::clear("AnyPriceProductPageAmount");
		$obj = AnyPriceProductPage_ProductVariation::get()->filter(array(
			"ProductID" => $this->ID,
			"Price" => $amount
		))->First();
		//create new one if needed
		if(!$obj) {
			Currency::setCurrencySymbol(EcommercePayment::site_currency());
			$titleDescriptor = new Currency("titleDescriptor");
			$titleDescriptor->setValue($amount);
			$obj = new AnyPriceProductPage_ProductVariation();
			$obj->Title = _t("AnyPriceProductPage.PAYMENTFOR", "Payment for: ").$titleDescriptor->Nice();
			$obj->Price = $amount;
			$obj->AllowPurchase = true;
			$obj->ProductID = $this->ID;
			$obj->write("Stage");
			// line below does not work - suspected bug in framework Versioning System
			//$componentSet->add($obj);
		}
		//check if we have one now
		if(!$obj) {
			$obj = AnyPriceProductPage_ProductVariation::get()->filter(array(
				"ProductID" => $this->ID,
				"Price" => $amount
			))->First();
		}
		if($obj) {
			$shoppingCart = ShoppingCart::singleton();
			$shoppingCart->addBuyable($obj);
		}
		else {
			$form->sessionMessage(_t("AnyPriceProductPage.ERROROTHER", "Sorry, we could not add our entry."), "bad");
			$this->redirectBack();
			return;
		}
		$checkoutPage = CheckoutPage::get()->First();
		if($checkoutPage) {
			$this->redirect($checkoutPage->Link());
		}
		return;
	}

	function setamount($request) {
		if($amount = floatval($request->param("ID"))) {
			Session::set("AnyPriceProductPageAmount", $amount);
		}
		$this->redirect($this->Link());
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
			return AnyPriceProductPage_ProductVariation::get()->filter(array(
				"ProductID" => $this->ID,
				"Price" => $options
			));
		}
		elseif(floatval($options) == $options){
			return AnyPriceProductPage_ProductVariation::get()->filter(array(
				"ProductID" => $this->ID,
				"Price" => floatval($options)
			));
		}
	}

}

class AnyPriceProductPage_ProductVariation extends ProductVariation {

	/**
	 *
	 * @var String
	 */
	protected $defaultClassNameForOrderItem = "AnyPriceProductPage_ProductVariationOrderItem";

	function canPurchase(Member $member = null, $checkPrice = true) {
		return true;
	}

}

class AnyPriceProductPage_ProductVariationOrderItem extends ProductVariation_OrderItem {

	function onBeforeWrite(){
		parent::onBeforeWrite();
		$this->Quantity = 1;
	}



}

