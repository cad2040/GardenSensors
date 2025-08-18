<?php
namespace GardenSensors\Tests\Models;

use GardenSensors\Tests\TestCase;
use GardenSensors\Models\BaseModel;

// Create a test model class that extends BaseModel
class TestModel extends BaseModel {
    protected $table = 'test_models';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'value'];
    protected $hidden = ['created_at', 'updated_at'];
    
    // Add property declarations
    protected $id;
    protected $name;
    protected $value;
    protected $created_at;
    protected $updated_at;
    
    public function jsonSerialize(): mixed {
        return $this->toArray();
    }
    
    public function fill(array $attributes) {
        parent::fill($attributes);
        
        // Set properties from attributes
        if (isset($attributes['id'])) $this->id = $attributes['id'];
        if (isset($attributes['name'])) $this->name = $attributes['name'];
        if (isset($attributes['value'])) $this->value = $attributes['value'];
        if (isset($attributes['created_at'])) $this->created_at = $attributes['created_at'];
        if (isset($attributes['updated_at'])) $this->updated_at = $attributes['updated_at'];
    }
    
    public function save(): bool {
        $result = parent::save();
        
        // After save, update properties from attributes
        if (isset($this->attributes['id'])) $this->id = $this->attributes['id'];
        if (isset($this->attributes['name'])) $this->name = $this->attributes['name'];
        if (isset($this->attributes['value'])) $this->value = $this->attributes['value'];
        if (isset($this->attributes['created_at'])) $this->created_at = $this->attributes['created_at'];
        if (isset($this->attributes['updated_at'])) $this->updated_at = $this->attributes['updated_at'];
        
        return $result;
    }
    
    public function getId(): ?int {
        return $this->id;
    }
    
    public function getName(): ?string {
        return $this->name;
    }
    
    public function getValue(): ?string {
        return $this->value;
    }
    
    public function getCreatedAt(): ?string {
        return $this->created_at;
    }
    
    public function getUpdatedAt(): ?string {
        return $this->updated_at;
    }
}

class BaseModelTest extends TestCase {
    private $model;

    protected function setUp(): void {
        parent::setUp();
        
        // Create test table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS test_models (
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
        $this->db->exec("DROP TABLE IF EXISTS test_models");
        
        parent::tearDown();
    }

    public function testModelCreation() {
        $this->assertNull($this->model->getId());
        $this->assertEquals('test', $this->model->getName());
        $this->assertEquals('value', $this->model->getValue());
    }

    public function testModelSave() {
        $result = $this->model->save();
        
        $this->assertTrue($result);
        $this->assertNotNull($this->model->getId());
        $this->assertNotNull($this->model->getCreatedAt());
        $this->assertNotNull($this->model->getUpdatedAt());
    }

    public function testModelUpdate() {
        $this->model->save();
        
        $this->model->setAttribute('name', 'updated');
        $this->model->save();
        
        $updated = TestModel::find($this->model->getId());
        $this->assertNotNull($updated);
        $this->assertEquals('updated', $updated->getName());
        $this->assertNotEquals($updated->getCreatedAt(), $updated->getUpdatedAt());
    }

    public function testModelDelete() {
        $this->model->save();
        $id = $this->model->getId();
        
        $this->model->delete();
        
        $deleted = TestModel::find($id);
        $this->assertNull($deleted);
    }

    public function testModelFind() {
        $this->model->save();
        
        $found = TestModel::find($this->model->getId());
        $this->assertNotNull($found);
        $this->assertEquals($this->model->getId(), $found->getId());
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
        
        $testModel = new TestModel();
        $results = $testModel->where('name', '=', 'test');
        $this->assertCount(1, $results);
        $this->assertEquals($this->model->getId(), $results[0]->getId());
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