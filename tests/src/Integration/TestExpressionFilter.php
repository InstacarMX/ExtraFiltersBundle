<?php

namespace Instacar\ExtraFiltersBundle\Test\Integration;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use Instacar\ExtraFiltersBundle\Test\DataFixtures\BookFixture;
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
                                'name' => 'availableStart[before]',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'string',
                            ],
                            [
                                'name' => 'availableStart[strictly_before]',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'string',
                            ],
                            [
                                'name' => 'availableStart[after]',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'string',
                            ],
                            [
                                'name' => 'availableStart[strictly_after]',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'string',
                            ],
                            [
                                'name' => 'availableEnd[before]',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'string',
                            ],
                            [
                                'name' => 'availableEnd[strictly_before]',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'string',
                            ],
                            [
                                'name' => 'availableEnd[after]',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'string',
                            ],
                            [
                                'name' => 'availableEnd[strictly_after]',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'string',
                            ],
                            [
                                'name' => 'name',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'string',
                            ],
                            [
                                'name' => 'author.name',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'string',
                            ],
                            [
                                'name' => 'search',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'string',
                            ],
                            [
                                'name' => 'exclude',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'string',
                            ],
                            [
                                'name' => 'available',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'string',
                            ],
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'required' => false,
                                'description' => 'The collection page number',
                                'type' => 'integer',
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
                'availableStart' => '2022-01-01T00:00:00+00:00',
                'availableEnd' => '2022-01-31T00:00:00+00:00',
                'author' => '/authors/2',
            ],
            [
                'id' => 3,
                'name' => 'Symfony 6: The right way',
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
                'availableStart' => '2022-01-01T00:00:00+00:00',
                'availableEnd' => '2022-01-31T00:00:00+00:00',
                'author' => '/authors/2',
            ],
            [
                'id' => 3,
                'name' => 'Symfony 6: The right way',
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
                'availableStart' => '2022-01-01T00:00:00+00:00',
                'availableEnd' => '2022-01-31T00:00:00+00:00',
                'author' => '/authors/1',
            ],
        ]);
    }

    public function testAvailableExpressionFilters(): void
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
                'availableStart' => '2022-01-01T00:00:00+00:00',
                'availableEnd' => '2022-01-31T00:00:00+00:00',
                'author' => '/authors/1',
            ],
            [
                'id' => 2,
                'name' => 'How to test',
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
                'availableStart' => '2022-01-01T00:00:00+00:00',
                'availableEnd' => '2022-01-31T00:00:00+00:00',
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
