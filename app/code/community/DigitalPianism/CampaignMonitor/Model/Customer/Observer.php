<?php
include_once MAGENTO_ROOT . "/lib/createsend/csrest_subscribers.php";

/**
 * Class DigitalPianism_CampaignMonitor_Model_Customer_Observer
 */
class DigitalPianism_CampaignMonitor_Model_Customer_Observer
{
    /**
     * @param $observer
     */
    public function check_subscription_status($observer)
    {

        $event = $observer->getEvent();
        $customer = $event->getCustomer();

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
        if($auth && $listID)
        {
            $customFields = Mage::helper('campaignmonitor')->generateCustomFields($customer);

            try
            {
                $client = new CS_REST_Subscribers($listID,$auth);
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

                        if (!$result->was_successful()) {
                            // If you receive '121: Expired OAuth Token', refresh the access token
                            if ($result->response->Code == 121) {
                                // Refresh the token
                                Mage::helper('campaignmonitor')->refreshToken();
                            }
                            // Make the call again
                            $client->unsubscribe($oldEmail);
                        }
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

                    if (!$result->was_successful()) {
                        // If you receive '121: Expired OAuth Token', refresh the access token
                        if ($result->response->Code == 121) {
                            // Refresh the token
                            Mage::helper('campaignmonitor')->refreshToken();
                        }
                        // Make the call again
                        $client->add(array(
                            "EmailAddress" => $newEmail,
                            "Name" => $name,
                            "CustomFields" => $customFields,
                            "Resubscribe" => true));
                    }
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

                    if (!$result->was_successful()) {
                        // If you receive '121: Expired OAuth Token', refresh the access token
                        if ($result->response->Code == 121) {
                            // Refresh the token
                            Mage::helper('campaignmonitor')->refreshToken();
                        }
                        // Make the call again
                        $client->unsubscribe($oldEmail);
                    }
                }
                catch(Exception $e)
                {
                    Mage::helper('campaignmonitor')->log("Error in SOAP call: ".$e->getMessage());
                    return;
                }
            }
        }
    }

    /**
     * @param $observer
     */
    public function customer_deleted($observer)
    {
        $event = $observer->getEvent();
        $customer = $event->getCustomer();

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

        $email = $customer->getEmail();

        if($auth && $listID)
        {
            Mage::helper('campaignmonitor')->log("Customer deleted, unsubscribing: $email");
            try
            {
                $client = new CS_REST_Subscribers($listID,$auth);
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
            catch(Exception $e)
            {
                Mage::helper('campaignmonitor')->log("Error in SOAP call: ".$e->getMessage());
                return;
            }
        }
    }

    /**
     * Subscribe a customer who subscribed to Mage after a successfull registration
     * @param $observer
     */
    public function subscribeCustomer(Varien_Event_Observer $observer)
    {
        // Get the customer
        $customer = $observer->getEvent()->getCustomer();
        // Get its email
        $email = $customer->getEmail();
        $name = $customer->getFirstname() . " " . $customer->getLastname();
        // Check if subscribed
        $hasSubscribed = $customer->getIsSubscribed();

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

        // Check if the customer has subscribed via OG magento checkbox
        if ($hasSubscribed)
        {
            $customFields = Mage::helper('campaignmonitor')->generateCustomFields($customer);

            // Using 'add and resubscribe' rather than just 'add', otherwise
            // somebody who unsubscribes and resubscribes won't be put back
            // on the active list
            Mage::helper('campaignmonitor')->log("Subscribing new email address: $newEmail");
            try
            {
                $client = new CS_REST_Subscribers($listID,$auth);
                $result = $client->add(array(
                    "EmailAddress" => $email,
                    "Name" => $name,
                    "CustomFields" => $customFields,
                    "Resubscribe" => true));

                if (!$result->was_successful()) {
                    // If you receive '121: Expired OAuth Token', refresh the access token
                    if ($result->response->Code == 121) {
                        // Refresh the token
                        Mage::helper('campaignmonitor')->refreshToken();
                    }
                    // Make the call again
                    $client->add(array(
                        "EmailAddress" => $email,
                        "Name" => $name,
                        "CustomFields" => $customFields,
                        "Resubscribe" => true));
                }
            }
            catch(Exception $e)
            {
                Mage::helper('campaignmonitor')->log("Error in SOAP call: ".$e->getMessage());
                return;
            }
        }
    }
}