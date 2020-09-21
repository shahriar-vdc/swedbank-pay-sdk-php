<?php

// phpcs:disable
use SwedbankPay\Api\Client\Client;
use SwedbankPay\Api\Service\Payment\Resource\Collection\PricesCollection;
use SwedbankPay\Api\Service\Payment\Resource\Collection\Item\PriceItem;
use SwedbankPay\Api\Service\Payment\Resource\Request\Metadata;
use SwedbankPay\Api\Service\MobilePay\Request\Purchase;

use SwedbankPay\Api\Service\MobilePay\Resource\Request\PaymentPayeeInfo;
use SwedbankPay\Api\Service\MobilePay\Resource\Request\PaymentPrefillInfo;
use SwedbankPay\Api\Service\MobilePay\Resource\Request\PaymentUrl;
use SwedbankPay\Api\Service\MobilePay\Resource\Request\Payment;
use SwedbankPay\Api\Service\MobilePay\Resource\Request\PaymentObject;

use SwedbankPay\Api\Service\Data\ResponseInterface as ResponseServiceInterface;
use SwedbankPay\Api\Service\Resource\Data\ResponseInterface as ResponseResourceInterface;

use SwedbankPay\Api\Service\MobilePay\Transaction\Request\CreateCapture;
use SwedbankPay\Api\Service\MobilePay\Transaction\Request\CreateReversal;
use SwedbankPay\Api\Service\MobilePay\Transaction\Request\CreateCancellation;
use SwedbankPay\Api\Service\MobilePay\Transaction\Resource\Request\TransactionCapture;
use SwedbankPay\Api\Service\MobilePay\Transaction\Resource\Request\TransactionReversal;
use SwedbankPay\Api\Service\MobilePay\Transaction\Resource\Request\TransactionCancellation;

use SwedbankPay\Api\Service\Payment\Transaction\Resource\Request\TransactionObject;
use SwedbankPay\Api\Service\Payment\Transaction\Resource\Response\AuthorizationObject;
use SwedbankPay\Api\Service\Payment\Transaction\Resource\Response\CaptureObject;
use SwedbankPay\Api\Service\Payment\Transaction\Resource\Response\ReversalObject;
use SwedbankPay\Api\Service\Payment\Transaction\Resource\Response\CancellationObject;
use SwedbankPay\Api\Service\Payment\Transaction\Resource\Response\TransactionObject as TransactionObjectResponse;

use SwedbankPay\Api\Service\MobilePay\Transaction\Request\GetAuthorization;
use SwedbankPay\Api\Service\MobilePay\Transaction\Request\GetCapture;
use SwedbankPay\Api\Service\MobilePay\Transaction\Request\GetReversal;
use SwedbankPay\Api\Service\MobilePay\Transaction\Request\GetCancellation;
use SwedbankPay\Api\Service\MobilePay\Transaction\Request\GetTransaction;

use SwedbankPay\Api\Service\MobilePay\Transaction\Request\GetAuthorizations;
use SwedbankPay\Api\Service\MobilePay\Transaction\Request\GetCaptures;
use SwedbankPay\Api\Service\MobilePay\Transaction\Request\GetReversals;
use SwedbankPay\Api\Service\MobilePay\Transaction\Request\GetCancellations;
use SwedbankPay\Api\Service\MobilePay\Transaction\Request\GetTransactions;

use SwedbankPay\Api\Service\Payment\Transaction\Resource\Response\AuthorizationsObject;
use SwedbankPay\Api\Service\Payment\Transaction\Resource\Response\CancellationsObject;
use SwedbankPay\Api\Service\Payment\Transaction\Resource\Response\CapturesObject;
use SwedbankPay\Api\Service\Payment\Transaction\Resource\Response\ReversalsObject;
use SwedbankPay\Api\Service\Payment\Transaction\Resource\Response\TransactionsObject;
// phpcs:enable

/**
 * Class MobilePayPaymentTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MobilePayPaymentTest extends TestCase
{
    protected $paymentId = '/psp/mobilepay/payments/39465757-14e6-481c-a5c7-08d8509a065a';

    protected function setUp(): void
    {
        // phpcs:disable
        if (!defined('MERCHANT_TOKEN_MOBILEPAY') ||
            MERCHANT_TOKEN_MOBILEPAY === '<merchant_token>') {
            $this->fail('MERCHANT_TOKEN_MOBILEPAY not configured in INI file or environment variable.');
        }
        // phpcs:enable

        // phpcs:disable
        if (!defined('PAYEE_ID_MOBILEPAY') ||
            PAYEE_ID_MOBILEPAY === '<payee_id>') {
            $this->fail('PAYEE_ID_MOBILEPAY not configured in INI file or environment variable.');
        }
        // phpcs:enable

        $this->client = new Client();
        $this->client->setMerchantToken(MERCHANT_TOKEN_MOBILEPAY)
            ->setPayeeId(PAYEE_ID_MOBILEPAY)
            ->setMode(Client::MODE_TEST);
    }

    /**
     * @throws \SwedbankPay\Api\Client\Exception
     */
    public function testPurchaseRequest()
    {
        $url = new PaymentUrl();
        $url->setCompleteUrl('https://test-dummy.net/payment-completed')
            ->setCancelUrl('https://test-dummy.net/payment-canceled')
            ->setCallbackUrl('https://test-dummy.net/payment-callback')
            ->setHostUrls(['https://test-dummy.net']);

        $payeeInfo = new PaymentPayeeInfo();
        $payeeInfo->setPayeeId(PAYEE_ID_MOBILEPAY)
            ->setPayeeReference($this->generateRandomString(12))
            ->setPayeeName('Merchant1')
            ->setProductCategory('A123')
            ->setOrderReference('or-123456')
            ->setSubsite('MySubsite');

        $prefillInfo = new PaymentPrefillInfo();
        $prefillInfo->setMsisdn('+45739000001');

        $price = new PriceItem();
        $price->setType('MobilePay')
            ->setAmount(1500)
            ->setVatAmount(0);

        $prices = new PricesCollection();
        $prices->addItem($price);

        $metadata = new Metadata();
        $metadata->setData('order_id', 'or-123456');

        $payment = new Payment();
        $payment->setOperation('Purchase')
            ->setIntent('Authorization')
            ->setCurrency('DKK')
            ->setDescription('Test Purchase')
            ->setUserAgent('Mozilla/5.0...')
            ->setLanguage('sv-SE')
            ->setUrls($url)
            ->setPayeeInfo($payeeInfo)
            ->setPrefillInfo($prefillInfo)
            ->setPrices($prices)
            ->setPayerReference(uniqid())
            ->setMetadata($metadata);

        $paymentObject = new PaymentObject();
        $paymentObject->setPayment($payment)
            ->setShoplogoUrl('https://test-dummy.net/logo.png');

        $purchaseRequest = new Purchase($paymentObject);
        $purchaseRequest->setClient($this->client);

        /** @var ResponseServiceInterface $responseService */
        $responseService = $purchaseRequest->send();

        $this->assertInstanceOf(ResponseServiceInterface::class, $responseService);

        /** @var ResponseResourceInterface $response */
        $responseResource = $responseService->getResponseResource();

        $this->assertInstanceOf(ResponseResourceInterface::class, $responseResource);

        $result = $responseService->getResponseData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertArrayHasKey('operations', $result);
        $this->assertEquals('Purchase', $result['payment']['operation']);
        $this->assertEquals('MobilePay', $result['payment']['instrument']);

        return $result['payment']['id'];
    }

    /**
     * @depends MobilePayPaymentTest::testPurchaseRequest
     * @param string $paymentId
     */
    public function testCapture($paymentId)
    {
        $this->assertIsString($paymentId);

        $transactionData = new TransactionCapture();
        $transactionData->setAmount(100)
            ->setVatAmount(0)
            ->setDescription('Test Capture')
            ->setPayeeReference($this->generateRandomString(12));

        $transaction = new TransactionObject();
        $transaction->setTransaction($transactionData);

        $requestService = new CreateCapture($transaction);
        $requestService->setClient($this->client)
            //->setPaymentId($this->paymentId)
            ->setRequestEndpointVars(preg_replace('|/psp/[^/]+/payments/|', '', $this->paymentId));

        /** @var ResponseServiceInterface $responseService */
        $responseService = $requestService->send();
        $this->assertInstanceOf(ResponseServiceInterface::class, $responseService);

        /** @var CaptureObject $responseResource */
        $responseResource = $responseService->getResponseResource();
        $this->assertInstanceOf(CaptureObject::class, $responseResource);

        $result = $responseService->getResponseData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertArrayHasKey('capture', $result);
        $this->assertArrayHasKey('transaction', $result['capture']);
        $this->assertEquals('Capture', $result['capture']['transaction']['type']);

        return $result['capture'];
    }

    /**
     * @depends MobilePayPaymentTest::testPurchaseRequest
     * @depends MobilePayPaymentTest::testCapture
     * @param string $paymentId
     * @param string $capture
     */
    public function testReversal($paymentId, $capture)
    {
        $this->assertIsString($paymentId);
        $this->assertIsArray($capture);

        $transactionData = new TransactionReversal();
        $transactionData->setAmount(100)
            ->setVatAmount(0)
            ->setDescription('Test refund')
            ->setPayeeReference($this->generateRandomString(12));

        $transaction = new TransactionObject();
        $transaction->setTransaction($transactionData);

        $requestService = new CreateReversal($transaction);
        $requestService->setClient($this->client)
            //->setPaymentId($this->paymentId)
            ->setRequestEndpointVars(preg_replace('|/psp/[^/]+/payments/|', '', $this->paymentId));

        /** @var ResponseServiceInterface $responseService */
        $responseService = $requestService->send();
        $this->assertInstanceOf(ResponseServiceInterface::class, $responseService);

        /** @var ReversalObject $responseResource */
        $responseResource = $responseService->getResponseResource();
        $this->assertInstanceOf(ReversalObject::class, $responseResource);

        $result = $responseService->getResponseData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertArrayHasKey('reversal', $result);
        $this->assertArrayHasKey('transaction', $result['reversal']);
        $this->assertEquals('Reversal', $result['reversal']['transaction']['type']);

        return $result['reversal'];
    }

    public function testCancellation()
    {
        $this->markTestSkipped('Capture/Reversal tests will be broken if this test will be executed.');

        $transactionData = new TransactionCancellation();
        $transactionData
            ->setDescription('Test Cancellation')
            ->setPayeeReference($this->generateRandomString(12));

        $transaction = new TransactionObject();
        $transaction->setTransaction($transactionData);

        $requestService = new CreateCancellation($transaction);
        $requestService->setClient($this->client)
            //->setPaymentId($this->paymentId)
            ->setRequestEndpointVars(preg_replace('|/psp/[^/]+/payments/|', '', $this->paymentId));

        /** @var ResponseServiceInterface $responseService */
        $responseService = $requestService->send();
        $this->assertInstanceOf(ResponseServiceInterface::class, $responseService);

        /** @var CancellationObject $responseResource */
        $responseResource = $responseService->getResponseResource();
        $this->assertInstanceOf(CancellationObject::class, $responseResource);

        $result = $responseService->getResponseData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertArrayHasKey('cancellation', $result);
        $this->assertArrayHasKey('transaction', $result['cancellation']);
        $this->assertEquals('Cancellation', $result['cancellation']['transaction']['type']);
    }

    public function testGetAuthorizations()
    {
        $requestService = new GetAuthorizations();
        $requestService->setClient($this->client)
            //->setPaymentId($this->paymentId)
            ->setRequestEndpointVars(preg_replace('|/psp/[^/]+/payments/|', '', $this->paymentId));

        /** @var ResponseServiceInterface $responseService */
        $responseService = $requestService->send();
        $this->assertInstanceOf(ResponseServiceInterface::class, $responseService);

        /** @var AuthorizationsObject $responseResource */
        $responseResource = $responseService->getResponseResource();
        $this->assertInstanceOf(AuthorizationsObject::class, $responseResource);

        $result = $responseService->getResponseData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertArrayHasKey('authorizations', $result);
        $this->assertIsArray($result['authorizations']);

        return $result['authorizations'];
    }

    /**
     * @depends MobilePayPaymentTest::testGetAuthorizations
     * @param array $authorizations
     */
    public function testGetAuthorization($authorizations)
    {
        foreach ($authorizations['authorization_list'] as $authorization) {
            $requestService = new GetAuthorization();
            $requestService->setClient($this->client)
                ->setRequestEndpoint($authorization['id']);

            /** @var ResponseServiceInterface $responseService */
            $responseService = $requestService->send();
            $this->assertInstanceOf(ResponseServiceInterface::class, $responseService);

            /** @var AuthorizationObject $responseResource */
            $responseResource = $responseService->getResponseResource();
            $this->assertInstanceOf(AuthorizationObject::class, $responseResource);

            $result = $responseService->getResponseData();

            $this->assertIsArray($result);
            $this->assertArrayHasKey('payment', $result);
            $this->assertArrayHasKey('authorization', $result);
            $this->assertIsArray($result['authorization']);
            $this->assertArrayHasKey('id', $result['authorization']);

            // Test the first item only
            break;
        }
    }

    public function testGetCancellations()
    {
        $this->markTestSkipped('Impossible to test if no any Cancellations.');

        $requestService = new GetCancellations();
        $requestService->setClient($this->client)
            //->setPaymentId($this->paymentId)
            ->setRequestEndpointVars(preg_replace('|/psp/[^/]+/payments/|', '', $this->paymentId));

        /** @var ResponseServiceInterface $responseService */
        $responseService = $requestService->send();
        $this->assertInstanceOf(ResponseServiceInterface::class, $responseService);

        /** @var CancellationsObject $responseResource */
        $responseResource = $responseService->getResponseResource();
        $this->assertInstanceOf(CancellationsObject::class, $responseResource);

        $result = $responseService->getResponseData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertArrayHasKey('cancellations', $result);
        $this->assertIsArray($result['cancellations']);

        return $result['cancellations'];
    }

    /**
     * @depends MobilePayPaymentTest::testGetCancellations
     * @param array $cancellations
     */
    public function testGetCancellation($cancellations)
    {
        $this->markTestSkipped('Impossible to test if no any Cancellations.');

        foreach ($cancellations['cancellation_list'] as $cancellation) {
            $requestService = new GetCancellation();
            $requestService->setClient($this->client)
                ->setRequestEndpoint($cancellation['id']);

            /** @var ResponseServiceInterface $responseService */
            $responseService = $requestService->send();
            $this->assertInstanceOf(ResponseServiceInterface::class, $responseService);

            /** @var CancellationObject $responseResource */
            $responseResource = $responseService->getResponseResource();
            $this->assertInstanceOf(CancellationObject::class, $responseResource);

            $result = $responseService->getResponseData();

            $this->assertIsArray($result);
            $this->assertArrayHasKey('payment', $result);
            $this->assertArrayHasKey('cancellation', $result);
            $this->assertIsArray($result['cancellation']);
            $this->assertArrayHasKey('id', $result['cancellation']);

            // Test the first item only
            break;
        }
    }

    public function testGetCaptures()
    {
        $requestService = new GetCaptures();
        $requestService->setClient($this->client)
            //->setPaymentId($this->paymentId)
            ->setRequestEndpointVars(preg_replace('|/psp/[^/]+/payments/|', '', $this->paymentId));

        /** @var ResponseServiceInterface $responseService */
        $responseService = $requestService->send();
        $this->assertInstanceOf(ResponseServiceInterface::class, $responseService);

        /** @var CapturesObject $responseResource */
        $responseResource = $responseService->getResponseResource();
        $this->assertInstanceOf(CapturesObject::class, $responseResource);

        $result = $responseService->getResponseData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertArrayHasKey('captures', $result);
        $this->assertIsArray($result['captures']);

        return $result['captures'];
    }

    /**
     * @depends MobilePayPaymentTest::testGetCaptures
     * @param array $captures
     */
    public function testGetCapture($captures)
    {
        foreach ($captures['capture_list'] as $capture) {
            $requestService = new GetCapture();
            $requestService->setClient($this->client)
                ->setRequestEndpoint($capture['id']);

            /** @var ResponseServiceInterface $responseService */
            $responseService = $requestService->send();
            $this->assertInstanceOf(ResponseServiceInterface::class, $responseService);

            /** @var CaptureObject $responseResource */
            $responseResource = $responseService->getResponseResource();
            $this->assertInstanceOf(CaptureObject::class, $responseResource);

            $result = $responseService->getResponseData();

            $this->assertIsArray($result);
            $this->assertArrayHasKey('payment', $result);
            $this->assertArrayHasKey('capture', $result);
            $this->assertIsArray($result['capture']);
            $this->assertArrayHasKey('id', $result['capture']);

            // Test the first item only
            break;
        }
    }

    public function testGetReversals()
    {
        $requestService = new GetReversals();
        $requestService->setClient($this->client)
            //->setPaymentId($this->paymentId)
            ->setRequestEndpointVars(preg_replace('|/psp/[^/]+/payments/|', '', $this->paymentId));

        /** @var ResponseServiceInterface $responseService */
        $responseService = $requestService->send();
        $this->assertInstanceOf(ResponseServiceInterface::class, $responseService);

        /** @var ReversalsObject $responseResource */
        $responseResource = $responseService->getResponseResource();
        $this->assertInstanceOf(ReversalsObject::class, $responseResource);

        $result = $responseService->getResponseData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertArrayHasKey('reversals', $result);
        $this->assertIsArray($result['reversals']);

        return $result['reversals'];
    }

    /**
     * @depends MobilePayPaymentTest::testGetReversals
     * @param array $reversals
     */
    public function testGetReversal($reversals)
    {
        foreach ($reversals['reversal_list'] as $reversal) {
            $requestService = new GetReversal();
            $requestService->setClient($this->client)
                ->setRequestEndpoint($reversal['id']);

            /** @var ResponseServiceInterface $responseService */
            $responseService = $requestService->send();
            $this->assertInstanceOf(ResponseServiceInterface::class, $responseService);

            /** @var ReversalObject $responseResource */
            $responseResource = $responseService->getResponseResource();
            $this->assertInstanceOf(ReversalObject::class, $responseResource);

            $result = $responseService->getResponseData();

            $this->assertIsArray($result);
            $this->assertArrayHasKey('payment', $result);
            $this->assertArrayHasKey('reversal', $result);
            $this->assertIsArray($result['reversal']);
            $this->assertArrayHasKey('id', $result['reversal']);

            // Test the first item only
            break;
        }
    }

    public function testGetTransactions()
    {
        $requestService = new GetTransactions();
        $requestService->setClient($this->client)
            //->setPaymentId($this->paymentId)
            ->setRequestEndpointVars(preg_replace('|/psp/[^/]+/payments/|', '', $this->paymentId));

        /** @var ResponseServiceInterface $responseService */
        $responseService = $requestService->send();
        $this->assertInstanceOf(ResponseServiceInterface::class, $responseService);

        /** @var TransactionsObject $responseResource */
        $responseResource = $responseService->getResponseResource();
        $this->assertInstanceOf(TransactionsObject::class, $responseResource);

        $result = $responseService->getResponseData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertArrayHasKey('transactions', $result);
        $this->assertIsArray($result['transactions']);

        return $result['transactions'];
    }

    /**
     * @depends MobilePayPaymentTest::testGetTransactions
     * @param array $transactions
     */
    public function testGetTransaction($transactions)
    {
        foreach ($transactions['transaction_list'] as $transaction) {
            $requestService = new GetTransaction();
            $requestService->setClient($this->client)
                ->setRequestEndpoint($transaction['id']);

            /** @var ResponseServiceInterface $responseService */
            $responseService = $requestService->send();
            $this->assertInstanceOf(ResponseServiceInterface::class, $responseService);

            /** @var TransactionObjectResponse $responseResource */
            $responseResource = $responseService->getResponseResource();
            $this->assertInstanceOf(TransactionObjectResponse::class, $responseResource);

            $result = $responseService->getResponseData();

            $this->assertIsArray($result);
            $this->assertArrayHasKey('payment', $result);
            $this->assertArrayHasKey('transaction', $result);
            $this->assertIsArray($result['transaction']);
            $this->assertArrayHasKey('id', $result['transaction']);
            $this->assertArrayHasKey('created', $result['transaction']);
            $this->assertArrayHasKey('updated', $result['transaction']);
            $this->assertArrayHasKey('type', $result['transaction']);
            $this->assertArrayHasKey('state', $result['transaction']);
            $this->assertArrayHasKey('number', $result['transaction']);
            $this->assertArrayHasKey('amount', $result['transaction']);
            $this->assertArrayHasKey('vat_amount', $result['transaction']);
            $this->assertArrayHasKey('description', $result['transaction']);
            $this->assertArrayHasKey('payee_reference', $result['transaction']);
            $this->assertArrayHasKey('is_operational', $result['transaction']);
            $this->assertArrayHasKey('operations', $result['transaction']);

            // Test the first item only
            break;
        }
    }
}