<?php

namespace App\Entity;

use App\Repository\VersionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: VersionRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_version_per_major', columns: ['major_version_id', 'name'])]
#[UniqueEntity(fields: ['majorVersion', 'name'], message: 'Cette version existe déjà dans cette période majeure.')]
class Version
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 16)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 16)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'versions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?MajorVersion $majorVersion = null;

    /**
     * @var Collection<int, BannerTarget>
     */
    #[ORM\OneToMany(targetEntity: BannerTarget::class, mappedBy: 'version', orphanRemoval: true)]
    private Collection $bannerTargets;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $startLuminite = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $startAstrite = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $startPulls = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $estimatedEndLuminite = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $estimatedEndAstrite = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $estimatedEndPulls = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Assert\Length(max: 32)]
    private ?string $equivalence = null;

    public function __construct()
    {
        $this->bannerTargets = new ArrayCollection();
    }

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

    public function getMajorVersion(): ?MajorVersion
    {
        return $this->majorVersion;
    }

    public function setMajorVersion(?MajorVersion $majorVersion): static
    {
        $this->majorVersion = $majorVersion;

        return $this;
    }

    /**
     * @return Collection<int, BannerTarget>
     */
    public function getBannerTargets(): Collection
    {
        return $this->bannerTargets;
    }

    public function addBannerTarget(BannerTarget $bannerTarget): static
    {
        if (!$this->bannerTargets->contains($bannerTarget)) {
            $this->bannerTargets->add($bannerTarget);
            $bannerTarget->setVersion($this);
        }

        return $this;
    }

    public function removeBannerTarget(BannerTarget $bannerTarget): static
    {
        $this->bannerTargets->removeElement($bannerTarget);

        return $this;
    }

    public function getTotalPulls(): int
    {
        return array_sum(array_map(
            static fn (BannerTarget $target): int => $target->getTotalPulls(),
            $this->bannerTargets->toArray(),
        ));
    }

    public function getStartLuminite(): ?int
    {
        return $this->startLuminite;
    }

    public function setStartLuminite(?int $startLuminite): static
    {
        $this->startLuminite = $startLuminite;

        return $this;
    }

    public function getStartAstrite(): ?int
    {
        return $this->startAstrite;
    }

    public function setStartAstrite(?int $startAstrite): static
    {
        $this->startAstrite = $startAstrite;

        return $this;
    }

    public function getStartPulls(): ?int
    {
        return $this->startPulls;
    }

    public function setStartPulls(?int $startPulls): static
    {
        $this->startPulls = $startPulls;

        return $this;
    }

    public function getEstimatedEndLuminite(): ?int
    {
        return $this->estimatedEndLuminite;
    }

    public function setEstimatedEndLuminite(?int $estimatedEndLuminite): static
    {
        $this->estimatedEndLuminite = $estimatedEndLuminite;

        return $this;
    }

    public function getEstimatedEndAstrite(): ?int
    {
        return $this->estimatedEndAstrite;
    }

    public function setEstimatedEndAstrite(?int $estimatedEndAstrite): static
    {
        $this->estimatedEndAstrite = $estimatedEndAstrite;

        return $this;
    }

    public function getEstimatedEndPulls(): ?int
    {
        return $this->estimatedEndPulls;
    }

    public function setEstimatedEndPulls(?int $estimatedEndPulls): static
    {
        $this->estimatedEndPulls = $estimatedEndPulls;

        return $this;
    }

    public function getEquivalence(): ?string
    {
        return $this->equivalence;
    }

    public function setEquivalence(?string $equivalence): static
    {
        $this->equivalence = $equivalence !== null ? trim($equivalence) : null;

        return $this;
    }
}
