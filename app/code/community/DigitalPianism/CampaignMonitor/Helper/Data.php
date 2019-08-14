<?php
include_once MAGENTO_ROOT . "/lib/createsend/csrest_lists.php";

class DigitalPianism_CampaignMonitor_Helper_Data extends Mage_Core_Helper_Abstract
{
	protected $logFileName = 'factoryx_campaignmonitor.log';
	
	/**
	 * Log data
	 * @param string|object|array data to log
	 */
	public function log($data) 
	{
		Mage::log($data, null, $this->logFileName);
	}
	
	public function getApiKey()
	{
		return trim(Mage::getStoreConfig('newsletter/campaignmonitor/api_key'));
	}
	
	public function getListId()
	{
		return trim(Mage::getStoreConfig('newsletter/campaignmonitor/list_id'));
	}
	
	// get array of linked attributes from the config settings and
    // populate it
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
                    $d = false;
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
	
}