"use client";

import CloseIcon from "@mui/icons-material/Close";
import Box from "@mui/material/Box";
import Dialog from "@mui/material/Dialog";
import IconButton from "@mui/material/IconButton";
import Skeleton from "@mui/material/Skeleton";
import type { SxProps, Theme } from "@mui/material/styles";
import { useEffect, useState } from "react";
import { apiClient } from "@/lib/api-client";

// 収支に添付された画像を表示する。
// 画像取得には認証（Sanctumのセッションクッキー）が必要なため、
// <img src="...">に直接APIのURLを指定するのではなく、axios経由でblobとして取得してから
// オブジェクトURLに変換して表示する
export function TransactionImage({
  transactionId,
  sx,
}: {
  transactionId: number;
  sx?: SxProps<Theme>;
}) {
  const [imageUrl, setImageUrl] = useState<string | null>(null);
  const [enlarged, setEnlarged] = useState(false);

  useEffect(() => {
    let objectUrl: string | null = null;
    let cancelled = false;

    apiClient
      .get("/api/transactions/get-transaction-image", {
        params: { transaction_id: transactionId },
        responseType: "blob",
      })
      .then(({ data }) => {
        if (cancelled) return;
        objectUrl = URL.createObjectURL(data);
        setImageUrl(objectUrl);
      });

    return () => {
      cancelled = true;
      if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
      }
    };
  }, [transactionId]);

  // Stack等のflexコンテナ内に置かれたとき、デフォルトのalign-items: stretchで
  // 横幅いっぱいに引き伸ばされてしまうのを防ぐ（呼び出し側のsxで上書き可能）
  if (!imageUrl) {
    return (
      <Skeleton variant="rounded" sx={{ alignSelf: "flex-start", ...sx }} />
    );
  }

  return (
    <>
      <Box
        component="img"
        src={imageUrl}
        alt="添付画像"
        onClick={() => setEnlarged(true)}
        sx={{
          objectFit: "cover",
          display: "block",
          alignSelf: "flex-start",
          cursor: "pointer",
          ...sx,
        }}
      />
      {/* 画像の拡大表示ダイアログ */}
      <Dialog open={enlarged} onClose={() => setEnlarged(false)} maxWidth="lg">
        <IconButton
          onClick={() => setEnlarged(false)}
          aria-label="閉じる"
          sx={{
            position: "absolute",
            top: 8,
            right: 8,
            bgcolor: "background.paper",
            "&:hover": { bgcolor: "background.paper" },
          }}
        >
          <CloseIcon />
        </IconButton>
        <Box
          component="img"
          src={imageUrl}
          alt="添付画像（拡大）"
          sx={{
            display: "block",
            width: "100%",
            maxHeight: "85vh",
            objectFit: "contain",
          }}
        />
      </Dialog>
    </>
  );
}
