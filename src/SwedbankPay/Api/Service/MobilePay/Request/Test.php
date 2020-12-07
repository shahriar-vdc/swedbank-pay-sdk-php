<?php

namespace SwedbankPay\Api\Service\MobilePay\Request;

use SwedbankPay\Api\Service\Base\Request\Test as BaseTest;
use SwedbankPay\Api\Service\MobilePay\Resource\Request\PaymentPayeeInfo;
use SwedbankPay\Api\Service\MobilePay\Resource\Request\Payment;
use SwedbankPay\Api\Service\MobilePay\Resource\Request\PaymentObject;
use SwedbankPay\Api\Client\Exception;

class Test extends BaseTest
{
    /**
     * Test constructor.
     * @param string $merchantToken
     * @param string $payeeId
     * @param bool $isTest
     * @throws Exception
     */
    public function __construct($merchantToken, $payeeId, $isTest = true)
    {
        $payeeInfo = new PaymentPayeeInfo();
        $payeeInfo->setPayeeId($payeeId);

        $payment = new Payment();
        $payment->setOperation('Test')
            ->setPayeeInfo($payeeInfo);

        $paymentObject = new PaymentObject();
        $paymentObject->setPayment($payment);

        $request = new Purchase($paymentObject);
        $this->sendRequest($merchantToken, $payeeId, $isTest, $request);
    }
}
