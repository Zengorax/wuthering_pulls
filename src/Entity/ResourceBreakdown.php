<?php

namespace App\Entity;

use App\Repository\ResourceBreakdownRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ResourceBreakdownRepository::class)]
class ResourceBreakdown
{
    public const UNIT_ASTRITES = 'astrites';
    public const UNIT_PULLS = 'pulls';
    public const UNIT_TIDES = 'tides';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    private ?string $name = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $amount = 0;

    #[ORM\Column(length: 16)]
    #[Assert\Choice(choices: [self::UNIT_ASTRITES, self::UNIT_PULLS, self::UNIT_TIDES])]
    private string $unit = self::UNIT_ASTRITES;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\ManyToOne(inversedBy: 'resourceBreakdowns')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?MajorVersion $majorVersion = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name !== null ? trim($name) : null;

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getMajorVersion(): ?MajorVersion
    {
        return $this->majorVersion;
    }

    public function setMajorVersion(?MajorVersion $majorVersion): static
    {
        $this->majorVersion = $majorVersion;

        return $this;
    }
}
