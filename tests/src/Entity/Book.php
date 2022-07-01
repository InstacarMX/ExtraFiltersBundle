<?php

namespace Instacar\ExtraFiltersBundle\Test\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Instacar\ExtraFiltersBundle\Doctrine\Orm\Filter\ExpressionFilter;

/**
 * @ApiResource(
 *     collectionOperations={"get"},
 *     itemOperations={"get"},
 * )
 * @ApiFilter(filterClass=ExpressionFilter::class, properties={
 *     "search"="orWhere(search('name', 'partial'), search('author.name', 'partial'))",
 *     "exclude"="notWhere(orWhere(search('name', 'partial'), search('author.name', 'partial')))",
 *     "available"="andWhere(date('availableStart', 'exclude_null', {before: value}),date('availableEnd', 'exclude_null', {after: value}))",
 * })
 * @ORM\Entity()
 */
class Book
{
    /**
     * @ORM\Id()
     * @Orm\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string")
     */
    private string $name;

    /**
     * @ORM\Column(type="date")
     */
    private \DateTimeInterface $availableStart;

    /**
     * @ORM\Column(type="date")
     */
    private \DateTimeInterface $availableEnd;

    /**
     * @ORM\ManyToOne()
     * @ORM\JoinColumn(nullable=false)
     */
    private Author $author;

    public function __construct(string $name, \DateTimeInterface $availableDateStart, \DateTimeInterface $availableDateEnd, Author $author)
    {
        $this->name = $name;
        $this->availableStart = $availableDateStart;
        $this->availableEnd = $availableDateEnd;
        $this->author = $author;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getAvailableStart(): \DateTimeInterface
    {
        return $this->availableStart;
    }

    public function setAvailableStart(\DateTimeInterface $availableStart): void
    {
        $this->availableStart = $availableStart;
    }

    public function getAvailableEnd(): \DateTimeInterface
    {
        return $this->availableEnd;
    }

    public function setAvailableEnd(\DateTimeInterface $availableEnd): void
    {
        $this->availableEnd = $availableEnd;
    }

    public function getAuthor(): Author
    {
        return $this->author;
    }

    public function setAuthor(Author $author): void
    {
        $this->author = $author;
    }
}
