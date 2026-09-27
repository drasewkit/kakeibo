# 本番デプロイ手順

`https://kakeibo.drasewkit.dev` を AWS EC2 1台で動かすための手順。
2026-09-26 に初回構築し、2026-09-27 に Phase 0（外出先から記帳できる状態）を完了したときの実際の手順をまとめている。

## 構成

```
スマホ / PC
   │ HTTPS
   ▼
Cloudflare（DNS と TLS 終端）
   │ Cloudflare Tunnel（EC2 側から外向きに接続する。インバウンドは開けない）
   ▼
EC2 t4g.micro（Amazon Linux 2023 arm64）── docker-compose.prod.yml
   cloudflared → nginx ─┬─ /api, /sanctum, /up → backend（php-fpm）→ mysql
                        └─ それ以外           → frontend（Next.js standalone）
```

- **同一オリジン構成**。フロントと API を 1 ホストにまとめ、nginx がパスで振り分ける（`docker/nginx/kakeibo.conf`）。
  CORS が要らず、iOS の SameSite 制約も受けない
- **ホストにポートを公開しない**。外部からの到達は Cloudflare Tunnel 経由だけで、セキュリティグループはインバウンド全閉じ
- **サーバへの接続は SSM Session Manager**。SSH 鍵は作らない
- 開発用の `docker-compose.yml` とは別物。ソースは bind mount せず、イメージに焼き込む

| 項目 | 値 |
|---|---|
| リージョン | `ap-northeast-1`（東京） |
| インスタンス | 名前 `kakeibo` / `t4g.micro`（2コア・メモリ 1GiB）/ gp3 20GiB |
| IAM ロール | `kakeibo-ec2-ssm`（`AmazonSSMManagedInstanceCore` のみ） |
| アプリの配置先 | `/opt/kakeibo`（`main` を HTTPS で clone） |
| ドメイン | `kakeibo.drasewkit.dev`（固定。変えると PWA が別アプリ扱いになり再インストールが必要） |
| 月額（2026-09 試算） | 約 $13.5 + 税（EC2 約 $7.9 / EBS 約 $1.9 / パブリック IPv4 約 $3.7） |

## リリースの反映（通常の運用）

`develop → main` のリリースPRを **「Create a merge commit」** でマージしてから、サーバで反映する。
squash すると `develop` と `main` の履歴がずれる。

1. EC2 → インスタンス `kakeibo` → 「接続」→「Session Manager」→「接続」
2. 以下を実行する

```bash
sudo -i
cd /opt/kakeibo
git pull    # 出力の変更ファイル一覧で、frontend / backend のどちらが変わったかを見る
```

**frontend だけ変わった場合**（ビルド約4分）

```bash
docker compose -f docker-compose.prod.yml build frontend
docker compose -f docker-compose.prod.yml up -d frontend
```

**backend も変わった場合**（`backend/lang` のような設定ファイルだけの変更も含む）

```bash
docker compose -f docker-compose.prod.yml build backend
docker compose -f docker-compose.prod.yml build frontend
docker compose -f docker-compose.prod.yml up -d backend frontend
```

**`backend/database/migrations/` が変わった場合**は、上のあとにマイグレーションも流す

```bash
docker compose -f docker-compose.prod.yml exec backend php artisan migrate --force
```

- ビルドは**必ず1つずつ**。同時に走らせるとメモリ 1GiB では足りない
- Session Manager は **20分無操作で切断**される。長いビルドは下の「長いビルドを接続と切り離す」を使う
- 反映後は実機（iPhone）で該当の画面を再読み込みして確認する

### 前のリリースに戻す

`git log --merges --oneline -5` で戻したいマージコミットを探し、そのコミットで作り直す。

```bash
git checkout <戻したいマージコミット>
docker compose -f docker-compose.prod.yml build backend
docker compose -f docker-compose.prod.yml build frontend
docker compose -f docker-compose.prod.yml up -d backend frontend
# 直ったら修正をリリースし、git checkout main && git pull で main に戻す
```

マイグレーションを含むリリースを戻す場合は、DB の状態も考える必要がある（`migrate:rollback` の要否）。

## 初回構築

新しいサーバを一から作るときの手順。順番に進める。

### 1. EC2 を起動する

1. IAM → ロール → 「ロールを作成」→ AWS のサービス → EC2 → `AmazonSSMManagedInstanceCore` だけを付けて `kakeibo-ec2-ssm` として作成
2. EC2 → 「インスタンスを起動」
   - AMI: Amazon Linux 2023（**arm64**）
   - インスタンスタイプ: `t4g.micro`
   - キーペア: **なしで続行**
   - セキュリティグループ: **インバウンドルールをすべて削除**
   - ストレージ: gp3 20GiB
   - 高度な詳細 → IAM インスタンスプロフィール: `kakeibo-ec2-ssm`
3. 数分待ってから「接続」→「Session Manager」で入れることを確認する

Elastic IP は付けない（Tunnel 経由なので固定 IP が要らない）。付けなくても自動割り当てのパブリック IPv4 に同額の料金がかかる。

### 2. Cloudflare Tunnel を作る

Cloudflare のダッシュボード（Zero Trust → Networks → Tunnels）で操作する。

1. Tunnel を作成する（コネクタは cloudflared、名前は `kakeibo`）
2. 表示されるトークンを控える。手順 4 で `.env` の `TUNNEL_TOKEN` に書く
   （cloudflared のインストール手順は実行しない。`docker-compose.prod.yml` の `cloudflared` サービスが動かす）
3. Public Hostname を追加する
   - Hostname: `kakeibo.drasewkit.dev`
   - Service: `HTTP` / `nginx:80`（compose 内のサービス名で届く）

### 3. サーバを準備する

以降はすべて Session Manager で `sudo -i` したあとに実行する。

**swap（2GiB）**。メモリ 1GiB ではビルドに足りないため必須。

```bash
dd if=/dev/zero of=/swapfile bs=1M count=2048
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile swap swap defaults 0 0' >> /etc/fstab
free -h    # Swap: 2.0Gi と出れば OK
```

**Docker と git**。dnf の Docker には Compose と Buildx のプラグインが付かないため、GitHub のリリースから arm64 版を手動で置く。
バージョンは 2026-09-26 の構築時のもの（Docker 25.0.16 / Compose v5.5.1 / Buildx v0.37.1）。

```bash
dnf install -y docker git
systemctl enable --now docker

mkdir -p /usr/local/lib/docker/cli-plugins
curl -fSL -o /usr/local/lib/docker/cli-plugins/docker-compose \
  https://github.com/docker/compose/releases/download/v5.5.1/docker-compose-linux-aarch64
curl -fSL -o /usr/local/lib/docker/cli-plugins/docker-buildx \
  https://github.com/docker/buildx/releases/download/v0.37.1/buildx-v0.37.1.linux-arm64
chmod +x /usr/local/lib/docker/cli-plugins/docker-compose /usr/local/lib/docker/cli-plugins/docker-buildx

docker compose version && docker buildx version
```

### 4. アプリを配置して `.env` を2つ作る

```bash
cd /opt
git clone --branch main https://github.com/drasewkit/kakeibo.git
cd kakeibo
cp .env.prod.example .env
cp backend/.env.production.example backend/.env
chmod 600 .env backend/.env
```

| ファイル | 読む側 | 書く値 |
|---|---|---|
| `.env`（リポジトリ直下） | `docker-compose.prod.yml`（mysql と cloudflared） | `DB_*`・`DB_ROOT_PASSWORD`・`TUNNEL_TOKEN` |
| `backend/.env` | backend コンテナ（`env_file` で渡る） | `APP_KEY`・`DB_PASSWORD` ほか |

パスワードと `APP_KEY` は `openssl` で生成して直接書く。値はサーバ上にだけ置き、控えない。

```bash
openssl rand -base64 24    # DB_PASSWORD / DB_ROOT_PASSWORD 用（それぞれ別の値にする）
openssl rand -base64 32    # APP_KEY 用。backend/.env に APP_KEY=base64:<出力> と書く
```

- `DB_PASSWORD` は `.env` と `backend/.env` で**同じ値**にする
- `APP_KEY` は **`php artisan key:generate` では作れない**。`backend/.dockerignore` が `.env` を除外しているため、
  コンテナ内に書き込み先の `.env` が無い（値は `env_file` で環境変数として渡っている）
- `SESSION_DOMAIN` は**先頭ドットなし**の `kakeibo.drasewkit.dev` のまま。`.drasewkit.dev` にすると他のサブドメインにもセッションクッキーが送られる

### 5. ビルドして起動する

ビルドは1つずつ。初回は時間がかかるため、接続と切り離して走らせる（下の「長いビルドを接続と切り離す」）。

```bash
docker compose -f docker-compose.prod.yml build backend
docker compose -f docker-compose.prod.yml build frontend
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml exec backend php artisan migrate --force
docker compose -f docker-compose.prod.yml exec backend php artisan db:seed --force
```

- `--force` は必須。`APP_ENV=production` では確認プロンプトが出て止まる
- `db:seed` は本番ではカテゴリの初期データだけを入れる（テストユーザーは作らない）

### 6. 動作を確認する

手元の Mac から確認する。

```bash
curl -s -o /dev/null -w '%{http_code}\n' https://kakeibo.drasewkit.dev/up      # 200
curl -s -o /dev/null -w '%{http_code}\n' https://kakeibo.drasewkit.dev/login   # 200
curl -si https://kakeibo.drasewkit.dev/sanctum/csrf-cookie | grep -i set-cookie
```

`Set-Cookie` に `domain=kakeibo.drasewkit.dev`（先頭ドットなし）・`secure`・`samesite=lax` が付いていれば OK。
そのあと実機で、登録・ログイン・収支の登録・画像の添付・ホーム画面への追加を確かめる。

## 運用メモ

### 長いビルドを接続と切り離す

```bash
nohup docker compose -f docker-compose.prod.yml build backend > /tmp/build-backend.log 2>&1 &
tail -f /tmp/build-backend.log    # Ctrl+C で抜けてもビルドは続く
```

### 状態を見る

```bash
docker compose -f docker-compose.prod.yml ps
docker compose -f docker-compose.prod.yml logs --tail=100 backend    # nginx / frontend / cloudflared も同様
free -h
```

2026-09-27 の frontend 更新後で used 570Mi / available 240Mi / swap 307Mi。available が 100Mi を切るようなら構成を見直す。

### やってはいけないこと

- **`docker compose -f docker-compose.prod.yml down -v`**。`-v` は MySQL のデータ（`kakeibo-mysql`）と添付画像（`kakeibo-storage`）のボリュームを消す
- **ビルドを同時に走らせる**。メモリ不足でサーバが応答しなくなる
- **root ユーザーで AWS コンソールに入る**。普段は IAM ユーザー `drasewkit` を使う

## 未対応（今後の課題）

- **バックアップが無い**。MySQL のデータと添付画像は EBS 上の Docker ボリュームにしか無い。EBS スナップショットか `mysqldump` の定期取得を検討する
- 反映は手動。GitHub Actions からの自動デプロイは未導入
- 監視・アラートは未設定（AWS Budgets による請求額の通知のみ）
