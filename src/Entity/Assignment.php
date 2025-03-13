<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\AssignmentRepository;

#[ORM\Entity(repositoryClass: AssignmentRepository::class)]
#[ORM\Table(name: "assignments")]
class Assignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Machine", inversedBy: "assignments")]
    #[ORM\JoinColumn(name: "machine_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private $machine;

    #[ORM\ManyToOne(targetEntity: "App\Entity\ProcessEntity")]
    #[ORM\JoinColumn(name: "process_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private $process;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMachine(): ?Machine
    {
        return $this->machine;
    }

    public function setMachine(Machine $machine): self
    {
        $this->machine = $machine;

        return $this;
    }

    public function getProcess(): ?ProcessEntity
    {
        return $this->process;
    }

    public function setProcess(ProcessEntity $process): self
    {
        $this->process = $process;

        return $this;
    }
}
