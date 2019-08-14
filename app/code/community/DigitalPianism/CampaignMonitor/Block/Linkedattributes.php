<?php

/**
 * Class DigitalPianism_CampaignMonitor_Block_Linkedattributes
 */
class DigitalPianism_CampaignMonitor_Block_Linkedattributes extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $magentoOptions;

    /**
     *
     */
    public function __construct()
    {
        $this->addColumn('magento', array(
            'label' => Mage::helper('adminhtml')->__('Magento customer attribute'),
            'size'  => 28,
        ));
        $this->addColumn('campaignmonitor', array(
            'label' => Mage::helper('adminhtml')->__('Campaign Monitor custom field personalization tag'),
            'size'  => 28
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add linked attribute');
        
        parent::__construct();
        $this->setTemplate('digitalpianism/campaignmonitor/system/config/form/field/array_dropdown.phtml');
        
        // customer options
        $magentoAttributes = Mage::getModel('customer/customer')->getAttributes();
        $this->magentoOptions = array();
        foreach(array_keys($magentoAttributes) as $att)
        {
            if($att != 'entity_type_id'
                    and $att != 'entity_id'
                    and $att != 'attribute_set_id'
                    and $att != 'password_hash'
                    and $att != 'increment_id'
                    and $att != 'updated_at'
                    and $att != 'created_at'
                    and $att != 'email'
                    and $att != 'default_billing'
                    and $att != 'default_shipping')
            {
                // give nicer names to the attributes we're translating
                // from IDs to values
                if($att == 'store_id')
                    $name = 'Store';
                else if($att == 'group_id')
                    $name = 'Customer Group';
                else if($att == 'website_id')
                    $name = 'Website';
                else $name = $att;
                
                $this->magentoOptions[$att] = $name;
            }
        }
        asort($this->magentoOptions);
        // address options
        $this->magentoOptions['DIGITALPIANISM-billing-firstname'] = 'Billing Address: First name';
        $this->magentoOptions['DIGITALPIANISM-billing-lastname'] = 'Billing Address: Last name';
        $this->magentoOptions['DIGITALPIANISM-billing-company'] = 'Billing Address: Company';
        $this->magentoOptions['DIGITALPIANISM-billing-telephone'] = 'Billing Address: Phone';
        $this->magentoOptions['DIGITALPIANISM-billing-fax'] = 'Billing Address: Fax';
        $this->magentoOptions['DIGITALPIANISM-billing-street'] = 'Billing Address: Street';
        $this->magentoOptions['DIGITALPIANISM-billing-city'] = 'Billing Address: City';
        $this->magentoOptions['DIGITALPIANISM-billing-region'] = 'Billing Address: State/Province';
        $this->magentoOptions['DIGITALPIANISM-billing-postcode'] = 'Billing Address: Zip/Postal Code';
        $this->magentoOptions['DIGITALPIANISM-billing-country_id'] = 'Billing Address: Country';
        
        $this->magentoOptions['DIGITALPIANISM-shipping-firstname'] = 'Shipping Address: First name';
        $this->magentoOptions['DIGITALPIANISM-shipping-lastname'] = 'Shipping Address: Last name';
        $this->magentoOptions['DIGITALPIANISM-shipping-company'] = 'Shipping Address: Company';
        $this->magentoOptions['DIGITALPIANISM-shipping-telephone'] = 'Shipping Address: Phone';
        $this->magentoOptions['DIGITALPIANISM-shipping-fax'] = 'Shipping Address: Fax';
        $this->magentoOptions['DIGITALPIANISM-shipping-street'] = 'Shipping Address: Street';
        $this->magentoOptions['DIGITALPIANISM-shipping-city'] = 'Shipping Address: City';
        $this->magentoOptions['DIGITALPIANISM-shipping-region'] = 'Shipping Address: State/Province';
        $this->magentoOptions['DIGITALPIANISM-shipping-postcode'] = 'Shipping Address: Zip/Postal Code';
        $this->magentoOptions['DIGITALPIANISM-shipping-country_id'] = 'Shipping Address: Country';
    }

    /**
     * @param string $columnName
     * @return string
     * @throws Exception
     */
    protected function _renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new Exception('Wrong column name specified.');
        }
        $column     = $this->_columns[$columnName];
        $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

        if($columnName == 'magento')
        {
            $rendered = '<select name="'.$inputName.'">';
            foreach($this->magentoOptions as $att => $name)
            {
                $rendered .= '<option value="'.$att.'">'.$name.'</option>';
            }
            $rendered .= '</select>';
        }
        else
        {
            return '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}" ' . ($column['size'] ? 'size="' . $column['size'] . '"' : '') . '/>';
        }
        
        return $rendered;
    }
}
