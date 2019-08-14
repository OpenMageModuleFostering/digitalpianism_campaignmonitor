<?php
class DigitalPianism_CampaignMonitor_Model_System_Config_Source_Authtype
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'api', 'label'=>Mage::helper('campaignmonitor')->__('API Key')),
            array('value' => 'oauth', 'label'=>Mage::helper('campaignmonitor')->__('OAuth 2')),
        );
    }
}