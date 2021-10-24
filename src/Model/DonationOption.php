<?php

namespace Sunnysideup\EcommerceAnyPriceProduct\Model;

use DataObject;


class DonationOption extends DataObject
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
    
    private static $table_name = 'DonationOption';

    private static $db = array(
        'Title' => 'Varchar'
    );
}
