<?php
include_once MAGENTO_ROOT . "/lib/createsend/csrest_lists.php";
include_once MAGENTO_ROOT . "/lib/createsend/csrest_general.php";

/**
 * Class DigitalPianism_CampaignMonitor_Helper_Data
 */
class DigitalPianism_CampaignMonitor_Helper_Data extends Mage_Core_Helper_Abstract
{
	const CAMPAIGNMONITOR_CONFIG_DATA_KEY = 'newsletter/campaignmonitor/campaignmonitor_data';
	const CAMPAIGNMONITOR_SESSION_DATA_KEY = 'campaignmonitor_session_data';
	
	protected $logFileName = 'digitalpianism_campaignmonitor.log';
	
	/**
	 * Log data
	 * @param string|object|array data to log
	 */
	public function log($data) 
	{
		Mage::log($data, null, $this->logFileName);
	}
	
	/*
	 *	Check if the auth type is OAuth
	 */
    /**
     * @return bool
     */
    public function isOAuth()
	{
		if (Mage::getStoreConfig('newsletter/campaignmonitor/authentication_type') == "oauth") return true;
		else return false;
	}
	
	/*
	 *	Retrieve the API Key
	 */
    /**
     * @return string
     */
    public function getApiKey()
	{
		return trim(Mage::getStoreConfig('newsletter/campaignmonitor/api_key'));
	}
	
	/*
	 *	Retrieve the List ID
	 */
    /**
     * @return string
     */
    public function getListId()
	{
		return trim(Mage::getStoreConfig('newsletter/campaignmonitor/list_id'));
	}
	
	/*
	 *	Retrieve the Client ID
	 */
    /**
     * @return string
     */
    public function getClientId()
	{
		return trim(Mage::getStoreConfig('newsletter/campaignmonitor/client_id'));
	}
	
	/*
	 *	Retrieve the Client Secret
	 */
    /**
     * @return string
     */
    public function getClientSecret()
	{
		return trim(Mage::getStoreConfig('newsletter/campaignmonitor/client_secret'));
	}
	
	// get array of linked attributes from the config settings and
    // populate it
    /**
     * @param $customer
     * @return array
     */
    public static function generateCustomFields($customer)
    {
        $linkedAttributes = @unserialize(Mage::getStoreConfig('newsletter/campaignmonitor/m_to_cm_attributes',
                Mage::app()->getStore()->getStoreId()));
        $customFields = array();
        if(!empty($linkedAttributes))
        {
            $customerData = $customer->getData();
            foreach($linkedAttributes as $la)
            {
                $magentoAtt = $la['magento'];
                $cmAtt = $la['campaignmonitor'];
               
                // try and translate IDs to names where possible
                if($magentoAtt == 'group_id')
                {
                    $d = Mage::getModel('customer/group')->load($customer->getGroupId())->getData();
                    if(array_key_exists('customer_group_code', $d))
                    {
                        $customFields[] = array("Key" => $cmAtt, "Value" => $d['customer_group_code']);
                    }
                }
                else if($magentoAtt == 'website_id')
                {
                    $d = Mage::getModel('core/website')->load($customer->getWebsiteId())->getData();
                    if(array_key_exists('name', $d))
                    {
                        $customFields[] = array("Key" => $cmAtt, "Value" => $d['name']);
                    }
                }
                else if($magentoAtt == 'store_id')
                {
                    $d = Mage::getModel('core/store')->load($customer->getStoreId())->getData();
                    if(array_key_exists('name', $d))
                    {
                        $customFields[] = array("Key" => $cmAtt, "Value" => $d['name']);
                    }
                }
                else if(strncmp('DIGITALPIANISM', $magentoAtt, 6) == 0)
                {
                    // 15 == strlen('DIGITALPIANISM-billing-')
                    if(strncmp('DIGITALPIANISM-billing', $magentoAtt, 14) == 0)
                    {
                        $d = $customer->getDefaultBillingAddress();
                        if($d)
                        {
                            $d = $d->getData();
                            $addressAtt = substr($magentoAtt, 15, strlen($magentoAtt));
                        }
                    }
                    // 16 == strlen('DIGITALPIANISM-shipping-')
                    else
                    {
                        $d = $customer->getDefaultShippingAddress();
                        if($d)
                        {
                            $d = $d->getData();
                            $addressAtt = substr($magentoAtt, 16, strlen($magentoAtt));
                        }
                    }
                    
                    if($d and $addressAtt == 'country_id')
                    {
                        if(array_key_exists('country_id', $d))
                        {
                            $country = Mage::getModel('directory/country')->load($d['country_id']);
                            $customFields[] = array("Key" , $d=> $cmAtt, "Value" => $country->getName());
                        }
                    }
                    else if($d)
                    {
                        if(array_key_exists($addressAtt, $d))
                        {
                            $customFields[] = array("Key" => $cmAtt, "Value" => $d[$addressAtt]);
                        }
                    }
                }
                else
                {
                    if(array_key_exists($magentoAtt, $customerData))
                    {
                        $customFields[] = array("Key" => $cmAtt, "Value" => $customerData[$magentoAtt]);
                    }
                }
            }
        }
		
        return $customFields;
    }

    /**
     * Get module config section url in admin configuration
     * @return string
     */
    public function getAdminConfigSectionUrl()
    {
        $url = Mage::getModel('adminhtml/url');
        return $url->getUrl('adminhtml/system_config/edit', array(
            '_current'  => true,
            'section'   => 'newsletter'
        ));
    }
	
	/*
	 *	Refresh the token
	 */
	public function refreshToken()
	{
		// Check if auth type is OAuth
		if ($this->isOAuth())
		{
			// Get the credentials
			$accessToken = Mage::getModel('campaignmonitor/auth')->getAccessToken();
			$refreshToken = Mage::getModel('campaignmonitor/auth')->getRefreshToken();
			
			$auth = array(
						'access_token' => $accessToken,
						'refresh_token' => $refreshToken
					);
			
			// Use the REST lib to refresh the token
			$wrap = new CS_REST_General($auth);
			list($new_access_token, $new_expires_in, $new_refresh_token) = $wrap->refresh_token();
			
			// Use stdClass as it's the same type as OG response
			$response = new stdClass;
			$response->access_token = $new_access_token;
			$response->expires_in = $new_expires_in;
			$response->refresh_token = $new_refresh_token;
				
			$session = Mage::getModel('core/session');
			$session->setData(self::CAMPAIGNMONITOR_SESSION_DATA_KEY, $response);
			
			// Save $new_access_token, $new_expires_in, and $new_refresh_token
			Mage::getConfig()->saveConfig(self::CAMPAIGNMONITOR_CONFIG_DATA_KEY, serialize($response), 'default', 0);
		}
	}
	
}