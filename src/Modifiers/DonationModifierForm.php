<?php

namespace Sunnysideup\EcommerceAnyPriceProduct\Modifiers;

use OrderModifierForm;
use Form;
use ShoppingCart;


class DonationModifierForm extends OrderModifierForm
{
    public function submit(array $data, Form $form, $message = "order updated", $status = "good")
    {
        $order = ShoppingCart::current_order();
        if ($order) {
            $modifier = $order->Modifiers('DonationModifier');
            if ($modifier) {
                $modifier = $modifier->First();
                $modifier->updateAddDonation($data['DonationID']);
                $msg = $data['DonationID'] ? _t("AnyPriceRoundUpDonationModifier.UPDATED", "Round up donation added - THANK YOU.") : _t("AnyPriceRoundUpDonationModifier.UPDATED", "Round up donation removed.");
                if (isset($data['OtherValue'])) {
                    $modifier->updateOtherValue(floatval($data['OtherValue']));
                    if (floatval($data['OtherValue']) > 0) {
                        $msg .= _t("AnyPriceRoundUpDonationModifier.UPDATED", "Added donation - THANK YOU.");
                    }
                } else {
                    $modifier->updateOtherValue(0);
                }
                $modifier->write();
                return ShoppingCart::singleton()->setMessageAndReturn($msg, "good");
            }
        }
        return ShoppingCart::singleton()->setMessageAndReturn(_t("AnyPriceRoundUpDonationModifier.NOTUPDATED", "Could not update the round up donation status.", "bad"));
    }
}
