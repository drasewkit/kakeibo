# kakeibo プロジェクトルール

Next.js（React）+ Laravel で作る家計簿アプリ。このファイルはこれまでの会話で決定した規約・方針をまとめたもの。実装・提案を行う際は必ずこの内容に従うこと。

なお、これからやること・検討中の課題（ロードマップ / TODO）は、各自ローカル管理の `CLAUDE.local.md`（`.gitignore` 対象、Claude Code が自動読み込み）に記録している。作業を始める前にそちらも確認すること。決定して恒久ルール化すべき事項が出たら `CLAUDE.local.md` からこのファイルへ昇格させる。

## 技術スタック

- フロントエンド: Next.js（App Router, TypeScript, React）
- UIコンポーネント: **MUI (Material UI) v9**。Tailwind CSSは廃止し、スタイリングはMUIの`sx`プロパティ・テーマ（`src/lib/theme.ts`）に統一する
  - MUI v9ではBox/Typography/Stack等に`mb`/`fontWeight`/`justifyContent`のような直接props（旧バージョンのsystem props）が使えなくなっており、必ず`sx={{ ... }}`にまとめる必要がある（Stackの`direction`/`spacing`/`divider`/`useFlexGap`のようなコンポーネント固有propsは除く）
  - App RouterでのSSR対応として`@mui/material-nextjs`の`AppRouterCacheProvider`（`src/app/providers.tsx`）を使用する
  - 将来スマホアプリ化する場合もCapacitor等でのWebViewラップを想定しており、React Native化は現状予定していない（MUIはReact Native非対応のため、方針転換時は別途検討）
- バックエンド: Laravel（PHP）, MySQL
- 開発環境: Docker Compose（frontend / backend / mysql の3コンテナ、`docker-compose.yml`はルート）
  - **定型コマンドはルートの`Makefile`に集約する**（`make help`で一覧）。起動・停止・マイグレーション・整形・検証などは`docker compose ...`を直接書かず、対応するターゲットを使う／無ければ追加する。READMEの「よく使うコマンド」もMakefileを案内する形にしてある
  - `frontend`サービスは`node_modules`をbind mount（`./frontend:/app`）でホストと共有しており、匿名ボリュームによる隔離は行わない。`frontend/entrypoint.sh`がコンテナ起動のたびに`npm install`を実行してから`npm run dev`するため、`package.json`さえ変更されていれば`docker compose up`（コンテナ再起動）するだけでホスト側`frontend/node_modules`にも自動的に反映される。
  - パッケージの追加・更新はホスト側で`npm install <package>`を実行してもよいし、コンテナを再起動するだけでも（entrypointが自動で`npm install`するため）反映される。どちらの方法でも同じディレクトリに書き込まれるため、明示的な同期手順（別途`npm install`し直す等）は不要。
  - 以前は`node_modules`を匿名ボリュームで隔離していたため、ホストとコンテナのnode_modulesが乖離し、ホストの`tsc`やエディタのTS Language Serverが「モジュールが見つかりません」というエラーを出す不具合が実際に発生していた（MUI導入時）。原因調査の上でこの設計に変更し、解消済み。
  - `.next`ディレクトリのみ引き続き匿名ボリューム（`/app/.next`）で隔離する（ビルドキャッシュであり同期の必要がなく、ファイル数が多くbind mountだと遅いため）。
- 認証: Laravel Sanctum の **SPAクッキー認証**（Bearerトークンは使わない）
- フロントのHTTPクライアント: **axios**（fetchではない）
  - 理由: Sanctum SPA認証はCSRFクッキー→ヘッダーの自動変換が必要で、axiosの`withCredentials`+`withXSRFToken`がこれを標準機能で賄える。fetchに寄せる場合は自前でCSRF処理を保守する必要があり、採用する定番モジュールもない。
- フロントのデータ取得/状態管理: **TanStack Query (React Query)**

## GitHub / Git運用

- 個人アカウント: https://github.com/drasewkit 、リポジトリ: `drasewkit/kakeibo`
- `develop`ブランチが**デフォルトブランチ**かつ開発の統合ブランチ
- **`develop`へ直接コミット・pushしない（厳守）**。作業は`develop`から切った作業ブランチで行い、`develop`向けのプルリクエストをGitHub上で作成してマージする
  - ブランチ名は`{種別}/{内容}`のケバブケース。種別は`feature`（機能追加）/ `fix`（不具合修正）/ `refactor`（挙動を変えない整理）/ `docs`（ドキュメントのみ）/ `chore`（設定・依存更新など）
    （例: `feature/household-foundation`, `fix/transaction-date-timezone`）
  - コミットは**レビューしやすい単位に分ける**。1コミット1目的とし、DB変更・ロジック変更・テスト・ドキュメントなど性質の違う変更を、意味のまとまりごとに分ける。整形だけの変更は単独のコミットにする
  - 各コミットの時点でテストが通る状態を保つ（途中のコミットで壊れていると、差分を追うときやリバート時に困るため）
  - 理由: GitHub上でプルリクエストの差分を確認してから取り込むため（2026-10-02決定）
- `main`は**リリース専用**。`develop → main`のプルリクエストをGitHub上で作成してマージする運用
- `main`にはブランチ保護ルールを設定済み（PR必須・force push禁止・削除禁止・管理者にも適用）
- **コミットメッセージ・ドキュメント類はすべて日本語で記載する**

## コメント方針

- 実装時は、その処理が何を行っているか一目でわかるように**日本語のコメント**を残す
- クラス（Controller/Service/Repository等）の冒頭には、何をするクラスかを一言で示すPHPDoc（`/** ... */`）をつける
- メソッド内の主要な処理ブロックの前には、処理内容を短く要約したコメントを入れる（例: `// ログインユーザーを取得`, `// カテゴリの種類と収支の種類が一致しているか検証`）
- フロントエンドのフック・コンポーネントも同様に、何を行っているかをコメントで示す
- コメントは簡潔に。処理内容の要約に留め、自明なこと（`// idを取得` のような変数名そのままの説明）や長い説明文は避ける

## API境界の命名規則

フロントエンド・バックエンドを横断するルール。

- **APIのリクエスト／レスポンスのJSONキーはすべてcamelCase**（`categoryId`, `hasImage`, `availableYears`）
- **DBのカラム名はsnake_caseのまま**（`category_id`）。変換はAPIの境界（`app/Http/Resources/`とFormRequest）で行う
- フロントエンド側にcamelCase変換層は置かない。APIが返した形をそのまま型として扱う
- 理由: フロントエンド（TypeScript / JavaScript）の標準的な作法がcamelCaseであり、APIが返した形をそのまま型として使えるようにするため。境界で一度だけ変換すれば、フロント全体でケースが混在しない
- 再検討条件: なし

## コード整形

- **バックエンド: Laravel Pint**（Laravel既定プリセット）。`./vendor/bin/pint`
- **フロントエンド: Prettier**。`npm run format`
- コミット前に対象範囲を整形してから差分を確認する
- 理由: AIに書かせる量が多く、整形ゆれで差分が汚れるとレビューが成立しなくなるため
- エディタ側（Neovim / LazyVim + conform.nvim）の設定は**dotfilesリポジトリ**（chezmoi管理）で別途管理する。このリポジトリには含めない

## 静的解析

- **バックエンド: Larastan（PHPStan）**。`./vendor/bin/phpstan analyse`。設定は`backend/phpstan.neon`
- **フロントエンド: ESLint**（`npm run lint`）と TypeScript の型検査（`npx tsc --noEmit`）
- 整形ツールとは役割を分ける。**整形は見た目だけを変え、静的解析はバグになりうる書き方を指摘する**
- **PHPのlintにphpcsは使わない。** 既定のPEAR標準がPintと衝突するうえ、バグ検出ではなくスタイル検査であるため
- PHPStanのlevelは5から始め、通るようになったら段階的に上げる
  - 2026-09-19時点の実測: level 5で0件、6で33件、8で46件、10で61件
- 理由: PHP側に静的解析が無いと、コントローラ再編のような大規模リファクタで参照の壊れを検出できない。
  Featureテストは実行されたコードパスしか見ないため、守備範囲が重ならない

## CI

- GitHub Actions（`.github/workflows/ci.yml`）で `develop` への push と `main` へのプルリクエストに対して、整形チェック・静的解析・型検査・テストを実行する（コマンドは`ci.yml`を参照）
- 実行環境は開発コンテナに揃える（Node 22 / PHP 8.5）
- 理由: Featureテスト（`backend/CLAUDE.md`参照）は、自動で実行されなければ回帰に気づけず、リファクタの安全網として機能しないため

## その他

- 新しいNext.js/Laravelのバージョンは訓練データと異なる挙動をしている可能性があるため、実装前に`frontend/node_modules/next/dist/docs/`や実際にインストールされたLaravelのvendorソースを確認してから進める（`proxy.ts`の件、`statefulApi()`の件はいずれもこの方法で確認済み）。
