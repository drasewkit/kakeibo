"use client";

import AppBar from "@mui/material/AppBar";
import Button from "@mui/material/Button";
import Toolbar from "@mui/material/Toolbar";
import Typography from "@mui/material/Typography";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useLogout, useUser } from "@/hooks/useAuth";
import { UserAvatar } from "./UserAvatar";

// ログイン後の各画面で共通のヘッダー（アプリ名・アイコンとユーザー名・ログアウトボタン）
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
        {/* アイコンとユーザー名から設定画面へ移動する（スマホ幅ではアイコンのみ） */}
        {user && (
          <Button
            component={Link}
            href="/settings"
            color="inherit"
            aria-label="設定"
            startIcon={<UserAvatar user={user} size={28} />}
            sx={{
              mr: 1,
              color: "text.secondary",
              minWidth: 0,
              "& .MuiButton-startIcon": { mr: { xs: 0, sm: 1 }, ml: 0 },
            }}
          >
            <Typography
              component="span"
              sx={{ display: { xs: "none", sm: "inline" } }}
            >
              {user.name}
            </Typography>
          </Button>
        )}
        <Button color="inherit" onClick={handleLogout}>
          ログアウト
        </Button>
      </Toolbar>
    </AppBar>
  );
}
