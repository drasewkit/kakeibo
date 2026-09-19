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
import { useRegister, useUser } from "@/hooks/useAuth";
import { getErrorMessage } from "@/lib/errors";

// 新規登録画面（名前・メールアドレス・パスワードによるアカウント作成）
export function RegisterPage() {
  const router = useRouter();
  const { data: user } = useUser();
  const register = useRegister();
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");

  // すでにログイン済みの場合は一覧画面へリダイレクト
  useEffect(() => {
    if (user) {
      router.replace("/transactions");
    }
  }, [user, router]);

  function handleSubmit(event: SubmitEvent) {
    event.preventDefault();
    register.mutate(
      {
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
      },
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
        py: 4,
      }}
    >
      <Paper variant="outlined" sx={{ p: 4, width: "100%" }}>
        <Typography variant="h5" component="h1" sx={{ fontWeight: 600, mb: 3 }}>
          新規登録
        </Typography>
        <Box component="form" onSubmit={handleSubmit} noValidate>
          <Stack spacing={2}>
            <TextField
              label="名前"
              required
              fullWidth
              value={name}
              onChange={(e) => setName(e.target.value)}
            />
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
              slotProps={{ htmlInput: { minLength: 8 } }}
              helperText="8文字以上で入力してください"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
            />
            <TextField
              label="パスワード（確認）"
              type="password"
              required
              fullWidth
              slotProps={{ htmlInput: { minLength: 8 } }}
              value={passwordConfirmation}
              onChange={(e) => setPasswordConfirmation(e.target.value)}
            />
            {register.isError && (
              <Alert severity="error" sx={{ whiteSpace: "pre-line" }}>
                {getErrorMessage(register.error)}
              </Alert>
            )}
            <Button
              type="submit"
              variant="contained"
              size="large"
              disabled={register.isPending}
            >
              {register.isPending ? "登録中..." : "登録する"}
            </Button>
          </Stack>
        </Box>
        <Typography variant="body2" color="text.secondary" sx={{ mt: 3 }}>
          すでにアカウントをお持ちの方は{" "}
          <Link component={NextLink} href="/login" sx={{ fontWeight: 600 }}>
            ログイン
          </Link>
        </Typography>
      </Paper>
    </Container>
  );
}
