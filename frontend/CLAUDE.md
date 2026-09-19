@AGENTS.md

## フロントエンドの構成方針

- ルートガードは`middleware.ts`ではなく**`proxy.ts`**を使う（Next.js 16で`middleware`は非推奨・`proxy`に名称変更されたため。ファイル名・エクスポート関数名ともに`proxy`）
  - `proxy.ts`はセッションクッキーの有無を見る簡易ガードに留める。クッキーはローテーションされても残ることがあるため、「クッキーあり→ログイン画面から一覧へ強制リダイレクト」のような逆方向のリダイレクトはproxy側ではやらない（ログアウト後にログイン画面へ戻れなくなるバグの原因になった）。真の認証状態はクライアント側で`/api/get-user`を叩いて判定し、401時は`useEffect`やaxiosのレスポンスインターセプターでリダイレクトする。
- ディレクトリ構成: `src/hooks/`（TanStack Queryのカスタムフック）, `src/lib/`（`api-client.ts`, `types.ts`, `errors.ts`など）, `src/components/ui/`（共通UIコンポーネント）
- **`app/{route}/page.tsx`は薄いラッパーに留め、実装は`src/components/{feature}/{Feature}Page.tsx`に切り出す**
  - `page.tsx`は`export default function Page() { return <{Feature}Page />; }`のみを持ち、状態やイベントハンドラを持たない（Server Componentのままにできる）
  - 実装本体（`"use client"`を含む）は`src/components/{feature}/{Feature}Page.tsx`に置く。命名例: `src/components/login/LoginPage.tsx`, `src/components/transactions/TransactionsPage.tsx`
  - そのページ内でのみ使う下位コンポーネントも同じ`src/components/{feature}/`配下に置く（例: `TransactionFilters.tsx`, `TransactionForm.tsx`, `TransactionList.tsx`）
- **`window.confirm`/`window.alert`などブラウザネイティブのダイアログは使わない**。アプリ内のカスタムモーダル/ダイアログコンポーネントで代替する。
  - 理由: ブラウザ自動化ツール（Claude in Chrome等）がネイティブダイアログでフリーズし、手動でのダイアログ解除が必要になった実例があるため。ユーザー体験の観点でも独自スタイルの方が望ましい。
- ライトテーマに固定する（OS/ブラウザのダークモード設定に自動追従させない）。UIコンポーネントがダークモード非対応のまま`prefers-color-scheme`の自動切り替えを残すと、背景が黒くなり視認性が崩れる不具合が起きた。

## APIとの型の対応

- `src/lib/types.ts`はAPIレスポンスの形をそのまま写したものとして扱う。**キーはcamelCase**（ルート`CLAUDE.md`の「API境界の命名規則」参照）
- バックエンドのResource（`backend/app/Http/Resources/`）を変更したら、必ず`types.ts`も同時に直す。片方だけの変更はしない
- エラーレスポンスは`{ error: { code, message, fields } }`形式。`src/lib/errors.ts`がこの形を解釈する唯一の場所とし、各コンポーネントでレスポンスの構造を直接読まない

## コード整形

- Prettierを使う（`npm run format`）。設定はリポジトリ直下の`.prettierrc`
- ESLintは`npm run lint`。Prettierと役割を分け、ESLintに整形ルールを持たせない
