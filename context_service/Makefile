.PHONY: test test-unit test-feature test-filter mock-start mock-stop test-with-mock test-model test-ai test-clean

# Запуск всех тестов
test:
	php artisan test

# Запуск Unit тестов
test-unit:
	php artisan test --testsuite=Unit

# Запуск Feature тестов
test-feature:
	php artisan test --testsuite=Feature

# Запуск конкретного теста (использование: make test-filter filter=GlobalPlotTest)
test-filter:
	php artisan test --filter $(filter)

# Запуск тестов моделей
test-model:
	php artisan test --filter GlobalPlotTest
	php artisan test --filter CharacterTest

# Запуск тестов AI заглушки
test-ai:
	php artisan test --filter AIServiceStubTest

# Запуск заглушки AI-сервиса
mock-start:
	@echo "Запуск заглушки AI-сервиса..."
	@mkdir -p tests/mock-server
	@cd tests/mock-server && php -S 0.0.0.0:8001 server.php > /dev/null 2>&1 &
	@echo $$! > /tmp/mock-ai.pid
	@sleep 2
	@echo "AI-сервис-заглушка запущен на http://localhost:8001"

# Остановка заглушки AI-сервиса
mock-stop:
	@echo "Остановка заглушки AI-сервиса..."
	@if [ -f /tmp/mock-ai.pid ]; then \
		kill $$(cat /tmp/mock-ai.pid) 2>/dev/null || true; \
		rm /tmp/mock-ai.pid; \
	fi
	@pkill -f "php -S 0.0.0.0:8001" 2>/dev/null || true
	@echo "AI-сервис-заглушка остановлен"

# Запуск тестов с заглушкой
test-with-mock: mock-start
	@sleep 1
	php artisan test --filter AIServiceStubTest
	make mock-stop

# Очистка временных файлов
test-clean:
	@rm -f /tmp/mock-ai.pid
	@php artisan config:clear
	@php artisan cache:clear
	@echo "Временные файлы очищены"

# Быстрый запуск только успешных тестов
test-quick:
	php artisan test --filter GlobalPlotTest
	php artisan test --filter CharacterTest
	php artisan test --filter AIServiceStubTest

# Запуск HTTP тестов
test-http:
	php artisan test --filter RealHttpTest
	php artisan test --filter HttpStatusTest

# Запуск всех тестов включая HTTP
test-all:
	php artisan test
