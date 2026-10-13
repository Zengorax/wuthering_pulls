<?php

namespace App\Entity;

use App\Enum\Element;
use App\Repository\BannerTargetRepository;
use App\Statistics\PullRules;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BannerTargetRepository::class)]
class BannerTarget
{
    public const FIFTY_FIFTY_WON = 'won';
    public const FIFTY_FIFTY_LOST = 'lost';
    public const RARITIES = [4, 5];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    private ?string $name = null;

    #[ORM\Column(type: 'smallint', options: ['default' => 5])]
    #[Assert\Choice(choices: self::RARITIES)]
    private int $rarity = 5;

    #[ORM\Column(length: 16, nullable: true, enumType: Element::class)]
    private ?Element $element = null;

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

    public function getRarity(): int
    {
        return $this->rarity;
    }

    public function setRarity(int $rarity): static
    {
        $this->rarity = $rarity;

        return $this;
    }

    public function isFiveStar(): bool
    {
        return 5 === $this->rarity;
    }

    public function getElement(): ?Element
    {
        return $this->element;
    }

    public function setElement(?Element $element): static
    {
        $this->element = $element;

        return $this;
    }

    /**
     * Plafond de la jauge de pitié du personnage : 80, ou 160 si le 50/50 a été perdu (ou dépassé).
     */
    public function getCharacterPityCap(): int
    {
        $hardPity = PullRules::HARD_PITY;

        return self::FIFTY_FIFTY_LOST === $this->fiftyFiftyResult || $this->pullsCharacter > $hardPity
            ? 2 * $hardPity
            : $hardPity;
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
