#!/bin/bash

echo "Запуск заглушки AI-сервиса на порту 8001..."

cd tests/mock-server
php -S 0.0.0.0:8001 server.php &

MOCK_PID=$!
echo $MOCK_PID > /tmp/mock-ai.pid

echo "AI-сервис-заглушка запущен на http://localhost:8001"
echo "PID: $MOCK_PID"
echo "Для остановки: ./scripts/stop-mock-ai.sh"