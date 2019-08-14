<?php

/**
 * Class DigitalPianism_CampaignMonitor_Block_Adminhtml_System_Config_Form_Field_Auth
 */
class DigitalPianism_CampaignMonitor_Block_Adminhtml_System_Config_Form_Field_Auth extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	// Template to the button
    protected $_template = "digitalpianism/campaignmonitor/system/config/form/field/auth.phtml";

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

	// Get the OAuth data once authenticated
    public function getUserData()
    {
        $info = Mage::getModel('campaignmonitor/auth')->getUserData();
        return $info;
    }

}
