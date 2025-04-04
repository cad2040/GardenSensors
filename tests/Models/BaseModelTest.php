<?php
namespace Tests\Models;

use PHPUnit\Framework\TestCase;
use App\Models\BaseModel;

// Create a test model class that extends BaseModel
class TestModel extends BaseModel {
    protected static $table = 'test_models';
    protected static $primaryKey = 'id';
    protected static $fillable = ['name', 'value'];
}

class BaseModelTest extends TestCase {
    private $model;

    protected function setUp(): void {
        parent::setUp();
        
        // Create test table
        $db = \App\Core\Database::getInstance();
        $db->exec("
            CREATE TABLE test_models (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                value TEXT,
                inserted DATETIME,
                updated DATETIME
            )
        ");
        
        $this->model = new TestModel([
            'name' => 'test',
            'value' => 'value'
        ]);
    }

    protected function tearDown(): void {
        // Drop test table
        $db = \App\Core\Database::getInstance();
        $db->exec("DROP TABLE test_models");
        
        parent::tearDown();
    }

    public function testModelCreation() {
        $this->assertNull($this->model->id);
        $this->assertEquals('test', $this->model->name);
        $this->assertEquals('value', $this->model->value);
    }

    public function testModelSave() {
        $this->model->save();
        
        $this->assertNotNull($this->model->id);
        $this->assertNotNull($this->model->inserted);
        $this->assertNotNull($this->model->updated);
    }

    public function testModelUpdate() {
        $this->model->save();
        
        $this->model->name = 'updated';
        $this->model->save();
        
        $updated = TestModel::find($this->model->id);
        $this->assertEquals('updated', $updated->name);
        $this->assertNotEquals($updated->inserted, $updated->updated);
    }

    public function testModelDelete() {
        $this->model->save();
        $id = $this->model->id;
        
        $this->model->delete();
        
        $deleted = TestModel::find($id);
        $this->assertNull($deleted);
    }

    public function testModelFind() {
        $this->model->save();
        
        $found = TestModel::find($this->model->id);
        $this->assertNotNull($found);
        $this->assertEquals($this->model->id, $found->id);
    }

    public function testModelFindAll() {
        $this->model->save();
        
        $model2 = new TestModel([
            'name' => 'test2',
            'value' => 'value2'
        ]);
        $model2->save();
        
        $all = TestModel::findAll();
        $this->assertCount(2, $all);
    }

    public function testModelWhere() {
        $this->model->save();
        
        $model2 = new TestModel([
            'name' => 'test2',
            'value' => 'value2'
        ]);
        $model2->save();
        
        $results = TestModel::where('name = ?', ['test']);
        $this->assertCount(1, $results);
        $this->assertEquals($this->model->id, $results[0]->id);
    }

    public function testModelFillable() {
        $this->model->non_fillable = 'test';
        $this->model->save();
        
        $found = TestModel::find($this->model->id);
        $this->assertNull($found->non_fillable);
    }

    public function testModelHidden() {
        $this->model->save();
        
        $array = $this->model->toArray();
        $this->assertArrayNotHasKey('inserted', $array);
        $this->assertArrayNotHasKey('updated', $array);
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