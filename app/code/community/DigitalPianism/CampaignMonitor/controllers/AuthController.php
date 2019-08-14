<?php
class DigitalPianism_CampaignMonitor_AuthController extends Mage_Core_Controller_Front_Action
{
	// Frontend redirect URI for the CM OAuth authentication
    public function indexAction()
    {
        //@TODO check isAdmin login
        $code = $this->getRequest()->getQuery('code');
		$state = $this->getRequest()->getQuery('state');

        $adminUrl = Mage::helper("adminhtml")->getUrl("campaignmonitor/adminhtml_auth/callback", array( 'code' => $code, 'state' => $state ));

        $this->_redirectUrl($adminUrl);
        return;

    }

}
