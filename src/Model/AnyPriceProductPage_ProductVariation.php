<?php

namespace Sunnysideup\EcommerceAnyPriceProduct\Model;



use Sunnysideup\EcommerceAnyPriceProduct\Model\AnyPriceProductPage_ProductVariationOrderItem;
use SilverStripe\Security\Member;
use Sunnysideup\EcommerceProductVariation\Model\Buyables\ProductVariation;




class AnyPriceProductPage_ProductVariation extends ProductVariation
{

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * OLD: private static $db (case sensitive)
  * NEW: 
    private static $table_name = '[SEARCH_REPLACE_CLASS_NAME_GOES_HERE]';

    private static $db (COMPLEX)
  * EXP: Check that is class indeed extends DataObject and that it is not a data-extension!
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
    
    private static $table_name = 'AnyPriceProductPage_ProductVariation';

    private static $db =array(
        "Description" => "Varchar(200)"
    );

    /**
     *
     * @var String
     */
    protected $defaultClassNameForOrderItem = AnyPriceProductPage_ProductVariationOrderItem::class;

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
