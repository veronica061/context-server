<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\GlobalPlot;
use App\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CharacterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_character()
    {
        $plot = GlobalPlot::create([
            'title' => 'Тестовый сюжет',
            'status' => 'active'
        ]);

        $character = Character::create([
            'plot_id' => $plot->plot_id,
            'name' => 'Алиса',
            'attitude_to_player' => 'нейтральное',
            'description' => 'Мудрая волшебница',
            'status' => 'active',
            'memory' => 'Только что появилась',
            'role' => 'персонаж'
        ]);

        $this->assertDatabaseHas('characters', [
            'character_id' => $character->character_id,
            'name' => 'Алиса',
            'attitude_to_player' => 'нейтральное'
        ]);
    }

    /** @test */
    public function it_can_update_character_attitude()
    {
        $plot = GlobalPlot::create([
            'title' => 'Тестовый сюжет',
            'status' => 'active'
        ]);

        $character = Character::create([
            'plot_id' => $plot->plot_id,
            'name' => 'Алиса',
            'attitude_to_player' => 'нейтральное',
            'description' => 'Описание',
            'status' => 'active',
            'role' => 'персонаж'
        ]);

        $character->attitude_to_player = 'дружелюбное';
        $character->save();

        $this->assertDatabaseHas('characters', [
            'character_id' => $character->character_id,
            'attitude_to_player' => 'дружелюбное'
        ]);
    }

    /** @test */
    public function it_can_update_character_memory()
    {
        $plot = GlobalPlot::create([
            'title' => 'Тестовый сюжет',
            'status' => 'active'
        ]);

        $character = Character::create([
            'plot_id' => $plot->plot_id,
            'name' => 'Алиса',
            'attitude_to_player' => 'нейтральное',
            'description' => 'Описание',
            'status' => 'active',
            'memory' => null,
            'role' => 'персонаж'
        ]);

        $character->memory = 'Встретила игрока в лесу';
        $character->save();

        $this->assertDatabaseHas('characters', [
            'character_id' => $character->character_id,
            'memory' => 'Встретила игрока в лесу'
        ]);
    }

    /** @test */
    public function it_can_soft_delete_character()
    {
        $plot = GlobalPlot::create([
            'title' => 'Тестовый сюжет',
            'status' => 'active'
        ]);

        $character = Character::create([
            'plot_id' => $plot->plot_id,
            'name' => 'Алиса',
            'attitude_to_player' => 'нейтральное',
            'description' => 'Описание',
            'status' => 'active',
            'role' => 'персонаж'
        ]);

        $character->status = 'inactive';
        $character->save();

        $this->assertDatabaseHas('characters', [
            'character_id' => $character->character_id,
            'status' => 'inactive'
        ]);
    }
}