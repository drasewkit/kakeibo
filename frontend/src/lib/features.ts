// 新規登録の受付可否（ビルド時の環境変数で決まる）。
// 一般公開するまでは無効にしておく。バックエンドの REGISTRATION_ENABLED と揃えること
export const REGISTRATION_ENABLED =
  process.env.NEXT_PUBLIC_REGISTRATION_ENABLED === "true";
