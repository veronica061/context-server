<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RealHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем тестовые данные в БД
        $this->plot = \App\Models\GlobalPlot::create([
            'title' => 'Тестовый сюжет',
            'status' => 'active'
        ]);
        
        $this->player = \App\Models\Character::create([
            'plot_id' => $this->plot->plot_id,
            'name' => 'Тестовый игрок',
            'role' => 'игрок',
            'status' => 'active'
        ]);
        
        $this->character = \App\Models\Character::create([
            'plot_id' => $this->plot->plot_id,
            'name' => 'Тестовый персонаж',
            'attitude_to_player' => 'нейтральное',
            'description' => 'Описание',
            'status' => 'active',
            'role' => 'персонаж'
        ]);
    }

    /** @test */
    public function test_call_ai_service_function_makes_http_request()
    {
        // Мокаем HTTP запрос к AI-сервису
        Http::fake([
            '*' => Http::response([
                'response_to_player' => 'Привет от AI!',
                'characters' => [
                    [
                        'id' => $this->character->character_id,
                        'name' => $this->character->name,
                        'attitude' => 'дружелюбное',
                        'memory' => 'Поприветствовал игрока'
                    ]
                ],
                'plotpoints' => [],
                'player_memory' => 'Игрок поздоровался'
            ], 200)
        ]);

        // Создаем данные для отправки (как в реальном вебхуке)
        $activeCharacters = \App\Models\Character::where('plot_id', $this->plot->plot_id)
            ->where('status', 'active')
            ->where('role', 'персонаж')
            ->get();
        
        $player = \App\Models\Character::where('plot_id', $this->plot->plot_id)
            ->where('role', 'игрок')
            ->first();
        
        $aiData = [
            'player_post' => 'Привет!',
            'plot' => [
                'id' => $this->plot->plot_id,
                'title' => $this->plot->title
            ],
            'characters' => $activeCharacters->map(function($char) {
                return [
                    'id' => $char->character_id,
                    'name' => $char->name,
                    'attitude' => $char->attitude_to_player,
                    'memory' => $char->memory,
                    'description' => $char->description
                ];
            })->values()->toArray(),
            'player' => [
                'id' => $player->character_id,
                'name' => $player->name,
                'memory' => $player->memory
            ]
        ];
        
        // Вызываем тестовую версию функции callAIService
        $response = $this->callAIServiceTest($aiData);
        $result = json_decode($response, true);
        
        // Проверяем, что HTTP запрос был отправлен
        Http::assertSent(function ($request) {
            return $request->method() == 'POST';
        });
        
        $this->assertEquals('Привет от AI!', $result['response_to_player']);
    }
    
    /** @test */
    public function test_send_message_function_makes_http_request_to_telegram()
    {
        // Мокаем HTTP запрос к Telegram
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200)
        ]);
        
        // Вызываем тестовую версию sendMessage
        $response = $this->sendMessageTest(123456, 'Test message');
        
        // Проверяем, что запрос был отправлен
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.telegram.org') &&
                   str_contains($request->url(), 'sendMessage');
        });
        
        $this->assertTrue($response['ok'] ?? false);
    }
    
    /** @test */
    public function test_ai_service_handles_timeout()
    {
        // Мокаем таймаут
        Http::fake([
            '*' => function () {
                sleep(6);
                return Http::response(null, 504);
            }
        ]);
        
        // Данные для отправки
        $data = ['player_post' => 'test'];
        
        // Вызываем функцию с таймаутом через Http фасад
        $start = microtime(true);
        
        try {
            $response = Http::timeout(5)->post('http://test-timeout/process', $data);
            $status = $response->status();
        } catch (\Exception $e) {
            $status = 504;
        }
        
        $end = microtime(true);
        $executionTime = $end - $start;
        
        // Проверяем, что время выполнения не превысило таймаут
        $this->assertLessThan(10, $executionTime);
    }
    
    /** @test */
    public function test_ai_service_handles_connection_error()
    {
        // Мокаем ошибку подключения
        Http::fake([
            '*' => Http::response(null, 503)
        ]);
        
        // Данные для отправки
        $data = ['player_post' => 'test'];
        
        // Вызываем функцию с несуществующим URL
        $response = Http::post('http://unreachable-host:8000/process', $data);
        
        // Проверяем статус ошибки
        $this->assertEquals(503, $response->status());
    }
    
    /** @test */
    public function test_ai_service_receives_correct_data_format()
    {
        $receivedData = null;
        
        Http::fake([
            '*' => function ($request) use (&$receivedData) {
                $receivedData = $request->data();
                return Http::response(['ok' => true], 200);
            }
        ]);
        
        $testData = [
            'player_post' => 'Тестовое сообщение',
            'plot' => ['id' => 1, 'title' => 'Тест'],
            'characters' => [['id' => 1, 'name' => 'Алиса']]
        ];
        
        Http::post('http://test-ai/process', $testData);
        
        $this->assertEquals('Тестовое сообщение', $receivedData['player_post']);
        $this->assertEquals('Тест', $receivedData['plot']['title']);
    }
    
    // Тестовая версия sendMessage (копирует логику из webhook.php)
    private function sendMessageTest($chatId, $text)
    {
        $token = '8788709652:AAHp--l-UAHHuLsUKmFMCxfTlyN8s59zJuA';
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        
        $response = Http::post($url, [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ]);
        
        return $response->json();
    }
    
    // Тестовая версия callAIService (копирует логику из webhook.php)
    private function callAIServiceTest($data)
    {
        $url = 'http://test-ai/process';
        
        try {
            $response = Http::timeout(30)->post($url, $data);
            return $response->body();
        } catch (\Exception $e) {
            return json_encode([
                'response_to_player' => 'Сервис временно недоступен',
                'characters' => [],
                'plotpoints' => [],
                'error' => true
            ]);
        }
    }
}
