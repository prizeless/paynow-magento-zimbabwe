<?php

class Prizeless_Paynow_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'paynow';
    protected $_formBlockType = 'paynow/form';
    protected $_infoBlockType = 'paynow/payment_info';
    protected $_order;

    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;
    protected $_canSaveCc = false;

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function getConfig()
    {
        return Mage::getSingleton('paynow/config');
    }

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('paynow/redirect/redirect', array('_secure' => true));
    }

    public function getPaidSuccessUrl($orderId)
    {
        return Mage::getUrl('paynow/redirect/success', array('_secure' => true, 'paynow_id' => $orderId));
    }

    public function getPaidNotifyUrl($orderId)
    {
        return Mage::getUrl('paynow/notify', array('_secure' => true, 'paynow_id' => $orderId));
    }

    public function getRealOrderId()
    {
        return Mage::getSingleton('checkout/session')->getLastRealOrderId();
    }

    public function getNumberFormat($number)
    {
        return number_format($number, 2, '.', '');
    }

    public function getTotalAmount($order)
    {
        if ($this->getConfigData('use_store_currency'))
            $price = $this->getNumberFormat($order->getGrandTotal());
        else
            $price = $this->getNumberFormat($order->getBaseGrandTotal());

        return $price;
    }

    public function getStoreName()
    {
        $store_info = Mage::app()->getStore();
        return $store_info->getName();
    }

    public function getStandardCheckoutFormFields()
    {
        $orderIncrementId = $this->getCheckout()->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        $description = '';

        $intergrationId = $this->getConfigData('client_id');

        foreach ($order->getAllItems() as $items) {
            $totalPrice = $this->getNumberFormat($items->getQtyOrdered() * $items->getPrice());
            $description .=
                $this->getNumberFormat($items->getQtyOrdered()) .
                ' x ' . $items->getName() .
                ' @ ' . $order->getOrderCurrencyCode() . $this->getNumberFormat($items->getPrice()) .
                ' = ' . $order->getOrderCurrencyCode() . $totalPrice . '; ';
        }
        $description .= 'Shipping = ' . $order->getOrderCurrencyCode() . $this->getNumberFormat($order->getShippingAmount()) . ';';
        $description .= 'Total = ' . $order->getOrderCurrencyCode() . $this->getTotalAmount($order) . ';';
        $orderId = $this->getRealOrderId();
        $data = array(
            'resulturl' => $this->getPaidNotifyUrl($orderId),
            'returnurl' => $this->getPaidSuccessUrl($orderId),
            'reference' => $orderId,
            'amount' => $this->getTotalAmount($order),
            'id' => $intergrationId,
            'additionalinfo' => $description,
            'authemail' => $order->getData('customer_email'),
            'status' => 'Message'
        );
        $integrationKey = $this->getConfigData('client_int_key');
        $data['hash'] = $this->createHash($data, $integrationKey);

        $url = new Prizeless_Paynow_Url('https://www.paynow.co.zw/interface/initiatetransaction');
        $attributes = $url->runCurlActivePost($data);
        $components = $url->parseMessage($attributes);

        if(strtolower($components['status']) !== 'ok'){
            throw new \Exception('Payment gateway error. Please try again');
        }

        $this->addToPaynowTable($orderId, $components['pollurl']);

        return $components['browserurl'];
    }

    private function addToPaynowTable($orderId, $pollUrl)
    {
        $connection = Mage::getSingleton('core/resource')
            ->getConnection('core_write');
        $connection->beginTransaction();
        $fields = array();
        $fields['order_id'] = $orderId;
        $fields['paynow_poll_url'] = $pollUrl;
        $connection->insert('prizeless_paynow_transactions', $fields);
        $connection->commit();


    }

    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }

    public function getPaynowUrl()
    {
        return 'https://www.paynow.co.zw/interface/initiatetransaction';
    }


    private function createHash($values, $MerchantKey)
    {
        $string = "";
        foreach ($values as $key => $value) {
            if (strtoupper($key) != "HASH") {
                $string .= $value;
            }
        }
        $string .= $MerchantKey;

        $hash = hash("sha512", $string);
        return strtoupper($hash);
    }
}