<?php
require_once MAGENTO_ROOT . "/lib/createsend/csrest_general.php";

/**
 * Class DigitalPianism_CampaignMonitor_Adminhtml_CampaignmonitorController
 */
class DigitalPianism_CampaignMonitor_Adminhtml_CampaignmonitorController extends Mage_Adminhtml_Controller_Action
{

    const CAMPAIGNMONITOR_AUTH_URL = 'https://api.createsend.com/oauth';
    const CAMPAIGNMONITOR_ACCESSS_TOKEN_URL = 'https://api.createsend.com/oauth/token';

    const CAMPAIGNMONITOR_SESSION_DATA_KEY = 'campaignmonitor_session_data';
    const CAMPAIGNMONITOR_CONFIG_DATA_KEY = 'newsletter/campaignmonitor/campaignmonitor_data';

    /**
     * Check for is allowed
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/newsletter');
    }

    public function preDispatch()
    {
        Mage::getSingleton('adminhtml/url')->turnOffSecretKey();
        parent::preDispatch();
    }

	public function indexAction()
    {
        $this->_redirectUrl($this->_getAuthUrl());
    }
	
	public function refreshtokenAction()
    {
        Mage::helper('campaignmonitor')->refreshToken();
		
		$redirectUrl = Mage::helper('campaignmonitor')->getAdminConfigSectionUrl();
        $this->_redirectUrl($redirectUrl);
    }

    public function callbackAction()
    {
        $code = $this->getRequest()->getParam('code');
        //$state = $this->getRequest()->getParam('state');
        $response = $this->_getAccessToken($code);
		if ($response)
		{
			/** @var $session Mage_Core_Model_Session  */
			$session = Mage::getModel('core/session');
			$session->setData(self::CAMPAIGNMONITOR_SESSION_DATA_KEY, $response);

			Mage::getConfig()->saveConfig(self::CAMPAIGNMONITOR_CONFIG_DATA_KEY, serialize($response), 'default', 0);
		}
		else
		{
			Mage::helper('campaignmonitor')->log("There has been an error during the callback action to retrieve the access token");
		}

        $redirectUrl = Mage::helper('campaignmonitor')->getAdminConfigSectionUrl();
        $this->_redirectUrl($redirectUrl);
    }

    /**
     * @param $code
     * @return bool|mixed
     */
    protected function _getAccessToken($code)
    {
        $result = CS_REST_General::exchange_token(
			$this->_getClientId(),
			$this->_getClientSecret(),
			$this->_getAuthRedirectUri(),
			$code
		);
		
		if ($result->was_successful()) {
			/*
			$access_token = $result->response->access_token;
			$expires_in = $result->response->expires_in;
			$refresh_token = $result->response->refresh_token;
			*/
			return $result->response;
		} else {
			echo 'An error occurred:\n';
			echo $result->response->error.': '.$result->response->error_description."\n";
			return false;
		}

    }

    /**
     * Get url for authentification on Instagram
     * @return string
     */
    protected function _getAuthUrl()
    {
		$url = CS_REST_General::authorize_url(
			$this->_getClientId(),
			$this->_getAuthRedirectUri(),
			'ImportSubscribers,ManageLists'
		);
		
        return $url;
    }

    /**
     * @return mixed
     * @throws Mage_Core_Exception
     */
    protected function _getAuthRedirectUri()
    {
        return str_replace('http://','https://',Mage::app()->getStore(1)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK)."campaignmonitor/auth/index");
    }

    protected function _getClientId()
    {
		return Mage::helper('campaignmonitor')->getClientId();
    }


    protected function _getClientSecret()
    {
		return Mage::helper('campaignmonitor')->getClientSecret();
    }

}
