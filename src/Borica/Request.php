<?php

namespace Mirovit\Borica;

use Mirovit\Borica\Exceptions\InvalidParameterException;
use Mirovit\Borica\Exceptions\LengthException;

class Request
{
    use KeyReader;

    const REGISTER_TRANSACTION = 10;
    const PAY_PROFIT = 11;
    const DELAYED_AUTHORIZATION_REQUEST = 21;
    const DELAYED_AUTHORIZATION_COMPLETE = 22;
    const DELAYED_AUTHORIZATION_REVERSAL = 23;
    const REVERSAL = 40;
    const PAYED_PROFIT_REVERSAL = 41;

    const SUPPORTED_VERSIONS = ['1.0', '1.1', '2.0'];

    private $terminalID;
    private $privateKey;
    private $privateKeyPassword;
    private $useFileKeyReader;

    private $gatewayURL = 'https://gate.borica.bg/boreps/';
    private $testGatewayURL = 'https://gatet.borica.bg/boreps/';

    private $transactionCode;
    private $amount;
    private $orderID;
    private $description;
    private $language;
    private $currency = 'EUR';
    private $debug;

    public function __construct($terminalID, $privateKey, $privateKeyPassword = '', $language = '', $debug = false, $useFileKeyReader = true)
    {
        $this->terminalID = $terminalID;
        $this->privateKey = $privateKey;
        $this->privateKeyPassword = $privateKeyPassword;
        $this->useFileKeyReader = $useFileKeyReader;
        $this->language = strtoupper($language);
        $this->debug = $debug;
    }

    /**
     * Register a transaction with Borica.
     *
     * @param string $protocolVersion
     * @param string $oneTimeTicket
     * @return string
     */
    public function register($protocolVersion = '1.1', $oneTimeTicket = null)
    {
        $message = $this->getBaseMessage(self::REGISTER_TRANSACTION, $protocolVersion);

        if ($protocolVersion == '2.0') {
            $message[] = str_pad($oneTimeTicket, 6);
        }

        return $this->generateURL($message, 'registerTransaction');
    }

    /**
     * Check the status of a transaction with Borica.
     *
     * @param string $protocolVersion
     * @return string
     */
    public function status($protocolVersion = '1.1')
    {
        $message = $this->getBaseMessage(self::REGISTER_TRANSACTION, $protocolVersion);

        return $this->generateURL($message, 'transactionStatusReport');
    }

    /**
     * Register a delayed request.
     *
     * @param string $protocolVersion
     * @return string
     */
    public function registerDelayedRequest($protocolVersion = '1.1')
    {
        $message = $this->getBaseMessage(self::DELAYED_AUTHORIZATION_REQUEST, $protocolVersion);

        return $this->generateURL($message);
    }

    /**
     * Complete an already registered transaction.
     *
     * @param string $protocolVersion
     * @return string
     */
    public function completeDelayedRequest($protocolVersion = '1.1')
    {
        $message = $this->getBaseMessage(self::DELAYED_AUTHORIZATION_COMPLETE, $protocolVersion);

        return $this->generateURL($message);
    }

    /**
     * Cancel already registered delayed request.
     *
     * @param string $protocolVersion
     * @return string
     */
    public function reverseDelayedRequest($protocolVersion = '1.1')
    {
        $message = $this->getBaseMessage(self::DELAYED_AUTHORIZATION_REVERSAL, $protocolVersion);

        return $this->generateURL($message);
    }

    /**
     * Reverse a payment.
     *
     * @param string $protocolVersion
     * @return string
     */
    public function reverse($protocolVersion = '1.1')
    {
        $message = $this->getBaseMessage(self::REVERSAL, $protocolVersion);

        return $this->generateURL($message);
    }

    public function getDate()
    {
        return date('YmdHis');
    }

    public function getAmount()
    {
        $this->validateAmount($this->amount);

        return str_pad($this->amount, 12, '0', STR_PAD_LEFT);
    }

    public function getTerminalID()
    {
        return $this->terminalID;
    }

    public function getOrderID()
    {
        $this->validateOrderID($this->orderID);

        return str_pad(substr($this->orderID, 0, 15), 15);
    }

    public function getDescription()
    {
        $this->validateDescription($this->description);

        return str_pad(substr($this->description, 0, 125), 125);
    }

    public function getLanguage()
    {
        return ($this->language == 'BG' || $this->language == 'EN') ? $this->language : 'EN';
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function transactionCode($code)
    {
        $this->transactionCode = $code;

        return $this;
    }

    public function amount($amount)
    {
        $this->validateAmount($amount);

        $this->amount = $amount * 100;

        return $this;
    }

    public function orderID($id)
    {
        $this->validateOrderID($id);

        $this->orderID = $id;

        return $this;
    }

    public function description($desc)
    {
        $this->validateDescription($desc);

        $this->description = $desc;

        return $this;
    }

    public function currency($currency)
    {
        $this->currency = strtoupper($currency);

        return $this;
    }

    /**
     * Ensure that the protocol version is correct.
     *
     * @param $protocolVersion
     * @return bool
     */
    public function getProtocolVersion($protocolVersion)
    {
        if(in_array($protocolVersion, self::SUPPORTED_VERSIONS)) {
            return $protocolVersion;
        }

        return '1.1';
    }

    /**
     * Get the proper gateway url.
     *
     * @return string
     */
    public function getGatewayURL()
    {
        return (bool)$this->debug ? $this->testGatewayURL : $this->gatewayURL;
    }

    /**
     * Generate the request URL for Borica.
     * 
     * @param $message
     * @param string $type
     * @return string
     */
    public function generateURL($message, $type = 'manageTransaction')
    {
        $message = $this->signMessage($message);

        return "{$this->getGatewayURL()}{$type}?eBorica=" . urlencode(base64_encode($message));
    }


    /**
     * Read the private key contents and return it.
     *
     * @return string
     */
    public function getPrivateKey()
    {
        if ($this->useFileKeyReader) {
            return $this->readKey($this->privateKey);
        }

        return $this->privateKey;
    }

    /**
     * Sign the message with the private key of the merchant.
     *
     * @param $message
     * @return mixed
     */
    public function signMessage($message)
    {
        if(is_array($message)) {
            $message = implode('', $message);
        }

        $signature = null;

        $pkeyid = openssl_pkey_get_private($this->getPrivateKey(), $this->privateKeyPassword);
        openssl_sign($message, $signature, $pkeyid);
        openssl_free_key($pkeyid);

        return $message . $signature;
    }

    /**
     * Get the base message structure.
     *
     * @param $messageType
     * @param string $protocolVersion
     * @return array
     */
    protected function getBaseMessage($messageType, $protocolVersion = '1.1')
    {
        $protocolVersion = $this->getProtocolVersion($protocolVersion);

        $message = [
            $messageType,
            $this->getDate(),
            $this->getAmount(),
            $this->getTerminalID(),
            $this->getOrderID(),
            $this->getDescription(),
            $this->getLanguage(),
            $protocolVersion,
        ];

        if ($protocolVersion != '1.0') {
            $message[] = $this->getCurrency();
        }

        return $message;
    }

    /**
     * @param $amount
     */
    private function validateAmount($amount)
    {
        if (!is_numeric($amount)) {
            throw new InvalidParameterException('The amount should be a number!');
        }
    }

    /**
     * @param string $desc
     */
    private function validateDescription($desc)
    {
        $descLength = strlen($desc);

        if ($descLength < 1 || $descLength > 125) {
            throw new LengthException('The description of the request should be between 1 and 125 symbols.');
        }
    }

    /**
     * @param $id
     */
    private function validateOrderID($id)
    {
        $idLength = strlen($id);

        if ($idLength < 1 || $idLength > 15) {
            throw new LengthException('The order id should be between 1 and 15 symbols.');
        }
    }
}
