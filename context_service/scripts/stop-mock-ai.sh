#!/bin/bash

if [ -f /tmp/mock-ai.pid ]; then
    PID=$(cat /tmp/mock-ai.pid)
    kill $PID 2>/dev/null
    rm /tmp/mock-ai.pid
    echo "AI-сервис-заглушка остановлен"
else
    echo "Файл с PID не найден. Попробуйте: pkill -f 'php -S 0.0.0.0:8001'"
fi