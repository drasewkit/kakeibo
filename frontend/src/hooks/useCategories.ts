import { useQuery } from "@tanstack/react-query";
import { apiClient } from "@/lib/api-client";
import type { Category } from "@/lib/types";

// カテゴリ一覧を取得する。ほぼ不変のマスタデータなのでstaleTimeを長めに設定している
export function useCategories() {
  return useQuery<Category[]>({
    queryKey: ["categories"],
    queryFn: async () => {
      const { data } = await apiClient.get<Category[]>(
        "/api/categories/get-list",
      );
      return data;
    },
    staleTime: 1000 * 60 * 60,
  });
}
