<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\GlobalPlot;
use App\Models\Character;
use App\Models\Chapter;
use App\Models\Plotpoint;
use Illuminate\Support\Collection;

define('BOT_TOKEN', '8788709652:AAHp--l-UAHHuLsUKmFMCxfTlyN8s59zJuA');
define('AI_SERVICE_URL', 'http://ai-service:8000/process');

function sendMessage($chatId, $text) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chatId, 
        'text' => $text, 
        'parse_mode' => 'HTML'
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    return file_get_contents($url, false, $context);
}

function callAIService($data) {
    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data, JSON_UNESCAPED_UNICODE)
        ]
    ];
    
    $context = stream_context_create($options);
    return file_get_contents(AI_SERVICE_URL, false, $context);
}

function bot_log($message, $data = null) {
    $logFile = __DIR__ . '/../storage/logs/bot.log';
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data) {
        $log .= "\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    $log .= "\n\n";
    file_put_contents($logFile, $log, FILE_APPEND);
}

$input = file_get_contents('php://input');
$update = json_decode($input, true);

bot_log('Webhook received', $update);

if (isset($update['message'])) {
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $text = trim($message['text'] ?? '');
    $firstName = $message['from']['first_name'] ?? 'User';
    
    $activePlot = GlobalPlot::where('status', 'active')->first();
    
    if (str_starts_with($text, '/')) {
        $parts = explode(' ', $text);
        $command = $parts[0];
        $params = array_slice($parts, 1);
        
        switch ($command) {
            case '/start':
                $response = "Добро пожаловать в систему управления сюжетами, $firstName!\n\n";
                $response .= "<b>Доступные команды:</b>\n\n";
                $response .= "<b>Сюжеты:</b>\n";
                $response .= "/new_plot [название] - создать новый сюжет\n";
                $response .= "/plots - список сюжетов (с ID)\n";
                $response .= "/plot_info [ID] - информация о сюжете\n";
                $response .= "/switch_plot [ID] - переключить сюжет\n";
                $response .= "/delete_plot [ID] - удалить сюжет\n\n";
                
                $response .= "<b>Игрок:</b>\n";
                $response .= "/create_player Имя | Описание - создать игрока в сюжете\n";
                $response .= "   Пример: /create_player Артур | Храбрый рыцарь в поисках приключений\n";
                $response .= "/player - информация о вашем игроке\n";
                $response .= "/edit_player [поле] [значение] - изменить данные игрока\n";
                $response .= "   Поля: name, description, memory\n";
                $response .= "   Пример: /edit_player name Артур Рыцарь\n";
                $response .= "/delete_player - удалить игрока из сюжета\n\n";
                
                $response .= "<b>Персонажи:</b>\n";
                $response .= "/add_character Имя | Описание - добавить персонажа\n";
                $response .= "   Пример: /add_character Алиса Ивановна | Мудрая волшебница из леса\n";
                $response .= "/characters - список персонажей (с ID)\n";
                $response .= "/character_info [ID] - информация о персонаже\n";
                $response .= "/delete_character [ID] - удалить персонажа\n";
                $response .= "/edit_character [ID] [поле] [значение] - изменить персонажа\n";
                $response .= "   Поля: name, attitude, description, memory\n\n";
                
                $response .= "<b>Статус:</b>\n";
                $response .= "/status - текущий статус";
                break;
                
            case '/create_player':
                if (!$activePlot) {
                    $response = "Нет активного сюжета. Сначала создайте сюжет через /new_plot";
                    break;
                }
                
                // Проверяем, есть ли уже игрок в сюжете
                $existingPlayer = Character::where('plot_id', $activePlot->plot_id)
                    ->where('role', 'игрок')
                    ->first();
                
                if ($existingPlayer) {
                    $response = "В этом сюжете уже есть игрок «{$existingPlayer->name}»\n\n";
                    $response .= "Сначала удалите существующего игрока через /delete_player, если хотите создать нового";
                    break;
                }
                
                if (count($params) < 1) {
                    $response = "Неправильный формат. Используйте:\n";
                    $response .= "<code>/create_player Имя | Описание</code>\n\n";
                    $response .= "Пример: /create_player Артур | Храбрый рыцарь в поисках приключений\n\n";
                    $response .= "Имя и описание разделяются вертикальной чертой |";
                    break;
                }
                
                $fullText = implode(' ', $params);
                
                if (!str_contains($fullText, '|')) {
                    $response = "Используйте разделитель | между именем и описанием\n\n";
                    $response .= "Пример: /create_player Артур | Храбрый рыцарь в поисках приключений";
                    break;
                }
                
                $parts = explode('|', $fullText, 2);
                $name = trim($parts[0]);
                $description = trim($parts[1] ?? '');
                
                if (empty($name)) {
                    $response = "Имя игрока не может быть пустым";
                    break;
                }
                
                if (empty($description)) {
                    $response = "Описание игрока не может быть пустым";
                    break;
                }
                
                $player = Character::create([
                    'plot_id' => $activePlot->plot_id,
                    'name' => $name,
                    'attitude_to_player' => 'нейтральное',
                    'description' => $description,
                    'status' => 'active',
                    'memory' => 'Начало приключения',
                    'role' => 'игрок'
                ]);
                
                $response = "Игрок успешно создан!\n\n";
                $response .= "ID: {$player->character_id}\n";
                $response .= "Имя: {$player->name}\n";
                $response .= "Описание: {$player->description}\n\n";
                $response .= "Для просмотра информации: /player\n";
                $response .= "Для изменения: /edit_player [поле] [значение]";
                
                bot_log('Player created', [
                    'plot_id' => $activePlot->plot_id,
                    'player_id' => $player->character_id,
                    'name' => $name
                ]);
                break;
                
            case '/player':
                if (!$activePlot) {
                    $response = "Нет активного сюжета";
                    break;
                }
                
                $player = Character::where('plot_id', $activePlot->plot_id)
                    ->where('role', 'игрок')
                    ->first();
                
                if (!$player) {
                    $response = "В сюжете «{$activePlot->title}» нет игрока.\n\n";
                    $response .= "Создайте игрока через /create_player Имя | Описание";
                } else {
                    $response = "<b>Ваш игрок</b>\n\n";
                    $response .= "ID: {$player->character_id}\n";
                    $response .= "<b>Имя:</b> {$player->name}\n";
                    $response .= "<b>Описание:</b>\n{$player->description}\n\n";
                    $response .= "<b>Память:</b>\n" . ($player->memory ?: '—') . "\n\n";
                    $response .= "Для изменения: /edit_player [поле] [значение]\n";
                    $response .= "Для удаления: /delete_player";
                }
                break;
                
            case '/edit_player':
                if (!$activePlot) {
                    $response = "Нет активного сюжета";
                    break;
                }
                
                $player = Character::where('plot_id', $activePlot->plot_id)
                    ->where('role', 'игрок')
                    ->first();
                
                if (!$player) {
                    $response = "В сюжете нет игрока.\n\n";
                    $response .= "Создайте игрока через /create_player Имя | Описание";
                    break;
                }
                
                if (count($params) < 2) {
                    $response = "Использование: /edit_player [поле] [значение]\n\n";
                    $response .= "Доступные поля: name, description, memory\n\n";
                    $response .= "Примеры:\n";
                    $response .= "/edit_player name Артур Рыцарь\n";
                    $response .= "/edit_player description Храбрый рыцарь, ищущий Святой Грааль\n";
                    $response .= "/edit_player memory Встретил старого друга в таверне";
                    break;
                }
                
                $field = $params[0];
                $value = implode(' ', array_slice($params, 1));
                
                $allowedFields = ['name', 'description', 'memory'];
                if (!in_array($field, $allowedFields)) {
                    $response = "Поле «{$field}» недоступно. Доступные: " . implode(', ', $allowedFields);
                    break;
                }
                
                $oldValue = $player->$field;
                $player->$field = $value;
                $player->save();
                
                $fieldNames = [
                    'name' => 'имя',
                    'description' => 'описание',
                    'memory' => 'память'
                ];
                
                $response = "Игрок «{$player->name}» обновлен\n\n";
                $response .= "Поле: {$fieldNames[$field]}\n";
                $response .= "Было: {$oldValue}\n";
                $response .= "Стало: {$value}";
                break;
                
            case '/delete_player':
                if (!$activePlot) {
                    $response = "Нет активного сюжета";
                    break;
                }
                
                $player = Character::where('plot_id', $activePlot->plot_id)
                    ->where('role', 'игрок')
                    ->first();
                
                if (!$player) {
                    $response = "В сюжете нет игрока";
                    break;
                }
                
                $playerName = $player->name;
                $player->delete();
                
                $response = "Игрок «{$playerName}» удален из сюжета\n\n";
                $response .= "Вы можете создать нового игрока через /create_player";
                break;
                
            case '/status':
                if (!$activePlot) {
                    $response = "Нет активного сюжета";
                    break;
                }
                
                $player = Character::where('plot_id', $activePlot->plot_id)
                    ->where('role', 'игрок')
                    ->first();
                
                $characters = Character::where('plot_id', $activePlot->plot_id)
                    ->where('status', 'active')
                    ->where('role', 'персонаж')
                    ->get();
                
                $response = "<b>Текущий статус</b>\n\n";
                $response .= "<b>Сюжет:</b> ID {$activePlot->plot_id} - {$activePlot->title}\n";
                
                if ($player) {
                    $response .= "<b>Игрок:</b> {$player->name}\n";
                } else {
                    $response .= "<b>Игрок:</b> не создан (создайте через /create_player)\n";
                }
                
                $response .= "<b>Персонажей:</b> {$characters->count()}\n\n";
                
                if ($characters->count() > 0) {
                    $response .= "<b>Отношения персонажей:</b>\n";
                    foreach ($characters as $char) {
                        $response .= "• ID {$char->character_id}: {$char->name} - {$char->attitude_to_player}\n";
                    }
                }
                break;
                
            case '/plots':
                $plots = GlobalPlot::all();
                
                if ($plots->isEmpty()) {
                    $response = "Нет созданных сюжетов";
                } else {
                    $response = "<b>Список сюжетов:</b>\n\n";
                    foreach ($plots as $plot) {
                        $active = $plot->status == 'active' ? ' (активный)' : '';
                        $player = Character::where('plot_id', $plot->plot_id)
                            ->where('role', 'игрок')
                            ->first();
                        $characters = Character::where('plot_id', $plot->plot_id)
                            ->where('status', 'active')
                            ->where('role', 'персонаж')
                            ->count();
                        
                        $response .= "<b>ID {$plot->plot_id}</b>: {$plot->title}{$active}\n";
                        $response .= "   Игрок: " . ($player ? $player->name : 'не создан') . "\n";
                        $response .= "   Персонажей: {$characters}\n\n";
                    }
                    $response .= "Для просмотра: /plot_info [ID]\nДля удаления: /delete_plot [ID]";
                }
                break;
                
            case '/plot_info':
                if (count($params) < 1) {
                    $response = "Использование: /plot_info [ID сюжета]\n\n";
                    $plots = GlobalPlot::all();
                    if ($plots->isNotEmpty()) {
                        $response .= "Доступные сюжеты:\n";
                        foreach ($plots as $plot) {
                            $response .= "ID {$plot->plot_id}: {$plot->title}\n";
                        }
                    }
                    break;
                }
                
                $plotId = (int)$params[0];
                $plot = GlobalPlot::find($plotId);
                
                if (!$plot) {
                    $response = "Сюжет с ID {$plotId} не найден";
                } else {
                    $player = Character::where('plot_id', $plot->plot_id)
                        ->where('role', 'игрок')
                        ->first();
                    $characters = Character::where('plot_id', $plot->plot_id)
                        ->where('status', 'active')
                        ->where('role', 'персонаж')
                        ->count();
                    $active = $plot->status == 'active' ? 'Активный' : 'Неактивный';
                    
                    $response = "<b>{$plot->title}</b>\n\n";
                    $response .= "ID: {$plot->plot_id}\n";
                    $response .= "Статус: {$active}\n";
                    $response .= "Игрок: " . ($player ? $player->name : 'не создан') . "\n";
                    $response .= "Персонажей: {$characters}\n";
                    $response .= "Создан: " . $plot->created_at->format('d.m.Y H:i');
                }
                break;
                
            case '/delete_plot':
                if (count($params) < 1) {
                    $response = "Использование: /delete_plot [ID сюжета]\n\n";
                    $plots = GlobalPlot::all();
                    if ($plots->isNotEmpty()) {
                        $response .= "Доступные сюжеты:\n";
                        foreach ($plots as $plot) {
                            $response .= "ID {$plot->plot_id}: {$plot->title}\n";
                        }
                    }
                    break;
                }
                
                $plotId = (int)$params[0];
                $plot = GlobalPlot::find($plotId);
                
                if (!$plot) {
                    $response = "Сюжет с ID {$plotId} не найден";
                } else {
                    $title = $plot->title;
                    $wasActive = ($plot->status == 'active');
                    $plot->delete();
                    
                    if ($wasActive) {
                        $nextPlot = GlobalPlot::first();
                        if ($nextPlot) {
                            $nextPlot->status = 'active';
                            $nextPlot->save();
                            Character::where('plot_id', $nextPlot->plot_id)->update(['status' => 'active']);
                        }
                    }
                    
                    $response = "Сюжет «{$title}» (ID {$plotId}) удален";
                }
                break;
                
            case '/switch_plot':
                if (count($params) < 1) {
                    $response = "Использование: /switch_plot [ID]\n\n";
                    $plots = GlobalPlot::all();
                    if ($plots->isNotEmpty()) {
                        $response .= "Доступные сюжеты:\n";
                        foreach ($plots as $plot) {
                            $active = $plot->status == 'active' ? ' (активный)' : '';
                            $response .= "ID {$plot->plot_id}: {$plot->title}{$active}\n";
                        }
                    }
                    break;
                }
                
                $plotId = (int)$params[0];
                $newPlot = GlobalPlot::find($plotId);
                
                if (!$newPlot) {
                    $response = "Сюжет с ID {$plotId} не найден";
                    break;
                }
                
                GlobalPlot::query()->update(['status' => 'inactive']);
                $newPlot->status = 'active';
                $newPlot->save();
                
                Character::where('plot_id', $plotId)->update(['status' => 'active']);
                Character::where('plot_id', '!=', $plotId)->update(['status' => 'inactive']);
                
                $response = "Переключено на сюжет: ID {$newPlot->plot_id} - {$newPlot->title}";
                break;
                
            case '/new_plot':
                if (count($params) < 1) {
                    $response = "Использование: /new_plot [название сюжета]";
                    break;
                }
                
                $title = implode(' ', $params);
                
                GlobalPlot::query()->update(['status' => 'inactive']);
                
                $newPlot = GlobalPlot::create([
                    'title' => $title,
                    'status' => 'active'
                ]);
                
                Character::where('plot_id', '!=', $newPlot->plot_id)
                    ->update(['status' => 'inactive']);
                
                // При создании сюжета НЕ создаем игрока автоматически
                // Игрок создается отдельно через /create_player
                
                $response = "Создан новый сюжет!\n\n";
                $response .= "ID: {$newPlot->plot_id}\n";
                $response .= "Название: {$title}\n\n";
                $response .= "Теперь создайте игрока через /create_player Имя | Описание\n";
                $response .= "Затем добавляйте персонажей через /add_character";
                break;
                
            case '/add_character':
                if (!$activePlot) {
                    $response = "Нет активного сюжета. Сначала создайте сюжет через /new_plot";
                    break;
                }
                
                if (count($params) < 1) {
                    $response = "Неправильный формат. Используйте:\n";
                    $response .= "<code>/add_character Имя | Описание</code>\n\n";
                    $response .= "Пример: /add_character Алиса Ивановна | Мудрая волшебница из далекого королевства\n\n";
                    $response .= "Имя и описание разделяются вертикальной чертой |";
                    break;
                }
                
                $fullText = implode(' ', $params);
                
                if (!str_contains($fullText, '|')) {
                    $response = "Используйте разделитель | между именем и описанием\n\n";
                    $response .= "Пример: /add_character Алиса Ивановна | Мудрая волшебница";
                    break;
                }
                
                $parts = explode('|', $fullText, 2);
                $name = trim($parts[0]);
                $description = trim($parts[1] ?? '');
                
                if (empty($name)) {
                    $response = "Имя персонажа не может быть пустым";
                    break;
                }
                
                if (empty($description)) {
                    $response = "Описание персонажа не может быть пустым";
                    break;
                }
                
                $existing = Character::where('plot_id', $activePlot->plot_id)
                    ->where('name', $name)
                    ->first();
                
                if ($existing) {
                    $response = "Персонаж с именем '{$name}' уже существует";
                    break;
                }
                
                $character = Character::create([
                    'plot_id' => $activePlot->plot_id,
                    'name' => $name,
                    'attitude_to_player' => 'нейтральное',
                    'description' => $description,
                    'status' => 'active',
                    'memory' => "Только что появился в сюжете",
                    'role' => 'персонаж'
                ]);
                
                $response = "Персонаж успешно добавлен!\n\n";
                $response .= "ID: {$character->character_id}\n";
                $response .= "Имя: {$character->name}\n";
                $response .= "Отношение: {$character->attitude_to_player}\n";
                $response .= "Описание: {$character->description}\n\n";
                $response .= "Для просмотра всех персонажей: /characters\n";
                $response .= "Для изменения: /edit_character {$character->character_id} [поле] [значение]";
                
                bot_log('Character added', [
                    'plot_id' => $activePlot->plot_id,
                    'character_id' => $character->character_id,
                    'name' => $name
                ]);
                break;
                
            case '/characters':
                if (!$activePlot) {
                    $response = "Нет активного сюжета";
                    break;
                }
                
                $characters = Character::where('plot_id', $activePlot->plot_id)
                    ->where('status', 'active')
                    ->where('role', 'персонаж')
                    ->orderBy('name')
                    ->get();
                
                if ($characters->isEmpty()) {
                    $response = "В сюжете «{$activePlot->title}» нет персонажей.\nДобавьте первого через /add_character";
                } else {
                    $response = "<b>Персонажи сюжета «{$activePlot->title}»:</b>\n\n";
                    foreach ($characters as $char) {
                        $response .= "<b>ID {$char->character_id}</b>: {$char->name}\n";
                        $response .= "   Отношение: {$char->attitude_to_player}\n";
                        $response .= "   Память: " . ($char->memory ? substr($char->memory, 0, 50) . '...' : '—') . "\n\n";
                    }
                    $response .= "Для просмотра: /character_info [ID]\nДля удаления: /delete_character [ID]\nДля изменения: /edit_character [ID] [поле] [значение]";
                }
                break;
                
            case '/character_info':
                if (count($params) < 1) {
                    $response = "Использование: /character_info [ID персонажа]\n\n";
                    if ($activePlot) {
                        $characters = Character::where('plot_id', $activePlot->plot_id)
                            ->where('status', 'active')
                            ->where('role', 'персонаж')
                            ->get();
                        if ($characters->isNotEmpty()) {
                            $response .= "Доступные персонажи:\n";
                            foreach ($characters as $char) {
                                $response .= "ID {$char->character_id}: {$char->name}\n";
                            }
                        }
                    }
                    break;
                }
                
                $characterId = (int)$params[0];
                $character = Character::find($characterId);
                
                if (!$character) {
                    $response = "Персонаж с ID {$characterId} не найден";
                } else {
                    $response = "<b>{$character->name}</b>\n\n";
                    $response .= "ID: {$character->character_id}\n";
                    $response .= "<b>Описание:</b>\n{$character->description}\n\n";
                    $response .= "<b>Отношение к вам:</b> {$character->attitude_to_player}\n\n";
                    $response .= "<b>Память:</b>\n" . ($character->memory ?: '—');
                }
                break;
                
            case '/delete_character':
                if (count($params) < 1) {
                    $response = "Использование: /delete_character [ID персонажа]\n\n";
                    if ($activePlot) {
                        $characters = Character::where('plot_id', $activePlot->plot_id)
                            ->where('status', 'active')
                            ->where('role', 'персонаж')
                            ->get();
                        if ($characters->isNotEmpty()) {
                            $response .= "Доступные персонажи:\n";
                            foreach ($characters as $char) {
                                $response .= "ID {$char->character_id}: {$char->name}\n";
                            }
                        }
                    }
                    break;
                }
                
                $characterId = (int)$params[0];
                $character = Character::find($characterId);
                
                if (!$character) {
                    $response = "Персонаж с ID {$characterId} не найден";
                } else {
                    $name = $character->name;
                    $character->status = 'inactive';
                    $character->save();
                    $response = "Персонаж «{$name}» (ID {$characterId}) удален из сюжета";
                }
                break;
                
            case '/edit_character':
                if (count($params) < 3) {
                    $response = "Использование: /edit_character [ID] [поле] [значение]\n\n";
                    $response .= "Доступные поля: name, attitude, description, memory\n\n";
                    $response .= "Примеры:\n";
                    $response .= "/edit_character 5 name Алиса Ивановна\n";
                    $response .= "/edit_character 5 attitude дружелюбное\n";
                    $response .= "/edit_character 5 memory Помнит, как игрок помог ей в лесу\n\n";
                    $response .= "Для значений из нескольких слов просто пишите всё после поля";
                    break;
                }
                
                $characterId = (int)$params[0];
                $field = $params[1];
                $value = implode(' ', array_slice($params, 2));
                
                $character = Character::find($characterId);
                
                if (!$character) {
                    $response = "Персонаж с ID {$characterId} не найден";
                    break;
                }
                
                $allowedFields = ['name', 'attitude', 'description', 'memory'];
                if (!in_array($field, $allowedFields)) {
                    $response = "Поле «{$field}» недоступно. Доступные: " . implode(', ', $allowedFields);
                    break;
                }
                
                $oldValue = $character->$field;
                $character->$field = $value;
                $character->save();
                
                $fieldNames = [
                    'name' => 'имя',
                    'attitude' => 'отношение',
                    'description' => 'описание',
                    'memory' => 'память'
                ];
                
                $response = "Персонаж «{$character->name}» обновлен\n\n";
                $response .= "Поле: {$fieldNames[$field]}\n";
                $response .= "Было: {$oldValue}\n";
                $response .= "Стало: {$value}";
                break;
                
            default:
                $response = "Неизвестная команда. Введите /start для списка команд";
        }
    } else {
        // Обычное сообщение - пост пользователя (взаимодействие с AI-сервисом)
        if (!$activePlot) {
            $response = "Нет активного сюжета. Создайте новый через /new_plot";
        } else {
            $player = Character::where('plot_id', $activePlot->plot_id)
                ->where('role', 'игрок')
                ->first();
            
            if (!$player) {
                $response = "В сюжете нет игрока.\n\nСоздайте игрока через /create_player Имя | Описание, затем продолжайте игру";
            } else {
                $activeCharacters = Character::where('plot_id', $activePlot->plot_id)
                    ->where('status', 'active')
                    ->where('role', 'персонаж')
                    ->get();
                
                $activeChapter = Chapter::where('plot_id', $activePlot->plot_id)
                    ->where('status', 'active')
                    ->first();
                
                $plotpoints = [];
                if ($activeChapter) {
                    $plotpoints = Plotpoint::where('chapter_id', $activeChapter->chapter_id)
                        ->whereIn('status', ['active', 'completed'])
                        ->orderBy('point_number')
                        ->get();
                }
                
                $activeCharacters = $activeCharacters instanceof Collection ? $activeCharacters : collect($activeCharacters);
                $plotpoints = $plotpoints instanceof Collection ? $plotpoints : collect($plotpoints);
                
                $aiData = [
                    'player_post' => $text,
                    'player' => [
                        'id' => $player->character_id,
                        'name' => $player->name,
                        'description' => $player->description,
                        'memory' => $player->memory
                    ],
                    'plot' => [
                        'id' => $activePlot->plot_id,
                        'title' => $activePlot->title
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
                    'chapter' => $activeChapter ? [
                        'id' => $activeChapter->chapter_id,
                        'number' => $activeChapter->chapter_number,
                        'text' => $activeChapter->chapter_text,
                        'summary' => $activeChapter->chapter_summary
                    ] : null,
                    'plotpoints' => $plotpoints->map(function($point) {
                        return [
                            'id' => $point->plotpoint_id,
                            'number' => $point->point_number,
                            'text' => $point->plot_text,
                            'ai_post' => $point->ai_post,
                            'player_post' => $point->player_post,
                            'result' => $point->result,
                            'status' => $point->status
                        ];
                    })->values()->toArray()
                ];
                
                bot_log('Sending to AI service', $aiData);
                
                try {
                    $aiResponse = callAIService($aiData);
                    $aiResult = json_decode($aiResponse, true);
                    
                    bot_log('AI response received', $aiResult);
                    
                    if ($player && isset($aiResult['player_memory'])) {
                        $player->memory = $aiResult['player_memory'];
                        $player->save();
                    }
                    
                    if (isset($aiResult['characters']) && is_array($aiResult['characters'])) {
                        foreach ($aiResult['characters'] as $charData) {
                            if (isset($charData['id'])) {
                                $character = Character::find($charData['id']);
                                if ($character) {
                                    if (isset($charData['memory'])) {
                                        $character->memory = $charData['memory'];
                                    }
                                    if (isset($charData['attitude'])) {
                                        $character->attitude_to_player = $charData['attitude'];
                                    }
                                    $character->save();
                                }
                            }
                        }
                    }
                    
                    if (isset($aiResult['plotpoints']) && is_array($aiResult['plotpoints'])) {
                        foreach ($aiResult['plotpoints'] as $pointData) {
                            if (isset($pointData['id'])) {
                                $plotpoint = Plotpoint::find($pointData['id']);
                                if ($plotpoint) {
                                    if (isset($pointData['ai_post'])) {
                                        $plotpoint->ai_post = $pointData['ai_post'];
                                    }
                                    if (isset($pointData['result'])) {
                                        $plotpoint->result = $pointData['result'];
                                    }
                                    if (isset($pointData['status'])) {
                                        $plotpoint->status = $pointData['status'];
                                    }
                                    $plotpoint->save();
                                }
                            }
                        }
                    }
                    
                    $response = $aiResult['response_to_player'] ?? "Ваше сообщение принято. Сюжет продолжается.";
                    
                } catch (Exception $e) {
                    bot_log('AI service error', ['error' => $e->getMessage()]);
                    $response = "Ошибка при обработке сообщения. Попробуйте позже.";
                }
            }
        }
    }
    
    sendMessage($chatId, $response);
    bot_log('Response sent', ['chat_id' => $chatId]);
}

echo 'OK';