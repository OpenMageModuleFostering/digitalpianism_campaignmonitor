<?php
// TODO MAPPING
include_once MAGENTO_ROOT . "/lib/createsend/csrest_subscribers.php";

/**
 * Class DigitalPianism_CampaignMonitor_Model_Checkout_Observer
 */
class DigitalPianism_CampaignMonitor_Model_Checkout_Observer
{
    /**
     * @param $observer
     */
    public function subscribeCustomer($observer)
    {       	 
		// Check if the checkbox has been ticked using the sessions
        if ((bool) Mage::getSingleton('checkout/session')->getCustomerIsSubscribed())
		{
			// Get the quote & customer
            $quote = $observer->getEvent()->getQuote();
            $order = $observer->getEvent()->getOrder(); 

            // if (!$order->getCustomerIsGuest()) return;
            // Mage::helper('campaignmonitor')->log('passed');

			$session = Mage::getSingleton('core/session');

			// Get the email using during checking out
			$email = $order->getCustomerEmail();	
			
			// We get the API and List ID
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
			$apiKey = Mage::helper('campaignmonitor')->getApiKey();

			// Check if already susbcribed
			try {
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
			} catch(Exception $e) {
				Mage::helper('campaignmonitor')->log("Error in REST call: ".$e->getMessage());
				$session->addException($e, Mage::helper('campaignmonitor')->__('There was a problem with the subscription'));
			}
			
			// If we are not subscribed in Campaign Monitor
			if ($result->was_successful() && $result->response->State != 'Active')
			{
				// We generate the custom fields
				if ($mobile = $quote->getBillingAddress()->getTelephone())
				{
					$customFields[] = array("Key" => "Mobile", "Value" => $mobile);
				}
				$state = $quote->getBillingAddress()->getRegion();
				$country = $quote->getBillingAddress()->getCountryId();
				if ($state || $country)
				{
					$campaignMonitorStates = Mage::helper('campaignmonitor')->getCampaignMonitorStates();
					if ($country == "AU" && in_array($state,$campaignMonitorStates))
					{
						$customFields[] = array("Key" => "State", "Value" => $state);
					}
					elseif($country == "NZ")
					{
						$customFields[] = array("Key" => "State", "Value" => "New Zealand");
					}
					elseif($country)
					{
						$customFields[] = array("Key" => "State", "Value" => "Other");
					}
					else
					{
						$customFields[] = array("Key" => "State", "Value" => "Unknown");
					}
				}
				else
				{
					$customFields[] = array("Key" => "State", "Value" => "Unknown");
				}
				if ($postcode = $quote->getBillingAddress()->getPostcode())
				{
					$customFields[] = array("Key" => "Postcode", "Value" => $postcode);
				}
				if ($dob = $quote->getCustomerDob())
				{
					$customFields[] = array("Key" => "DOB", "Value" => $dob);
				}
				// And generate the hash
				$customFields[] = array("Key" => "securehash", "Value" => md5($email.$apiKey));
				// We generate the Magento fields
				$fullname = $quote->getBillingAddress()->getName();
				$fullname = trim($fullname);
				$customFields[] = array("Key" => "fullname", "Value" => $fullname);
				
				// Check the checkout method (logged in, register or guest)
				switch ($quote->getCheckoutMethod())
				{
					// Customer is logged in
					case Mage_Sales_Model_Quote::CHECKOUT_METHOD_LOGIN_IN:					
					// Customer is registering
					case Mage_Sales_Model_Quote::CHECKOUT_METHOD_REGISTER:
					// Customer is a guest
					case Mage_Sales_Model_Quote::CHECKOUT_METHOD_GUEST:
						try {
							// Subscribe the customer to CampaignMonitor
							if($client) {
									$result = $client->add(array(
												"EmailAddress" => $email,
												"Name" => $fullname,
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
												"Name" => $fullname,
												"CustomFields" => $customFields,
												"Resubscribe" => true));
									}
							}
						}
						catch (Exception $e) 
						{
							Mage::helper('campaignmonitor')->log("Error in CampaignMonitor REST call: ".$e->getMessage());
							$session->addException($e, Mage::helper('campaignmonitor')->__('There was a problem with the subscription'));
						}
						break;
				}
			}
						
			// Remove the session variable
			Mage::getSingleton('checkout/session')->setCustomerIsSubscribed(0);
        	
        	Mage::getModel('campaignmonitor/subscriber')->syncSubscriber($email,true);
        }
    }
}