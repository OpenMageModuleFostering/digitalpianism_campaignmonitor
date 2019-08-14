<?php
include_once MAGENTO_ROOT . "/lib/createsend/csrest_subscribers.php";

/**
 * Class DigitalPianism_CampaignMonitor_UnsubscribeController
 */
class DigitalPianism_CampaignMonitor_UnsubscribeController extends Mage_Core_Controller_Front_Action
{	
    public function indexAction()
    {
        $session = Mage::getSingleton('core/session');

        // Don't do anything if we didn't get the email parameter
        if(isset($_GET['email']))
        {
            $email = $_GET['email'];
			
			// Get the CampaignMonitor credentials
            if (Mage::helper('campaignmonitor')->isOAuth())
			{
				$accessToken = Mage::getModel('campaignmonitor/auth')->getAccessToken();
				$refreshToken = Mage::getModel('campaignmonitor/auth')->getRefreshToken();
				
				$auth = array(
							'access_token' => $accessToken,
							'refresh_token' => $refreshToken
						);
			}
			else
			{
				$auth = Mage::helper('campaignmonitor')->getApiKey();
			}
			
            $listID = Mage::helper('campaignmonitor')->getListId();
            
            // Check that the email address actually is unsubscribed in Campaign Monitor.
            if($auth && $listID)
            {
				// Retrieve the subscriber
                try 
				{
					$client = new CS_REST_Subscribers($listID,$auth);
					$result = $client->get($email);
					if (!$result->was_successful()) {
						// If you receive '121: Expired OAuth Token', refresh the access token
						if ($result->response->Code == 121) {
							// Refresh the token
							Mage::helper('campaignmonitor')->refreshToken();
						}
						// Make the call again
						$result = $client->get($email);
					}
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
				
				// If we are unsubscribed, deleted or not subscribed in Campaign Monitor, mark us as
                // unsubscribed in Magento.
                if ($state != "Unsubscribed" && $state != "Not Subscribed" && $state != "Deleted")
                {
					try
					{
						$result = $client->unsubscribe($email);
						if (!$result->was_successful()) {
							// If you receive '121: Expired OAuth Token', refresh the access token
							if ($result->response->Code == 121) {
								// Refresh the token
								Mage::helper('campaignmonitor')->refreshToken();
							}
							// Make the call again
							$result = $client->unsubscribe($email);
						}
						
						if($result->was_successful()) 
						{
							Mage::getModel('newsletter/subscriber')
									->loadByEmail($email)
									->unsubscribe();
							Mage::getSingleton('customer/session')->addSuccess($this->__('You were successfully unsubscribed'));
						}
					}
					catch (Exception $e)
                    {
                        Mage::helper('campaignmonitor')->log(sprintf("%s", $e->getMessage()));
                        Mage::getSingleton('customer/session')->addError($this->__('There was an error while saving your subscription details'));
                    }
                }
                elseif($state == "Unsubscribed" || $state == "Not Subscribed" || $state == "Deleted")
                {
					try
					{
						$subscriberStatus = Mage::getModel('newsletter/subscriber')
										->loadByEmail($email)
										->getStatus();
						// 2 = Not Activated
						// 1 = Subscribed
						// 3 = Unsubscribed
						// 4 = Unconfirmed
						if ($subscriberStatus != 3)
						{
							Mage::getModel('newsletter/subscriber')
										->loadByEmail($email)
										->unsubscribe();
							Mage::getSingleton('customer/session')->addSuccess($this->__('You were successfully unsubscribed'));
							
							$block = Mage::getModel('cms/block')->load('unsubscribe-custom-message');

							if ($block) 
							{
								Mage::getSingleton('customer/session')->addNotice($block->getContent());
							}
						}
						else
						{
							Mage::getSingleton('customer/session')->addSuccess($this->__('You have already unsubscribed to our newsletter, click <a href="/subscribe">here</a> to resubscribe'));
						}
					} catch (Exception $e) {
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
		$this->_redirect('/');
    }
}