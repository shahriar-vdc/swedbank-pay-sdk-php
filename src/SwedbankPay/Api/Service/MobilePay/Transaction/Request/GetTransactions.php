<?php

namespace SwedbankPay\Api\Service\MobilePay\Transaction\Request;

use SwedbankPay\Api\Service\Payment\Transaction\Resource\Response\TransactionsObject;
use SwedbankPay\Api\Service\Request;

class GetTransactions extends Request
{
    public function setup()
    {
        $this->setRequestMethod('GET');
        $this->setRequestEndpoint('/psp/mobilepay/payments/%s/transactions');
        $this->setResponseResourceFQCN(TransactionsObject::class);
    }
}