import { NextRequest, NextResponse } from "next/server";

const PUBLIC_PATHS = ["/login", "/register"];
const SESSION_COOKIE_NAME = "kakeibo-session";

// ルートガード（Next.js 16ではmiddleware.tsではなくproxy.tsが正式名称）。
// セッションクッキーの有無だけを見る簡易チェックであり、実際の認証状態は
// 保証しない（セッション切れでもクッキー自体は残るため）。
// 未ログイン状態でのフラッシュ表示を防ぐためのUX目的のガードで、
// 本当の認証チェックはクライアント側の/api/get-user呼び出し（401時リダイレクト）が担う。
export function proxy(request: NextRequest) {
  const hasSession = request.cookies.has(SESSION_COOKIE_NAME);
  const isPublicPath = PUBLIC_PATHS.some((path) =>
    request.nextUrl.pathname.startsWith(path),
  );

  // 未ログインで保護ページにアクセスした場合のみログイン画面へ強制遷移させる
  // （逆に「クッキーがあるからログイン画面から一覧へ」は行わない。
  // ログアウト直後もクッキー自体は残るため、ログイン画面に戻れなくなるバグになる）
  if (!hasSession && !isPublicPath) {
    return NextResponse.redirect(new URL("/login", request.url));
  }

  return NextResponse.next();
}

export const config = {
  // 静的アセットはガードの対象外にする。特にPWAのマニフェストとアイコンは
  // 未ログイン状態（かつクッキーを送らない条件）で取得されるため、
  // ここで除外しないと/loginへリダイレクトされホーム画面へのインストールが失敗する
  matcher: [
    "/((?!_next/static|_next/image|.*\\.(?:png|svg|ico|webmanifest)$).*)",
  ],
};
