<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class CategoriesTest extends TikiTestCase
{
    protected $categories = [];
    protected $db;

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
        $this->cleanDatabaseTables(['tiki_categories']);

        foreach ($this->categories as $index => $category) {
            if ($index > 1) {
                $category['parentId'] = $this->categories[$index - 1]['categId'];
                $category['rootId'] = $this->categories[$index - 1]['categId'];
            }

            $response = $this->makeHttpRequest('POST', '/categories', 'Admins', $category);
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

    public function testCreateCategoryWithInsufficientPermissions(): void
    {
        $category = [
            'name' => 'Test Category Six',
            'description' => 'Description Six',
        ];
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Permission denied');
    
        $response = $this->makeHttpRequest('POST', '/categories', 'Anonymous', $category);
        if ($response->getStatusCode() === 403) {
            throw new \RuntimeException('Failed to make request: ' . $response->getBody());
        }
    }

    public function testGetCategoriesWithInsufficientPermissions(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Permission denied');
    
        $response = $this->makeHttpRequest('GET', '/categories', 'Anonymous', ['type' => 'all']);
        if ($response->getStatusCode() === 403) {
            throw new \RuntimeException('Failed to make request: ' . $response->getBody());
        }
    } 
    

    /* Fix missing name parameter issue for the endpoint /categories
    public function testCreateCategoryWithMissingName(): void
    {
        $category = [
            'description' => 'Description Six',
        ];
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing required parameter: name');
    
        $response = $this->makeHttpRequest('POST', '/categories', 'Admins', $category);
        if ($response->getStatusCode() === 403) {
            throw new \RuntimeException('Failed to make request: ' . $response->getBody());
        }
    }
    */

    public function testCreateCategoryWithDuplicateName(): void
    {
        $duplicateCategory = $this->categories[0];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A category named '.$duplicateCategory['name'].' already exists in Top.');
    
        $response = $this->makeHttpRequest('POST', '/categories', 'Admins', $duplicateCategory);
        if ($response->getStatusCode() === 403) {
            throw new \RuntimeException('Failed to make request: ' . $response->getBody());
        }
    }

    public function testGetAllCategoriesSuccess(): void
    {
        $response = $this->makeHttpRequest('GET', '/categories', 'Admins', ['type' => 'all']);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getBody(), true);
        $this->assertNotEmpty($responseData);
        $this->assertIsArray($responseData);
    }

    public function testGetAllCategoriesCount(): void
    {
        $response = $this->makeHttpRequest('GET', '/categories', 'Admins', ['type' => 'all']);
        $responseData = json_decode($response->getBody(), true);

        $this->assertEquals(count($this->categories), count($responseData));
    }

    public function testGetAllCategoriesAttributes(): void
    {
        $response = $this->makeHttpRequest('GET', '/categories', 'Admins', ['type' => 'all']);
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
        $response = $this->makeHttpRequest('GET', '/categories', 'Admins', ['type' => 'roots']);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getBody(), true);
        $this->assertNotEmpty($responseData);
        $this->assertIsArray($responseData);
    }

    public function testGetRootsCategoriesCount(): void
    {
        $response = $this->makeHttpRequest('GET', '/categories', 'Admins', ['type' => 'roots']);
        $responseData = json_decode($response->getBody(), true);

        $this->assertEquals(2, count($responseData));
    }

    public function testGetRootsCategoriesAttributes(): void
    {
        $response = $this->makeHttpRequest('GET', '/categories', 'Admins', ['type' => 'roots']);
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
        $categoryFixtureTwo = $categoriesFixtures[2];
        $categoryFixtureThree = $categoriesFixtures[3];

        $response = $this->makeHttpRequest('GET', '/categories', 'Admins', ['parentId' => $categoryFixtureTwo['categId']]);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getBody(), true);
        $this->assertNotEmpty($responseData);

        $responseDataCategory = $responseData[3];
        $this->assertEquals($categoryFixtureThree['name'], $responseDataCategory['name']);
        $this->assertEquals($categoryFixtureThree['description'], $responseDataCategory['description']);
        $this->assertEquals($categoryFixtureThree['parentId'], $responseDataCategory['parentId']);
        $this->assertEquals($categoryFixtureThree['rootId'], $responseDataCategory['rootId']);
        $this->assertEquals($categoryFixtureThree['hits'], $responseDataCategory['hits']);
        $this->assertEquals($categoryFixtureThree['tplGroupContainerId'], $responseDataCategory['tplGroupContainerId']);
        $this->assertEquals($categoryFixtureThree['tplGroupPattern'], $responseDataCategory['tplGroupPattern']);
        $this->assertEquals($categoryFixtureThree['num_roles'], $responseDataCategory['num_roles']);
        $this->assertEquals($categoryFixtureThree['objects'], $responseDataCategory['objects']);
        $this->assertEquals($categoryFixtureThree['children'], $responseDataCategory['children']);
        $this->assertEquals($categoryFixtureThree['descendants'], $responseDataCategory['descendants']);
        $this->assertEquals($categoryFixtureThree['tepath'], $responseDataCategory['tepath']);
        $this->assertEquals($categoryFixtureThree['categpath'], $responseDataCategory['categpath']);
        $this->assertEquals($responseDataCategory['name'], $responseDataCategory['relativePathString']);
    }

    public function testUpdateCategory(): void
    {
        $categoriesFixtures = require 'fixtures/categories_fixtures.php';
        $categoryFixtureOne = $categoriesFixtures[1];
        $categoryFixtureFive = $categoriesFixtures[5];
        $updateData = [
            'name' => 'Updated Category Name',
            'description' => 'Updated Description',
            'parentId' => $categoryFixtureOne['categId'],
            'rootId' => $categoryFixtureOne['categId'],
            'tplGroupContainerId' => 1,
            'tplGroupPattern' => 'testPattern',
            'parentPerms' => true
        ];
        $tepath = [
            '1' => $categoryFixtureOne['name'],
            '5' => 'Updated Category Name',
        ];
        $categpath = $relativePathString = $categoryFixtureOne['name'].'::'.$updateData['name'];

        $response = $this->makeHttpRequest('POST', '/categories/' . $categoryFixtureFive['categId'], 'Admins', $updateData, 'application/x-www-form-urlencoded');

        $responseData = json_decode($response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertNotEmpty($responseData);

        $this->assertEquals(5, $responseData['categId']);
        $this->assertEquals($updateData['name'], $responseData['name']);
        $this->assertEquals($updateData['description'], $responseData['description']);
        $this->assertEquals($categoryFixtureOne['categId'], $responseData['parentId']);
        $this->assertEquals($categoryFixtureOne['categId'], $responseData['rootId']);
        $this->assertEquals(0, $responseData['hits']);
        $this->assertEquals($updateData['tplGroupContainerId'], $responseData['tplGroupContainerId']);
        $this->assertEquals($updateData['tplGroupPattern'], $responseData['tplGroupPattern']);
        $this->assertEquals(0, $responseData['num_roles']);
        $this->assertEquals(0, $responseData['objects']);
        $this->assertEquals([], $responseData['children']);
        $this->assertEquals([], $responseData['descendants']);
        $this->assertEquals($tepath, $responseData['tepath']);
        $this->assertEquals($categpath, $responseData['categpath']);
        $this->assertEquals($relativePathString, $responseData['relativePathString']);        
    }

    public function testUpdateCategoryWithInsufficientPermissions(): void
    {
        $categoriesFixtures = require 'fixtures/categories_fixtures.php';
        $categoryFixtureOne = $categoriesFixtures[1];
        $categoryFixtureFive = $categoriesFixtures[5];
        $updateData = [
            'name' => 'Updated Category Name',
            'description' => 'Updated Description',
            'parentId' => $categoryFixtureOne['categId'],
            'rootId' => $categoryFixtureOne['categId'],
            'tplGroupContainerId' => 1,
            'tplGroupPattern' => 'testPattern',
            'parentPerms' => true
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Permission denied');
    
        $response = $this->makeHttpRequest('POST', '/categories/' . $categoryFixtureFive['categId'], 'Anonymous', $updateData, 'application/x-www-form-urlencoded');
        if ($response->getStatusCode() === 403) {
            throw new \RuntimeException('Failed to make request: ' . $response->getBody());
        }
    }

    public function testUpdateCategoryWithWrongCategId(): void
    {
        $categoriesFixtures = require 'fixtures/categories_fixtures.php';
        $categoryFixtureOne = $categoriesFixtures[1];
        $updateData = [
            'name' => 'Updated Category Name',
            'description' => 'Updated Description',
            'parentId' => $categoryFixtureOne['categId'],
            'rootId' => $categoryFixtureOne['categId'],
            'tplGroupContainerId' => 1,
            'tplGroupPattern' => 'testPattern',
            'parentPerms' => true
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not found');
    
        $response = $this->makeHttpRequest('POST', '/categories/999', 'Admins', $updateData, 'application/x-www-form-urlencoded');
        if ($response->getStatusCode() === 403) {
            throw new \RuntimeException('Failed to make request: ' . $response->getBody());
        }
    }

    public function testDeleteCategoryWithInsufficientPermissions(): void
    {
        $categoriesFixtures = require 'fixtures/categories_fixtures.php';
        $categoryFixtureFive = $categoriesFixtures[5];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Permission denied');
    
        $response = $this->makeHttpRequest('DELETE', '/categories/' . $categoryFixtureFive['categId'], 'Anonymous');
        if ($response->getStatusCode() === 403) {
            throw new \RuntimeException('Failed to make request: ' . $response->getBody());
        }
    }

    public function testDeleteCategoryWithWrongCategId(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not found');
    
        $response = $this->makeHttpRequest('DELETE', '/categories/999', 'Admins');
        if ($response->getStatusCode() === 403) {
            throw new \RuntimeException('Failed to make request: ' . $response->getBody());
        }
    }

    public function testDeleteCategory(): void
    {
        $categoriesFixtures = require 'fixtures/categories_fixtures.php';
        $categoryFixtureFive = $categoriesFixtures[5];
        $categoryFixtureFive['name'] = 'Updated Category Name';
        $categoryFixtureFive['description'] = 'Updated Description';
        $categoryFixtureFive['parentId'] = 1;
        $categoryFixtureFive['rootId'] = 1;
        $categoryFixtureFive['tplGroupContainerId'] = 1;
        $categoryFixtureFive['tplGroupPattern'] = 'testPattern';
        $categoryFixtureFive['num_roles'] = 0;
        $categoryFixtureFive['objects'] = 0;
        $categoryFixtureFive['children'] = [];
        $categoryFixtureFive['descendants'] = [];
        $categoryFixtureFive['tepath'] = [
            '1' => 'Test Category One',
            '5' => 'Updated Category Name',
        ];
        $categoryFixtureFive['categpath'] = 'Test Category One::Updated Category Name';
        $categoryFixtureFive['relativePathString'] = 'Test Category One::Updated Category Name';

        $response = $this->makeHttpRequest('DELETE', '/categories/' . $categoryFixtureFive['categId'], 'Admins');
        $responseData = json_decode($response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertNotEmpty($responseData);

        $this->assertEquals($categoryFixtureFive['categId'], $responseData['categId']);
        $this->assertEquals($categoryFixtureFive['name'], $responseData['name']);
        $this->assertEquals($categoryFixtureFive['description'], $responseData['description']);
        $this->assertEquals($categoryFixtureFive['parentId'], $responseData['parentId']);
        $this->assertEquals($categoryFixtureFive['rootId'], $responseData['rootId']);
        $this->assertEquals($categoryFixtureFive['hits'], $responseData['hits']);
        $this->assertEquals($categoryFixtureFive['tplGroupContainerId'], $responseData['tplGroupContainerId']);
        $this->assertEquals($categoryFixtureFive['tplGroupPattern'], $responseData['tplGroupPattern']);
        $this->assertEquals($categoryFixtureFive['num_roles'], $responseData['num_roles']);
        $this->assertEquals($categoryFixtureFive['objects'], $responseData['objects']);
        $this->assertEquals($categoryFixtureFive['children'], $responseData['children']);
        $this->assertEquals($categoryFixtureFive['descendants'], $responseData['descendants']);
        $this->assertEquals($categoryFixtureFive['tepath'], $responseData['tepath']);
        $this->assertEquals($categoryFixtureFive['categpath'], $responseData['categpath']);
        $this->assertEquals($categoryFixtureFive['relativePathString'], $responseData['relativePathString']);
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
