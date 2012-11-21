<?php


/**
*@author Nicolaas [at] sunnysideup.co.nz
*
**/

//copy the lines between the START AND END line to your /mysite/_config.php file and choose the right settings

//===================---------------- START ecommerce_anypriceproduct MODULE ----------------===================

// MUST SET (if you would like to use the AnyPriceRoundUpDonationModifier)
/**
 * ADD TO ECOMMERCE.YAML:
Order:
	modifiers: [
		...
		AnyPriceRoundUpDonationModifier
	]
*/

// MAY SET (as part of the AnyPriceRoundUpDonationModifier)
//AnyPriceRoundUpDonationModifier::set_precision(1);
//AnyPriceRoundUpDonationModifier::set_maximum_round_up(5);
//AnyPriceRoundUpDonationModifier::set_round_up_even_if_there_is_nothing_to_round(true);
//AnyPriceRoundUpDonationModifier::use_dropdown_in_modifier_form(true);
//===================---------------- END ecommerce_anypriceproduct MODULE ----------------===================

