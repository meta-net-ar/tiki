<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class CategoriesTest extends TikiTestCase
{
    protected $categories = [];

    protected function setUp(): void
    {
        $this->categories[] = [
            'name' => 'Test Category One',
            'description' => 'Description One',
        ];

        $this->categories[] = [
            'name' => 'Test Category Two',
            'description' => 'Description Two',
        ];

        $this->categories[] = [
            'name' => 'Test Category Three',
            'description' => 'Description Three',
        ];

        $this->categories[] = [
            'name' => 'Test Category Four',
            'description' => 'Description Four',
        ];

        $this->categories[] = [
            'name' => 'Test Category Five',
            'description' => 'Description Five',
        ];
    }

    public function testCreateCategory(): void
    {
        foreach ($this->categories as $index => $category) {
            if ($index > 1) {
                $category['parentId'] = $this->categories[$index - 1]['categId'];
                $category['rootId'] = $this->categories[$index - 1]['categId'];
            }

            $response = $this->makeRequest('POST', '/categories', $category);
            $this->assertEquals(200, $response->getStatusCode());

            $responseData = json_decode($response->getBody(), true);
            $this->assertNotEmpty($responseData);

            $this->assertEquals($category['name'], $responseData['name']);
            $this->assertEquals($category['description'], $responseData['description']);
            $this->assertIsArray($responseData['children']);
            $this->assertIsArray($responseData['descendants']);
            $this->assertIsArray($responseData['tepath']);

            $this->assertCategoryResponseHasKeys([$responseData]);

            $tepath_key = $responseData['categId'];
            $this->assertArrayHasKey($tepath_key, $responseData['tepath']);
            $this->assertEquals($responseData['name'], $responseData['tepath'][$tepath_key]);

            if ($index > 1) {
                $this->assertEquals($this->categories[$index - 1]['categId'], $responseData['parentId']);
                $this->assertEquals($this->categories[1]['categId'], $responseData['rootId']);

                $this->assertStringContainsString($this->categories[$index - 1]['name'] . '::' . $responseData['name'], $responseData['categpath']);
                $this->assertStringContainsString($this->categories[$index - 1]['name'] . '::' . $responseData['name'], $responseData['relativePathString']);
            } else {
                $this->assertEquals($responseData['name'], $responseData['categpath']);
                $this->assertEquals($responseData['name'], $responseData['relativePathString']);
            }

            $this->categories[$index] = array_merge($this->categories[$index], $responseData);
        }
    }

    public function testGetAllCategoriesSuccess(): void
    {
        $response = $this->makeRequest('GET', '/categories', ['type' => 'all']);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getBody(), true);
        $this->assertNotEmpty($responseData);
        $this->assertIsArray($responseData);
    }

    public function testGetAllCategoriesCount(): void
    {
        $response = $this->makeRequest('GET', '/categories', ['type' => 'all']);
        $responseData = json_decode($response->getBody(), true);

        $this->assertEquals(count($this->categories), count($responseData));
    }

    public function testGetAllCategoriesAttributes(): void
    {
        $response = $this->makeRequest('GET', '/categories', ['type' => 'all']);
        $responseData = json_decode($response->getBody(), true);

        $categoriesFixtures = require 'fixtures/categories_fixtures.php';

        foreach ($responseData as $index => $category) {
            $this->assertEquals($categoriesFixtures[$index]['name'], $category['name']);
            $this->assertEquals($categoriesFixtures[$index]['description'], $category['description']);
            $this->assertEquals($categoriesFixtures[$index]['parentId'], $category['parentId']);
            $this->assertEquals($categoriesFixtures[$index]['rootId'], $category['rootId']);
            $this->assertEquals($categoriesFixtures[$index]['hits'], $category['hits']);
            $this->assertEquals($categoriesFixtures[$index]['tplGroupContainerId'], $category['tplGroupContainerId']);
            $this->assertEquals($categoriesFixtures[$index]['tplGroupPattern'], $category['tplGroupPattern']);
            $this->assertEquals($categoriesFixtures[$index]['num_roles'], $category['num_roles']);
            $this->assertEquals($categoriesFixtures[$index]['objects'], $category['objects']);
            $this->assertEquals($categoriesFixtures[$index]['children'], $category['children']);
            $this->assertEquals($categoriesFixtures[$index]['descendants'], $category['descendants']);
            $this->assertEquals($categoriesFixtures[$index]['tepath'], $category['tepath']);
            $this->assertEquals($categoriesFixtures[$index]['categpath'], $category['categpath']);
            $this->assertEquals($categoriesFixtures[$index]['relativePathString'], $category['relativePathString']);
        }
    }

    public function testGetRootsCategoriesSuccess(): void
    {
        $response = $this->makeRequest('GET', '/categories', ['type' => 'roots']);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getBody(), true);
        $this->assertNotEmpty($responseData);
        $this->assertIsArray($responseData);
    }

    public function testGetRootsCategoriesCount(): void
    {
        $response = $this->makeRequest('GET', '/categories', ['type' => 'roots']);
        $responseData = json_decode($response->getBody(), true);

        $this->assertEquals(2, count($responseData));
    }

    public function testGetRootsCategoriesAttributes(): void
    {
        $response = $this->makeRequest('GET', '/categories', ['type' => 'roots']);
        $responseData = json_decode($response->getBody(), true);

        $categoriesFixtures = require 'fixtures/categories_fixtures.php';

        foreach ($responseData as $index => $category) {
            $this->assertEquals($categoriesFixtures[$index]['name'], $category['name']);
            $this->assertEquals($categoriesFixtures[$index]['description'], $category['description']);
            $this->assertEquals($categoriesFixtures[$index]['parentId'], $category['parentId']);
            $this->assertEquals($categoriesFixtures[$index]['rootId'], $category['rootId']);
            $this->assertEquals($categoriesFixtures[$index]['hits'], $category['hits']);
            $this->assertEquals($categoriesFixtures[$index]['tplGroupContainerId'], $category['tplGroupContainerId']);
            $this->assertEquals($categoriesFixtures[$index]['tplGroupPattern'], $category['tplGroupPattern']);
            $this->assertEquals($categoriesFixtures[$index]['num_roles'], $category['num_roles']);
            $this->assertEquals($categoriesFixtures[$index]['objects'], $category['objects']);
            $this->assertEquals($categoriesFixtures[$index]['children'], $category['children']);
            $this->assertEquals($categoriesFixtures[$index]['descendants'], $category['descendants']);
            $this->assertEquals($categoriesFixtures[$index]['tepath'], $category['tepath']);
            $this->assertEquals($categoriesFixtures[$index]['categpath'], $category['categpath']);
            $this->assertEquals($categoriesFixtures[$index]['relativePathString'], $category['relativePathString']);
        }
    }

    public function testGetCategoryByParentId(): void
    {
        $categoriesFixtures = require 'fixtures/categories_fixtures.php';

        $response = $this->makeRequest('GET', '/categories', ['parentId' => $categoriesFixtures[3]['categId']]);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getBody(), true);
        $this->assertNotEmpty($responseData);

        $this->assertEquals($categoriesFixture[3]['name'], $responseData['name']);
        $this->assertEquals($categoriesFixture[3]['description'], $responseData['description']);
        $this->assertEquals($categoriesFixture[3]['parentId'], $responseData['parentId']);
        $this->assertEquals($categoriesFixture[3]['rootId'], $responseData['rootId']);
        $this->assertEquals($categoriesFixture[3]['hits'], $responseData['hits']);
        $this->assertEquals($categoriesFixture[3]['tplGroupContainerId'], $responseData['tplGroupContainerId']);
        $this->assertEquals($categoriesFixture[3]['tplGroupPattern'], $responseData['tplGroupPattern']);
        $this->assertEquals($categoriesFixture[3]['num_roles'], $responseData['num_roles']);
        $this->assertEquals($categoriesFixture[3]['objects'], $responseData['objects']);
        $this->assertEquals($categoriesFixture[3]['children'], $responseData['children']);
        $this->assertEquals($categoriesFixture[3]['descendants'], $responseData['descendants']);
        $this->assertEquals($categoriesFixture[3]['tepath'], $responseData['tepath']);
        $this->assertEquals($categoriesFixture[3]['categpath'], $responseData['categpath']);
        $this->assertEquals($categoriesFixture[3]['relativePathString'], $responseData['relativePathString']);
    }

    public function testUpdateCategory(): void
    {
        $response = $this->makeRequest('PUT', '/categories/1', [
            'name' => 'Test Category Updated',
            'description' => 'This is a test category updated',
            'parentId' => 1
        ]);
        $responseData = json_decode($response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertNotEmpty($responseData);

        $this->assertNotEmpty($responseData);
    }

    public function testDeleteCategory(): void
    {
        $response = $this->makeRequest('DELETE', '/categories/1');
        $responseData = json_decode($response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertNotEmpty($responseData);
    }

    // Helper function to assert category response keys
    private function assertCategoryResponseHasKeys(array $responseData): void
    {
        $expectedKeys = [
            'categId',
            'name',
            'description',
            'parentId',
            'rootId',
            'hits',
            'tplGroupContainerId',
            'tplGroupPattern',
            'num_roles',
            'objects',
            'children',
            'descendants',
            'tepath',
            'categpath',
            'relativePathString'
        ];

        foreach ($responseData as $category) {
            foreach ($expectedKeys as $key) {
                $this->assertArrayHasKey($key, $category, "Key $key not found in response data for category {$category['name']}");
            }
        }
    }
}
