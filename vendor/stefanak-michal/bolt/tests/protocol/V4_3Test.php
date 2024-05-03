<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\Response;
use Bolt\protocol\ServerState;
use Bolt\protocol\V4_3;
use Bolt\packstream\v1\{Packer, Unpacker};

/**
 * Class V4_3Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\protocol\AProtocol
 * @covers \Bolt\protocol\V4_3
 *
 * @package Bolt\tests\protocol
 */
class V4_3Test extends ATest
{
    /**
     * @return V4_3
     */
    public function test__construct(): V4_3
    {
        $cls = new V4_3(new Packer, new Unpacker, $this->mockConnection(), new \Bolt\protocol\ServerState());
        $this->assertInstanceOf(V4_3::class, $cls);
        $cls->serverState->expectedServerStateMismatchCallback = function (string $current, array $expected) {
            $this->markTestIncomplete('Server in ' . $current . ' state. Expected ' . implode(' or ', $expected) . '.');
        };
        return $cls;
    }

    /**
     * @depends test__construct
     * @param V4_3 $cls
     */
    public function testRoute(V4_3 $cls)
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']],
            [0x7E, (object)[]]
        ];
        self::$writeBuffer = [
            '0001b3',
            '000166',
            '0001a1',
            '00088761646472657373',
            '000f8e6c6f63616c686f73743a37363837',
            '000190',
            '0001c0',
        ];

        $cls->serverState->set(ServerState::READY);
        $this->assertEquals(Response::SIGNATURE_SUCCESS, $cls->route(['address' => 'localhost:7687'])->getResponse()->getSignature());
        $this->assertEquals(ServerState::READY, $cls->serverState->get());

        $cls->serverState->set(ServerState::READY);
        $response = $cls->route(['address' => 'localhost:7687'])->getResponse();
        $this->checkFailure($response);
        $this->assertEquals(ServerState::FAILED, $cls->serverState->get());

        $cls->serverState->set(ServerState::READY);
        $response = $cls->route(['address' => 'localhost:7687'])->getResponse();
        $this->assertEquals(Response::SIGNATURE_IGNORED, $response->getSignature());
        $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState->get());
    }

}
