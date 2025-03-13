<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ProcessEntityRepository;

#[ORM\Entity(repositoryClass: ProcessEntityRepository::class)]
#[ORM\Table(name: "processes")]
class ProcessEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "integer")]
    private $requiredMemory;

    #[ORM\Column(type: "integer")]
    private $requiredCpu;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequiredMemory(): int
    {
        return $this->requiredMemory;
    }

    public function setRequiredMemory(int $requiredMemory): self
    {
        $this->requiredMemory = $requiredMemory;

        return $this;
    }

    public function getRequiredCpu(): int
    {
        return $this->requiredCpu;
    }

    public function setRequiredCpu(int $requiredCpu): self
    {
        $this->requiredCpu = $requiredCpu;

        return $this;
    }
}
