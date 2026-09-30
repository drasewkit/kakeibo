"use client";

import ArrowBackIcon from "@mui/icons-material/ArrowBack";
import Button from "@mui/material/Button";
import Card from "@mui/material/Card";
import CardContent from "@mui/material/CardContent";
import CircularProgress from "@mui/material/CircularProgress";
import Container from "@mui/material/Container";
import Stack from "@mui/material/Stack";
import Typography from "@mui/material/Typography";
import Link from "next/link";
import { useUser } from "@/hooks/useAuth";
import { AvatarSettings } from "./AvatarSettings";

// 設定画面（現在はプロフィール画像のみ。世帯の設定も今後ここに追加する）
export function SettingsPage() {
  const { data: user, isPending } = useUser();

  return (
    <Container maxWidth="sm" sx={{ py: 4 }}>
      <Stack spacing={3}>
        <Stack direction="row" spacing={1} sx={{ alignItems: "center" }}>
          <Button
            component={Link}
            href="/transactions"
            color="inherit"
            startIcon={<ArrowBackIcon />}
          >
            一覧へ戻る
          </Button>
        </Stack>
        <Typography variant="h5" component="h1" sx={{ fontWeight: 700 }}>
          設定
        </Typography>
        <Card variant="outlined">
          <CardContent>
            {isPending || !user ? (
              <CircularProgress size={24} />
            ) : (
              <AvatarSettings user={user} />
            )}
          </CardContent>
        </Card>
      </Stack>
    </Container>
  );
}
