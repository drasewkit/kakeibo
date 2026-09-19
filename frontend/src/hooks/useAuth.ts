import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { apiClient, ensureCsrfCookie } from "@/lib/api-client";
import type { User } from "@/lib/types";

// ログイン中ユーザーを取得する。未認証(401)の場合はnullを返す（エラー扱いにしない）
export function useUser() {
  return useQuery<User | null>({
    queryKey: ["user"],
    queryFn: async () => {
      try {
        const { data } = await apiClient.get<User>("/api/auth/get-user");
        return data;
      } catch {
        return null;
      }
    },
    retry: false,
  });
}

export function useLogin() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (input: { email: string; password: string }) => {
      // ログイン前に必ずCSRFクッキーを取得しておく
      await ensureCsrfCookie();
      const { data } = await apiClient.post<User>("/api/auth/login", input);
      return data;
    },
    onSuccess: (user) => {
      // 取得済みのユーザー情報をキャッシュに反映し、再フェッチ待ちなしで画面に反映する
      queryClient.setQueryData(["user"], user);
    },
  });
}

export function useRegister() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (input: {
      name: string;
      email: string;
      password: string;
      passwordConfirmation: string;
    }) => {
      await ensureCsrfCookie();
      const { data } = await apiClient.post<User>("/api/auth/register", input);
      return data;
    },
    onSuccess: (user) => {
      // 登録と同時にログイン状態になるため、そのままユーザー情報をキャッシュする
      queryClient.setQueryData(["user"], user);
    },
  });
}

export function useLogout() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async () => {
      await apiClient.post("/api/auth/logout");
    },
    onSuccess: () => {
      queryClient.setQueryData(["user"], null);
    },
  });
}
