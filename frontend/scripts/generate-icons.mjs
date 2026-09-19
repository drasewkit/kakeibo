// アイコンのソースSVG（assets/icons）からPWA用のPNGを生成する。
// 実行: node scripts/generate-icons.mjs
import { readFile, writeFile } from "node:fs/promises";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";
import sharp from "sharp";

const root = join(dirname(fileURLToPath(import.meta.url)), "..");

// 出力先: [ソースSVG, 出力パス, 一辺のピクセル数]
const targets = [
  ["icon.svg", "public/icon-192.png", 192],
  ["icon.svg", "public/icon-512.png", 512],
  // Android のアイコン切り抜きに対応する版（安全域内に内容を収めてある）
  ["icon-maskable.svg", "public/icon-maskable-512.png", 512],
  // iOS はマニフェストのiconsを見ないため、専用のapple-touch-iconが必要
  ["icon.svg", "src/app/apple-icon.png", 180],
];

for (const [src, out, size] of targets) {
  const svg = await readFile(join(root, "assets/icons", src));
  const png = await sharp(svg, { density: 512 }).resize(size, size).png().toBuffer();
  await writeFile(join(root, out), png);
  console.log(`${out} (${size}x${size})`);
}
