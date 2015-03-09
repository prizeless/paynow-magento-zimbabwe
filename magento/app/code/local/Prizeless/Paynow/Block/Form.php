<?php
class Prizeless_Paynow_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate( 'paynow/form.phtml' );
    }
}