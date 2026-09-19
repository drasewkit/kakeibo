import Card from "@mui/material/Card";
import Skeleton from "@mui/material/Skeleton";
import Stack from "@mui/material/Stack";
import Typography from "@mui/material/Typography";
import type { TransactionSummary } from "@/lib/types";

function formatYen(amount: number) {
  return `${amount.toLocaleString("ja-JP")}円`;
}

// 1件分の集計（見出し・金額・色）を表示するカード。
// amountがundefinedの間（取得中）は金額部分だけSkeletonにし、カード自体は表示し続ける
function SummaryCard({
  label,
  amount,
  color,
}: {
  label: string;
  amount: number | undefined;
  color?: string;
}) {
  return (
    <Card variant="outlined" sx={{ flex: 1, p: 2 }}>
      <Typography variant="body2" color="text.secondary">
        {label}
      </Typography>
      {amount === undefined ? (
        <Skeleton variant="text" width={100} height={32} />
      ) : (
        <Typography variant="h6" sx={{ fontWeight: 700, color }}>
          {formatYen(amount)}
        </Typography>
      )}
    </Card>
  );
}

// 対象期間の収入・支出・差引を表示する（一覧の種別・カテゴリ絞り込みには影響されない）。
// summaryが未取得（絞り込み変更直後の再取得中など）でもカード自体は表示したままにする
export function TransactionSummaryCards({
  summary,
}: {
  summary: TransactionSummary | undefined;
}) {
  return (
    <Stack direction={{ xs: "column", sm: "row" }} spacing={2} sx={{ mb: 3 }}>
      <SummaryCard label="収入" amount={summary?.income} color="success.main" />
      <SummaryCard label="支出" amount={summary?.expense} color="error.main" />
      <SummaryCard
        label="差引"
        amount={summary?.balance}
        color={
          summary !== undefined && summary.balance < 0
            ? "error.main"
            : undefined
        }
      />
    </Stack>
  );
}
