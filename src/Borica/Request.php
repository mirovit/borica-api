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
        $message = self::REGISTER_TRANSACTION;
        $message .= $this->getDate();
        $message .= $this->getAmount();
        $message .= $this->getTerminalID();
        $message .= $this->getOrderID();
        $message .= $this->getDescription();
        $message .= $this->getLanguage();
        $message .= $this->verifyProtocolVersion($protocolVersion) ? $protocolVersion : '1.0';

        if ($protocolVersion != '1.0') {
            $message .= $this->getCurrency();
        }

        if ($protocolVersion == '2.0') {
            $message .= str_pad($oneTimeTicket, 6);
        }

        $message = $this->signMessage($message);

        return "{$this->getGatewayURL()}registerTransaction?eBorica=" . urlencode(base64_encode($message));
    }

    /**
     * Check the status of a transaction with Borica.
     *
     * @param string $protocolVersion
     * @return string
     */
    public function status($protocolVersion = '1.1')
    {
        $message = self::REGISTER_TRANSACTION;
        $message .= $this->getDate();
        $message .= $this->getAmount();
        $message .= $this->getTerminalID();
        $message .= $this->getOrderID();
        $message .= $this->getDescription();
        $message .= $this->getLanguage();
        $message .= $this->verifyProtocolVersion($protocolVersion) ? $protocolVersion : '1.0';

        if ($protocolVersion != '1.0') {
            $message .= $this->getCurrency();
        }

        $message = $this->signMessage($message);

        return "{$this->getGatewayURL()}transactionStatusReport?eBorica=" . urlencode(base64_encode($message));
    }

    /**
     * Register a delayed request.
     *
     * @param string $protocolVersion
     * @return string
     */
    public function registerDelayedRequest($protocolVersion = '1.1')
    {
        $message = self::DELAYED_AUTHORIZATION_REQUEST;
        $message .= $this->getDate();
        $message .= $this->getAmount();
        $message .= $this->getTerminalID();
        $message .= $this->getOrderID();
        $message .= $this->getDescription();
        $message .= $this->getLanguage();
        $message .= $this->verifyProtocolVersion($protocolVersion) ? $protocolVersion : '1.0';

        if ($protocolVersion != '1.0') {
            $message .= $this->getCurrency();
        }

        $message = $this->signMessage($message);

        return "{$this->getGatewayURL()}manageTransaction?eBorica=" . urlencode(base64_encode($message));
    }

    /**
     * Complete an already registered transaction.
     *
     * @param string $protocolVersion
     * @return string
     */
    public function completeDelayedRequest($protocolVersion = '1.1')
    {
        $message = self::DELAYED_AUTHORIZATION_COMPLETE;
        $message .= $this->getDate();
        $message .= $this->getAmount();
        $message .= $this->getTerminalID();
        $message .= $this->getOrderID();
        $message .= $this->getDescription();
        $message .= $this->getLanguage();
        $message .= $this->verifyProtocolVersion($protocolVersion) ? $protocolVersion : '1.0';

        if ($protocolVersion != '1.0') {
            $message .= $this->getCurrency();
        }

        $message = $this->signMessage($message);

        return "{$this->getGatewayURL()}manageTransaction?eBorica=" . urlencode(base64_encode($message));
    }

    /**
     * Cancel already registered delayed request.
     *
     * @param string $protocolVersion
     * @return string
     */
    public function reverseDelayedRequest($protocolVersion = '1.1')
    {
        $message = self::DELAYED_AUTHORIZATION_REVERSAL;
        $message .= $this->getDate();
        $message .= $this->getAmount();
        $message .= $this->getTerminalID();
        $message .= $this->getOrderID();
        $message .= $this->getDescription();
        $message .= $this->getLanguage();
        $message .= $this->verifyProtocolVersion($protocolVersion) ? $protocolVersion : '1.0';

        if ($protocolVersion != '1.0') {
            $message .= $this->getCurrency();
        }

        $message = $this->signMessage($message);

        return "{$this->getGatewayURL()}manageTransaction?eBorica=" . urlencode(base64_encode($message));
    }

    /**
     * Reverse a payment.
     *
     * @param string $protocolVersion
     * @return string
     */
    public function reverse($protocolVersion = '1.1')
    {
        $message = self::REVERSAL;
        $message .= $this->getDate();
        $message .= $this->getAmount();
        $message .= $this->getTerminalID();
        $message .= $this->getOrderID();
        $message .= $this->getDescription();
        $message .= $this->getLanguage();
        $message .= $this->verifyProtocolVersion($protocolVersion) ? $protocolVersion : '1.0';

        if ($protocolVersion != '1.0') {
            $message .= $this->getCurrency();
        }
        
        $message = $this->signMessage($message);

        return "{$this->getGatewayURL()}manageTransaction?eBorica=" . urlencode(base64_encode($message));
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
    public function verifyProtocolVersion($protocolVersion)
    {
        return $protocolVersion == '1.0' || $protocolVersion == '1.1' || $protocolVersion == '2.0';
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
        $signature = null;

        $pkeyid = openssl_pkey_get_private($this->getPrivateKey(), $this->privateKeyPassword);
        openssl_sign($message, $signature, $pkeyid);
        openssl_free_key($pkeyid);

        return $message . $signature;
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
     * @param $desc
     */
    private function validateDescription($desc)
    {
        if (strlen($desc) < 1 || strlen($desc) > 125) {
            throw new LengthException('The description of the request should be between 1 and 125 symbols.');
        }
    }

    /**
     * @param $id
     */
    private function validateOrderID($id)
    {
        if (strlen($id) < 1 || strlen($id) > 15) {
            throw new LengthException('The order id should be between 1 and 15 symbols.');
        }
    }
}
