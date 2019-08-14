<?php
include_once MAGENTO_ROOT . "/lib/createsend/csrest_subscribers.php";
include_once Mage::getModuleDir('controllers','Mage_Newsletter').DS."SubscriberController.php";

/**
 * Class DigitalPianism_CampaignMonitor_ManageController
 */
class DigitalPianism_CampaignMonitor_ManageController extends Mage_Newsletter_SubscriberController
{
	public function massUnsubscribeAction() 
	{
        $session = Mage::getSingleton('core/session');
        Mage::helper('campaignmonitor')->log("massUnsubscribeAction");

        $subscribersIds = $this->getRequest()->getParam('subscriber');
        if (!is_array($subscribersIds)) {
             Mage::getSingleton('adminhtml/session')->addError(Mage::helper('newsletter')->__('Please select subscriber(s)'));
             $this->_redirect('*/*/index');
        }
        else {
            try {
                
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
        
                try 
				{
                    $client = new CS_REST_Subscribers($listID,$auth);
                } 
				catch(Exception $e) 
				{
                    Mage::helper('campaignmonitor')->log("Error connecting to CampaignMonitor server: ".$e->getMessage());
                    $session->addException($e, $this->__('There was a problem with the subscription'));
                    $this->_redirectReferer();
                }

                foreach ($subscribersIds as $subscriberId) 
				{
                    $subscriber = Mage::getModel('newsletter/subscriber')->load($subscriberId);
                    $email = $subscriber->getEmail();
                    Mage::helper('campaignmonitor')->log($this->__("Unsubscribing: %s", $email));
					
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
							$client->unsubscribe($email);
						}
                    } 
					catch (Exception $e) 
					{
                        Mage::helper('campaignmonitor')->log("Error in CampaignMonitor SOAP call: ".$e->getMessage());
                    }
                }
            } 
			catch (Exception $e) 
			{
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        parent::massUnsubscribeAction();
    }
}