<?php
include_once MAGENTO_ROOT . "/lib/createsend/csrest_subscribers.php";
include_once Mage::getModuleDir('controllers','Mage_Newsletter').DS."SubscriberController.php";

class DigitalPianism_CampaignMonitor_ManageController extends Mage_Newsletter_SubscriberController
{
	public function massUnsubscribeAction() 
	{
        Mage::helper('campaignmonitor')->log("massUnsubscribeAction");

        $subscribersIds = $this->getRequest()->getParam('subscriber');
        if (!is_array($subscribersIds)) {
             Mage::getSingleton('adminhtml/session')->addError(Mage::helper('newsletter')->__('Please select subscriber(s)'));
             $this->_redirect('*/*/index');
        }
        else {
            try {
                $apiKey = Mage::helper('campaignmonitor')->getApiKey();
                $listID = Mage::helper('campaignmonitor')->getListId();
        
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

                foreach ($subscribersIds as $subscriberId) 
				{
                    $subscriber = Mage::getModel('newsletter/subscriber')->load($subscriberId);
                    $email = $subscriber->getEmail();
                    Mage::helper('campaignmonitor')->log($this->__("Unsubscribing: %s", $email));
					
                    try 
					{
                        $result = $client->unsubscribe($email);
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
?>