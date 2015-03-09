<?php

class Prizeless_Paynow_NotifyController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $completeStatus = array('Paid', 'Awaiting Delivery', 'Delivered');
        $orderId = $this->getRequest()->getParam('paynow_id');

        $pollUrl = $this->getPollData($orderId);

        $orderStatus = $this->getOrderStatus($pollUrl);

        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($orderId);
        $this->_storeID = $order->getStoreId();
        $orderProcessed = false;

        if (in_array($order->getStatus(), $completeStatus) === true) {
            $orderProcessed = true;
        }
        if ($orderProcessed === false) {

            $payment = $order->getPayment();
            $payment->setAdditionalInformation("payment_status", $orderStatus['status']);
            $payment->setAdditionalInformation("m_payment_id", $orderStatus['paynowreference']);
            $payment->setAdditionalInformation("amount_fee", $orderStatus['amount']);
            $payment->save();

            $this->saveInvoice($order);
        }
    }

    private function getOrderStatus($pollUrl)
    {
        $url = new Prizeless_Paynow_Url($pollUrl);

        $result = $url->runCurlActiveGet($pollUrl);

        return  $url->parseMessage($result);
    }

    protected function saveInvoice(Mage_Sales_Model_Order $order)
    {
        $invoice = $order->prepareInvoice();

        $invoice->register()->capture();
        Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();

        $message = Mage::helper('paynow')->__('Notified customer about invoice #%s.', $invoice->getIncrementId());
        $order->sendNewOrderEmail()->addStatusHistoryComment($message)
            ->setIsCustomerNotified(true)
            ->save();
    }

    private function getPollData($orderId)
    {
        $coreResource = Mage::getSingleton('core/resource');
        $connection = $coreResource->getConnection('core_read');
        $data = $connection->fetchCol('SELECT `paynow_poll_url` FROM `prizeless_paynow_transactions` WHERE `order_id` = ' . $orderId);

        return $data[0];
    }
}