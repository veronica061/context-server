<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\GlobalPlot;
use App\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GlobalPlotTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_plot()
    {
        $plot = GlobalPlot::create([
            'title' => 'Тестовый сюжет',
            'status' => 'active'
        ]);

        $this->assertDatabaseHas('global_plots', [
            'plot_id' => $plot->plot_id,
            'title' => 'Тестовый сюжет',
            'status' => 'active'
        ]);
    }

    /** @test */
    public function it_can_update_plot_status()
    {
        $plot = GlobalPlot::create([
            'title' => 'Тестовый сюжет',
            'status' => 'inactive'
        ]);

        $plot->status = 'active';
        $plot->save();

        $this->assertDatabaseHas('global_plots', [
            'plot_id' => $plot->plot_id,
            'status' => 'active'
        ]);
    }

    /** @test */
    public function it_can_delete_a_plot()
    {
        $plot = GlobalPlot::create([
            'title' => 'Тестовый сюжет',
            'status' => 'active'
        ]);

        $plot->delete();

        $this->assertDatabaseMissing('global_plots', [
            'plot_id' => $plot->plot_id
        ]);
    }

    /** @test */
    public function it_has_many_characters()
    {
        $plot = GlobalPlot::create([
            'title' => 'Тестовый сюжет',
            'status' => 'active'
        ]);

        Character::create([
            'plot_id' => $plot->plot_id,
            'name' => 'Персонаж 1',
            'attitude_to_player' => 'нейтральное',
            'description' => 'Описание 1',
            'status' => 'active',
            'role' => 'персонаж'
        ]);

        Character::create([
            'plot_id' => $plot->plot_id,
            'name' => 'Персонаж 2',
            'attitude_to_player' => 'дружелюбное',
            'description' => 'Описание 2',
            'status' => 'active',
            'role' => 'персонаж'
        ]);

        $this->assertEquals(2, $plot->characters->count());
    }
}