<?php

namespace Mirovit\Borica;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Request
     */
    private $request;

    public function setUp()
    {
        $this->request = new Request(12345678, 'tests/stubs/privatekey.stub');
    }

    /** @test */
    public function it_registers_a_transaction_with_protocol_1_0()
    {
        $url = $this->request
            ->amount(1)
            ->orderID(1)
            ->description('testing the process')
            ->currency('EUR')
            ->register('1.0');

        $expected = $this->request->getGatewayURL() .
            'registerTransaction?eBorica=' .
            Request::REGISTER_TRANSACTION .
            $this->request->getDate() .
            $this->request->getAmount() .
            $this->request->getTerminalID() .
            $this->request->getOrderID() .
            $this->request->getDescription() .
            $this->request->getLanguage() .
            '1.0';

        $this->assertSame(
            $expected,
            $url
        );
    }

    /** @test */
    public function it_registers_a_transaction_with_protocol_1_1()
    {
        $url = $this->request
            ->amount(1)
            ->orderID(1)
            ->description('testing the process')
            ->currency('EUR')
            ->register('1.1');

        $expected = $this->request->getGatewayURL() .
            'registerTransaction?eBorica=' .
            Request::REGISTER_TRANSACTION .
            $this->request->getDate() .
            $this->request->getAmount() .
            $this->request->getTerminalID() .
            $this->request->getOrderID() .
            $this->request->getDescription() .
            $this->request->getLanguage() .
            '1.1' .
            $this->request->getCurrency();

        $this->assertSame(
            $expected,
            $url
        );
    }

    /** @test */
    public function it_registers_a_transaction_with_protocol_2_0()
    {
        $url = $this->request
            ->amount(1)
            ->orderID(1)
            ->description('testing the process')
            ->currency('EUR')
            ->register('2.0', '');

        $expected = $this->request->getGatewayURL() .
                    'registerTransaction?eBorica=' .
                    Request::REGISTER_TRANSACTION .
                    $this->request->getDate() .
                    $this->request->getAmount() .
                    $this->request->getTerminalID() .
                    $this->request->getOrderID() .
                    $this->request->getDescription() .
                    $this->request->getLanguage() .
                    '2.0' .
                    $this->request->getCurrency() .
                    str_pad('', 6);

        $this->assertSame(
            $expected,
            $url
        );
    }
    
    /** @test */
    public function it_checks_status()
    {
        $protocol_version = '2.0';

        $url = $this->request
            ->amount(1)
            ->orderID(1)
            ->description('testing the process')
            ->currency('EUR')
            ->status($protocol_version);

        $expected = $this->request->getGatewayURL() .
            'transactionStatusReport?eBorica=' .
            Request::REGISTER_TRANSACTION .
            $this->request->getDate() .
            $this->request->getAmount() .
            $this->request->getTerminalID() .
            $this->request->getOrderID() .
            $this->request->getDescription() .
            $this->request->getLanguage() .
            $protocol_version .
            $this->request->getCurrency();

        $this->assertSame(
            $expected,
            $url
        );
    }

    /** @test */
    public function it_reverses_a_transaction()
    {
        $protocol_version = '1.1';

        $url = $this->request
            ->amount(1)
            ->orderID(1)
            ->description('testing the process')
            ->currency('EUR')
            ->reverse($protocol_version);

        $expected = $this->request->getGatewayURL() .
            'manageTransaction?eBorica=' .
            Request::REVERSAL .
            $this->request->getDate() .
            $this->request->getAmount() .
            $this->request->getTerminalID() .
            $this->request->getOrderID() .
            $this->request->getDescription() .
            $this->request->getLanguage() .
            $protocol_version .
            $this->request->getCurrency();

        $this->assertSame(
            $expected,
            $url
        );
    }

    /** skip for now */
    public function it_pays_profit()
    {
        $url = $this->request
            ->amount(1)
            ->orderID(1)
            ->payProfit();

        $expected = $this->request->getGatewayURL() .
            'manageTransaction?eBorica=' .
            Request::PAY_PROFIT .
            $this->request->getAmount() .
            $this->request->getOrderID();

        $this->assertSame(
            $expected,
            $url
        );
    }

    /** @test */
    public function it_gets_correct_terminal_id()
    {
        $this->assertSame(12345678, $this->request->getTerminalID());
    }

    /** @test */
    public function it_sets_correct_value_for_amount()
    {
        $this->request->amount(1);
        $this->assertSame('000000000100', $this->request->getAmount());
    }
    
    /** @test */
    public function it_sets_correct_value_for_order_id()
    {
        $this->request->orderID(1);
        $this->assertSame(str_pad('1', 15), $this->request->getOrderID());
    }

    /** @test */
    public function it_sets_correct_value_for_description()
    {
        $this->request->description('testing the process');
        $this->assertSame(str_pad('testing the process', 125), $this->request->getDescription());
    }

    /** @test */
    public function it_sets_correct_value_for_currency()
    {
        $this->request->currency('eUr');
        $this->assertSame('EUR', $this->request->getCurrency());
    }

    /** @test */
    public function it_verifies_the_protocol_version()
    {
        $this->assertTrue($this->request->verifyProtocolVersion('1.0'));
        $this->assertTrue($this->request->verifyProtocolVersion('1.1'));
        $this->assertTrue($this->request->verifyProtocolVersion('2.0'));
    }

    /**
     * @test
     * @expectedException \Mirovit\Borica\Exceptions\InvalidParameterException
     */
    public function it_validates_amount()
    {
        $this->request->amount('not-a-number-amount');
    }

    /**
     * @test
     * @expectedException \Mirovit\Borica\Exceptions\LengthException
     */
    public function it_validates_description()
    {
        $this->request->description('');
    }

    /**
     * @test
     * @expectedException \Mirovit\Borica\Exceptions\LengthException
     */
    public function it_validates_order_id()
    {
        $this->request->orderID(12345678910111213);
    }
}

function base64_encode($str)
{
    return $str;
}

function urlencode($str)
{
    return $str;
}

function openssl_pkey_get_private($privateKey, $privateKeyPassword)
{
    return null;
}

function openssl_sign($message, $signature, $pkeyid)
{
    return null;
}

function openssl_free_key($pkId)
{
    return null;
}