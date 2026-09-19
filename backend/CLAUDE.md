## バックエンドのAPI設計方針

各ルールには「理由」と「再検討条件」を添える。判断を覆すときは、まず再検討条件に該当するかを確認すること。

### 層構成

- **Controller / Service / Repository / Resource の4層構成**
  - Controller: HTTPの入出力のみを担当する薄い層
  - Service: ビジネスロジック（`app/Services/{Feature}/`）
  - Repository: データアクセスを抽象化（`app/Repositories/`、インターフェースを`app/Repositories/Interfaces/`に定義し`RepositoryServiceProvider`でDIコンテナに束縛）
  - Resource: APIレスポンスの形を定義（`app/Http/Resources/{Feature}/`）
- **モデルごとに1つのRepositoryを実装する方針**（例: `TransactionRepository`, `CategoryRepository`, `UserRepository`）。今後モデルが増えたら同様にRepositoryを追加する。

### レスポンスは必ずResourceを経由する

- Eloquentモデルを`response()->json()`で直接返さない。必ず`app/Http/Resources/{Feature}/`のResourceを通す
- モデルの`$hidden` / `$appends`でAPI出力を制御しない
- ページネーションを返す場合も独自のResourceで形を定義する（Laravelのページネータが吐く`current_page`等をそのまま露出させない）
- 理由: モデルにカラムを追加したときに既定で公開されてしまう構造を避ける。「隠すものを列挙する」ではなく「公開するものだけ書く」に反転させるため。加えて、Phase 3のNestJS移行時にResourceがそのままレスポンス仕様書として機能する
- 再検討条件: なし（層が増えるコストより、情報が漏れる構造のリスクを重く見る）

### URI規約（フレームワーク非依存の恒久ルール）

- **HTTPメソッドはGET/POSTのみ**。PUT/PATCH/DELETEは使わない
  - 一覧・詳細取得はGET、作成・更新・削除はPOST
  - 更新・削除対象のIDは**URLのルートパラメータではなくバリデーション対象として渡す**（GETはクエリパラメータ、POSTはボディに`transactionId`等を含める）
- URIは`/{リソース複数形}/{操作名}`のケバブケース
  - **操作名は「動詞」または「動詞-目的語」とし、リソース名を繰り返さない**（プレフィックスに既にあるため）
  - 例: `GET /transactions/get-list`（`get-transaction-list`としない）、`POST /transactions/upload-image`
  - 認証系も`/auth`配下に揃える
- 理由: ルーティングの見通しの良さとIDのバリデーション一元化を重視して決定した。
  操作名からリソース名を除くのは、旧「1コントローラ1アクション・URI末尾＝コントローラー名」規約の
  名残で冗長になっていたため（2026-09-19に見直し）

エンドポイント一覧:

| メソッド | URI | コントローラーのメソッド |
|---|---|---|
| POST | `/auth/register` | `AuthController::register()` |
| POST | `/auth/login` | `AuthController::login()` |
| POST | `/auth/logout` | `AuthController::logout()` |
| GET | `/auth/get-user` | `AuthController::getUser()` |
| GET | `/categories/get-list` | `CategoryController::getList()` |
| GET | `/transactions/get-list` | `TransactionController::getList()` |
| GET | `/transactions/get-detail` | `TransactionController::getDetail()` |
| POST | `/transactions/create` | `TransactionController::create()` |
| POST | `/transactions/update` | `TransactionController::update()` |
| POST | `/transactions/delete` | `TransactionController::delete()` |
| GET | `/transactions/get-image` | `TransactionController::getImage()` |
| POST | `/transactions/upload-image` | `TransactionController::uploadImage()` |
| POST | `/transactions/delete-image` | `TransactionController::deleteImage()` |
- **Phase 3のNestJS移行後も同じURIを維持する。** フロントの`api-client.ts` / hooksを変更せずにバックエンドを差し替えられる状態を保つため
- 再検討条件: 外部に公開するAPIを出す場合

### コントローラの粒度（Laravel実装における規約）

- **リソース単位で1コントローラ**とし、操作ごとにpublicメソッドを生やす
  - 配置は`app/Http/Controllers/{Feature}/{Feature}Controller.php`
  - メソッド名はURIの末尾セグメントをキャメルケースにしたものと一致させる（`/transactions/get-list` → `getList()`）
  - FormRequestは操作ごとに分ける（`app/Http/Requests/{Feature}/`）
- 単一アクションコントローラ（`__invoke()`のみ）は使わない。`Route::apiResource()`も使わない（上のURI規約と両立しないため）
- 理由: NestJSは1コントローラに複数ハンドラを持たせるのが標準であり、移行時の構造差を小さくできる
- **これは実装構造の規約であり、上のURI規約とは独立している。** URIを変えずにこちらだけを変更してよい

### 認可

- **Laravel Policyを使わず、Repository層のクエリスコープで行う**
  - 例: `TransactionRepository::findForUser(userId, transactionId)`のように、常にログインユーザーのIDでスコープしたクエリでレコードを取得する
  - 対象レコードが存在しない場合と、他人のレコードで所有権がない場合は**区別せず一律404**を返す（存在有無の情報漏洩を避けるため）

### エラーレスポンス

Laravel既定の`{message, errors}`は使わず、以下の形式に統一する。`bootstrap/app.php`の例外ハンドラで変換する。

```json
{
  "error": {
    "code": "VALIDATION_FAILED",
    "message": "入力内容を確認してください。",
    "fields": { "amount": ["金額は必須です。"] }
  }
}
```

| code | HTTPステータス | 用途 |
|---|---|---|
| `VALIDATION_FAILED` | 422 | バリデーション失敗。`fields`を含む |
| `UNAUTHENTICATED` | 401 | 未ログイン |
| `NOT_FOUND` | 404 | 対象が存在しない、または他人のレコード（**403は使わない**） |
| `INTERNAL_ERROR` | 500 | サーバエラー |

- `message`は利用者にそのまま表示できる日本語
- `fields`は`VALIDATION_FAILED`のときのみ含める。キーはリクエストのフィールド名（camelCase）
- 理由: Phase 3でNestJSの`ExceptionFilter`が再現すべき仕様を明示するため。フレームワーク既定の形に暗黙依存していると、移行時にフロントの`errors.ts`が静かに壊れる
- 再検討条件: 外部に公開するAPIを出す場合（RFC 9457準拠を検討する）

### 金額の扱い

- 金額は**円単位の非負整数**（`unsignedInteger`）。小数は持たない
- 負数は使わず、収支の向きは`type`（`income` / `expense`）で表す
- 理由: 円のみを扱う前提であり、浮動小数点の誤差と符号の二重表現を避けるため
- 再検討条件: 外貨または小数点以下を扱う場合

### タイムゾーン

- アプリケーションもDBも**日本時間（`Asia/Tokyo`）**で動作させる。`.env`に`APP_TIMEZONE=Asia/Tokyo`を設定する
  - `config/app.php`の既定値も`Asia/Tokyo`にしてある。環境変数の設定漏れで静かにUTCへ落ちると日付がずれるため
- UTC保存＋表示時変換は行わない
- 理由: 利用者が日本在住で確定しており、変換漏れによる日付ズレのバグを避けることを優先する
- 再検討条件: 複数のタイムゾーンにまたがる利用者を持つ場合

### テスト

- **エンドポイントを追加・変更したら必ずFeatureテストを書く**
- テストは**本番と同じMySQL**で実行する（`phpunit.xml`、DBは`testing`）
  - 当初SQLiteのインメモリDBを採用したが、`TransactionRepository::getAvailableYearsForUser()`が
    MySQL固有の`YEAR()`を使っており「no such function: YEAR」で落ちたため2026-09-19に変更した
  - engineが違うと**`enum`列の`ORDER BY`の結果まで変わる**（MySQLは定義順、SQLite/PostgreSQLは文字列比較）。
    本番と同じengineで検証する
  - `DB_HOST`は環境側で与える。コンテナ内は`mysql`、ホストとCIは`127.0.0.1`
    （ホストから実行する場合は`DB_HOST=127.0.0.1 php artisan test`）
  - 再検討条件: Phase 4でPostgreSQLへ移行したら、そちらに合わせる
- 最低限の観点:
  - 正常系のレスポンス形（Resourceが定義した通りのキーが返るか）
  - 未ログイン時に401（`UNAUTHENTICATED`）
  - 他人のレコードへのアクセスが404（`NOT_FOUND`）
  - バリデーション失敗が422（`VALIDATION_FAILED`）で`fields`を含む
- 理由: 現状APIの仕様書がフロントの手書き`types.ts`しかなく、実レスポンスとの乖離を検知できない。**Phase 3のNestJS移行では、このテストが移行前後の挙動の同一性を保証する唯一の手段になる**
- 再検討条件: なし

## マイグレーションの規約

- **各カラムに`->comment('...')`で日本語コメントをつける**（DBeaverなどのDBクライアントでテーブル構造を見たときに内容がわかるようにするため）
  - ただし`id`（主キー）と`created_at`/`updated_at`は自明なのでコメント不要。`timestamps()`ショートカットのままでよい
- 外部キー（`foreignId()`）にコメントを付ける場合は、`->constrained()`より前に`->comment()`を呼ぶこと（`constrained()`以降は別オブジェクト（FK制約）になり、コメントがカラムに反映されない）
  ```php
  // 良い例
  $table->foreignId('user_id')->comment('登録したユーザーのID')->constrained()->cascadeOnDelete();
  // 悪い例（コメントが効かない）
  $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('登録したユーザーのID');
  ```
- **DBのカラム名はsnake_caseのまま**とする（APIの境界でcamelCaseに変換する。ルートCLAUDE.md参照）
