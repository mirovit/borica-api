<?php

use Mirovit\Borica\Factory;
use Mirovit\Borica\Request;
use Mirovit\Borica\Response;

use \Mockery as m;

class FactoryTest extends PHPUnit_Framework_TestCase
{
    private $factory;
    private $request;
    private $response;

    public function __construct()
    {
        $this->request = m::mock(Request::class);
        $this->response = m::mock(Response::class);

        $this->factory = new Factory(
            $this->request,
            $this->response
        );
    }

    /** @test */
    public function it_provides_access_to_the_request()
    {
        $this->assertInstanceOf(Request::class, $this->factory->request());
    }

    /** @test */
    public function it_provides_access_to_the_response()
    {
        $this->assertInstanceOf(Response::class, $this->factory->response());
    }
    
    /** @test */
    public function it_calls_the_parse_method_when_response_has_param()
    {
        $this->response
            ->shouldReceive('parse')
            ->once()
            ->andReturnSelf();

        $this->assertInstanceOf(Response::class, $this->factory->response('123'));
    }

    public function tearDown()
    {
        m::close();
    }
}
