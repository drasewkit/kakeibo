"use client";

import Alert from "@mui/material/Alert";
import Box from "@mui/material/Box";
import Button from "@mui/material/Button";
import Container from "@mui/material/Container";
import Link from "@mui/material/Link";
import Paper from "@mui/material/Paper";
import Stack from "@mui/material/Stack";
import TextField from "@mui/material/TextField";
import Typography from "@mui/material/Typography";
import NextLink from "next/link";
import { useRouter } from "next/navigation";
import { SubmitEvent, useEffect, useState } from "react";
import { useLogin, useUser } from "@/hooks/useAuth";
import { getErrorMessage } from "@/lib/errors";

// ログイン画面（メールアドレス・パスワードによる認証）
export function LoginPage() {
  const router = useRouter();
  const { data: user } = useUser();
  const login = useLogin();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

  // すでにログイン済みの場合は一覧画面へリダイレクト
  // （proxy.ts側ではこの判定を行わないため、こちらで担う）
  useEffect(() => {
    if (user) {
      router.replace("/transactions");
    }
  }, [user, router]);

  function handleSubmit(event: SubmitEvent) {
    event.preventDefault();
    login.mutate(
      { email, password },
      { onSuccess: () => router.push("/transactions") },
    );
  }

  return (
    <Container
      maxWidth="xs"
      sx={{
        minHeight: "100dvh",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
      }}
    >
      <Paper variant="outlined" sx={{ p: 4, width: "100%" }}>
        <Typography variant="h5" component="h1" sx={{ fontWeight: 600, mb: 3 }}>
          ログイン
        </Typography>
        <Box component="form" onSubmit={handleSubmit} noValidate>
          <Stack spacing={2}>
            <TextField
              label="メールアドレス"
              type="email"
              required
              fullWidth
              value={email}
              onChange={(e) => setEmail(e.target.value)}
            />
            <TextField
              label="パスワード"
              type="password"
              required
              fullWidth
              value={password}
              onChange={(e) => setPassword(e.target.value)}
            />
            {login.isError && (
              <Alert severity="error" sx={{ whiteSpace: "pre-line" }}>
                {getErrorMessage(login.error)}
              </Alert>
            )}
            <Button
              type="submit"
              variant="contained"
              size="large"
              disabled={login.isPending}
            >
              {login.isPending ? "ログイン中..." : "ログイン"}
            </Button>
          </Stack>
        </Box>
        <Typography variant="body2" color="text.secondary" sx={{ mt: 3 }}>
          アカウントをお持ちでない方は{" "}
          <Link component={NextLink} href="/register" sx={{ fontWeight: 600 }}>
            新規登録
          </Link>
        </Typography>
      </Paper>
    </Container>
  );
}
