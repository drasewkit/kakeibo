import Box from "@mui/material/Box";
import { AppHeader } from "@/components/ui/AppHeader";

// ログイン後の画面共通レイアウト（ヘッダーを各ページで書かずに済むよう共通化）
export default function AppLayout({ children }: { children: React.ReactNode }) {
  return (
    <Box
      sx={{
        minHeight: "100dvh",
        bgcolor: "background.default",
        // PWAのstandalone表示ではホームバーが画面下端に重なるため、その分の余白を確保する
        pb: "env(safe-area-inset-bottom)",
      }}
    >
      <AppHeader />
      {children}
    </Box>
  );
}
