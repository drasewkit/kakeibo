# kakeibo の開発用コマンド集。すべて Docker Compose 前提。
# 一覧は `make help` で確認できる。

COMPOSE := docker compose
BACK := $(COMPOSE) exec backend
FRONT := $(COMPOSE) exec frontend

.DEFAULT_GOAL := help

# ---------------- 起動・停止 ----------------

.PHONY: up
up: ## コンテナを起動する
	$(COMPOSE) up -d

.PHONY: up-build
up-build: ## イメージを再ビルドしてから起動する
	$(COMPOSE) up -d --build

.PHONY: down
down: ## コンテナを停止・削除する（DBのデータは残る）
	$(COMPOSE) down

.PHONY: down-v
down-v: ## コンテナを停止・削除し、DBのデータも消す
	$(COMPOSE) down -v

.PHONY: ps
ps: ## コンテナの状態を表示する
	$(COMPOSE) ps

.PHONY: logs
logs: ## 全コンテナのログを追う
	$(COMPOSE) logs -f

# ---------------- コンテナに入る ----------------

.PHONY: bash-back
bash-back: ## backendコンテナにbashで入る
	$(BACK) bash

.PHONY: sh-front
sh-front: ## frontendコンテナにshで入る
	$(FRONT) sh

.PHONY: tinker
tinker: ## Tinkerを起動する
	$(BACK) php artisan tinker

# ---------------- データベース ----------------

.PHONY: migrate
migrate: ## マイグレーションを実行する
	$(BACK) php artisan migrate

.PHONY: seed
seed: ## シーダーを流す（カテゴリの初期データ）
	$(BACK) php artisan db:seed

.PHONY: fresh
fresh: ## DBを作り直してシーダーまで流す（既存データは消える）
	$(BACK) php artisan migrate:fresh --seed

# ---------------- 整形 ----------------

.PHONY: format-front
format-front: ## フロントエンドをPrettierで整形する
	$(FRONT) npm run format

.PHONY: pint
pint: ## バックエンドをLaravel Pintで整形する
	$(BACK) ./vendor/bin/pint

# ---------------- 検証 ----------------

.PHONY: lint-front
lint-front: ## フロントエンドのESLintを実行する
	$(FRONT) npm run lint

.PHONY: tsc
tsc: ## フロントエンドの型検査（Next.jsのルート型を生成してから実行）
	$(FRONT) npx next typegen
	$(FRONT) npx tsc --noEmit

.PHONY: phpstan
phpstan: ## バックエンドの静的解析（Larastan）を実行する
	$(BACK) ./vendor/bin/phpstan analyse --memory-limit=1G

.PHONY: test
test: ## バックエンドのテストを実行する
	$(BACK) php artisan test

.PHONY: check
check: ## CIと同じ検証をまとめて実行する（整形は確認のみ・書き換えない）
	$(FRONT) npm run lint
	$(FRONT) npx next typegen
	$(FRONT) npx tsc --noEmit
	$(FRONT) npm run format:check
	$(BACK) ./vendor/bin/pint --test
	$(BACK) ./vendor/bin/phpstan analyse --memory-limit=1G
	$(BACK) php artisan test

# ---------------- 初回セットアップ ----------------

.PHONY: setup
setup: ## 初回セットアップ（.env配置→起動→キー生成→マイグレーション→シード）
	@test -f backend/.env || cp backend/.env.example backend/.env
	$(COMPOSE) up -d --build
	@echo "MySQLの起動を待っています..."
	@until $(COMPOSE) exec -T mysql mysqladmin ping -ppassword --silent >/dev/null 2>&1; do sleep 2; done
	$(BACK) php artisan key:generate
	$(BACK) php artisan migrate
	$(BACK) php artisan db:seed
	@echo "完了。フロントエンド: http://localhost:3000 / API: http://localhost:8000"

# ---------------- ヘルプ ----------------

.PHONY: help
help: ## このヘルプを表示する
	@grep -E '^[a-zA-Z0-9_-]+:.*?## ' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-13s\033[0m %s\n", $$1, $$2}'
