import type { MetadataRoute } from "next";

// PWAのWebアプリマニフェスト。ホーム画面へのインストール時の名称・配色・アイコンを定義する。
// Next.jsのファイル規約なので、置くだけで<link rel="manifest">が自動で出力される。
export default function manifest(): MetadataRoute.Manifest {
  return {
    // インストール済みPWAの同一性はこのidで決まる。固定しておくことで、
    // 将来name/short_nameを変更しても別アプリとして二重インストールされない
    id: "/",
    name: "kakeibo",
    short_name: "kakeibo",
    description: "収支を記録・管理する家計簿アプリ",
    lang: "ja",
    // 起動直後に記帳できるよう一覧画面を開く（未ログイン時はproxy.tsが/loginへ回す）
    start_url: "/transactions",
    scope: "/",
    // ブラウザのUIを出さずネイティブアプリのように表示する
    display: "standalone",
    orientation: "portrait",
    background_color: "#FBFDFE",
    theme_color: "#367FAE",
    icons: [
      {
        src: "/icon-192.png",
        sizes: "192x192",
        type: "image/png",
        purpose: "any",
      },
      {
        src: "/icon-512.png",
        sizes: "512x512",
        type: "image/png",
        purpose: "any",
      },
      // Androidのアイコン切り抜き（丸・角丸など）に対応する版
      {
        src: "/icon-maskable-512.png",
        sizes: "512x512",
        type: "image/png",
        purpose: "maskable",
      },
    ],
  };
}
