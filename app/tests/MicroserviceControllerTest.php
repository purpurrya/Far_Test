<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Psr\Log\LoggerInterface;

class MicroserviceControllerTest extends WebTestCase
{
    public function testGetAssignmentsEmpty(): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);
        $client->request('GET', '/assignments');

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data, 'Данные должны быть массивом');
    }

    public function testAddMachine(): void
    {
        $client = static::createClient();
        $payload = [
            'totalMemory' => 2048,
            'totalCpu'    => 8
        ];
        $client->request(
            'POST',
            '/machine/add',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Статус добавления машины должен быть 200');

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('machineId', $data, 'В ответе должен быть machineId');
        $this->assertEquals('Машина добавлена, назначения перераспределены', $data['status']);
    }

    public function testAddProcessWithoutMachine(): void
    {
        $client = static::createClient();

        $client->request('GET', '/machines');
        $machinesResponse = $client->getResponse();
        $machinesData = json_decode($machinesResponse->getContent(), true);

        foreach ($machinesData as $machine) {
            $client->request('DELETE', '/machine/delete/' . $machine['id']);
            $deleteResponse = $client->getResponse();
            $this->assertEquals(200, $deleteResponse->getStatusCode(), 'Статус удаления машины должен быть 200');
        }

        $client->request('GET', '/processes');
        $processesResponse = $client->getResponse();
        $processesData = json_decode($processesResponse->getContent(), true);

        foreach ($processesData as $process) {
            $client->request('DELETE', '/process/delete/' . $process['id']);
            $deleteResponse = $client->getResponse();
            $this->assertEquals(200, $deleteResponse->getStatusCode(), 'Статус удаления процесса должен быть 200');
        }

        $payload = [
            'requiredMemory' => 512,
            'requiredCpu'    => 2
        ];
        $client->request(
            'POST',
            '/process/add',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), 'При отсутствии машин процесс добавить нельзя');

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Нет доступной машины для размещения процесса', $data['error']);
    }

    public function testAddProcessWithMachine(): void
    {
        $client = static::createClient();

        // Сначала добавляем машину, чтобы процесс смог назначиться.
        $machinePayload = [
            'totalMemory' => 2048,
            'totalCpu'    => 8
        ];
        $client->request(
            'POST',
            '/machine/add',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($machinePayload)
        );
        $machineResponse = $client->getResponse();
        $this->assertEquals(200, $machineResponse->getStatusCode());

        // Затем добавляем процесс.
        $processPayload = [
            'requiredMemory' => 512,
            'requiredCpu'    => 2
        ];
        $client->request(
            'POST',
            '/process/add',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($processPayload)
        );
        $processResponse = $client->getResponse();
        $this->assertEquals(200, $processResponse->getStatusCode(), 'Статус добавления процесса должен быть 200');

        $data = json_decode($processResponse->getContent(), true);
        $this->assertArrayHasKey('machineId', $data, 'Процесс должен быть назначен машине');
        $this->assertEquals('Процесс добавлен и назначен машине', $data['status']);
    }

    public function testDeleteProcess(): void
    {
        $client = static::createClient();

        // Добавляем машину для процесса.
        $machinePayload = [
            'totalMemory' => 2048,
            'totalCpu'    => 8
        ];
        $client->request(
            'POST',
            '/machine/add',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($machinePayload)
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Добавляем процесс.
        $processPayload = [
            'requiredMemory' => 512,
            'requiredCpu'    => 2
        ];
        $client->request(
            'POST',
            '/process/add',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($processPayload)
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Получаем назначение, чтобы узнать ID процесса.
        $client->request('GET', '/assignments');
        $assignments = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotEmpty($assignments, 'После добавления процесса должны быть назначения');
        $processId = $assignments[0]['processId'];

        // Удаляем процесс.
        $client->request('DELETE', '/process/delete/' . $processId);
        $deleteResponse = $client->getResponse();
        $this->assertEquals(200, $deleteResponse->getStatusCode(), 'Статус удаления процесса должен быть 200');

        $deleteData = json_decode($deleteResponse->getContent(), true);
        $this->assertEquals('Процесс удалён, назначения перераспределены', $deleteData['status']);
    }

    public function testDeleteMachine(): void
    {
        $client = static::createClient();

        // Добавляем машину.
        $machinePayload = [
            'totalMemory' => 2048,
            'totalCpu'    => 8
        ];
        $client->request(
            'POST',
            '/machine/add',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($machinePayload)
        );
        $machineResponse = $client->getResponse();
        $this->assertEquals(200, $machineResponse->getStatusCode());

        $machineData = json_decode($machineResponse->getContent(), true);
        $machineId = $machineData['machineId'];

        // Добавляем процесс, который будет назначен на эту машину.
        $processPayload = [
            'requiredMemory' => 512,
            'requiredCpu'    => 2
        ];
        $client->request(
            'POST',
            '/process/add',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($processPayload)
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Удаляем машину.
        $client->request('DELETE', '/machine/delete/' . $machineId);
        $deleteResponse = $client->getResponse();
        $this->assertEquals(200, $deleteResponse->getStatusCode(), 'Статус удаления машины должен быть 200');

        $deleteData = json_decode($deleteResponse->getContent(), true);
        $this->assertEquals('Машина удалена, назначения перераспределены', $deleteData['status']);
    }
}
