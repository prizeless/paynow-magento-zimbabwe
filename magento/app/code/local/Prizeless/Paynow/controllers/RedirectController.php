<?php

class Prizeless_Paynow_RedirectController extends Mage_Core_Controller_Front_Action
{
    protected $_order;

    protected $_WHAT_STATUS = false;

    public function getOrder()
    {
        return ($this->_order);
    }

    protected function _expireAjax()
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1', '403 Session Expired');
            exit;
        }
    }

    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function getStandard()
    {
        return Mage::getSingleton('paynow/standard');
    }

    public function getConfig()
    {
        return $this->getStandard()->getConfig();
    }

    protected function _getPendingPaymentStatus()
    {
        return Mage::helper('paynow')->getPendingPaymentStatus();
    }

    public function redirectAction()
    {
        try {
            $session = Mage::getSingleton('checkout/session');

            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($session->getLastRealOrderId());

            if (!$order->getId())
                Mage::throwException('No order for processing found');

            if ($order->getState() != Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
                $order->setState(
                    Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                    $this->_getPendingPaymentStatus(),
                    Mage::helper('paynow')->__('Customer was redirected to PayNow.')
                )->save();
            }

            if ($session->getQuoteId() && $session->getLastSuccessQuoteId()) {
                $session->setPaynowQuoteId($session->getQuoteId());
                $session->setPaynowSuccessQuoteId($session->getLastSuccessQuoteId());
                $session->setPayNowRealOrderId($session->getLastRealOrderId());
                $session->getQuote()->setIsActive(false)->save();
                $session->clear();
            }

            $this->getResponse()->setBody($this->getLayout()->createBlock('paynow/request')->toHtml());
            $session->unsQuoteId();

            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getCheckout()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
        }

        $this->_redirect('checkout/cart');
    }

    public function cancelAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getPaynowQuoteId(true));
        $session = $this->_getCheckout();

        if ($quoteId = $session->getPaynowQuoteId()) {
            $quote = Mage::getModel('sales/quote')->load($quoteId);

            if ($quote->getId()) {
                $quote->setIsActive(true)->save();
                $session->setQuoteId($quoteId);
            }
        }

        $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
        if ($order->getId())
            $order->cancel()->save();

        $this->_redirect('checkout/cart');
    }

    public function successAction()
    {
        try {
            $session = Mage::getSingleton('checkout/session');;
            $session->unsPaynowRealOrderId();
            $session->setQuoteId($session->getPaynowQuoteId(true));
            $session->setLastSuccessQuoteId($session->getPaynowSuccessQuoteId(true));
            $this->_redirect('checkout/onepage/success', array('_secure' => true));

            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getCheckout()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
        }

        $this->_redirect('checkout/cart');
    }
}