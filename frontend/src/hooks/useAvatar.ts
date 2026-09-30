import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { apiClient } from "@/lib/api-client";
import type { User } from "@/lib/types";

// プロフィール画像をblobとして取得する。
// 画像を差し替えるとavatarVersionが変わり、別のキャッシュとして取り直される
export function useAvatarImage(userId: number, avatarVersion: string | null) {
  return useQuery({
    queryKey: ["avatar", userId, avatarVersion],
    queryFn: async () => {
      const { data } = await apiClient.get<Blob>("/api/users/get-avatar", {
        params: { userId },
        responseType: "blob",
      });
      return data;
    },
    // 未登録のユーザーは取得しない（頭文字の表示にする）
    enabled: avatarVersion !== null,
    // 同じバージョンの画像は変わらないため、再取得しない
    staleTime: Infinity,
    retry: false,
  });
}

// 自分のプロフィール画像を登録する（登録済みなら差し替える）
export function useUploadAvatar() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (image: File) => {
      const formData = new FormData();
      formData.append("image", image);
      const { data } = await apiClient.post<User>(
        "/api/users/upload-avatar",
        formData,
      );
      return data;
    },
    onSuccess: (user) => {
      // ヘッダー等のアバター表示にすぐ反映する
      queryClient.setQueryData(["user"], user);
    },
  });
}

// 自分のプロフィール画像を削除する
export function useDeleteAvatar() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async () => {
      const { data } = await apiClient.post<User>("/api/users/delete-avatar");
      return data;
    },
    onSuccess: (user) => {
      queryClient.setQueryData(["user"], user);
    },
  });
}
