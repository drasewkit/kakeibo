"use client";

import AddIcon from "@mui/icons-material/Add";
import Box from "@mui/material/Box";
import Button from "@mui/material/Button";
import Card from "@mui/material/Card";
import CircularProgress from "@mui/material/CircularProgress";
import Container from "@mui/material/Container";
import Dialog from "@mui/material/Dialog";
import DialogActions from "@mui/material/DialogActions";
import DialogContent from "@mui/material/DialogContent";
import DialogContentText from "@mui/material/DialogContentText";
import DialogTitle from "@mui/material/DialogTitle";
import Stack from "@mui/material/Stack";
import { keyframes } from "@mui/material/styles";
import { useState } from "react";
import { useCategories } from "@/hooks/useCategories";
import {
  useCreateTransaction,
  useDeleteTransaction,
  useDeleteTransactionImage,
  useTransactions,
  useUpdateTransaction,
  useUploadTransactionImage,
} from "@/hooks/useTransactions";
import type {
  Transaction,
  TransactionFilters as Filters,
  TransactionSummary,
} from "@/lib/types";
import { TransactionFilters } from "./TransactionFilters";
import { TransactionForm } from "./TransactionForm";
import { TransactionList } from "./TransactionList";
import { TransactionSummaryCards } from "./TransactionSummaryCards";

// 翌月方向（右）へ移動したときは右から、前月方向（左）へ移動したときは左から
// テーブルがスライドインするアニメーション
const slideFromRight = keyframes`
  from { opacity: 0; transform: translateX(16px); }
  to { opacity: 1; transform: translateX(0); }
`;
const slideFromLeft = keyframes`
  from { opacity: 0; transform: translateX(-16px); }
  to { opacity: 1; transform: translateX(0); }
`;

// 収支の一覧・登録・編集・削除を1画面で行うページ
export function TransactionsPage() {
  // デフォルトは当月を表示
  const now = new Date();
  const [filters, setFilters] = useState<Filters>({
    year: now.getFullYear(),
    month: now.getMonth() + 1,
  });
  // undefined: フォーム非表示 / null: 新規登録フォーム / Transaction: 編集フォーム
  const [editingTransaction, setEditingTransaction] = useState<
    Transaction | null | undefined
  >(undefined);
  // 削除確認ダイアログの表示対象（window.confirmは使わずMUIのDialogにする方針）
  const [deletingTransaction, setDeletingTransaction] =
    useState<Transaction | null>(null);
  // 年月移動時にテーブルをどちら向きにスライドインさせるか
  const [slideDirection, setSlideDirection] = useState<"left" | "right">(
    "right",
  );

  // summaryは種別・カテゴリの絞り込みに関係なく年月だけで決まる値のため、
  // それらのセレクター変更時にまで再取得中のSkeletonへ戻さないよう、
  // 年月ごとに直近取得できた値を保持しておく
  const [summaryCache, setSummaryCache] = useState<
    Record<string, TransactionSummary>
  >({});
  const periodKey = `${filters.year}-${filters.month}`;

  const { data: categoriesData } = useCategories();
  const { data: transactionsData, isLoading } = useTransactions(filters);
  const createTransaction = useCreateTransaction();
  const updateTransaction = useUpdateTransaction();
  const deleteTransaction = useDeleteTransaction();
  const uploadTransactionImage = useUploadTransactionImage();
  const deleteTransactionImage = useDeleteTransactionImage();

  const categories = categoriesData ?? [];
  const transactions = transactionsData?.data ?? [];

  // レンダー中に新しいsummaryが届いていればキャッシュへ反映する
  // （同一オブジェクトなら条件がfalseになるため無限ループにはならない）
  if (
    transactionsData?.summary &&
    summaryCache[periodKey] !== transactionsData.summary
  ) {
    setSummaryCache((prev) => ({
      ...prev,
      [periodKey]: transactionsData.summary,
    }));
  }

  function closeForm() {
    setEditingTransaction(undefined);
  }

  // 年月が変わる操作の場合、前後どちらへ移動したかを見てスライド方向を決める。
  // 年月・種別・カテゴリのいずれかを変更したら、絞り込み結果が変わるためページを1に戻す
  function handleFiltersChange(newFilters: Filters) {
    const currentKey = (filters.year ?? 0) * 12 + (filters.month ?? 0);
    const nextKey = (newFilters.year ?? 0) * 12 + (newFilters.month ?? 0);
    if (nextKey !== currentKey) {
      setSlideDirection(nextKey > currentKey ? "right" : "left");
    }
    setFilters({ ...newFilters, page: 1 });
  }

  // ページネーションの操作。他の絞り込み条件は変えない
  function handlePageChange(page: number) {
    setFilters((prev) => ({ ...prev, page }));
  }

  return (
    <>
      <Container maxWidth="md" sx={{ py: 4 }}>
        <TransactionSummaryCards summary={summaryCache[periodKey]} />

        <Stack
          direction={{ xs: "column", sm: "row" }}
          spacing={2}
          sx={{
            justifyContent: "space-between",
            alignItems: { sm: "center" },
            mb: 3,
          }}
        >
          <TransactionFilters
            filters={filters}
            onChange={handleFiltersChange}
            availableYears={transactionsData?.available_years ?? []}
            categories={categories}
          />
          <Button
            variant="contained"
            startIcon={<AddIcon />}
            onClick={() => setEditingTransaction(null)}
          >
            収支を登録
          </Button>
        </Stack>

        <Box
          key={`${filters.year}-${filters.month}`}
          sx={{
            animation: `${
              slideDirection === "right" ? slideFromRight : slideFromLeft
            } 250ms ease-out`,
          }}
        >
          {isLoading ? (
            <Card variant="outlined">
              <Box sx={{ py: 6, display: "flex", justifyContent: "center" }}>
                <CircularProgress size={28} />
              </Box>
            </Card>
          ) : (
            <TransactionList
              transactions={transactions}
              total={transactionsData?.total ?? 0}
              page={filters.page ?? 1}
              onPageChange={handlePageChange}
              onEdit={setEditingTransaction}
              onDelete={setDeletingTransaction}
            />
          )}
        </Box>
      </Container>

      {/* 新規登録・編集共通のフォーム。editingTransactionの有無で挙動を切り替える */}
      {editingTransaction !== undefined && (
        <TransactionForm
          categories={categories}
          initialValue={editingTransaction ?? undefined}
          isSubmitting={
            createTransaction.isPending || updateTransaction.isPending
          }
          error={createTransaction.error ?? updateTransaction.error}
          onCancel={closeForm}
          onSubmit={(input, image) => {
            // 画像に変更がある場合、収支の保存が成功した後にそのIDを使って反映する
            // （undefined: 変更なし, null: 削除, File: 新規添付/差し替え）
            function applyImageChange(transaction: Transaction) {
              if (image === undefined) return;
              if (image === null) {
                deleteTransactionImage.mutate(transaction.id);
              } else {
                uploadTransactionImage.mutate({ id: transaction.id, image });
              }
            }

            if (editingTransaction) {
              updateTransaction.mutate(
                { id: editingTransaction.id, input },
                {
                  onSuccess: (transaction) => {
                    applyImageChange(transaction);
                    closeForm();
                  },
                },
              );
            } else {
              createTransaction.mutate(input, {
                onSuccess: (transaction) => {
                  applyImageChange(transaction);
                  closeForm();
                },
              });
            }
          }}
        />
      )}

      {/* 削除確認ダイアログ */}
      <Dialog
        open={Boolean(deletingTransaction)}
        onClose={() => setDeletingTransaction(null)}
        maxWidth="xs"
        fullWidth
      >
        <DialogTitle sx={{ fontWeight: 600 }}>収支の削除</DialogTitle>
        <DialogContent>
          <DialogContentText>
            この収支を削除しますか？この操作は取り消せません。
          </DialogContentText>
        </DialogContent>
        <DialogActions sx={{ px: 3, pb: 3 }}>
          <Button color="inherit" onClick={() => setDeletingTransaction(null)}>
            キャンセル
          </Button>
          <Button
            variant="contained"
            color="error"
            disabled={deleteTransaction.isPending}
            onClick={() => {
              if (!deletingTransaction) return;
              deleteTransaction.mutate(deletingTransaction.id, {
                onSuccess: () => setDeletingTransaction(null),
              });
            }}
          >
            削除する
          </Button>
        </DialogActions>
      </Dialog>
    </>
  );
}
