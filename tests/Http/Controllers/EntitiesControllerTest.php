<?php

namespace Inovector\Mixpost\Tests\Http\Controllers;

use Inovector\Mixpost\Models\Entity;
use Inovector\Mixpost\Models\Account;
use Inovector\Mixpost\Tests\TestCase;

class EntitiesControllerTest extends TestCase
{
    /** @test */
    public function it_can_list_entities()
    {
        Entity::create(['name' => 'Test Entity', 'hex_color' => '#ff0000']);
        
        $response = $this->getJson(route('mixpost.entities.index'));
        
        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_create_entity()
    {
        $response = $this->postJson(route('mixpost.entities.store'), [
            'name' => 'Mighty House Inc',
            'hex_color' => '#6366f1',
        ]);
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('mixpost_entities', [
            'name' => 'Mighty House Inc',
            'hex_color' => '#6366f1',
        ]);
    }

    /** @test */
    public function it_can_update_entity()
    {
        $entity = Entity::create(['name' => 'Old Name', 'hex_color' => '#000000']);
        
        $response = $this->putJson(route('mixpost.entities.update', $entity), [
            'name' => 'New Name',
            'hex_color' => '#ffffff',
        ]);
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('mixpost_entities', [
            'id' => $entity->id,
            'name' => 'New Name',
            'hex_color' => '#ffffff',
        ]);
    }

    /** @test */
    public function it_can_delete_entity()
    {
        $entity = Entity::create(['name' => 'To Delete', 'hex_color' => '#ff0000']);
        
        $response = $this->deleteJson(route('mixpost.entities.destroy', $entity));
        
        $response->assertRedirect();
        
        $this->assertDatabaseMissing('mixpost_entities', [
            'id' => $entity->id,
        ]);
    }

    /** @test */
    public function it_can_assign_entity_to_account()
    {
        $entity = Entity::create(['name' => 'DSAIC', 'hex_color' => '#00ff00']);
        
        $account = Account::factory()->create();
        
        $response = $this->putJson(route('mixpost.accounts.update', $account->uuid), [
            'entity_id' => $entity->id,
        ]);
        
        $response->assertRedirect();
        
        $this->assertEquals($entity->id, $account->fresh()->entity_id);
    }

    /** @test */
    public function it_nullifies_account_entity_when_entity_deleted()
    {
        $entity = Entity::create(['name' => 'Computer Store', 'hex_color' => '#0000ff']);
        
        $account = Account::factory()->create(['entity_id' => $entity->id]);
        
        $this->assertEquals($entity->id, $account->entity_id);
        
        $entity->delete();
        
        $this->assertNull($account->fresh()->entity_id);
    }

    /** @test */
    public function entity_has_accounts_relationship()
    {
        $entity = Entity::create(['name' => 'Test', 'hex_color' => '#123456']);
        
        $account1 = Account::factory()->create(['entity_id' => $entity->id]);
        $account2 = Account::factory()->create(['entity_id' => $entity->id]);
        
        $this->assertCount(2, $entity->accounts);
        $this->assertTrue($entity->accounts->contains($account1));
        $this->assertTrue($entity->accounts->contains($account2));
    }

    /** @test */
    public function account_belongs_to_entity()
    {
        $entity = Entity::create(['name' => 'MHI', 'hex_color' => '#abcdef']);
        
        $account = Account::factory()->create(['entity_id' => $entity->id]);
        
        $this->assertNotNull($account->entity);
        $this->assertEquals($entity->id, $account->entity->id);
        $this->assertEquals('MHI', $account->entity->name);
    }
}
