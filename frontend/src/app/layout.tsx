import type { Metadata, Viewport } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import "./globals.css";
import { Providers } from "./providers";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "kakeibo",
  description: "収支を記録・管理する家計簿アプリ",
  // iOSはマニフェストのicons/nameを読まないため、ホーム画面追加用の指定を別途行う
  appleWebApp: {
    capable: true,
    title: "kakeibo",
    statusBarStyle: "default",
  },
};

export const viewport: Viewport = {
  // standalone表示時のステータスバー・アドレスバーの色
  themeColor: "#367FAE",
  // ノッチ/ホームバーのある端末で画面全体を使う（余白はsafe-area-insetで確保する）
  viewportFit: "cover",
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="ja" className={`${geistSans.variable} ${geistMono.variable}`}>
      <body>
        <Providers>{children}</Providers>
      </body>
    </html>
  );
}
