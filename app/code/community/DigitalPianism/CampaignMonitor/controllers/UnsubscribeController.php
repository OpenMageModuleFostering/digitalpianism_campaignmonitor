<?php
include_once MAGENTO_ROOT . "/lib/createsend/csrest_subscribers.php";

class DigitalPianism_CampaignMonitor_UnsubscribeController extends Mage_Core_Controller_Front_Action
{	
    public function indexAction()
    {
        // Don't do anything if we didn't get the email parameter
        if(isset($_GET['email']))
        {
            $email = $_GET['email'];
			
			// Get the CampaignMonitor credentials
            $apiKey = Mage::helper('campaignmonitor')->getApiKey();
            $listID = Mage::helper('campaignmonitor')->getListId();
            
            // Check that the email address actually is unsubscribed in Campaign Monitor.
            if($apiKey && $listID)
            {
				// Retrieve the subscriber
                try 
				{
					$client = new CS_REST_Subscribers($listID,$apiKey);
					$result = $client->get($email);
                } 
				catch (Exception $e) 
				{
                    Mage::helper('campaignmonitor')->log(sprintf("Error in SOAP call: %s", $e->getMessage()));
                    $session->addException($e, $this->__('There was a problem with the unsubscription'));
                    $this->_redirectReferer();
                }

				// Get the subscription state
                $state = "";
                try
				{
					if($result->was_successful() && isset($result->response->State)) 
					{
						$state = $result->response->State;
					}
				} 
				catch(Exception $e) 
				{
					Mage::helper('campaignmonitor')->log(sprintf("Error in SOAP call: %s", $e->getMessage()));
                    $session->addException($e, $this->__('There was a problem with the unsubscription'));
                    $this->_redirectReferer();
				}
                
				// If we are unsubscribed in Campaign Monitor, mark us as
                // unsubscribed in Magento.
                if($state == "Unsubscribed")
                {
					try
					{
						Mage::helper('campaignmonitor')->log($this->__('Unsubscribing %s from Magento',$email));
						
						$unsubscribe = Mage::getModel('newsletter/subscriber')
									->loadByEmail($email)
									->unsubscribe();
						Mage::getSingleton('customer/session')->addSuccess($this->__('You were successfully unsubscribed'));
					} 
					catch (Exception $e) 
					{
                        Mage::helper('campaignmonitor')->log(sprintf("%s", $e->getMessage()));
                        Mage::getSingleton('customer/session')->addError($this->__('There was an error while saving your subscription details'));
                    }
                }
				else
                {
                    Mage::helper('campaignmonitor')->log($this->__("Not unsubscribing %s, not unsubscribed in Campaign Monitor",$email));
                }
            }
        }
    }
}
?>