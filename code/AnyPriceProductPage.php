<?php
/**
 * @author nicolaas [at] sunnysideup.co.nz
 * @requires ecommerce
 * @requires ecommerce_product_variation
 */
class AnyPriceProductPage extends Product
{
    private static $db = array(
        'DescriptionFieldLabel' => 'Varchar(255)',
        'AmountFieldLabel' => 'Varchar(255)',
        'ActionFieldLabel' => 'Varchar(255)',
        'MinimumAmount' => 'Decimal(9,2)',
        'MaximumAmount' => 'Decimal(9,2)',
        'RecommendedAmounts' => 'Varchar(255)',
        'CanSetDescription' => 'Boolean',
        'DefaultDescription' => 'Varchar(255)',
    );

    private static $defaults = array(
        'DescriptionFieldLabel' => 'Enter Description',
        'AmountFieldLabel' => 'Enter Amount',
        'ActionFieldLabel' => 'Add to cart',
        'MinimumAmount' => 1,
        'MaximumAmount' => 100,
        'AllowPurchase' => false,
        'Price' => 0,
    );

    private static $field_labels = array(
        'DescriptionFieldLabel' => 'Description Label',
        'AmountFieldLabel' => 'Amount Label',
        'ActionFieldLabel' => 'Button Label',
        'MinimumAmount' => 'Minimum Amount',
        'MaximumAmount' => 'Maximum Amount',
        'RecommendedAmounts' => 'Hinted amounts',
        'CanSetDescription' => 'Customer Adds Description',
        'DefaultDescription' => 'Default Description',
    );

    private static $field_labels_right = array(
        'DescriptionFieldLabel' => 'e.g. please enter title for payment',
        'AmountFieldLabel' => 'e.g. please enter amount for payment',
        'ActionFieldLabel' => 'e.g. pay now',
        'MinimumAmount' => 'e.g. 10.00',
        'MaximumAmount' => 'e.g. 100.00',
        'RecommendedAmounts' => 'create a list of recommended payment amounts, separated by a space, e.g. 10.00 12.00 19.00 23.00',
        'CanSetDescription' => 'can the customer add their own description to the payment?',
        'DefaultDescription' => 'e.g. generic product, this field is optional',
    );

    private static $singular_name = 'Any Price Product';
    public function i18n_singular_name()
    {
        return _t('AnyPriceProductPage.ANY_PRICE_PRODUCT', 'Any Price Product');
    }

    private static $plural_name = 'Any Price Products';
    public function i18n_plural_name()
    {
        return _t('AnyPriceProductPage.ANY_PRICE_PRODUCTS', 'Any Price Products');
    }

    private static $icon = 'ecommerce_anypriceproduct/images/treeicons/AnyPriceProductPage';

    /**
     * @config
     *
     * @var string Description of the class functionality, typically shown to a user
     *             when selecting which page type to create. Translated through {@link provideI18nEntities()}.
     */
    private static $description = 'Generic product that can be used to allow customers to choose a specific amount to pay.';

    public function canCreate($member = null)
    {
        return SiteTree::get()->filter(array('ClassName' => 'AnyPriceProductPage'))->count() ? false : true;
    }

    public function canPurchase(Member $member = null, $checkPrice = true)
    {
        return false;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fieldLabels = $this->fieldLabels();
        $fieldLabelsRight = Config::inst()->get('AnyPriceProductPage', 'field_labels_right');
        $exampleLink = Director::absoluteURL($this->Link('setamount')).'/123.45/?description='.urlencode('test payment only');
        $exampleLinkExplanation = sprintf(_t('AnyPriceProductPage.EXPLANATION', '<br /><br /><h5>How to preset the amount?</h5><p>The link <a href="%1$s">%1$s</a> will pre-set the amount to 123.45. You can use this link (and vary the amount as needed) to cutomers to receive payments.</p>.'), $exampleLink);
        $fields->addFieldsToTab(
            'Root.Form',
            array(
                TextField::create('DescriptionFieldLabel', $fieldLabels['DescriptionFieldLabel'])->setDescription($fieldLabelsRight['DescriptionFieldLabel']),
                TextField::create('AmountFieldLabel', $fieldLabels['AmountFieldLabel'])->setDescription($fieldLabelsRight['AmountFieldLabel']),
                TextField::create('ActionFieldLabel', $fieldLabels['ActionFieldLabel'])->setDescription($fieldLabelsRight['ActionFieldLabel']),
                NumericField::create('MinimumAmount', $fieldLabels['MinimumAmount'])->setDescription($fieldLabelsRight['MinimumAmount']),
                NumericField::create('MaximumAmount', $fieldLabels['MaximumAmount'])->setDescription($fieldLabelsRight['MaximumAmount']),
                TextField::create('RecommendedAmounts', $fieldLabels['RecommendedAmounts'])->setDescription($fieldLabelsRight['RecommendedAmounts']),
                CheckboxField::create('CanSetDescription', $fieldLabels['CanSetDescription'])->setDescription($fieldLabelsRight['CanSetDescription']),
                TextField::create('DefaultDescription', $fieldLabels['DefaultDescription'])->setDescription($fieldLabelsRight['DefaultDescription']),
                LiteralField::create('ExampleLinkExplanation', $exampleLinkExplanation),
            )
        );
        if (!$this->CanSetDescription) {
            $fields->removeByName('DescriptionFieldLabel');
        }
        // Standard product detail fields
        $fields->removeFieldsFromTab(
            'Root.Details',
            array(
                'Weight',
                'Price',
                'Model',
            )
        );

        // Flags for this product which affect it's behaviour on the site
        $fields->removeFieldsFromTab(
            'Root.Details',
            array(
                'FeaturedProduct',
            )
        );

        return $fields;
    }
}

class AnyPriceProductPage_Controller extends Product_Controller
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
        $className = $this->getClassNameOfVariations();
        if (count($options)) {
            return $className::get()->filter(array(
                'ProductID' => $this->ID,
                'Price' => $options,
            ));
        } else {
            return $className::get()->filter(array(
                'ProductID' => $this->ID,
            ));
        }
    }

    public function AddNewPriceForm()
    {
        $requiredFields = array();
        $amount = $this->MinimumAmount;
        if ($newAmount = Session::get('AnyPriceProductPageAmount')) {
            $amount = $newAmount;
        }
        $description = $this->DefaultDescription;
        if ($newDescription = Session::get('AnyPriceProductPageDescription')) {
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
        Session::clear('AnyPriceProductPageAmount');
        Session::clear('AnyPriceProductPageDescription');

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
        if(!$orderItem) {
            $form->sessionMessage(_t('AnyPriceProductPage.ERROROTHER', 'Sorry, we could not add your entry.'), 'bad');
            $this->redirectBack();

            return;
        }
        $orderItem = $this->updateOrderItem($orderItem, $data, $form);

        if( ! $orderItem) {
            $form->sessionMessage(_t('AnyPriceProductPage.ERROROTHER', 'Sorry, we could not add your entry.'), 'bad');
            $this->redirectBack();

            return;
        }
        $checkoutPage = CheckoutPage::get()->First();
        if ($checkoutPage) {

            return $this->redirect($checkoutPage->Link());
        }
        return array();
    }

    public function setamount($request)
    {
        if ($amount = floatval($request->param('ID'))) {
            Session::set('AnyPriceProductPageAmount', $amount);
        }
        if ($description = Convert::raw2sql($request->param('OtherID'))) {
            Session::set('AnyPriceProductPageDescription', $_GET['description']);
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
    protected function createVariationFromData($amount, $description, $data) {
        //check if we have one now
        $filter = array(
            'ProductID' => $this->ID,
            'Price' => $amount,
            'Description' => $description,
        );
        $className = $this->getClassNameOfVariations();
        $variation = $className::get()->filter($filter)->First();
        if (!$variation) {
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
     * AnyPriceProductPage_Controller so that you can do something with the Product Variation
     *
     * @param ProductVariation $variation
     * @param array $data
     * @param Form $form
     *
     * @return ProductVariation
     */
    protected function updateProductVariation($variation, $data, $form) {
        return $variation;
    }

    /**
     * you can add this method to a class extending
     * AnyPriceProductPage_Controller so that you can do something with the OrderItem
     *
     * @param OrderItem $orderItem
     * @param array $data
     * @param Form $form
     *
     * @return OrderItem
     */
    protected function updateOrderItem($orderItem, $data, $form) {
        return $orderItem;
    }
}
