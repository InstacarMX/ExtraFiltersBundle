<?php

namespace Instacar\ExtraFiltersBundle\Test\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Instacar\ExtraFiltersBundle\Doctrine\Orm\Filter\ExpressionFilter;

/**
 * @ApiResource(
 *     collectionOperations={"get"},
 *     itemOperations={"get"},
 * )
 * @ApiFilter(filterClass=SearchFilter::class, properties={
 *     "name"="ipartial",
 *     "author.name"="ipartial",
 *     "year"="exact",
 * })
 * @ApiFilter(filterClass=ExpressionFilter::class, properties={
 *     "search"="orWhere(search('name'), search('author.name'), search('year'))",
 *     "exclude"="notWhere(orWhere(search('name'), search('author.name')))"
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
     * @ORM\Column(type="string", length=4)
     */
    private string $year;

    /**
     * @ORM\ManyToOne()
     * @ORM\JoinColumn(nullable=false)
     */
    private Author $author;

    public function __construct(string $name, string $year, Author $author)
    {
        $this->name = $name;
        $this->year = $year;
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

    public function getYear(): string
    {
        return $this->year;
    }

    public function setYear(string $year): void
    {
        $this->year = $year;
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
