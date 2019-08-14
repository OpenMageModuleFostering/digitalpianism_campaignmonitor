<?php

/**
 * Class DigitalPianism_CampaignMonitor_Model_System_Config_Source_Authtype
 */
class DigitalPianism_CampaignMonitor_Model_System_Config_Source_Authtype
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'api', 'label'=>Mage::helper('campaignmonitor')->__('API Key')),
            array('value' => 'oauth', 'label'=>Mage::helper('campaignmonitor')->__('OAuth 2')),
        );
    }
}