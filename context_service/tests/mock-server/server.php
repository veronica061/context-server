<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input = file_get_contents('php://input');
$request = json_decode($input, true);

$playerPost = strtolower($request['player_post'] ?? '');

$response = [
    'response_to_player' => 'Тестовый ответ от заглушки AI-сервиса!',
    'characters' => [],
    'plotpoints' => [],
    'chapter_break' => false
];

if (str_contains($playerPost, 'привет')) {
    $response['response_to_player'] = 'Привет! Это тестовый AI-сервис.';
}

if (str_contains($playerPost, 'статус')) {
    $response['response_to_player'] = 'Всё работает отлично!';
}

if (isset($request['characters']) && !empty($request['characters'])) {
    foreach ($request['characters'] as $character) {
        $response['characters'][] = [
            'id' => $character['id'],
            'name' => $character['name'],
            'attitude' => 'дружелюбное',
            'memory' => 'Отреагировал на ваше сообщение'
        ];
    }
}

if (isset($request['player'])) {
    $response['player_memory'] = 'Вы взаимодействуете с сюжетом';
}

echo json_encode($response);