<?php

namespace App\Tests\Project;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Project\Data;

class DataTest extends TestCase
{
    public function testSetAndGet(): void
    {
        $sessionMock = $this->createMock(SessionInterface::class);
        $sessionMock->method('get')->willReturn([]);
        $data = new Data($sessionMock);

        $data->set('testKey', 'testValue');
        $this->assertSame('testValue', $data->get('testKey'));
    }

    public function testGetWithDefault(): void
    {
        $sessionMock = $this->createMock(SessionInterface::class);
        $sessionMock->method('get')->willReturn([]);
        $data = new Data($sessionMock);

        $this->assertSame('default', $data->get('missingKey', 'default'));
    }

    public function testGetAll(): void
    {
        $sessionMock = $this->createMock(SessionInterface::class);
        $sessionMock->method('get')->willReturn([]);
        $data = new Data($sessionMock);

        $data->set('key1', 'val1');
        $data->set('key2', 'val2');

        $all = $data->getAll();
        $this->assertArrayHasKey('key1', $all);
        $this->assertArrayHasKey('key2', $all);
    }

    public function testSaveCallsSessionSet(): void
    {
        $sessionMock = $this->createMock(SessionInterface::class);
        $sessionMock->expects($this->once())
            ->method('set')
            ->with($this->equalTo('game_state'), $this->arrayHasKey('key1'));

        $data = new Data($sessionMock);
        $data->set('key1', 'value1');
        $data->save();
    }

    public function testLoadUsesSessionGet(): void
    {
        $sessionMock = $this->createMock(SessionInterface::class);
        $sessionMock->expects($this->atLeastOnce())
            ->method('get')
            ->with($this->equalTo('game_state'), $this->equalTo([]))
            ->willReturn(['key1' => 'value1']);
    
        $data = new Data($sessionMock);
        $data->load();
        $this->assertSame('value1', $data->get('key1'));
    }

    public function testResetClearsState(): void
    {
        $sessionMock = $this->createMock(SessionInterface::class);
        $sessionMock->expects($this->once())
            ->method('set')
            ->with('game_state', []);

        $data = new Data($sessionMock);
        $data->set('key', 'val');
        $data->reset();

        $this->assertEmpty($data->getAll());
    }
}