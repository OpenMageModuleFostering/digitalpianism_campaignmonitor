<?php
class DigitalPianism_CampaignMonitor_Model_Auth
{
    const CAMPAIGNMONITOR_SESSION_DATA_KEY = 'campaignmonitor_session_data';
    const CAMPAIGNMONITOR_CONFIG_DATA_KEY = 'newsletter/campaignmonitor/campaignmonitor_data';
    
    public function getUserData()
    {
        /** @var $session Mage_Core_Model_Session  */
        $session = Mage::getModel('core/session');
        $info = $session->getData(self::CAMPAIGNMONITOR_SESSION_DATA_KEY);
   
        if (!$info) {
            $configDataKey = self::CAMPAIGNMONITOR_CONFIG_DATA_KEY;
               
            $info = unserialize(Mage::getStoreConfig($configDataKey, 0));
        }

        return $info;
    }

    public function isValid()
    {
        $configDataKey = self::CAMPAIGNMONITOR_CONFIG_DATA_KEY;
        return (!!$this->getUserData() || Mage::getStoreConfig($configDataKey, 0));
    }

    public function getAccessToken()
    {
        return $this->getUserData()->access_token;
    }
	
	public function getRefreshToken()
    {
        return $this->getUserData()->refresh_token;
    }

}
