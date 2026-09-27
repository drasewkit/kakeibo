// 添付画像のサイズ上限。バックエンドのバリデーション（UploadTransactionImageRequestのmax:5120）と一致させる
export const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

// 縮小後の長辺の上限と JPEG の画質。レシートの文字が読める範囲で軽くする
const MAX_LONG_EDGE = 2048;
const JPEG_QUALITY = 0.85;

// アップロード前に画像を縮小して JPEG に変換する。
// 既に十分小さい画像や、ブラウザが読み込めない形式（Android の HEIC 等）はそのまま返す
export async function shrinkImage(file: File): Promise<File> {
  const url = URL.createObjectURL(file);
  try {
    // 画像を読み込む。EXIF の向きはブラウザが既定で反映する
    const img = new Image();
    img.src = url;
    await img.decode();

    const scale = Math.min(
      1,
      MAX_LONG_EDGE / Math.max(img.naturalWidth, img.naturalHeight),
    );
    if (scale === 1 && file.size <= MAX_IMAGE_BYTES) {
      return file;
    }

    // canvas に縮小して描き、JPEG として書き出す（透過部分は白で埋める）
    const canvas = document.createElement("canvas");
    canvas.width = Math.round(img.naturalWidth * scale);
    canvas.height = Math.round(img.naturalHeight * scale);
    const context = canvas.getContext("2d");
    if (!context) return file;
    context.fillStyle = "#fff";
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.drawImage(img, 0, 0, canvas.width, canvas.height);

    const blob = await new Promise<Blob | null>((resolve) =>
      canvas.toBlob(resolve, "image/jpeg", JPEG_QUALITY),
    );
    if (!blob) return file;

    const name = file.name.replace(/\.[^.]+$/, "") + ".jpg";
    return new File([blob], name, {
      type: "image/jpeg",
      lastModified: file.lastModified,
    });
  } catch {
    return file;
  } finally {
    URL.revokeObjectURL(url);
  }
}
