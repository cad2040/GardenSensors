<?php
namespace GardenSensors\Tests\Controllers;

use GardenSensors\Tests\TestCase;
use GardenSensors\Controllers\ApiController;

class ApiControllerTest extends TestCase
{
    private $apiController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiController = new ApiController();
    }

    public function testApiControllerInitialization()
    {
        $this->assertInstanceOf(ApiController::class, $this->apiController);
    }

    public function testGetSensorsEndpoint()
    {
        // Mock the response
        $response = $this->apiController->getSensors();
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
    }

    public function testGetReadingsEndpoint()
    {
        // Mock the response
        $response = $this->apiController->getReadings();
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
    }

    public function testGetPlantsEndpoint()
    {
        // Mock the response
        $response = $this->apiController->getPlants();
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
    }

    public function testInvalidEndpoint()
    {
        // Test invalid endpoint handling
        $response = $this->apiController->handleRequest('invalid_endpoint');
        
        $this->assertIsArray($response);
        $this->assertEquals('error', $response['status']);
    }
} 