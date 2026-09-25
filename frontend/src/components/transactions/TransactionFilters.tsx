import ChevronLeftIcon from "@mui/icons-material/ChevronLeft";
import ChevronRightIcon from "@mui/icons-material/ChevronRight";
import Button from "@mui/material/Button";
import IconButton from "@mui/material/IconButton";
import MenuItem from "@mui/material/MenuItem";
import Stack from "@mui/material/Stack";
import TextField from "@mui/material/TextField";
import type {
  Category,
  TransactionFilters as Filters,
  TransactionType,
} from "@/lib/types";

const MONTHS = Array.from({ length: 12 }, (_, i) => i + 1);

// 年・月・種別・カテゴリで収支一覧を絞り込むフィルターUI
export function TransactionFilters({
  filters,
  onChange,
  availableYears,
  categories,
}: {
  filters: Filters;
  onChange: (filters: Filters) => void;
  // 収支が存在する年の一覧（降順）。年セレクターの選択肢に使う
  availableYears: number[];
  categories: Category[];
}) {
  const now = new Date();
  const currentYear = now.getFullYear();
  const currentMonth = now.getMonth() + 1;
  const currentKey = currentYear * 12 + currentMonth;
  const year = filters.year ?? currentYear;
  const month = filters.month ?? currentMonth;

  // 記録のある年 + 現在の年 + 現在選択中の年を選択肢にする（未来の年は除外し、重複除去して降順）
  const years = Array.from(new Set([...availableYears, currentYear, year]))
    .filter((y) => y <= currentYear)
    .sort((a, b) => b - a);

  // 種別を絞り込んでいる場合はそのカテゴリのみ選択肢に出す（未指定ならすべて）
  const filteredCategories = filters.type
    ? categories.filter((c) => c.type === filters.type)
    : categories;

  // 前月・翌月へ移動する（年をまたぐ場合も考慮。当月より先へは進めない）
  function stepMonth(delta: number) {
    const totalMonths = Math.min(
      year * 12 + (month - 1) + delta,
      currentKey - 1,
    );
    onChange({
      ...filters,
      year: Math.floor(totalMonths / 12),
      month: (((totalMonths % 12) + 12) % 12) + 1,
    });
  }

  return (
    <Stack
      direction="row"
      spacing={1.5}
      useFlexGap
      sx={{ flexWrap: "wrap", alignItems: "center" }}
    >
      <Button
        size="small"
        variant="outlined"
        disabled={year * 12 + month === currentKey}
        onClick={() =>
          onChange({ ...filters, year: currentYear, month: currentMonth })
        }
      >
        今月
      </Button>
      <IconButton
        size="small"
        aria-label="前月へ"
        onClick={() => stepMonth(-1)}
      >
        <ChevronLeftIcon />
      </IconButton>
      <TextField
        select
        size="small"
        label="年"
        value={year}
        onChange={(e) => {
          const newYear = Number(e.target.value);
          // 現在の年に切り替えた際、選択中の月が未来月にならないよう調整する
          const newMonth =
            newYear === currentYear ? Math.min(month, currentMonth) : month;
          onChange({ ...filters, year: newYear, month: newMonth });
        }}
        sx={{ minWidth: 110 }}
      >
        {years.map((year) => (
          <MenuItem key={year} value={year}>
            {year}年
          </MenuItem>
        ))}
      </TextField>
      <TextField
        select
        size="small"
        label="月"
        value={month}
        onChange={(e) =>
          onChange({ ...filters, month: Number(e.target.value) })
        }
        sx={{ minWidth: 90 }}
      >
        {MONTHS.map((month) => (
          <MenuItem
            key={month}
            value={month}
            disabled={year === currentYear && month > currentMonth}
          >
            {month}月
          </MenuItem>
        ))}
      </TextField>
      <IconButton
        size="small"
        aria-label="翌月へ"
        onClick={() => stepMonth(1)}
        disabled={year * 12 + month >= currentKey}
      >
        <ChevronRightIcon />
      </IconButton>
      <TextField
        select
        size="small"
        label="種別"
        value={filters.type ?? ""}
        onChange={(e) => {
          const newType = (e.target.value || undefined) as
            TransactionType | undefined;
          // 種別を切り替えたことで選択中のカテゴリが対象外になる場合はクリアする
          const selectedCategory = categories.find(
            (c) => c.id === filters.categoryId,
          );
          const newCategoryId =
            newType && selectedCategory && selectedCategory.type !== newType
              ? undefined
              : filters.categoryId;
          onChange({ ...filters, type: newType, categoryId: newCategoryId });
        }}
        sx={{ minWidth: 110 }}
      >
        <MenuItem value="">すべて</MenuItem>
        <MenuItem value="expense">支出</MenuItem>
        <MenuItem value="income">収入</MenuItem>
      </TextField>
      <TextField
        select
        size="small"
        label="カテゴリ"
        value={filters.categoryId ?? ""}
        onChange={(e) =>
          onChange({
            ...filters,
            categoryId: e.target.value ? Number(e.target.value) : undefined,
          })
        }
        sx={{ minWidth: 130 }}
      >
        <MenuItem value="">すべて</MenuItem>
        {filteredCategories.map((category) => (
          <MenuItem key={category.id} value={category.id}>
            {category.name}
          </MenuItem>
        ))}
      </TextField>
    </Stack>
  );
}
