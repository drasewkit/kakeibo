"use client";

import AddPhotoAlternateOutlinedIcon from "@mui/icons-material/AddPhotoAlternateOutlined";
import Alert from "@mui/material/Alert";
import Button from "@mui/material/Button";
import Stack from "@mui/material/Stack";
import Typography from "@mui/material/Typography";
import { type ChangeEvent, useState } from "react";
import { UserAvatar } from "@/components/ui/UserAvatar";
import { useDeleteAvatar, useUploadAvatar } from "@/hooks/useAvatar";
import { getErrorMessage } from "@/lib/errors";
import { MAX_IMAGE_BYTES, shrinkImage } from "@/lib/image";
import type { User } from "@/lib/types";

// アイコンとして小さく表示するだけなので、長辺512pxまで縮小してから送る
const AVATAR_LONG_EDGE = 512;

// 自分のプロフィール画像の登録・変更・削除
export function AvatarSettings({ user }: { user: User }) {
  const upload = useUploadAvatar();
  const remove = useDeleteAvatar();
  const [isProcessing, setIsProcessing] = useState(false);
  const [sizeError, setSizeError] = useState(false);

  // 選んだ画像を縮小してすぐに登録する
  async function handleChange(event: ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0];
    // 同じファイルを選び直しても change が発火するよう、選択状態を空に戻す
    event.target.value = "";
    if (!file) return;

    setIsProcessing(true);
    setSizeError(false);
    remove.reset();
    try {
      const shrunk = await shrinkImage(file, AVATAR_LONG_EDGE);
      if (shrunk.size > MAX_IMAGE_BYTES) {
        setSizeError(true);
        return;
      }
      upload.mutate(shrunk);
    } finally {
      setIsProcessing(false);
    }
  }

  function handleDelete() {
    upload.reset();
    setSizeError(false);
    remove.mutate();
  }

  const isBusy = isProcessing || upload.isPending || remove.isPending;
  const error = upload.error ?? remove.error;

  return (
    <Stack spacing={2}>
      <Typography variant="h6" component="h2" sx={{ fontWeight: 700 }}>
        プロフィール画像
      </Typography>
      <Stack direction="row" spacing={3} sx={{ alignItems: "center" }}>
        <UserAvatar user={user} size={80} />
        <Stack spacing={1} sx={{ alignItems: "flex-start" }}>
          <Button
            component="label"
            variant="outlined"
            size="small"
            startIcon={<AddPhotoAlternateOutlinedIcon />}
            disabled={isBusy}
          >
            {isBusy
              ? "処理中..."
              : user.hasAvatar
                ? "画像を変更"
                : "画像を選択"}
            <input
              type="file"
              accept="image/*"
              hidden
              disabled={isBusy}
              onChange={handleChange}
            />
          </Button>
          {user.hasAvatar && (
            <Button
              size="small"
              color="error"
              onClick={handleDelete}
              disabled={isBusy}
            >
              画像を削除
            </Button>
          )}
        </Stack>
      </Stack>
      <Typography variant="body2" color="text.secondary">
        収支一覧で、誰が記帳したかを表すアイコンとして使います。
      </Typography>
      {sizeError && (
        <Alert severity="error">画像は5MB以下にしてください。</Alert>
      )}
      {Boolean(error) && (
        <Alert severity="error" sx={{ whiteSpace: "pre-line" }}>
          {getErrorMessage(error)}
        </Alert>
      )}
    </Stack>
  );
}
