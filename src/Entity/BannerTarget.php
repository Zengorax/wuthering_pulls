<?php

namespace App\Entity;

use App\Repository\BannerTargetRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BannerTargetRepository::class)]
class BannerTarget
{
    public const FIFTY_FIFTY_WON = 'won';
    public const FIFTY_FIFTY_LOST = 'lost';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    private ?string $name = null;

    #[ORM\Column(options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    private int $pullsCharacter = 0;

    #[ORM\Column(options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    private int $pullsWeapon = 0;

    #[ORM\Column(options: ['default' => false])]
    private bool $isEstimate = false;

    #[ORM\Column(length: 8, nullable: true)]
    #[Assert\Choice(choices: [self::FIFTY_FIFTY_WON, self::FIFTY_FIFTY_LOST])]
    private ?string $fiftyFiftyResult = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\ManyToOne(inversedBy: 'bannerTargets')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Version $version = null;

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

    public function getPullsCharacter(): int
    {
        return $this->pullsCharacter;
    }

    public function setPullsCharacter(int $pullsCharacter): static
    {
        $this->pullsCharacter = $pullsCharacter;

        return $this;
    }

    public function getPullsWeapon(): int
    {
        return $this->pullsWeapon;
    }

    public function setPullsWeapon(int $pullsWeapon): static
    {
        $this->pullsWeapon = $pullsWeapon;

        return $this;
    }

    public function getTotalPulls(): int
    {
        return $this->pullsCharacter + $this->pullsWeapon;
    }

    public function isEstimate(): bool
    {
        return $this->isEstimate;
    }

    public function setIsEstimate(bool $isEstimate): static
    {
        $this->isEstimate = $isEstimate;

        return $this;
    }

    public function getFiftyFiftyResult(): ?string
    {
        return $this->fiftyFiftyResult;
    }

    public function setFiftyFiftyResult(?string $fiftyFiftyResult): static
    {
        $this->fiftyFiftyResult = $fiftyFiftyResult;

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

    public function getVersion(): ?Version
    {
        return $this->version;
    }

    public function setVersion(?Version $version): static
    {
        $this->version = $version;

        return $this;
    }
}
