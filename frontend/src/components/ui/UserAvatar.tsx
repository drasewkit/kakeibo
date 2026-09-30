"use client";

import Avatar from "@mui/material/Avatar";
import type { SxProps, Theme } from "@mui/material/styles";
import { useEffect, useMemo } from "react";
import { useAvatarImage } from "@/hooks/useAvatar";
import type { User } from "@/lib/types";

// ユーザーのアイコン。プロフィール画像が無い・取得できない場合は名前の頭文字を表示する
export function UserAvatar({
  user,
  size = 32,
  sx,
}: {
  user: Pick<User, "id" | "name" | "avatarVersion">;
  size?: number;
  sx?: SxProps<Theme>;
}) {
  const { data: blob } = useAvatarImage(user.id, user.avatarVersion);
  // 取得したblobをオブジェクトURLに変換し、不要になったら解放する
  const imageUrl = useMemo(
    () => (blob ? URL.createObjectURL(blob) : null),
    [blob],
  );
  useEffect(() => {
    return () => {
      if (imageUrl) URL.revokeObjectURL(imageUrl);
    };
  }, [imageUrl]);

  return (
    <Avatar
      src={imageUrl ?? undefined}
      alt={user.name}
      sx={{ width: size, height: size, fontSize: size * 0.45, ...sx }}
    >
      {user.name.slice(0, 1)}
    </Avatar>
  );
}
