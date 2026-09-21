# kakeibo

Next.js（React）+ Laravel で作る家計簿アプリ。

## 構成

| ディレクトリ / ファイル | 内容 |
|---|---|
| `frontend/` | Next.js（App Router, TypeScript, MUI v9, TanStack Query, axios） |
| `backend/` | Laravel 13 / PHP 8.5 / MySQL 8.4。認証は Sanctum の SPAクッキー認証 |
| `docker-compose.yml` | 開発環境（frontend / backend / mysql の3コンテナ） |
| `docker-compose.prod.yml` | 本番環境（AWS EC2 + Cloudflare Tunnel。nginx を加えた4コンテナ） |
| `Makefile` | 開発用の定型コマンド。`make help` で一覧を表示する |

開発・実装上の規約は [`CLAUDE.md`](./CLAUDE.md) にまとめてある。

## セットアップ

事前に Docker Desktop を起動しておく。

```bash
make setup
```

`.env` の配置からコンテナの起動、アプリケーションキーの生成、マイグレーション、
カテゴリの初期データ投入までを一括で行う。

- フロントエンド: http://localhost:3000
- バックエンドAPI: http://localhost:8000
- MySQL: localhost:3306（database: `kakeibo` / user: `sail` / password: `password`）

2回目以降の起動は `make up`、停止は `make down`（DBのデータも消す場合は `make down-v`）。

## よく使うコマンド

```bash
make help
```

主なものは以下。

| コマンド | 内容 |
|---|---|
| `make up` / `make down` | 起動 / 停止 |
| `make logs` | 全コンテナのログを追う |
| `make bash-back` / `make sh-front` | 各コンテナに入る |
| `make migrate` / `make fresh` | マイグレーション / DBを作り直してシードまで |
| `make check` | CIと同じ検証をまとめて実行する |

`make` を使わない場合は `docker compose ...` を直接叩いてもよい。中身は `Makefile` を参照。

## コード整形と検証

コミット前に対象範囲を整形してから差分を確認する。

```bash
make format-front   # Prettier（フロントエンド）
make pint           # Laravel Pint（バックエンド）
```

CI（`.github/workflows/ci.yml`）は `develop` への push と `main` への
プルリクエストで走る。同じ内容をローカルで確認したい場合は次を使う。

```bash
make check
```

内訳は、フロントエンドが ESLint / `tsc --noEmit` / Prettier の確認、
バックエンドが Pint の確認 / Larastan / `artisan test`。

## Docker を使わない場合

### backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# .env の DB_HOST を 127.0.0.1 に、DB_USERNAME / DB_PASSWORD をローカルMySQLの認証情報に変更する
php artisan migrate --seed
php artisan serve
```

### frontend

```bash
cd frontend
npm install
npm run dev
```

## ブランチ運用

- `develop` がデフォルトブランチかつ日常の開発ブランチ
- `main` はリリース専用。`develop → main` のプルリクエストをGitHub上で作成してマージする
