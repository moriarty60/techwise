<?php
// Secure image upload helpers. Used by profile.php (avatars) and admin/index.php (article covers).
function save_image(array $f, string $sub, int $max): array
{
    $code = $f['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($code === UPLOAD_ERR_NO_FILE) {
        return [null, ''];
    }
    if ($code !== UPLOAD_ERR_OK) {
        return [null, 'Upload gagal (kode ' . $code . ').'];
    }
    if ($f['size'] > $max) {
        return [null, 'Ukuran file maksimal ' . round($max / 1048576) . ' MB.'];
    }
    // Type is decided by the file's real content, never by its name or browser-sent type.
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext || !@getimagesize($f['tmp_name'])) {
        return [null, 'Hanya gambar JPG, PNG, atau WebP yang diperbolehkan.'];
    }
    $dir = __DIR__ . '/uploads/' . $sub;
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        return [null, 'Folder upload tidak bisa dibuat.'];
    }
    $ht = __DIR__ . '/uploads/.htaccess';
    if (!file_exists($ht)) {
        file_put_contents($ht, "<FilesMatch \"\\.(php|phtml|phar|php[0-9])$\">\n    Require all denied\n</FilesMatch>\n");
    }
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], "$dir/$name")) {
        return [null, 'Gagal menyimpan file.'];
    }
    return ["uploads/$sub/$name", ''];
}

function delete_image(?string $p): void
{
    if ($p && preg_match('#^uploads/(covers|avatars)/[a-f0-9]{24}\.(jpg|png|webp)$#', $p)) {
        @unlink(__DIR__ . '/' . $p);
    }
}
