<?php

class Prizeless_Paynow_Block_Request extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $standard = Mage::getModel('paynow/standard');
        $url = $standard->getStandardCheckoutFormFields();

        $html = '<html><body>';
        $html .= $this->__('You will be redirected to PayNow in a few seconds.');
        $html .= '<script type="text/javascript">
        setTimeout(function()
        {
         window.location.href = "' . $url . '"
         }, 2000);
        </script>';
        $html .= '</body></html>';
        return $html;
    }
}