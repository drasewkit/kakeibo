import axios from "axios";

// Laravel(Sanctum SPA認証)向けのAPIクライアント。
// withCredentials: セッションクッキーを送受信する
// withXSRFToken: XSRF-TOKENクッキーをX-XSRF-TOKENヘッダーへ自動変換する（CSRF対策）
export const apiClient = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL,
  withCredentials: true,
  withXSRFToken: true,
  headers: { Accept: "application/json" },
});

// 401（未認証）を受け取ったら強制的にログイン画面へ遷移させる。
// proxy.tsのクッキー有無チェックだけでは「クッキーはあるが実際には
// 認証切れ」というケースを防げないため、こちらが最終防衛ライン。
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    const isAuthCheck = error.config?.url === "/api/auth/get-user";
    if (
      error.response?.status === 401 &&
      !isAuthCheck &&
      typeof window !== "undefined" &&
      window.location.pathname !== "/login"
    ) {
      window.location.href = "/login";
    }
    return Promise.reject(error);
  },
);

// ログイン・登録の前に一度呼び、XSRF-TOKENクッキーを取得しておく
export async function ensureCsrfCookie() {
  await apiClient.get("/sanctum/csrf-cookie");
}
