<?php

namespace Sunnysideup\EcommerceAnyPriceProduct\Modifiers;

use OrderModifierForm;
use Requirements;
use Form;
use ShoppingCart;


class AnyPriceRoundUpDonationModifierForm extends OrderModifierForm
{
    public function __construct($optionalController = null, $name, $fields, $actions, $optionalValidator = null)
    {
        parent::__construct($optionalController, $name, $fields, $actions, $optionalValidator);
        Requirements::javascript("sunnysideup/ecommerce_anypriceproduct: ecommerce_anypriceproduct/javascript/AnyPriceRoundUpDonationModifier.js");
    }

    public function submit(array $data, Form $form, $message = "order updated", $status = "good")
    {
        $order = ShoppingCart::current_order();
        if ($order) {
            if ($modifiers = $order->Modifiers("AnyPriceRoundUpDonationModifier")) {
                $msg = "";
                foreach ($modifiers as $modifier) {
                    if (isset($data['AddDonation']) && $data['AddDonation']) {
                        $modifier->updateAddDonation(true);
                        $msg .= _t("AnyPriceRoundUpDonationModifier.UPDATED", "Round up donation added - THANK YOU.");
                    } else {
                        $modifier->updateAddDonation(false);
                        $msg .= _t("AnyPriceRoundUpDonationModifier.REMOVED", "Round up donation removed.");
                    }
                    if (isset($data['OtherValue'])) {
                        $modifier->updateOtherValue(floatval($data['OtherValue']));
                        if (floatval($data['OtherValue']) > 0) {
                            //here we replace the message!
                            $msg = _t("AnyPriceRoundUpDonationModifier.UPDATED_OTHER", "Added donation - THANK YOU.");
                        }
                    } else {
                        $modifier->updateOtherValue(0);
                    }
                    $modifier->write();
                }
                return ShoppingCart::singleton()->setMessageAndReturn($msg, "good");
            }
        }
        return ShoppingCart::singleton()->setMessageAndReturn(_t("AnyPriceRoundUpDonationModifier.NOTUPDATED", "Could not update the round up donation status.", "bad"));
    }
}
