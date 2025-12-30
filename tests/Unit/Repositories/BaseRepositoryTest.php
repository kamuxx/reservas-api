<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use Repositories\BaseRepository;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class BaseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_insert_success(): void
    {
        $data = ['name' => 'test-role', 'uuid' => (string) Str::uuid()];
        $result = BaseRepository::insert(Role::class, $data);

        $this->assertInstanceOf(Role::class, $result);
        $this->assertDatabaseHas('roles', ['name' => 'test-role']);
    }

    public function test_insert_throws_exception_on_invalid_model(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("El modelo debe ser una subclase de Model");

        BaseRepository::insert('NotAModel', []);
    }

    public function test_get_all(): void
    {
        Role::query()->delete();
        Role::create(['name' => 'role1', 'uuid' => (string) Str::uuid()]);
        Role::create(['name' => 'role2', 'uuid' => (string) Str::uuid()]);

        $result = BaseRepository::getAll(Role::class);
        $this->assertCount(2, $result);
    }

    public function test_get_by(): void
    {
        Role::create(['name' => 'role1', 'uuid' => (string) Str::uuid()]);
        
        $result = BaseRepository::getBy(Role::class, ['name' => 'role1']);
        $this->assertCount(1, $result);
    }

    public function test_get_one_by(): void
    {
        $uuid = (string) Str::uuid();
        Role::create(['name' => 'role1', 'uuid' => $uuid]);
        
        $result = BaseRepository::getOneBy(Role::class, ['uuid' => $uuid]);
        $this->assertEquals('role1', $result->name);
    }

    public function test_update(): void
    {
        $role = Role::create(['name' => 'old-name']);
        $uuid = $role->uuid;
        
        // BaseRepository::update uses where($filters)->update($data)
        $result = BaseRepository::update(Role::class, ['uuid' => $uuid], ['name' => 'new-name']);
        $this->assertTrue($result); 
        $this->assertDatabaseHas('roles', ['uuid' => $uuid, 'name' => 'new-name']);
    }
}
