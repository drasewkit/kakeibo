import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { apiClient } from "@/lib/api-client";
import type {
  Transaction,
  TransactionFilters,
  TransactionInput,
  TransactionListResponse,
} from "@/lib/types";

// 収支一覧を取得する（年月・種別・カテゴリで絞り込み）。
// filtersが変わるたびに別クエリとして扱われ、自動で再フェッチされる。
// レスポンスには一覧と合わせて対象期間の収入・支出・差引の集計（summary）も含まれる
export function useTransactions(filters: TransactionFilters) {
  return useQuery<TransactionListResponse>({
    queryKey: ["transactions", filters],
    queryFn: async () => {
      const { data } = await apiClient.get<TransactionListResponse>(
        "/api/transactions/get-list",
        { params: filters },
      );
      return data;
    },
  });
}

export function useCreateTransaction() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (input: TransactionInput) => {
      const { data } = await apiClient.post<Transaction>(
        "/api/transactions/create",
        input,
      );
      return data;
    },
    onSuccess: () => {
      // 一覧キャッシュを無効化し、最新の状態を再取得させる
      queryClient.invalidateQueries({ queryKey: ["transactions"] });
    },
  });
}

export function useUpdateTransaction() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({
      id,
      input,
    }: {
      id: number;
      input: TransactionInput;
    }) => {
      // PUT/PATCHではなくPOSTに統一する方針のため、更新対象のIDはボディに含める
      const { data } = await apiClient.post<Transaction>(
        "/api/transactions/update",
        { transactionId: id, ...input },
      );
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["transactions"] });
    },
  });
}

export function useDeleteTransaction() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: number) => {
      // DELETEではなくPOSTに統一する方針のため、削除対象のIDはボディに含める
      await apiClient.post("/api/transactions/delete", {
        transactionId: id,
      });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["transactions"] });
    },
  });
}

// 収支に画像（レシート等）を添付する。既に添付済みの場合は差し替えになる
export function useUploadTransactionImage() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, image }: { id: number; image: File }) => {
      const formData = new FormData();
      formData.append("transactionId", String(id));
      formData.append("image", image);
      const { data } = await apiClient.post<Transaction>(
        "/api/transactions/upload-image",
        formData,
      );
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["transactions"] });
    },
  });
}

// 収支の添付画像を削除する（収支自体は削除しない）
export function useDeleteTransactionImage() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: number) => {
      const { data } = await apiClient.post<Transaction>(
        "/api/transactions/delete-image",
        { transactionId: id },
      );
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["transactions"] });
    },
  });
}
