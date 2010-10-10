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
		"MaximumAmount" => "Decimal(9,2)"
	);

	public static $defaults = array(
		'AllowPurchase' => true,
		'Price' => 0
	);

	static $add_action = 'Product With Adjustable Price';

	static $icon = 'ecommerceanypriceproduct/images/treeicons/AnyPriceProductPage';

	function canCreate() {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		return !DataObject::get("SiteTree", "{$bt}ClassName{$bt} = 'AnyPriceProductPage'");
	}

	function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldsToTab(
			"Root.Content.AddAmountForm",
			array(
				new TextField("AmountFieldLabel", "Amount Field Label (what amount would you like to pay?)"),
				new TextField("ActionFieldLabel", "Action Field Label (e.g. pay entered amount now)"),
				new CurrencyField("MinimumAmount", "Minimum Amount"),
				new CurrencyField("MaximumAmount", "Maximum Amount")
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


	/**
	 * Conditions for whether a product can be purchased.
	 *
	 * If it has the checkbox for 'Allow this product to be purchased',
	 * as well as having a price, it can be purchased. Otherwise a user
	 * can't buy it.
	 *
	 * @return boolean
	 */
	function AllowPurchase() {
		return false ;
	}



}

class AnyPriceProductPage_Controller extends Product_Controller {

	function init() {
		parent::init();
	}

	function AddNewPriceForm() {
		$fields = new FieldSet(
			new CurrencyField("Amount", $this->AmountFieldLabel)
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
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		$amount = floatval($data["Amount"]);
		if($amount < $this->MinimimAmount) {
			die("minimum amount is ....");
		}
		$alreadyExistingVariations = DataObject::get_one("ProductVariation", "{$bt}ProductID{$bt} = ".$this->ID." AND {$bt}Price{$bt} = ".$amount);
		//create new one if needed
		if(!$alreadyExistingVariations) {
			Currency::setCurrencySymbol(Payment::site_currency());
			$titleDescriptor = new Currency("titleDescriptor");
			$titleDescriptor->setValue($amount);
			$obj = new ProductVariation();
			$obj->Title = "Payment for: ".$titleDescriptor->Nice();
			$obj->Price = $amount;
			$obj->ProductID = $this->ID;
			$obj->writeToStage("Stage");
			// line below does not work - suspected bug in Sapphire Versioning System
			//$componentSet->add($obj);
		}
		//check if we have one now
		$ourVariation = DataObject::get_one("ProductVariation", "{$bt}ProductID{$bt} = ".$this->ID." AND {$bt}Price{$bt} = ".$amount);
		if($ourVariation) {
			ShoppingCart::add_new_item(new ProductVariation_OrderItem($ourVariation));
		}
		else {
			die("no count");
		}
		$checkoutPage = DataObject::get_one("CheckoutPage");
		if($checkoutPage) {
			Director::redirect($checkoutPage->Link());
		}
		return;
	}

}
