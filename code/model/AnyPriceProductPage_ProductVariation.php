<?php


class AnyPriceProductPage_ProductVariation extends ProductVariation {

	private static $db =array(
		"Description" => "Varchar(200)"
	);

	/**
	 *
	 * @var String
	 */
	protected $defaultClassNameForOrderItem = "AnyPriceProductPage_ProductVariationOrderItem";

	function canPurchase(Member $member = null, $checkPrice = true) {
		return true;
	}

	function TableSubTitle(){
		return $this->getTableSubTitle();
	}

	function getTableSubTitle(){
		return $this->Description;
	}

}
