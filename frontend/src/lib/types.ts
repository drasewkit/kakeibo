export type TransactionType = "income" | "expense";

export type User = {
  id: number;
  name: string;
  email: string;
};

export type Category = {
  id: number;
  name: string;
  type: TransactionType;
};

// バックエンドのTransactionリソース（APIレスポンス）に対応する型
export type Transaction = {
  id: number;
  userId: number;
  categoryId: number | null;
  category: Category | null;
  type: TransactionType;
  amount: number;
  date: string;
  memo: string | null;
  // 画像が添付されているかどうか。実際の画像データは/transactions/get-imageで別途取得する
  hasImage: boolean;
  createdAt: string;
  updatedAt: string;
};

// ページネーションの共通項目。バックエンドのResourceが返す項目と一対一で対応する
// （Laravelのページネータが吐くlinksやpath等は公開していない）
export type PaginatedResponse<T> = {
  data: T[];
  currentPage: number;
  lastPage: number;
  total: number;
};

// 対象期間の収入・支出・差引（一覧の種別・カテゴリ絞り込みには影響されない）
export type TransactionSummary = {
  income: number;
  expense: number;
  balance: number;
};

// 収支一覧取得APIのレスポンス（ページネーションされた一覧 + 期間合計 + 登録済み年一覧）
export type TransactionListResponse = PaginatedResponse<Transaction> & {
  summary: TransactionSummary;
  // 収支が1件でも存在する年の一覧（降順）。年セレクターの選択肢に使う
  availableYears: number[];
};

// 収支一覧の絞り込み条件（すべて任意）
export type TransactionFilters = {
  year?: number;
  month?: number;
  type?: TransactionType;
  categoryId?: number;
  // 1始まりのページ番号。未指定時はAPI側で1として扱われる
  page?: number;
};

// 収支の登録・更新フォームの入力値
export type TransactionInput = {
  type: TransactionType;
  categoryId: number | null;
  amount: number;
  date: string;
  memo: string | null;
};
