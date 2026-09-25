"use client";

import { AppRouterCacheProvider } from "@mui/material-nextjs/v16-appRouter";
import CssBaseline from "@mui/material/CssBaseline";
import { ThemeProvider } from "@mui/material/styles";
import { AdapterDayjs } from "@mui/x-date-pickers/AdapterDayjs";
import { jaJP } from "@mui/x-date-pickers/locales";
import { LocalizationProvider } from "@mui/x-date-pickers/LocalizationProvider";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import "dayjs/locale/ja";
import { useState } from "react";
import { theme } from "@/lib/theme";

// TanStack QueryのProvider。QueryClientをuseStateで生成することで、
// サーバー側のリクエスト間でインスタンスが共有されないようにしている。
// AppRouterCacheProviderはEmotion(MUIのCSS-in-JSエンジン)のスタイルを
// App RouterのSSR/ストリーミングと正しく連携させるためのラッパー
// LocalizationProviderはDatePicker等が使う日付操作ライブラリ(dayjs)のアダプタと
// 日本語ロケール（曜日表記・OK/キャンセルボタン文言等）を提供する
export function Providers({ children }: { children: React.ReactNode }) {
  const [queryClient] = useState(() => new QueryClient());

  return (
    <AppRouterCacheProvider>
      <ThemeProvider theme={theme}>
        <CssBaseline />
        <LocalizationProvider
          dateAdapter={AdapterDayjs}
          adapterLocale="ja"
          localeText={
            jaJP.components.MuiLocalizationProvider.defaultProps.localeText
          }
        >
          <QueryClientProvider client={queryClient}>
            {children}
          </QueryClientProvider>
        </LocalizationProvider>
      </ThemeProvider>
    </AppRouterCacheProvider>
  );
}
