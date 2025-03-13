<?php
namespace App\Controller;

use App\Entity\ProcessEntity;
use App\Entity\Machine;
use App\Entity\Assignment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class MicroserviceController extends AbstractController
{
    #[Route("/assignments", name: "get_assignments", methods: ["GET"])]
    public function getAssignments(EntityManagerInterface $em): JsonResponse
    {
        $assignments = $em->getRepository(Assignment::class)->findAll();
        $data = [];

        foreach ($assignments as $assignment) {
            $data[] = [
                'id' => $assignment->getId(),
                'machineId' => $assignment->getMachine()->getId(),
                'processId' => $assignment->getProcess()->getId(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route("/machines", name: "get_machines", methods: ["GET"])]
    public function getMachines(EntityManagerInterface $em): JsonResponse
    {
        $machines = $em->getRepository(Machine::class)->findAll();
        $data = [];

        foreach ($machines as $machine) {
            $data[] = [
                'id' => $machine->getId(),
                'totalMemory' => $machine->getTotalMemory(),
                'totalCpu' => $machine->getTotalCpu(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route("/processes", name: "get_processes", methods: ["GET"])]
    public function getProcesses(EntityManagerInterface $em): JsonResponse
    {
        $processes = $em->getRepository(ProcessEntity::class)->findAll();
        $data = [];

        foreach ($processes as $process) {
            $data[] = [
                'id' => $process->getId(),
                'requiredMemory' => $process->getRequiredMemory(),
                'requiredCpu' => $process->getRequiredCpu(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route("/process/add", name: "add_process", methods: ["POST"])]
    public function addProcess(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $requiredMemory = $data['requiredMemory'] ?? null;
        $requiredCpu = $data['requiredCpu'] ?? null;
        
        if ($requiredMemory === null || $requiredCpu === null) {
            return new JsonResponse(['error' => 'Некорректные входные данные'], 400);
        }
        
        $process = new ProcessEntity();
        $process->setRequiredMemory((int)$requiredMemory);
        $process->setRequiredCpu((int)$requiredCpu);
        $em->persist($process);
        $em->flush();
        
        $machine = $this->findSuitableMachine($process, $em);
        if (!$machine) {
            return new JsonResponse(['error' => 'Нет доступной машины для размещения процесса'], 400);
        }
        
        $assignment = new Assignment();
        $assignment->setMachine($machine);
        $assignment->setProcess($process);
        $em->persist($assignment);
        $em->flush();
        
        return new JsonResponse([
            'status'    => 'Процесс добавлен и назначен машине',
            'machineId' => $machine->getId()
        ]);
    }
    
    #[Route("/process/delete/{id}", name: "delete_process", methods: ["DELETE"])]
    public function deleteProcess(int $id, EntityManagerInterface $em): JsonResponse
    {
        $process = $em->getRepository(ProcessEntity::class)->find($id);
        if (!$process) {
            return new JsonResponse(['error' => 'Процесс не найден'], 404);
        }
        
        $assignment = $em->getRepository(Assignment::class)->findOneBy(['process' => $process]);
        if ($assignment) {
            $em->remove($assignment);
        }
        
        $em->remove($process);
        $em->flush();
        
        $this->rebalance($em);
        
        return new JsonResponse(['status' => 'Процесс удалён, назначения перераспределены']);
    }

    #[Route("/machine/add", name: "add_machine", methods: ["POST"])]
    public function addMachine(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $totalMemory = $data['totalMemory'] ?? null;
        $totalCpu = $data['totalCpu'] ?? null;
        
        if ($totalMemory === null || $totalCpu === null) {
            return new JsonResponse(['error' => 'Некорректные входные данные'], 400);
        }
        
        $machine = new Machine();
        $machine->setTotalMemory((int)$totalMemory);
        $machine->setTotalCpu((int)$totalCpu);
        $em->persist($machine);
        $em->flush();
        
        $this->rebalance($em);
        
        return new JsonResponse([
            'status'    => 'Машина добавлена, назначения перераспределены',
            'machineId' => $machine->getId()
        ]);
    }

    #[Route("/machine/delete/{id}", name: "delete_machine", methods: ["DELETE"])]
    public function deleteMachine(int $id, EntityManagerInterface $em): JsonResponse
    {
        $machine = $em->getRepository(Machine::class)->find($id);
        if (!$machine) {
            return new JsonResponse(['error' => 'Машина не найдена'], 404);
        }

        foreach ($machine->getAssignments() as $assignment) {
            $process = $assignment->getProcess();
            $em->remove($assignment);
            $newMachine = $this->findSuitableMachine($process, $em, $excludeMachine = $machine);
            if ($newMachine) {
                $newAssignment = new Assignment();
                $newAssignment->setMachine($newMachine);
                $newAssignment->setProcess($process);
                $em->persist($newAssignment);
            }
        }
        
        $em->remove($machine);
        $em->flush();
        
        $this->rebalance($em);
        
        return new JsonResponse(['status' => 'Машина удалена, назначения перераспределены']);
    }
    
    /**
     * Нагрузка как среднее арифметическое занятых памяти и цпу после добавления процесса.
     *
     * @param ProcessEntity $process
     * @param EntityManagerInterface $em
     * @param Machine|null $excludeMachine Машина, которую следует исключить из поиска
     * @return Machine|null
     */
    private function findSuitableMachine(ProcessEntity $process, EntityManagerInterface $em, $excludeMachine = null): ?Machine
    {
        $machines = $em->getRepository(Machine::class)->findAll();
        $bestMachine = null;
        $bestLoad = PHP_FLOAT_MAX;
        
        foreach ($machines as $machine) {
            if ($excludeMachine && $machine->getId() === $excludeMachine->getId()) {
                continue;
            }
            
            $usedMemory = 0;
            $usedCpu = 0;
            foreach ($machine->getAssignments() as $assignment) {
                $proc = $assignment->getProcess();
                $usedMemory += $proc->getRequiredMemory();
                $usedCpu += $proc->getRequiredCpu();
            }
            
            if (($usedMemory + $process->getRequiredMemory()) > $machine->getTotalMemory() ||
                ($usedCpu + $process->getRequiredCpu()) > $machine->getTotalCpu()) {
                continue;
            }
            
            $memoryLoad = ($usedMemory + $process->getRequiredMemory()) / $machine->getTotalMemory();
            $cpuLoad = ($usedCpu + $process->getRequiredCpu()) / $machine->getTotalCpu();
            $combinedLoad = ($memoryLoad + $cpuLoad) / 2;
            
            if ($combinedLoad < $bestLoad) {
                $bestLoad = $combinedLoad;
                $bestMachine = $machine;
            }
        }
        
        return $bestMachine;
    }
    
    /**
     * Все удаляется, процессы сортируются по сумме памяти и цпу, назначаются на машины с наименьшей относительной нагрузкой.
     */
    private function rebalance(EntityManagerInterface $em): void
    {
        $processes = $em->getRepository(ProcessEntity::class)->findAll();
        
        $assignments = $em->getRepository(Assignment::class)->findAll();
        foreach ($assignments as $assignment) {
            $em->remove($assignment);
        }
        $em->flush();
        
        $machines = $em->getRepository(Machine::class)->findAll();
        $machineLoad = [];
        foreach ($machines as $machine) {
            $machineLoad[$machine->getId()] = [
                'machine'     => $machine,
                'usedMemory'  => 0,
                'usedCpu'     => 0,
            ];
        }
        
        usort($processes, function(ProcessEntity $a, ProcessEntity $b) {
            return (($b->getRequiredMemory() + $b->getRequiredCpu()) <=> ($a->getRequiredMemory() + $a->getRequiredCpu()));
        });
        
        foreach ($processes as $process) {
            $bestMachine = null;
            $bestLoad = PHP_FLOAT_MAX;
            
            foreach ($machineLoad as $id => $info) {
                $machine = $info['machine'];
                if (($info['usedMemory'] + $process->getRequiredMemory()) > $machine->getTotalMemory() ||
                    ($info['usedCpu'] + $process->getRequiredCpu()) > $machine->getTotalCpu()) {
                    continue;
                }
                
                $memoryLoad = ($info['usedMemory'] + $process->getRequiredMemory()) / $machine->getTotalMemory();
                $cpuLoad = ($info['usedCpu'] + $process->getRequiredCpu()) / $machine->getTotalCpu();
                $combinedLoad = ($memoryLoad + $cpuLoad) / 2;
                
                if ($combinedLoad < $bestLoad) {
                    $bestLoad = $combinedLoad;
                    $bestMachine = $machine;
                }
            }
            
            if ($bestMachine) {
                $assignment = new Assignment();
                $assignment->setMachine($bestMachine);
                $assignment->setProcess($process);
                $em->persist($assignment);
                
                $machineId = $bestMachine->getId();
                $machineLoad[$machineId]['usedMemory'] += $process->getRequiredMemory();
                $machineLoad[$machineId]['usedCpu'] += $process->getRequiredCpu();
            }
        }
        $em->flush();
    }
}
