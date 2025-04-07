<?php
namespace GardenSensors\Tests\Models;

use PHPUnit\Framework\TestCase;
use GardenSensors\Models\BaseModel;

// Create a test model class that extends BaseModel
class TestModel extends BaseModel {
    protected $table = 'test_models';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'value'];
    protected $hidden = ['created_at', 'updated_at'];
    
    public function jsonSerialize(): mixed {
        return $this->toArray();
    }
}

class BaseModelTest extends TestCase {
    private $model;

    protected function setUp(): void {
        parent::setUp();
        
        // Create test table
        $db = \GardenSensors\Core\Database::getInstance();
        $db->exec("
            CREATE TABLE test_models (
                id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB
        ");
        
        $this->model = new TestModel([
            'name' => 'test',
            'value' => 'value'
        ]);
    }

    protected function tearDown(): void {
        // Drop test table
        $db = \GardenSensors\Core\Database::getInstance();
        $db->exec("DROP TABLE test_models");
        
        parent::tearDown();
    }

    public function testModelCreation() {
        $this->assertNull($this->model->getAttribute('id'));
        $this->assertEquals('test', $this->model->getAttribute('name'));
        $this->assertEquals('value', $this->model->getAttribute('value'));
    }

    public function testModelSave() {
        $this->model->save();
        
        $this->assertNotNull($this->model->getAttribute('id'));
        $this->assertNotNull($this->model->getAttribute('created_at'));
        $this->assertNotNull($this->model->getAttribute('updated_at'));
    }

    public function testModelUpdate() {
        $this->model->save();
        
        $this->model->setAttribute('name', 'updated');
        $this->model->save();
        
        $updated = (new TestModel())->find($this->model->getAttribute('id'));
        $this->assertEquals('updated', $updated->getAttribute('name'));
        $this->assertNotEquals($updated->getAttribute('created_at'), $updated->getAttribute('updated_at'));
    }

    public function testModelDelete() {
        $this->model->save();
        $id = $this->model->getAttribute('id');
        
        $this->model->delete();
        
        $deleted = (new TestModel())->find($id);
        $this->assertNull($deleted);
    }

    public function testModelFind() {
        $this->model->save();
        
        $found = (new TestModel())->find($this->model->getAttribute('id'));
        $this->assertNotNull($found);
        $this->assertEquals($this->model->getAttribute('id'), $found->getAttribute('id'));
    }

    public function testModelFindAll() {
        $this->model->save();
        
        $model2 = new TestModel([
            'name' => 'test2',
            'value' => 'value2'
        ]);
        $model2->save();
        
        $all = (new TestModel())->all();
        $this->assertCount(2, $all);
    }

    public function testModelWhere() {
        $this->model->save();
        
        $model2 = new TestModel([
            'name' => 'test2',
            'value' => 'value2'
        ]);
        $model2->save();
        
        $results = (new TestModel())->where('name', '=', 'test');
        $this->assertCount(1, $results);
        $this->assertEquals($this->model->getAttribute('id'), $results[0]->getAttribute('id'));
    }

    public function testModelFillable() {
        $this->model->setAttribute('non_fillable', 'test');
        $this->model->save();
        
        $found = (new TestModel())->find($this->model->getAttribute('id'));
        $this->assertNull($found->getAttribute('non_fillable'));
    }

    public function testModelHidden() {
        $this->model->save();
        
        $array = $this->model->toArray();
        $this->assertArrayNotHasKey('created_at', $array);
        $this->assertArrayNotHasKey('updated_at', $array);
    }

    public function testModelToArray() {
        $this->model->save();
        
        $array = $this->model->toArray();
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('value', $array);
    }

    public function testModelToJson() {
        $this->model->save();
        
        $json = $this->model->toJson();
        $array = json_decode($json, true);
        
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('value', $array);
    }

    public function testModelValidation() {
        $this->expectException(\InvalidArgumentException::class);
        
        new TestModel([
            'name' => ''  // Empty name should fail validation
        ]);
    }
} 