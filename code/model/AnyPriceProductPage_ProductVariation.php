<?php


class AnyPriceProductPage_ProductVariation extends ProductVariation
{
    private static $db =array(
        "Description" => "Varchar(200)"
    );

    /**
     *
     * @var String
     */
    protected $defaultClassNameForOrderItem = "AnyPriceProductPage_ProductVariationOrderItem";

    public function canPurchase(Member $member = null, $checkPrice = true)
    {
        return true;
    }

    public function TableSubTitle()
    {
        return $this->getTableSubTitle();
    }

    public function getTableSubTitle()
    {
        return $this->Description;
    }
}
