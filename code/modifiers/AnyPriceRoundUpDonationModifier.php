<?php

/**
 * @author Nicolaas [at] sunnysideup.co.nz
 * @package: ecommerce
 * @sub-package: ecommerce_delivery
 * @description: allows you to add a modifier at checkout where the customer is prompted to
 * add a donation rounding up to the next round number.
 *
 * The trick here is to work out the value of the donation
 * from the total without the donation and then add it to the donation
 * to get to a round numer.
 *
 * ASSUMPTION: there is no tax payable on the donation.
 */
class AnyPriceRoundUpDonationModifier extends OrderModifier {

// ######################################## *** model defining static variables (e.g. $db, $has_one)

	/**
	 * add extra fields as you need them.
	 *
	 **/
	public static $db = array(
		"ModifierTotalExcludingDonation" => "Currency",
		"SubTotal" => "Currency",
		"OtherValue" => "Currency",
		"AddDonation" => "Boolean"
	);

// ######################################## *** cms variables + functions (e.g. getCMSFields, $searchableFields)

	function getCMSFields() {
		$fields = parent::getCMSFields();
		return $fields;
	}

	public static $singular_name = "Round Up Donation";
		function i18n_singular_name() { return _t("AnyPriceRoundUpDonationModifier.ROUNDUPDONATION", "Round Up Donation");}

	public static $plural_name = "Round Up Donations";
		function i18n_plural_name() { return _t("AnyPriceRoundUpDonationModifier.ROUNDUPDONATIONS", "Round Up Donations");}

// ######################################## *** other (non) static variables (e.g. protected static $special_name_for_something, protected $order)


	/**
	 * Maximum Round Up
	 * to which the donation should round.
	 * +1 = nearest 10, e.g. 73.45 rounds to 80
	 * 0 = nearest rounded integer - e.g. 73.45 rounds to 74
	 * -1 = nearest 10 cents - e.g. 73.45 rounds to 73.50
	 *
	 * @var Int
	 */
	protected static $precision = 1;
		static function set_precision($i) {self::$precision = $i;}
		static function get_precision() {return self::$precision;}

	/**
	 * Maximum Round Up - modifier will ensure that the round up is no more
	 * than the number specified here.
	 * @var Int
	 */
	protected static $maximum_round_up = 5;
		static function set_maximum_round_up($i) {self::$maximum_round_up = $i;}
		static function get_maximum_round_up() {return self::$maximum_round_up;}

// ######################################## *** CRUD functions (e.g. canEdit)
// ######################################## *** init and update functions

	/**
	 * For all modifers with their own database fields, we need to include this...
	 * It will update each of the fields.
	 * With this, we also need to create the methods
	 * Live{functionName}
	 * e.g LiveMyField() and LiveMyReduction() in this case...
	 * @param Bool $force - run it, even if it has run already
	 */
	public function runUpdate($force = false) {
		$this->checkField("AddDonation");
		$this->checkField("OtherValue");
		$this->checkField("SubTotal");
		$this->checkField("ModifierTotalExcludingDonation");
		parent::runUpdate($force);
	}

	/**
	 * allows you to save a new value AddDonation
	 * @param Boolean $b
	 */
	public function updateAddDonation($b) {
		$this->AddDonation = $b;
		$this->write();
	}

	/**
	 * allows you to save a new value OtherValue
	 * @param float
	 */
	public function updateOtherValue($f) {
		$this->OtherValue = $f;
		$this->write();
	}


// ######################################## *** form functions (e. g. showform and getform)

	/**
	 * standard OrderModifier Method
	 * Should we show a form in the checkout page for this modifier?
	 */
	public function showForm() {
		return $this->Order()->Items();
	}

	/**
	 * standard OrderModifier Method
	 * This method returns the form for the checkout page.
	 * @param Object $controller = Controller object for form
	 * @return Object - AnyPriceRoundUpDonationModifier
	 */
	function getModifierForm($optionalController = null, $optionalValidator = null) {
		$fields = new FieldSet();
		$fields->push($this->headingField());
		$fields->push($this->descriptionField());
		$maxRoundUpObject = DBField::create('Currency',self::get_maximum_round_up());
		$checkFieldTitle = sprintf(_t("AnyPriceRoundUpDonationModifier.ADDDONATION", "Add round up donation (maximum added %s)?"),$maxRoundUpObject->Nice());
		$fields->push(new CheckboxField('AddDonation', $checkFieldTitle, $this->AddDonation));
		$fields->push(new NumericFIeld('OtherValue', _t("AnyPriceRoundUpDonationModifier.OTHERVALUE", "Other Value"), $this->OtherValue));
		$actions = new FieldSet(
			new FormAction('submit', 'Update Order')
		);
		return new AnyPriceRoundUpDonationModifier_Form($optionalController, 'AnyPriceRoundUpDonationModifier', $fields, $actions, $optionalValidator);
	}

// ######################################## *** template functions (e.g. ShowInTable, TableTitle, etc...) ... USES DB VALUES


	public function ShowInTable() {
		return true;
	}

	public function CanBeRemoved() {
		return false;
	}

// ######################################## ***  inner calculations.... USES CALCULATED VALUES


	/**
	 * Works out if there is a donation at all.
	 *
	 *@return Boolean
	 */
	protected function hasDonation(){
		if(($this->LiveAddDonation() && self::get_maximum_round_up() > 0) || $this->OtherValue > 0) {
			return true;
		}
		return false;
	}
	/**
	 * Works out the total round up amount, using both the
	 * sub-total and the modifier total.
	 *
	 *@return Float
	 */
	protected function workOutRoundUpAmount(){
		if($this->hasDonation()) {
			if($this->OtherValue > 0) {
				$actualAdditionToTotal = $this->OtherValue;
			}
			else {
				$totalExcludingDonation = $this->LiveSubTotal() + $this->LiveModifierTotalExcludingDonation();
				$precisionMultiplier = pow(10, self::get_precision());
				$totalMultipliedByPrecision = $totalExcludingDonation / $precisionMultiplier;
				$roundedTotalMultipliedByPrecision = ceil($totalMultipliedByPrecision);
				$actualAdditionToTotal = ($roundedTotalMultipliedByPrecision * $precisionMultiplier) - $totalExcludingDonation;
				while($actualAdditionToTotal > self::get_maximum_round_up() && $actualAdditionToTotal > 0) {
					$actualAdditionToTotal = $actualAdditionToTotal - self::get_maximum_round_up();
				}
			}
		}
		else {
			$actualAdditionToTotal = 0;
		}
		return $actualAdditionToTotal;
	}


// ######################################## *** calculate database fields: protected function Live[field name]  ... USES CALCULATED VALUES

	/**
	 * if we want to change the default value for the Name field
	 * (defined in the OrderModifer class) then we can do this
	 * as shown in the method below.
	 * You may choose to return an empty string or just a standard message.
	 *
	 *
	 **/
	protected function LiveName() {
		if($this->OtherValue > 0) {
			return _t("AnyPriceRoundUpDonationModifier.DONATION", "Donation");
		}
		elseif($this->hasDonation()) {
			return _t("AnyPriceRoundUpDonationModifier.ROUNDUPDONATION", "Round up donation");
		}
		else {
			return _t("AnyPriceRoundUpDonationModifier.NOROUNDUPDONATION", "No round up donation added");
		}
	}

	/**
	 *
	 * @return Boolean
	 **/
	protected function LiveAddDonation() {
		return $this->AddDonation;
	}

	/**
	 *
	 * @return Float
	 **/
	protected function LiveOtherValue() {
		return $this->OtherValue;
	}

	/**
	 * Work out sub total amount for order
	 * @return float
	 **/
	protected function LiveSubTotal() {
		if($this->hasDonation()) {
			$order = $this->Order();
			return $order->SubTotal();
		}
		else {
			return 0;
		}
	}

	/**
	 * Work out modifier total excluding donation
	 * @return float
	 **/
	protected function LiveModifierTotalExcludingDonation() {
		if($this->hasDonation()) {
			$modifiersTotal = 0;
			$order = $this->Order();
			if($order) {
				if($modifiers = $order->Modifiers()) {
					foreach($modifiers as $modifier) {
						if(!$modifier->IsRemoved()) { //we just doubledouble-check this...
							if($modifier instanceOf $this->ClassName) {
								$totalForModifier = 0;
							}
							else {
								$totalForModifier = $modifier->CalculationTotal();
							}
							$modifiersTotal += floatval($totalForModifier);
						}
					}
				}
			}
			return $modifiersTotal;
		}
		else {
			return 0;
		}
	}

	protected function LiveCalculatedTotal() {
		if($this->hasDonation()) {
			return $this->workOutRoundUpAmount();
		}
		else {
			return 0;
		}
	}

	public function LiveTableValue() {
		return $this->LiveCalculatedTotal();
	}


// ######################################## *** Type Functions (IsChargeable, IsDeductable, IsNoChange, IsRemoved)



// ######################################## *** standard database related functions (e.g. onBeforeWrite, onAfterWrite, etc...)

	function onBeforeWrite() {
		parent::onBeforeWrite();
	}


// ######################################## *** AJAX related functions
	/**
	* some modifiers can be hidden after an ajax update (e.g. if someone enters a discount coupon and it does not exist).
	* There might be instances where ShowInTable (the starting point) is TRUE and HideInAjaxUpdate return false.
	*@return Boolean
	**/
	public function HideInAjaxUpdate() {
		//we check if the parent wants to hide it...
		//we need to do this first in case it is being removed.
		if(parent::HideInAjaxUpdate()) {
			return true;
		}
		// we do NOT hide it if values have been entered
		if($this->hasDonation()) {
			return false;
		}
		return true;
	}
// ######################################## *** debug functions

}

class AnyPriceRoundUpDonationModifier_Form extends OrderModifierForm {

	function __construct($optionalController = null, $name, $fields, $actions, $optionalValidator = null) {
		parent::__construct($optionalController, $name,$fields,$actions,$optionalValidator);
		Requirements::javascript("ecommerce_anypriceproduct/javascript/AnyPriceRoundUpDonationModifier.js");
	}

	public function submit($data, $form) {
		$order = ShoppingCart::current_order();
		if($order) {
			if($modifiers = $order->Modifiers("AnyPriceRoundUpDonationModifier")) {
				$msg = "";
				foreach($modifiers as $modifier) {
					if(isset($data['AddDonation'])) {
						$modifier->updateAddDonation(true);
						$modifier->updateOtherValue(0);
						$msg .= _t("AnyPriceRoundUpDonationModifier.UPDATED", "Round up donation added - THANK YOU.");
					}
					else {
						$modifier->updateAddDonation(false);
						$msg .= _t("AnyPriceRoundUpDonationModifier.UPDATED", "Round up donation removed.");
					}
					if(isset($data['OtherValue'])) {
						$modifier->updateOtherValue(floatval($data['OtherValue']));
						if(floatval($data['OtherValue']) > 0) {
							$msg .= _t("AnyPriceRoundUpDonationModifier.UPDATED", "Added donation - THANK YOU.");
						}
					}
					$modifier->write();
				}
				return ShoppingCart::singleton()->setMessageAndReturn($msg, "good");
			}
		}
		return ShoppingCart::singleton()->setMessageAndReturn(_t("AnyPriceRoundUpDonationModifier.NOTUPDATED", "Could not update the round up donation status.", "bad"));
	}
}
