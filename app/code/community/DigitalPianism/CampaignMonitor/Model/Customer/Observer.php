<?php
include_once MAGENTO_ROOT . "/lib/createsend/csrest_subscribers.php";

class DigitalPianism_CampaignMonitor_Model_Customer_Observer
{
    public function check_subscription_status($observer)
    {           
                
        $event = $observer->getEvent();    
        $customer = $event->getCustomer();

        $apiKey = Mage::helper('campaignmonitor')->getApiKey();
        $listID = Mage::helper('campaignmonitor')->getListId();
        
        $name = $customer->getFirstname() . " " . $customer->getLastname();
        $newEmail = $customer->getEmail();
        $subscribed = $customer->getIsSubscribed();        
        
        $oldEmail = Mage::getModel('customer/customer')->load($customer->getId())->getEmail();

        if (empty($oldEmail)) return;
        // if subscribed is NULL (i.e. because the form didn't set it one way
        // or the other), get the existing value from the database
        if($subscribed === NULL)
        {
			$subscribed = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer)->isSubscribed();
        }
        
        //print "Name: $name, New email: $newEmail, Subscribed: $subscribed, Old email: $oldEmail<br />\n";

        if($apiKey && $listID)
        {
            $customFields = Mage::helper('campaignmonitor')->generateCustomFields($customer);
            
            try 
			{
                $client = new CS_REST_Subscribers($listID,$apiKey);
            } 
			catch(Exception $e) 
			{
                Mage::helper('campaignmonitor')->log("Error connecting to CampaignMonitor server: ".$e->getMessage());
                return;
            }

            if($subscribed)
            {
                /* If the customer:
                   
                   1) Already exists (i.e. has an old email address)
                   2) Has changed their email address
                    
                   unsubscribe their old address. */
                if ($oldEmail && $newEmail != $oldEmail)
                {
                    Mage::helper('campaignmonitor')->log("Unsubscribing old email address: $oldEmail");
                    try 
					{
                        $result = $client->unsubscribe($oldEmail);
                    } 
					catch(Exception $e) 
					{
                        Mage::helper('campaignmonitor')->log("Error in SOAP call: ".$e->getMessage());
                        return;
                    }
                }
            
                // Using 'add and resubscribe' rather than just 'add', otherwise
                // somebody who unsubscribes and resubscribes won't be put back
                // on the active list
                Mage::helper('campaignmonitor')->log("Subscribing new email address: $newEmail");
                try 
				{
                    $result = $client->add(array(
                            "EmailAddress" => $newEmail,
                            "Name" => $name,
                            "CustomFields" => $customFields,
							"Resubscribe" => true));
                } 
				catch(Exception $e) 
				{
                    Mage::helper('campaignmonitor')->log("Error in SOAP call: ".$e->getMessage());
                    return;
                }
            }
            else
            {
                Mage::helper('campaignmonitor')->log("Unsubscribing: $oldEmail");
                
                try 
				{
                    $result = $client->unsubscribe($oldEmail);
                } 
				catch(Exception $e) 
				{
                    Mage::helper('campaignmonitor')->log("Error in SOAP call: ".$e->getMessage());
                    return;
                }
            }
        }
    }

    public function customer_deleted($observer)
    {
        $event = $observer->getEvent();
        $customer = $event->getCustomer();

        $apiKey = Mage::helper('campaignmonitor')->getApiKey();
		$listID = Mage::helper('campaignmonitor')->getListId();
       
        $email = $customer->getEmail();

        if($apiKey && $listID)
        {
            Mage::helper('campaignmonitor')->log("Customer deleted, unsubscribing: $email");
            try 
			{
                $client = new CS_REST_Subscribers($listID,$apiKey);
                $result = $client->unsubscribe($email);
            } 
			catch(Exception $e) 
			{
                Mage::helper('campaignmonitor')->log("Error in SOAP call: ".$e->getMessage());
                return;
            }
        }
    }
}