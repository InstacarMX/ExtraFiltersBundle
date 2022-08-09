<?php

namespace Instacar\ExtraFiltersBundle\Test\Application;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Instacar\ExtraFiltersBundle\App\DataFixtures\BookFixture;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;

class TestExpressionFilter extends ApiTestCase
{
    protected AbstractDatabaseTool $databaseTool;

    protected function setUp(): void
    {
        /** @var DatabaseToolCollection $databaseTool */
        $databaseTool = self::getContainer()->get(DatabaseToolCollection::class);
        $this->databaseTool = $databaseTool->get();
    }

    public function testDocumentation(): void
    {
        $client = static::createClient();

        $client->request('GET', '/docs', [
            'headers' => ['accept' => 'application/json'],
        ]);
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        self::assertJsonContains([
            'paths' => [
                '/books' => [
                    'get' => [
                        'parameters' => [
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'required' => false,
                                'description' => 'The collection page number',
                                'schema' => [
                                    'type' => 'integer',
                                ],
                            ],
                            [
                                'name' => 'search',
                                'in' => 'query',
                                'required' => false,
                                'schema' => [
                                    'type' => 'string',
                                ],
                            ],
                            [
                                'name' => 'exclude',
                                'in' => 'query',
                                'required' => false,
                                'schema' => [
                                    'type' => 'string',
                                ],
                            ],
                            [
                                'name' => 'budget',
                                'in' => 'query',
                                'schema' => [
                                    'type' => 'string',
                                ],
                            ],
                            [
                                'name' => 'available',
                                'in' => 'query',
                                'required' => false,
                                'schema' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ]
                ]
            ]
        ]);
    }

    public function testSearchExpressionFilter(): void
    {
        $this->databaseTool->loadFixtures([
            BookFixture::class,
        ]);

        $client = static::createClient();

        $client->request('GET', '/books?search=dummies', [
            'headers' => ['accept' => 'application/json'],
        ]);
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        self::assertJsonEquals([
            [
                'id' => 1,
                'name' => 'PHP for dummies',
                'price' => 120,
                'availableStart' => '2022-01-01T00:00:00+00:00',
                'availableEnd' => '2022-01-31T00:00:00+00:00',
                'author' => '/authors/1',
            ],
        ]);

        $client->request('GET', '/books?search=jane', [
            'headers' => ['accept' => 'application/json'],
        ]);
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        self::assertJsonEquals([
            [
                'id' => 2,
                'name' => 'How to test',
                'price' => 180,
                'availableStart' => '2022-01-01T00:00:00+00:00',
                'availableEnd' => '2022-01-31T00:00:00+00:00',
                'author' => '/authors/2',
            ],
            [
                'id' => 3,
                'name' => 'Symfony 6: The right way',
                'price' => 250,
                'availableStart' => '2022-02-01T00:00:00+00:00',
                'availableEnd' => '2022-02-28T00:00:00+00:00',
                'author' => '/authors/2',
            ],
        ]);
    }

    public function testExcludeExpressionFilter(): void
    {
        $this->databaseTool->loadFixtures([
            BookFixture::class,
        ]);

        $client = static::createClient();

        $client->request('GET', '/books?exclude=dummies', [
            'headers' => ['accept' => 'application/json'],
        ]);
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        self::assertJsonEquals([
            [
                'id' => 2,
                'name' => 'How to test',
                'price' => 180,
                'availableStart' => '2022-01-01T00:00:00+00:00',
                'availableEnd' => '2022-01-31T00:00:00+00:00',
                'author' => '/authors/2',
            ],
            [
                'id' => 3,
                'name' => 'Symfony 6: The right way',
                'price' => 250,
                'availableStart' => '2022-02-01T00:00:00+00:00',
                'availableEnd' => '2022-02-28T00:00:00+00:00',
                'author' => '/authors/2',
            ],
        ]);

        $client->request('GET', '/books?exclude=jane', [
            'headers' => ['accept' => 'application/json'],
        ]);
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        self::assertJsonEquals([
            [
                'id' => 1,
                'name' => 'PHP for dummies',
                'price' => 120,
                'availableStart' => '2022-01-01T00:00:00+00:00',
                'availableEnd' => '2022-01-31T00:00:00+00:00',
                'author' => '/authors/1',
            ],
        ]);
    }

    public function testBudgetExpressionFilter(): void
    {
        $this->databaseTool->loadFixtures([
            BookFixture::class,
        ]);

        $client = static::createClient();

        $client->request('GET', '/books?budget=150', [
            'headers' => ['accept' => 'application/json'],
        ]);
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        self::assertJsonEquals([
            [
                'id' => 1,
                'name' => 'PHP for dummies',
                'price' => 120,
                'availableStart' => '2022-01-01T00:00:00+00:00',
                'availableEnd' => '2022-01-31T00:00:00+00:00',
                'author' => '/authors/1',
            ],
            [
                'id' => 2,
                'name' => 'How to test',
                'price' => 180,
                'availableStart' => '2022-01-01T00:00:00+00:00',
                'availableEnd' => '2022-01-31T00:00:00+00:00',
                'author' => '/authors/2',
            ],
        ]);
    }

    public function testAvailableExpressionFilter(): void
    {
        $this->databaseTool->loadFixtures([
            BookFixture::class,
        ]);

        $client = static::createClient();

        $client->request('GET', '/books?available=2022-01-15', [
            'headers' => ['accept' => 'application/json'],
        ]);
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        self::assertJsonEquals([
            [
                'id' => 1,
                'name' => 'PHP for dummies',
                'price' => 120,
                'availableStart' => '2022-01-01T00:00:00+00:00',
                'availableEnd' => '2022-01-31T00:00:00+00:00',
                'author' => '/authors/1',
            ],
            [
                'id' => 2,
                'name' => 'How to test',
                'price' => 180,
                'availableStart' => '2022-01-01T00:00:00+00:00',
                'availableEnd' => '2022-01-31T00:00:00+00:00',
                'author' => '/authors/2',
            ],
        ]);
    }

    public function testCombinedFilters(): void
    {
        $this->databaseTool->loadFixtures([
            BookFixture::class,
        ]);

        $client = static::createClient();

        $client->request('GET', '/books?search=jane&exclude=symfony', [
            'headers' => ['accept' => 'application/json'],
        ]);
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        self::assertJsonEquals([
            [
                'id' => 2,
                'name' => 'How to test',
                'price' => 180,
                'availableStart' => '2022-01-01T00:00:00+00:00',
                'availableEnd' => '2022-01-31T00:00:00+00:00',
                'author' => '/authors/2',
            ],
        ]);
    }

    public function testDirectFilters(): void
    {
        $this->databaseTool->loadFixtures([
            BookFixture::class,
        ]);

        $client = static::createClient();

        $client->request('GET', '/books?author.name=john&availableStart[before]=2022-01-15&availableEnd[after]=2022-01-15', [
            'headers' => ['accept' => 'application/json'],
        ]);
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        self::assertJsonEquals([
            [
                'id' => 1,
                'name' => 'PHP for dummies',
                'price' => 120,
                'availableStart' => '2022-01-01T00:00:00+00:00',
                'availableEnd' => '2022-01-31T00:00:00+00:00',
                'author' => '/authors/1',
            ],
            [
                'id' => 2,
                'name' => 'How to test',
                'price' => 180,
                'availableStart' => '2022-01-01T00:00:00+00:00',
                'availableEnd' => '2022-01-31T00:00:00+00:00',
                'author' => '/authors/2',
            ],
            [
                'id' => 3,
                'name' => 'Symfony 6: The right way',
                'price' => 250,
                'availableStart' => '2022-02-01T00:00:00+00:00',
                'availableEnd' => '2022-02-28T00:00:00+00:00',
                'author' => '/authors/2',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->databaseTool);
    }
}
