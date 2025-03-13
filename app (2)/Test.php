<?php

namespace App\Tests\Controller;

use App\Entity\ProcessEntity;
use App\Entity\Machine;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class Hehe extends WebTestCase
{
    public function testAddProcess()
    {
        $client = static::createClient();
        
        $client->request(
            'POST',
            '/machine/add',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'totalMemory' => 2048,
                'totalCpu'    => 8,
            ])
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request(
            'POST',
            '/process/add',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'requiredMemory' => 512,
                'requiredCpu'    => 2,
            ])
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $responseContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('machineId', $responseContent);
    }
    
    public function testDeleteProcess()
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        
        $client->request(
            'POST',
            '/machine/add',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'totalMemory' => 1024,
                'totalCpu'    => 4,
            ])
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $client->request(
            'POST',
            '/process/add',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'requiredMemory' => 256,
                'requiredCpu'    => 1,
            ])
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $process = $em->getRepository(ProcessEntity::class)->findOneBy([
            'requiredMemory' => 256,
            'requiredCpu'    => 1,
        ]);
        $this->assertNotNull($process, 'Процесс должен быть создан');
        $processId = $process->getId();
        
        $client->request('DELETE', '/process/delete/' . $processId);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $deletedProcess = $em->getRepository(ProcessEntity::class)->find($processId);
        $this->assertNull($deletedProcess, 'Процесс должен быть удален');
    }
    
    public function testAddMachine()
    {
        $client = static::createClient();
        
        $client->request(
            'POST',
            '/machine/add',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'totalMemory' => 4096,
                'totalCpu'    => 16,
            ])
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $responseContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('machineId', $responseContent);
    }
    
    public function testDeleteMachine()
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        $client->request(
            'POST',
            '/machine/add',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'totalMemory' => 2048,
                'totalCpu'    => 8,
            ])
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $machineResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('machineId', $machineResponse);
        $machineId = $machineResponse['machineId'];

        $client->request(
            'POST',
            '/process/add',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'requiredMemory' => 512,
                'requiredCpu'    => 2,
            ])
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $client->request('DELETE', '/machine/delete/' . $machineId);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $deletedMachine = $em->getRepository(Machine::class)->find($machineId);
        $this->assertNull($deletedMachine, 'Машина должна быть удалена');
    }
}
