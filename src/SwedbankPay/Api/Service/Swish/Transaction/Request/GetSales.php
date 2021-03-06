<?php

namespace SwedbankPay\Api\Service\Swish\Transaction\Request;

use SwedbankPay\Api\Service\Payment\Transaction\Resource\Response\SalesObject;
use SwedbankPay\Api\Service\Request;

class GetSales extends Request
{
    public function setup()
    {
        $this->setRequestMethod('GET');
        $this->setOperationRel('sales');
        $this->setResponseResourceFQCN(SalesObject::class);
    }
}
