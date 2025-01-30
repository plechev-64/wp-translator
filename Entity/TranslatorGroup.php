<?php

namespace Src\Module\Translator\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Src\Module\Translator\Enum\EntityType;
use Src\Module\Translator\Repository\TranslatorGroupRepository;

#[ORM\Entity(repositoryClass: TranslatorGroupRepository::class)]
#[ORM\Table(name: 'wp_translator_groups')]
#[ORM\Index(columns: ['source_id', 'source_type'], name: 'source_idx')]
class TranslatorGroup
{

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: "id", type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "source_type", enumType: EntityType::class)]
    private EntityType $sourceType;

    #[ORM\Column(name: "source_id", type: "integer")]
    private int $sourceId;

    #[ORM\Column(name: "old_id", type: "integer", nullable: true)]
    private ?int $oldId;

    #[ORM\OneToMany(mappedBy: "group", targetEntity: TranslatorEntity::class)]
    private Collection $entities;

    public function __construct()
    {
        $this->entities = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return EntityType
     */
    public function getSourceType(): EntityType
    {
        return $this->sourceType;
    }

    /**
     * @param EntityType $sourceType
     */
    public function setSourceType(EntityType $sourceType): void
    {
        $this->sourceType = $sourceType;
    }

    /**
     * @return int
     */
    public function getSourceId(): int
    {
        return $this->sourceId;
    }

    /**
     * @param int $sourceId
     */
    public function setSourceId(int $sourceId): void
    {
        $this->sourceId = $sourceId;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getEntities(): ArrayCollection|Collection
    {
        return $this->entities;
    }

    /**
     * @param ArrayCollection|Collection $entities
     */
    public function setEntities(ArrayCollection|Collection $entities): void
    {
        $this->entities = $entities;
    }

	/**
	 * @return int|null
	 */
	public function getOldId(): ?int {
		return $this->oldId;
	}

	/**
	 * @param int|null $oldId
	 */
	public function setOldId( ?int $oldId ): void {
		$this->oldId = $oldId;
	}
}
