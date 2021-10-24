<?php

namespace Sunnysideup\EcommerceAnyPriceProduct;










use Currency;



use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\CurrencyField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\Form;
use SilverStripe\Core\Convert;
use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;
use Sunnysideup\Ecommerce\Pages\CheckoutPage;
use SilverStripe\ORM\DataObject;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Pages\ProductController;



class AnyPriceProductPageController extends ProductController
{
    private static $allowed_actions = array(
        'AddNewPriceForm',
        'doaddnewpriceform',
        'setamount'
    );


    /**
     * A list of variations.
     *
     * @return DataList
     */
    public function Variations()
    {
        $options = explode(array(',', ' '), $this->RecommendedAmounts.' 0 0');
        if (is_array($options)  && count($options)) {
            foreach ($options as $key => $option) {
                if (!$option) {
                    unset($options[$key]);
                }
            }
        }

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $className (case sensitive)
  * NEW: $className (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        $className = $this->getClassNameOfVariations();
        if (count($options)) {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $className (case sensitive)
  * NEW: $className (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
            return $className::get()->filter(array(
                'ProductID' => $this->ID,
                'Price' => $options,
            ));
        } else {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $className (case sensitive)
  * NEW: $className (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
            return $className::get()->filter(array(
                'ProductID' => $this->ID,
            ));
        }
    }

    public function AddNewPriceForm()
    {
        $requiredFields = [];
        $amount = $this->MinimumAmount;

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: Session:: (case sensitive)
  * NEW: Controller::curr()->getRequest()->getSession()-> (COMPLEX)
  * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly. 
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        if ($newAmount = Controller::curr()->getRequest()->getSession()->get('AnyPriceProductPageAmount')) {
            $amount = $newAmount;
        }
        $description = $this->DefaultDescription;

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: Session:: (case sensitive)
  * NEW: Controller::curr()->getRequest()->getSession()-> (COMPLEX)
  * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly. 
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        if ($newDescription = Controller::curr()->getRequest()->getSession()->get('AnyPriceProductPageDescription')) {
            $description = $newDescription;
        }
        $fields = FieldList::create();
        if ($this->CanSetDescription) {
            $fields->push(TextField::create('Description', $this->DescriptionFieldLabel, $description));
            $requiredFields[] = 'Description';
        }
        $fields->push(CurrencyField::create('Amount', $this->AmountFieldLabel, $amount));
        $requiredFields[] = 'Amount';

        $actions = FieldList::create(
            FormAction::create('doaddnewpriceform', $this->ActionFieldLabel)
        );
        $requiredFields = RequiredFields::create($requiredFields);

        return Form::create(
            $controller = $this,
            $name = 'AddNewPriceForm',
            $fields,
            $actions,
            $requiredFields
        );
    }

    public function doaddnewpriceform($data, $form)
    {
        //check amount
        $amount = $this->parseFloat($data['Amount']);
        if ($this->MinimumAmount > 0 && ($amount < $this->MinimumAmount)) {
            $form->sessionMessage(_t('AnyPriceProductPage.ERRORINFORMTOOLOW', 'Please enter a higher amount.'), 'bad');
            $this->redirectBack();

            return;
        } elseif ($this->MaximumAmount > 0 && ($amount > $this->MaximumAmount)) {
            $form->sessionMessage(_t('AnyPriceProductPage.ERRORINFORMTOOHIGH', 'Please enter a lower amount.'), 'bad');
            $this->redirectBack();

            return;
        }

        //clear settings from URL

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: Session:: (case sensitive)
  * NEW: Controller::curr()->getRequest()->getSession()-> (COMPLEX)
  * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly. 
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        Controller::curr()->getRequest()->getSession()->clear('AnyPriceProductPageAmount');

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: Session:: (case sensitive)
  * NEW: Controller::curr()->getRequest()->getSession()-> (COMPLEX)
  * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly. 
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        Controller::curr()->getRequest()->getSession()->clear('AnyPriceProductPageDescription');

        //create a description
        if (isset($data['Description']) && $data['Description']) {
            $description = Convert::raw2sql($data['Description']);
        } elseif ($this->DefaultDescription) {
            $description = $this->DefaultDescription;
        } else {
            Currency::setCurrencySymbol(EcommercePayment::site_currency());
            $titleDescriptor = new Currency('titleDescriptor');
            $titleDescriptor->setValue($amount);
            $description = _t('AnyPriceProductPage.PAYMENTFOR', 'Payment for: ').$titleDescriptor->Nice();
        }

        //create variation and update it ... if needed
        $variation = $this->createVariationFromData($amount, $description, $data);
        $variation = $this->updateProductVariation($variation, $data, $form);
        //create order item and update it ... if needed
        $orderItem = $this->createOrderItemFromVariation($variation);
        if (!$orderItem) {
            $form->sessionMessage(_t('AnyPriceProductPage.ERROROTHER', 'Sorry, we could not add your entry.'), 'bad');
            $this->redirectBack();

            return;
        }
        $orderItem = $this->updateOrderItem($orderItem, $data, $form);

        if (! $orderItem) {
            $form->sessionMessage(_t('AnyPriceProductPage.ERROROTHER', 'Sorry, we could not add your entry.'), 'bad');
            $this->redirectBack();

            return;
        }
        $checkoutPage = DataObject::get_one(CheckoutPage::class);
        if ($checkoutPage) {
            return $this->redirect($checkoutPage->Link());
        }
        return array();
    }

    public function setamount($request)
    {
        if ($amount = floatval($request->param('ID'))) {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: Session:: (case sensitive)
  * NEW: Controller::curr()->getRequest()->getSession()-> (COMPLEX)
  * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly. 
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
            Controller::curr()->getRequest()->getSession()->set('AnyPriceProductPageAmount', $amount);
        }
        if ($description = Convert::raw2sql($request->param('OtherID'))) {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: Session:: (case sensitive)
  * NEW: Controller::curr()->getRequest()->getSession()-> (COMPLEX)
  * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly. 
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
            Controller::curr()->getRequest()->getSession()->set('AnyPriceProductPageDescription', $_GET['description']);
        }
        $this->redirect($this->Link());

        return array();
    }

    /**
     * clean up the amount, we may improve this in the future.
     *
     * @return float
     */
    protected function parseFloat($floatString)
    {
        return preg_replace('/([^0-9\\.])/i', '', $floatString);
    }

    /**
     *
     * @param currency $amount
     * @param string $description
     * @param array $data (form data)
     *
     * @return ProductVariation
     */
    protected function createVariationFromData($amount, $description, $data)
    {
        //check if we have one now
        $filter = array(
            'ProductID' => $this->ID,
            'Price' => $amount,
            'Description' => $description,
        );

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $className (case sensitive)
  * NEW: $className (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        $className = $this->getClassNameOfVariations();
        $variation = DataObject::get_one(

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $className (case sensitive)
  * NEW: $className (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
            $className,
            $filter,
            $cacheDataObjectGetOne = false
        );
        if (! $variation) {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $className (case sensitive)
  * NEW: $className (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
            $variation = $className::create($filter);
        }

        $variation->AllowPurchase = true;
        $variation->write();

        // line below does not work - suspected bug in framework Versioning System
        //$componentSet->add($obj);
        return $variation;
    }

    /**
     * @param Variation (optional) $variation
     * @return OrderItem | null
     */
    protected function createOrderItemFromVariation($variation = null)
    {
        if ($variation) {
            $shoppingCart = ShoppingCart::singleton();
            $orderItem = $shoppingCart->addBuyable($variation);
            return $orderItem;
        }
    }


    /**
     * you can add this method to a class extending
     * AnyPriceProductPageController so that you can do something with the Product Variation
     *
     * @param ProductVariation $variation
     * @param array $data
     * @param Form $form
     *
     * @return ProductVariation
     */
    protected function updateProductVariation($variation, $data, $form)
    {
        return $variation;
    }

    /**
     * you can add this method to a class extending
     * AnyPriceProductPageController so that you can do something with the OrderItem
     *
     * @param OrderItem $orderItem
     * @param array $data
     * @param Form $form
     *
     * @return OrderItem
     */
    protected function updateOrderItem($orderItem, $data, $form)
    {
        return $orderItem;
    }
}
