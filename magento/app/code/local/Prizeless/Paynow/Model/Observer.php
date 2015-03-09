<?php

class Prizeless_Paynow_Model_Observer
{
    public function catalogProductLoadAfter(Varien_Event_Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
    }

    public function pollStatus()
    {

    }
}