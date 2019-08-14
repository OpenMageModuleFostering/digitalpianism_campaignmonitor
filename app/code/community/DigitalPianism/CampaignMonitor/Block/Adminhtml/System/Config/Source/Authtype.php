<?php

/**
 * Class DigitalPianism_CampaignMonitor_Block_Adminhtml_System_Config_Source_Authtype
 */
class DigitalPianism_CampaignMonitor_Block_Adminhtml_System_Config_Source_Authtype extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Override method to output our custom HTML with JavaScript
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return String
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {		
        // Get the default HTML for this option
        $html = parent::_getElementHtml($element);
		// Set up additional JavaScript for our toggle action.
		// In order to hide useless fields when switching the auth type
        $javaScript = "
            <script type=\"text/javascript\">
                Event.observe('newsletter_campaignmonitor_authentication_type', 'change', function(){
                    value=$('newsletter_campaignmonitor_authentication_type').value;
                    if (value == 'api') {
						$('row_newsletter_campaignmonitor_api_key').show();
                        $('row_newsletter_campaignmonitor_client_id').hide();
                        $('row_newsletter_campaignmonitor_client_secret').hide();
                        $('row_newsletter_campaignmonitor_auth').hide();
                        $('row_newsletter_campaignmonitor_refresh_token').hide();
                    } else {
						$('row_newsletter_campaignmonitor_client_id').show();
                        $('row_newsletter_campaignmonitor_client_secret').show();
                        $('row_newsletter_campaignmonitor_auth').show();
						$('row_newsletter_campaignmonitor_refresh_token').show();
						$('row_newsletter_campaignmonitor_api_key').hide();
                    }
                });
				Event.observe(window, 'load', function(){
                    value=$('newsletter_campaignmonitor_authentication_type').value;
                    if (value == 'api') {
						$('row_newsletter_campaignmonitor_api_key').show();
                        $('row_newsletter_campaignmonitor_client_id').hide();
                        $('row_newsletter_campaignmonitor_client_secret').hide();
                        $('row_newsletter_campaignmonitor_auth').hide();
						$('row_newsletter_campaignmonitor_refresh_token').hide();
                    } else {
						$('row_newsletter_campaignmonitor_client_id').show();
                        $('row_newsletter_campaignmonitor_client_secret').show();
                        $('row_newsletter_campaignmonitor_auth').show();
						$('row_newsletter_campaignmonitor_refresh_token').show();
						$('row_newsletter_campaignmonitor_api_key').hide();
                    }
                });
            </script>";
 
        $html .= $javaScript;
        return $html;
    }
}