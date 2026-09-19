import { AxiosError } from "axios";

// バックエンドが返すエラーコード（backend/CLAUDE.mdの「エラーレスポンス」参照）
export type ApiErrorCode =
  "VALIDATION_FAILED" | "UNAUTHENTICATED" | "NOT_FOUND" | "INTERNAL_ERROR";

// エラーレスポンスの形。fieldsはVALIDATION_FAILEDのときだけ含まれる
type ApiError = {
  code: ApiErrorCode;
  message: string;
  fields?: Record<string, string[]>;
};

// レスポンスの構造を解釈するのはこのファイルだけにする（各コンポーネントで直接読まない）
function getApiError(error: unknown): ApiError | null {
  if (!(error instanceof AxiosError)) {
    return null;
  }

  const data = error.response?.data as { error?: ApiError } | undefined;

  return data?.error ?? null;
}

// axiosのエラーからユーザーに表示するメッセージを取り出す
export function getErrorMessage(error: unknown): string {
  const apiError = getApiError(error);

  if (apiError) {
    // バリデーションエラーの場合は各項目のメッセージをまとめて表示する
    if (apiError.fields) {
      return Object.values(apiError.fields).flat().join("\n");
    }
    return apiError.message;
  }

  return "エラーが発生しました。もう一度お試しください。";
}

// エラーの種類で処理を分けたい場合に使う
export function getErrorCode(error: unknown): ApiErrorCode | null {
  return getApiError(error)?.code ?? null;
}
