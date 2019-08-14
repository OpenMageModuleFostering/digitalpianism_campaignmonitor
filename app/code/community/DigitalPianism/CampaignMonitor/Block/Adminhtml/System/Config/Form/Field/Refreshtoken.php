<?php

/**
 * Class DigitalPianism_CampaignMonitor_Block_Adminhtml_System_Config_Form_Field_Refreshtoken
 */
class DigitalPianism_CampaignMonitor_Block_Adminhtml_System_Config_Form_Field_Refreshtoken extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	// Template to the button
    protected $_template = "digitalpianism/campaignmonitor/system/config/form/field/refreshtoken.phtml";

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }
}
