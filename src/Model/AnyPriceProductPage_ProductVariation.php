<?php

namespace Sunnysideup\EcommerceAnyPriceProduct\Model;

use ProductVariation;
use Member;



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
