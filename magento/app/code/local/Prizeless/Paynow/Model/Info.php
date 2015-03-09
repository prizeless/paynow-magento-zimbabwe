<?php

class Prizeless_Paynow_Model_Info
{
    const PAYMENT_STATUS = 'payment_status';
    const M_PAYMENT_ID = 'm_payment_id';
    const PN_PAYMENT_ID = 'pf_payment_id';
    const EMAIL_ADDRESS = 'email_address';

    protected $_paymentMap = array(
        self::PAYMENT_STATUS => 'payment_status',
        self::M_PAYMENT_ID => 'm_payment_id',
        self::PN_PAYMENT_ID => 'pf_payment_id',
        self::EMAIL_ADDRESS => 'email_address',
    );

    protected $_paymentPublicMap = array(
        'email_address'
    );

    protected $_paymentMapFull = array();

    public function getPaymentInfo(Mage_Payment_Model_Info $payment, $labelValuesOnly = false)
    {
        $result = $this->_getFullInfo(array_values($this->_paymentMap), $payment, $labelValuesOnly);

        return ($result);
    }

    public function getPublicPaymentInfo(Mage_Payment_Model_Info $payment, $labelValuesOnly = false)
    {
        return $this->_getFullInfo($this->_paymentPublicMap, $payment, $labelValuesOnly);
    }

    public function importToPayment($from, Mage_Payment_Model_Info $payment)
    {
        Varien_Object_Mapper::accumulateByMap($from, array($payment, 'setAdditionalInformation'), $this->_paymentMap);
    }

    public function &exportFromPayment(Mage_Payment_Model_Info $payment, $to, array $map = null)
    {
        Varien_Object_Mapper::accumulateByMap(array($payment, 'getAdditionalInformation'),
            $to, $map ? $map : array_flip($this->_paymentMap));

        return ($to);
    }

    protected function _getFullInfo(array $keys, Mage_Payment_Model_Info $payment, $labelValuesOnly)
    {
        $result = array();

        foreach ($keys as $key) {
            if (!isset($this->_paymentMapFull[$key]))
                $this->_paymentMapFull[$key] = array();

            if (!isset($this->_paymentMapFull[$key]['label'])) {
                if (!$payment->hasAdditionalInformation($key)) {
                    $this->_paymentMapFull[$key]['label'] = false;
                    $this->_paymentMapFull[$key]['value'] = false;
                } else {
                    $value = $payment->getAdditionalInformation($key);
                    $this->_paymentMapFull[$key]['label'] = $this->_getLabel($key);
                    $this->_paymentMapFull[$key]['value'] = $this->_getValue($value, $key);
                }
            }

            if (!empty($this->_paymentMapFull[$key]['value'])) {
                if ($labelValuesOnly)
                    $result[$this->_paymentMapFull[$key]['label']] = $this->_paymentMapFull[$key]['value'];
                else
                    $result[$key] = $this->_paymentMapFull[$key];
            }
        }

        return ($result);
    }

    protected function _getLabel($key)
    {
        switch ($key) {
            case 'payment_status':
                $label = Mage::helper('paynow')->__('Payment Status');
                break;
            case 'm_payment_id':
                $label = Mage::helper('paynow')->__('Paynow payment ID');
                break;
            default:
                $label = '';
                break;
        }

        return ($label);
    }

    protected function _getValue($value, $key)
    {
        return ($value);
    }
}