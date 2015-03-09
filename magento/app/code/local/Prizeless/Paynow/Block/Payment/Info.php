<?php
class Prizeless_Paynow_Block_Payment_Info extends Mage_Payment_Block_Info
{
    protected function _prepareSpecificInformation( $transport = null )
    {
        $transport = parent::_prepareSpecificInformation( $transport );
        $payment = $this->getInfo();
        $pfInfo = Mage::getModel( 'paynow/info' );
        
        if( !$this->getIsSecureMode() )
            $info = $pfInfo->getPaymentInfo( $payment, true );
        else
            $info = $pfInfo->getPublicPaymentInfo( $payment, true );

        return( $transport->addData( $info ) );
    }
}