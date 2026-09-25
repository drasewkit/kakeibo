"use client";

import AppBar from "@mui/material/AppBar";
import Button from "@mui/material/Button";
import Toolbar from "@mui/material/Toolbar";
import Typography from "@mui/material/Typography";
import { useRouter } from "next/navigation";
import { useLogout, useUser } from "@/hooks/useAuth";

// ログイン後の各画面で共通のヘッダー（アプリ名・ユーザー名・ログアウトボタン）
export function AppHeader() {
  const router = useRouter();
  const { data: user } = useUser();
  const logout = useLogout();

  function handleLogout() {
    logout.mutate(undefined, { onSuccess: () => router.push("/login") });
  }

  return (
    <AppBar position="static" color="transparent" elevation={0}>
      <Toolbar sx={{ borderBottom: 1, borderColor: "divider" }}>
        <Typography
          variant="h6"
          component="h1"
          sx={{ fontWeight: 700, flexGrow: 1 }}
        >
          kakeibo
        </Typography>
        {user && (
          <Typography color="text.secondary" sx={{ mr: 2 }}>
            {user.name}
          </Typography>
        )}
        <Button color="inherit" onClick={handleLogout}>
          ログアウト
        </Button>
      </Toolbar>
    </AppBar>
  );
}
