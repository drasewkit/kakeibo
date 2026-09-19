import { jaJP } from "@mui/material/locale";
import { createTheme } from "@mui/material/styles";

// アプリ全体で使うMUIテーマ。ライトモード固定（ダークモード自動切替はしない方針）
// jaJPを適用し、TablePagination等のMUI組み込みラベルを日本語化する
//
// 配色は名刺（Documents/Work/business-card-2026）のMarine系パレットを流用し、
// ポートフォリオ（drasewkit.dev）と見た目を揃えている。
// 収入・支出の色分けはsuccess/errorが担っているため、primary/secondaryの変更は影響しない。
export const theme = createTheme({
  palette: {
    mode: "light",
    primary: {
      main: "#367FAE", // 名刺のカラーブロック（Marine）
      dark: "#236A92", // 名刺のリンク文字に使っている濃い差し色
    },
    secondary: {
      main: "#16303D", // 名刺の氏名に使っている最も濃い色
    },
    background: {
      default: "#FBFDFE", // 青みをわずかに含んだ白（名刺の地色）
      paper: "#FFFFFF",
    },
    divider: "#E4EEF2",
  },
  shape: {
    borderRadius: 10,
  },
  typography: {
    fontFamily: "var(--font-geist-sans), system-ui, sans-serif",
  },
  components: {
    MuiButton: {
      defaultProps: {
        disableElevation: true,
      },
      styleOverrides: {
        root: {
          textTransform: "none",
        },
      },
    },
    MuiPaper: {
      styleOverrides: {
        root: {
          backgroundImage: "none",
        },
      },
    },
  },
}, jaJP);
