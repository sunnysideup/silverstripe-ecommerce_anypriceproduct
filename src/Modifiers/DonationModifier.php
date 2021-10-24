<?php

namespace Sunnysideup\EcommerceAnyPriceProduct\Modifiers;

use Controller;
use Validator;
use DonationOption;
use DropdownField;


class DonationModifier extends AnyPriceRoundUpDonationModifier
{
    private static $has_one = array(
        'Donation' => 'DonationOption'
    );

    public function getModifierForm(Controller $optionalController = null, Validator $optionalValidator = null)
    {
        $form = parent::getModifierForm($optionalController, $optionalValidator);
        $donations = DonationOption::get();
        $fields = $form->Fields();
        if ($donations->count()) {
            $field = $fields->fieldByName('AddDonation');
            $title = $field->Title();
            $source = $field->getSource();
            $fields->removeByName('AddDonation');
            unset($source[1]);
            $donations = $donations->map()->toArray();
            $source += $donations;
            $fields->push(new DropdownField('DonationID', $title, $source, $this->DonationID));
        }
        $form = new DonationModifierForm($form->Controller(), 'DonationModifier', $fields, $form->Actions(), $form->getValidator());
        //3.0TODO: Check me for consistencies.
        $form->addExtraClass('anyPriceRoundUpDonationModifier');
        return $form;
    }

    public function updateAddDonation($donationID)
    {
        $this->AddDonation = $donationID ? true : false;
        $this->DonationID = $donationID;
        $this->write();
    }

    protected function LiveName()
    {
        if ($this->hasDonation() && $this->DonationID) {
            return $this->Donation()->Title;
        } else {
            return parent::LiveName();
        }
    }
}
