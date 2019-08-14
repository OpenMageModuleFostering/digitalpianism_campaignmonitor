<?php
include_once MAGENTO_ROOT . "/lib/createsend/csrest_subscribers.php";
include_once Mage::getModuleDir('controllers','Mage_Newsletter').DS."SubscriberController.php";

class DigitalPianism_CampaignMonitor_HookController extends Mage_Newsletter_SubscriberController
{
    public function newAction()
	{
		if ($this->getRequest()->isPost() && $this->getRequest()->getPost('email')) 
		{
            $session   = Mage::getSingleton('core/session');
            $email     = (string)$this->getRequest()->getPost('email');
            
            Mage::log("Fontis_CampaignMonitor: Adding newsletter subscription via frontend 'Sign up' block for $email");

            $apiKey = Mage::helper('campaignmonitor')->getApiKey();
            $listID = Mage::helper('campaignmonitor')->getListId();
        
            if($apiKey && $listID) 
			{
				try 
				{
                    $client = new CS_REST_Subscribers($listID,$apiKey);
                } 
				catch(Exception $e) 
				{
                    Mage::helper('campaignmonitor')->log("Error connecting to CampaignMonitor server: ".$e->getMessage());
                    $session->addException($e, $this->__('There was a problem with the subscription'));
                    $this->_redirectReferer();
                }

                // if a user is logged in, fill in the Campaign Monitor custom
                // attributes with the data for the logged-in user
                $customerHelper = Mage::helper('customer');
				
                if($customerHelper->isLoggedIn()) 
				{
                    $customer = $customerHelper->getCustomer();
                    $name = $customer->getFirstname() . " " . $customer->getLastname();
                    $customFields = DigitalPianism_CampaignMonitor_Model_Customer_Observer::generateCustomFields($customer);
					
                    try 
					{
						$result = $client->add(array(
												"EmailAddress" => $email,
												"Name" => $name,
												"CustomFields" => $customFields,
												"Resubscribe" => true  // if the subscriber is already unsubscried - subscribe again!
												));
                    } 
					catch(Exception $e) 
					{
                        Mage::helper('campaignmonitor')->log("Error in CampaignMonitor SOAP call: ".$e->getMessage());
                        $session->addException($e, $this->__('There was a problem with the subscription'));
                        $this->_redirectReferer();
                    }
                } 
				else 
				{
                    // otherwise if nobody's logged in, ignore the custom
                    // attributes and just set the name to '(Guest)'
                    try 
					{
						$result = $client->add(array(
												"EmailAddress" => $email,
												"Name" => "(Guest)",
												"Resubscribe" => true  // if the subscriber is already unsubscried - subscribe again!
												));
                    } 
					catch (Exception $e) 
					{
                        Mage::helper('campaignmonitor')->log("Error in CampaignMonitor SOAP call: ".$e->getMessage());
                        $session->addException($e, $this->__('There was a problem with the subscription'));
                        $this->_redirectReferer();
                    }
                }
            } 
			else 
			{
                Mage::helper('campaignmonitor')->log("Error: Campaign Monitor API key and/or list ID not set in Magento Newsletter options.");
            }
        }

        parent::newAction();
    }
}