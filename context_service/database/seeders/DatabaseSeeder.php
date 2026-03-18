<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $plotId = DB::table('global_plots')->insertGetId([
            'title' => 'Основной сюжет',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('characters')->insert([
            'plot_id' => $plotId,
            'name' => 'Игрок',
            'attitude_to_player' => 'нейтральное',
            'description' => 'Главный герой',
            'status' => 'active',
            'memory' => null,
            'role' => 'игрок',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('characters')->insert([
            'plot_id' => $plotId,
            'name' => 'Помощник',
            'attitude_to_player' => 'дружелюбное',
            'description' => 'Первый персонаж',
            'status' => 'active',
            'memory' => 'Только что появился',
            'role' => 'персонаж',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $chapterId = DB::table('chapters')->insertGetId([
            'plot_id' => $plotId,
            'chapter_number' => 1,
            'chapter_text' => 'Начало приключения. Игрок встречает помощника.',
            'chapter_summary' => null,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        for ($i = 1; $i <= 5; $i++) {
            DB::table('plotpoints')->insert([
                'chapter_id' => $chapterId,
                'point_number' => $i,
                'plot_text' => "Сюжетная точка $i: основное событие",
                'ai_post' => null,
                'player_post' => null,
                'result' => null,
                'status' => $i == 1 ? 'active' : 'inactive',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
