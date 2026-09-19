import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // 本番コンテナ用。.next/standalone に依存を同梱した最小のserver.jsを出力する。
  // node_modulesを丸ごと持たずに済み、t4g.micro(1GiB)でのメモリと起動時間に効く
  output: "standalone",
};

export default nextConfig;
