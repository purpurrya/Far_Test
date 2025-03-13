<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\MachineRepository;

#[ORM\Entity(repositoryClass: MachineRepository::class)]
#[ORM\Table(name: "machines")]
class Machine
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private $id;
    
    #[ORM\Column(type: "integer")]
    private $totalMemory;
    
    #[ORM\Column(type: "integer")]
    private $totalCpu;
    
    #[ORM\OneToMany(targetEntity: "App\Entity\Assignment", mappedBy: "machine", cascade: ["persist", "remove"])]
    private $assignments;
    
    public function __construct()
    {
        $this->assignments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotalMemory(): int
    {
        return $this->totalMemory;
    }

    public function setTotalMemory(int $totalMemory): self
    {
        $this->totalMemory = $totalMemory;
        return $this;
    }

    public function getTotalCpu(): int
    {
        return $this->totalCpu;
    }

    public function setTotalCpu(int $totalCpu): self
    {
        $this->totalCpu = $totalCpu;
        return $this;
    }

    /**
     * @return Collection|Assignment[]
     */
    public function getAssignments(): Collection
    {
        return $this->assignments;
    }
}
