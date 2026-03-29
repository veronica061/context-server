<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Stubs\AIServiceStub;

class AIServiceStubTest extends TestCase
{
    public function test_stub_returns_basic_response()
    {
        $request = ['player_post' => 'Тестовое сообщение'];
        $response = AIServiceStub::getMockResponse($request);
        
        $this->assertArrayHasKey('response_to_player', $response);
        $this->assertArrayHasKey('characters', $response);
        $this->assertArrayHasKey('plotpoints', $response);
    }

    public function test_stub_responds_to_hello_message()
    {
        $request = ['player_post' => 'Привет'];
        $response = AIServiceStub::getMockResponse($request);
        
        $this->assertStringContainsString('Приветствую', $response['response_to_player']);
    }

    public function test_stub_updates_character_attitude_on_thanks()
    {
        $request = [
            'player_post' => 'Спасибо за помощь!',
            'characters' => [
                ['id' => 1, 'name' => 'Алиса', 'attitude' => 'нейтральное', 'memory' => '']
            ]
        ];
        
        $response = AIServiceStub::getMockResponse($request);
        
        $this->assertEquals('благодарное', $response['characters'][0]['attitude']);
    }
}
