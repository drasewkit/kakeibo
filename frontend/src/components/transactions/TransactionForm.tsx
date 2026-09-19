"use client";

import AddPhotoAlternateOutlinedIcon from "@mui/icons-material/AddPhotoAlternateOutlined";
import CloseIcon from "@mui/icons-material/Close";
import Alert from "@mui/material/Alert";
import Box from "@mui/material/Box";
import Button from "@mui/material/Button";
import Dialog from "@mui/material/Dialog";
import DialogActions from "@mui/material/DialogActions";
import DialogContent from "@mui/material/DialogContent";
import DialogTitle from "@mui/material/DialogTitle";
import IconButton from "@mui/material/IconButton";
import MenuItem from "@mui/material/MenuItem";
import Stack from "@mui/material/Stack";
import TextField from "@mui/material/TextField";
import ToggleButton from "@mui/material/ToggleButton";
import ToggleButtonGroup from "@mui/material/ToggleButtonGroup";
import Typography from "@mui/material/Typography";
import { DatePicker } from "@mui/x-date-pickers/DatePicker";
import dayjs, { Dayjs } from "dayjs";
import { ChangeEvent, SubmitEvent, useEffect, useMemo, useState } from "react";
import { getErrorMessage } from "@/lib/errors";
import type {
  Category,
  Transaction,
  TransactionInput,
  TransactionType,
} from "@/lib/types";
import { TransactionImage } from "./TransactionImage";

function today() {
  return new Date().toISOString().slice(0, 10);
}

const IMAGE_PREVIEW_SX = { width: 120, height: 120, borderRadius: 1 };

// 収支の登録・編集フォーム（ダイアログ形式の共通コンポーネント）。
// initialValueがあれば編集、なければ新規登録として扱う（呼び出し側で判定）。
// 画像はimageパラメータで意図を伝える: undefined=変更なし, null=削除, File=新規添付/差し替え
export function TransactionForm({
  categories,
  initialValue,
  isSubmitting,
  error,
  onSubmit,
  onCancel,
}: {
  categories: Category[];
  initialValue?: Transaction;
  isSubmitting: boolean;
  error?: unknown;
  onSubmit: (input: TransactionInput, image?: File | null) => void;
  onCancel: () => void;
}) {
  const [type, setType] = useState<TransactionType>(
    initialValue?.type ?? "expense",
  );
  const [categoryId, setCategoryId] = useState<string>(
    initialValue?.category_id ? String(initialValue.category_id) : "",
  );
  const [amount, setAmount] = useState(
    initialValue ? String(initialValue.amount) : "",
  );
  const [date, setDate] = useState(initialValue?.date.slice(0, 10) ?? today());
  const [memo, setMemo] = useState(initialValue?.memo ?? "");
  const [imageFile, setImageFile] = useState<File | null>(null);
  const [imageRemoved, setImageRemoved] = useState(false);

  // 選択中の収支種別（収入/支出）に対応するカテゴリのみ選択肢に出す
  const filteredCategories = categories.filter((c) => c.type === type);
  // 既存の添付画像 or 新たに選択した画像のどちらかがあればプレビューを表示する
  const hasVisibleImage =
    Boolean(imageFile) || (Boolean(initialValue?.has_image) && !imageRemoved);

  // 選択中ファイルのプレビュー用オブジェクトURL。imageFileが変わるたびに再生成する
  const imagePreviewUrl = useMemo(
    () => (imageFile ? URL.createObjectURL(imageFile) : null),
    [imageFile],
  );
  // 差し替え・アンマウント時に前のオブジェクトURLを解放する
  useEffect(() => {
    return () => {
      if (imagePreviewUrl) URL.revokeObjectURL(imagePreviewUrl);
    };
  }, [imagePreviewUrl]);

  function handleImageChange(event: ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0];
    if (!file) return;
    setImageFile(file);
    setImageRemoved(false);
  }

  function handleImageRemove() {
    setImageFile(null);
    setImageRemoved(true);
  }

  function handleSubmit(event: SubmitEvent) {
    event.preventDefault();
    // 画像が変更されていない場合はundefined（変更なし）を渡す
    const image = imageFile ?? (imageRemoved ? null : undefined);
    onSubmit(
      {
        type,
        category_id: categoryId ? Number(categoryId) : null,
        amount: Number(amount),
        date,
        memo: memo || null,
      },
      image,
    );
  }

  return (
    <Dialog open onClose={onCancel} fullWidth maxWidth="xs">
      <DialogTitle sx={{ fontWeight: 600 }}>
        {initialValue ? "収支を編集" : "収支を登録"}
      </DialogTitle>
      <Stack component="form" onSubmit={handleSubmit} noValidate>
        <DialogContent>
          <Stack spacing={2.5}>
            <ToggleButtonGroup
              exclusive
              fullWidth
              color={type === "income" ? "success" : "error"}
              value={type}
              onChange={(_, value: TransactionType | null) => {
                if (!value) return;
                setType(value);
                setCategoryId("");
              }}
            >
              <ToggleButton value="expense">支出</ToggleButton>
              <ToggleButton value="income">収入</ToggleButton>
            </ToggleButtonGroup>

            <TextField
              select
              label="カテゴリ"
              fullWidth
              value={categoryId}
              onChange={(e) => setCategoryId(e.target.value)}
            >
              <MenuItem value="">未分類</MenuItem>
              {filteredCategories.map((category) => (
                <MenuItem key={category.id} value={category.id}>
                  {category.name}
                </MenuItem>
              ))}
            </TextField>

            <TextField
              label="金額（円）"
              type="number"
              required
              fullWidth
              slotProps={{ htmlInput: { min: 1, step: 1 } }}
              value={amount}
              onChange={(e) => setAmount(e.target.value)}
            />

            <DatePicker
              label="日付"
              format="YYYY/MM/DD"
              value={dayjs(date)}
              onChange={(newValue: Dayjs | null) => {
                if (newValue?.isValid()) {
                  setDate(newValue.format("YYYY-MM-DD"));
                }
              }}
              slotProps={{ textField: { required: true, fullWidth: true } }}
            />

            <TextField
              label="メモ"
              fullWidth
              multiline
              minRows={2}
              maxRows={6}
              placeholder="品目ごとに改行して入力できます"
              value={memo ?? ""}
              onChange={(e) => setMemo(e.target.value)}
            />

            <Stack spacing={1}>
              <Typography variant="body2" sx={{ color: "text.secondary" }}>
                画像（レシート等）
              </Typography>
              <Stack
                direction="row"
                spacing={1.5}
                sx={{ alignItems: "center" }}
              >
                {hasVisibleImage && (
                  <Box sx={{ position: "relative" }}>
                    {imagePreviewUrl ? (
                      <Box
                        component="img"
                        src={imagePreviewUrl}
                        alt="選択した画像"
                        sx={{ ...IMAGE_PREVIEW_SX, objectFit: "cover" }}
                      />
                    ) : (
                      initialValue && (
                        <TransactionImage
                          transactionId={initialValue.id}
                          sx={IMAGE_PREVIEW_SX}
                        />
                      )
                    )}
                    <IconButton
                      size="small"
                      onClick={handleImageRemove}
                      sx={{
                        position: "absolute",
                        top: -8,
                        right: -8,
                        bgcolor: "background.paper",
                        boxShadow: 1,
                        "&:hover": { bgcolor: "background.paper" },
                      }}
                    >
                      <CloseIcon fontSize="small" />
                    </IconButton>
                  </Box>
                )}
                <Button
                  component="label"
                  variant="outlined"
                  size="small"
                  startIcon={<AddPhotoAlternateOutlinedIcon />}
                >
                  {hasVisibleImage ? "画像を変更" : "画像を選択"}
                  <input
                    type="file"
                    accept="image/*"
                    capture="environment"
                    hidden
                    onChange={handleImageChange}
                  />
                </Button>
              </Stack>
            </Stack>

            {Boolean(error) && (
              <Alert severity="error" sx={{ whiteSpace: "pre-line" }}>
                {getErrorMessage(error)}
              </Alert>
            )}
          </Stack>
        </DialogContent>
        <DialogActions sx={{ px: 3, pb: 3 }}>
          <Button onClick={onCancel} color="inherit">
            キャンセル
          </Button>
          <Button type="submit" variant="contained" disabled={isSubmitting}>
            {isSubmitting ? "保存中..." : "保存"}
          </Button>
        </DialogActions>
      </Stack>
    </Dialog>
  );
}
