<?php

namespace App\Entity;

use App\Repository\MajorVersionRepository;
use App\Statistics\PullRules;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: MajorVersionRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'major_version.unique')]
class MajorVersion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private ?string $name = null;

    /**
     * @var Collection<int, Version>
     */
    #[ORM\OneToMany(targetEntity: Version::class, mappedBy: 'majorVersion', orphanRemoval: true)]
    private Collection $versions;

    /**
     * @var Collection<int, ResourceBreakdown>
     */
    #[ORM\OneToMany(targetEntity: ResourceBreakdown::class, mappedBy: 'majorVersion', orphanRemoval: true)]
    private Collection $resourceBreakdowns;

    public function __construct()
    {
        $this->versions = new ArrayCollection();
        $this->resourceBreakdowns = new ArrayCollection();
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

    /**
     * @return Collection<int, Version>
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function addVersion(Version $version): static
    {
        if (!$this->versions->contains($version)) {
            $this->versions->add($version);
            $version->setMajorVersion($this);
        }

        return $this;
    }

    public function removeVersion(Version $version): static
    {
        if ($this->versions->removeElement($version) && $version->getMajorVersion() === $this) {
            $version->setMajorVersion(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, ResourceBreakdown>
     */
    public function getResourceBreakdowns(): Collection
    {
        return $this->resourceBreakdowns;
    }

    public function addResourceBreakdown(ResourceBreakdown $resourceBreakdown): static
    {
        if (!$this->resourceBreakdowns->contains($resourceBreakdown)) {
            $this->resourceBreakdowns->add($resourceBreakdown);
            $resourceBreakdown->setMajorVersion($this);
        }

        return $this;
    }

    public function removeResourceBreakdown(ResourceBreakdown $resourceBreakdown): static
    {
        if ($this->resourceBreakdowns->removeElement($resourceBreakdown) && $resourceBreakdown->getMajorVersion() === $this) {
            $resourceBreakdown->setMajorVersion(null);
        }

        return $this;
    }

    public function getTotalPulls(): int
    {
        return array_sum(array_map(
            static fn (Version $version): int => $version->getTotalPulls(),
            $this->versions->toArray(),
        ));
    }

    public function getRealPulls(): int
    {
        return array_sum(array_map(
            static fn (Version $version): int => $version->getRealPulls(),
            $this->versions->toArray(),
        ));
    }

    public function getEstimatedPulls(): int
    {
        return $this->getTotalPulls() - $this->getRealPulls();
    }

    public function getTotalAstrites(): int
    {
        return $this->getTotalPulls() * PullRules::ASTRITES_PER_PULL;
    }
}
