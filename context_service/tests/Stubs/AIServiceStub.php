<?php

namespace Tests\Stubs;

class AIServiceStub
{
    public static function getMockResponse($request)
    {
        $playerPost = $request['player_post'] ?? '';
        
        $response = [
            'response_to_player' => 'Сообщение получено. Сюжет продолжается.',
            'characters' => [],
            'plotpoints' => [],
            'chapter_break' => false
        ];
        
        // Простые проверки
        if ($playerPost === 'Привет') {
            $response['response_to_player'] = 'Приветствую, путник! Рад тебя видеть.';
        }
        
        if ($playerPost === 'Спасибо за помощь!') {
            $response['response_to_player'] = 'Пожалуйста! Рад помочь.';
        }
        
        // Обновляем персонажей для теста
        if (isset($request['characters']) && !empty($request['characters'])) {
            foreach ($request['characters'] as $character) {
                $newAttitude = $character['attitude'];
                
                if ($playerPost === 'Спасибо за помощь!') {
                    $newAttitude = 'благодарное';
                }
                
                $response['characters'][] = [
                    'id' => $character['id'],
                    'name' => $character['name'],
                    'attitude' => $newAttitude,
                    'memory' => "Отреагировал на: " . $playerPost
                ];
            }
        }
        
        return $response;
    }
}
