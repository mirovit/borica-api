<?php

namespace Mirovit\Borica;

use Carbon\Carbon;

class Response
{
    use KeyReader;

    const TRANSACTION_CODE = 'TRANSACTION_CODE';
    const TRANSACTION_TIME = 'TRANSACTION_TIME';
    const AMOUNT = 'AMOUNT';
    const TERMINAL_ID = 'TERMINAL_ID';
    const ORDER_ID = 'ORDER_ID';
    const RESPONSE_CODE = 'RESPONSE_CODE';
    const PROTOCOL_VERSION = 'PROTOCOL_VERSION';
    const SIGN = 'SIGN';
    const SIGNATURE_OK = 'SIGNATURE_OK';

    private $response = [];
    private $publicCertificate;
    private $useFileKeyReader;

    public function __construct($publicCertificate, $useFileKeyReader = true)
    {
        $this->publicCertificate = $publicCertificate;
        $this->useFileKeyReader = $useFileKeyReader;
    }

    public function parse($message)
    {
        $message = base64_decode($message);
        
        $response = [
            self::TRANSACTION_CODE  => substr($message, 0, 2),
            self::TRANSACTION_TIME  => substr($message, 2, 14),
            self::AMOUNT            => substr($message, 16, 12),
            self::TERMINAL_ID       => substr($message, 28, 8),
            self::ORDER_ID          => substr($message, 36, 15),
            self::RESPONSE_CODE     => substr($message, 51, 2),
            self::PROTOCOL_VERSION  => substr($message, 53, 3),
            self::SIGN              => substr($message, 56, 128),
            self::SIGNATURE_OK      => $this->verifySignature($message, substr($message, 56, 128)),
        ];

        $this->response = $response;

        return $this;
    }

    public function transactionCode()
    {
        return $this->response[self::TRANSACTION_CODE];
    }

    public function transactionTime()
    {
        return Carbon::createFromFormat('YmdHms', $this->response[self::TRANSACTION_TIME]);
    }

    public function amount()
    {
        return (float)$this->response[self::AMOUNT] / 100;
    }

    public function terminalID()
    {
        return $this->response[self::TERMINAL_ID];
    }

    public function orderID()
    {
        return trim($this->response[self::ORDER_ID]);
    }

    public function responseCode()
    {
        return $this->response[self::RESPONSE_CODE];
    }

    public function protocolVersion()
    {
        return $this->response[self::PROTOCOL_VERSION];
    }

    public function signatureOk()
    {
        return $this->response[self::SIGNATURE_OK];
    }

    public function isSuccessful()
    {
        return $this->responseCode() === '00';
    }

    public function notSuccessful()
    {
        return !$this->isSuccessful();
    }

    /**
     * Verify the returned response.
     *
     * @param $message
     * @param $signature
     * @return mixed
     */
    public function verifySignature($message, $signature)
    {
        $cert = $this->getCertificate();

        $pubkeyid = openssl_get_publickey($cert);

        $verify = openssl_verify(substr($message, 0, strlen($message) - 128), $signature, $pubkeyid);

        openssl_free_key($pubkeyid);

        return $verify;
    }

    /**
     * Read the private key contents and return it.
     *
     * @return string
     */
    public function getCertificate()
    {
        if ($this->useFileKeyReader) {
          return $this->readKey($this->publicCertificate);
        }
        return $this->publicCertificate;
    }
}