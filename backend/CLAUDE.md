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
- 理由: モデルにカラムを追加したときに既定で公開されてしまう構造を避ける。「隠すものを列挙する」ではなく「公開するものだけ書く」に反転させるため
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
- 再検討条件: 外部に公開するAPIを出す場合

### コントローラの粒度（Laravel実装における規約）

- **リソース単位で1コントローラ**とし、操作ごとにpublicメソッドを生やす
  - 配置は`app/Http/Controllers/{Feature}/{Feature}Controller.php`
  - メソッド名はURIの末尾セグメントをキャメルケースにしたものと一致させる（`/transactions/get-list` → `getList()`）
  - FormRequestは操作ごとに分ける（`app/Http/Requests/{Feature}/`）
- 単一アクションコントローラ（`__invoke()`のみ）は使わない。`Route::apiResource()`も使わない（上のURI規約と両立しないため）
- 理由: 1操作1クラスではファイル数が操作の数だけ増え、同じリソースの処理が散らばって見通しが悪くなるため（2026-09-19に13本→3本へ再編）
- **これは実装構造の規約であり、上のURI規約とは独立している。** URIを変えずにこちらだけを変更してよい

### 認可

- **Laravel Policyを使わず、Repository層のクエリスコープで行う**
  - 例: `TransactionRepository::findForHousehold(householdId, transactionId)`のように、常にログインユーザーの所有単位でスコープしたクエリでレコードを取得する
  - 対象レコードが存在しない場合と、所有権がない場合は**区別せず一律404**を返す（存在有無の情報漏洩を避けるため）
- **収支の所有者は世帯（`households`）**。`transactions.household_id`でスコープし、同じ世帯のメンバーは相手が記帳した収支も閲覧・編集・削除できる
  - `transactions.user_id`は所有者ではなく「記帳者」。スコープには使わない
  - ユーザーは必ず1つの世帯に属する（`users.household_id`は必須）。新規登録時に1人世帯を作る
  - プロフィール画像の登録・削除は本人のみ。取得は同じ世帯のメンバーまで
  - 理由: 夫婦がそれぞれのアカウントで1つの家計簿を共有するため（2026-10-02導入）

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
| `NOT_FOUND` | 404 | 対象が存在しない、または所有権のないレコード（別の世帯の収支など。**403は使わない**） |
| `INTERNAL_ERROR` | 500 | サーバエラー |

- `message`は利用者にそのまま表示できる日本語
- `fields`は`VALIDATION_FAILED`のときのみ含める。キーはリクエストのフィールド名（camelCase）
- 理由: フロントの`errors.ts`が依存する形を明示し、テスト（`ApiErrorFormatTest`）で固定するため。フレームワーク既定の形に暗黙依存していると、Laravelの更新や例外の種類の違いで形が変わったとき、フロントが静かに壊れる
- 再検討条件: 外部に公開するAPIを出す場合（RFC 9457準拠を検討する）

### エディタ向けの型情報（laravel-ide-helper）

- `barryvdh/laravel-ide-helper`を開発用依存として入れている。`composer install`後や
  モデル・マイグレーションを変更したら再生成する

  ```
  php artisan ide-helper:generate   # ファサードとEloquentのスタブ
  php artisan ide-helper:models --nowrite   # モデルの@property / @method
  ```

  `ide-helper:models`はDBのスキーマを読むため、MySQLを起動した状態で実行する

- 理由: `Transaction::create()`のようなEloquentの静的呼び出しは`Model`に実体が無く、
  `__callStatic`で転送される。LarastanはこれをPHPStan拡張で解決するが、
  エディタのPHP LSP（Intelephense）はLaravel固有の知識を持たないため
  「Method "create" does not exist」と誤検出する。
  生成物の`\Eloquent`スタブを継承させることで解決する
- **診断を無効化して黙らせない。** 無効化すると`crate`のような本物のタイプミスも
  検出されなくなる（2026-09-19に実際に発生した）
- 生成物（`_ide_helper.php` / `_ide_helper_models.php` / `.phpstorm.meta.php`）は
  `.gitignore`に入れ、`pint.json`の`notName`で整形対象からも外す。
  `phpstan.neon`の解析対象はapp/config/database/routes/testsなので元から対象外

### 固定値はPHPのenumを単一の定義元にする

- 取りうる値が決まっている項目は`app/Enums/`にbacked enumを定義し、**そこを唯一の定義元にする**
  （例: `TransactionType`。`income` / `expense`）
- 文字列リテラルを各所に書かない。マイグレーションの`enum()`列、モデルの`casts()`、
  FormRequestの`Rule::enum()`、Seeder、Factory、Repositoryはすべてenumを参照する
- モデルのクラスPHPDocに`@property`でキャスト後の型を書く。書かないとLarastanが
  DBスキーマ由来の`string`と推論し、enumとの比較を「常にtrue」と誤検出する
- **enumにキャストした属性を文字列と比較しない。** `$model->type !== 'income'`は常にtrueになる。
  比較相手も`TransactionType::tryFrom()`等でenumへ揃える
  （2026-09-19、`CreateTransactionRequest::withValidator()`で実際に踏んだ）
- 理由: 値の定義が散らばると、追加・変更のたびに全箇所を追う必要があり、漏れても気づけない
- 再検討条件: なし

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
  - 当初SQLiteのインメモリDBを採用したが、engine差で実際に2件の食い違いが出たため2026-09-19に変更した
    （`YEAR()`が動かない / `enum`列の`ORDER BY`の結果が逆になる）
  - `DB_HOST`は環境側で与える。コンテナ内は`mysql`、ホストとCIは`127.0.0.1`
    （ホストから実行する場合は`DB_HOST=127.0.0.1 php artisan test`）
  - 再検討条件: 本番のDBを変更する場合
- **テストは必ずテスト用DB（`testing`）で実行する。** `tests/TestCase.php`が接続先を確認し、`testing`以外なら`RefreshDatabase`の前に例外で止める
  - 開発用`docker-compose.yml`のbackendに**`env_file`を戻さない**。`.env`の値がコンテナの環境変数になると、
    `phpunit.xml`の`<env>`（`force`なし）は既存の環境変数を上書きしないため、テスト用の設定がすべて無効になる
  - 2026-09-27に実際に発生した。`make test`が開発用DB`kakeibo`を`RefreshDatabase`で作り直し、開発データが消えた。
    同時に`APP_ENV`・`SESSION_DRIVER`・`SANCTUM_STATEFUL_DOMAINS`も開発用の値のままで、ローカルでのみ2件失敗していた
  - `.env`はマウント経由でLaravelが自分で読み込むため、`env_file`が無くても開発時の動作は変わらない
    （本番用`docker-compose.prod.yml`はイメージに`.env`を含めないため`env_file`が必要。こちらはテストを実行しない）
- 最低限の観点:
  - 正常系のレスポンス形（Resourceが定義した通りのキーが返るか）
  - 未ログイン時に401（`UNAUTHENTICATED`）
  - 所有権のないレコード（別の世帯の収支など）へのアクセスが404（`NOT_FOUND`）
  - バリデーション失敗が422（`VALIDATION_FAILED`）で`fields`を含む
- 理由: 現状APIの仕様書がフロントの手書き`types.ts`しかなく、実レスポンスとの乖離を検知できない。コントローラ再編のようなリファクタや機能追加（世帯の導入など）で、認可やレスポンスの形が壊れていないことを保証する安全網になる
- 再検討条件: なし

### 生SQLの扱い

- DBは**MySQL 8.4 を前提とする**（開発・本番・テストで同じ）。PostgreSQL への移行計画は2026-09-30に取り下げた
- 生SQL（`selectRaw` / `orderByRaw` 等）を足すときは、**その結果をFeatureテストで押さえる**。
  テストも本番と同じMySQLで動くため、テストが通れば本番でも成立する
- `enum`列の`ORDER BY`は、並び順を`CASE`で明示する。MySQLの定義順に暗黙依存すると、
  enumの定義を並べ替えたとき黙って順序が変わるため
- 再検討条件: 本番のDBを変更する場合

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
