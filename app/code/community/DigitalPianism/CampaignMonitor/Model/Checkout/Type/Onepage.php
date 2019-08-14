<?php

/**
 * Class DigitalPianism_CampaignMonitor_Model_Checkout_Type_Onepage
 */
class DigitalPianism_CampaignMonitor_Model_Checkout_Type_Onepage extends Mage_Checkout_Model_Type_Onepage
{
    /**
     * @param array $data
     * @param int $customerAddressId
     * @return Mage_Checkout_Model_Type_Onepage
     */
    public function saveBilling($data, $customerAddressId)
    {
        if (isset($data['is_subscribed']) && !empty($data['is_subscribed']))
		{
            $this->getCheckout()->setCustomerIsSubscribed(1);
        }
        else 
		{
            $this->getCheckout()->setCustomerIsSubscribed(0);
        }
        return parent::saveBilling($data, $customerAddressId);
    }
}
