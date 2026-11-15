<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WebsiteUpload
{
    /**
     * @param  list<string>  $extensions
     */
    public static function store(UploadedFile $file, string $directory, array $extensions): string
    {
        $extension = self::assertAllowed($file, $extensions);
        $directory = trim($directory, '/');
        abort_unless(str_starts_with($directory, 'website/'), 422);

        $name = (string) Str::uuid().'.'.$extension;

        $stored = $file->storeAs($directory, $name, 'local');
        abort_unless(is_string($stored) && $stored !== '', 500);

        return $stored;
    }

    public static function delete(?string $path): void
    {
        if (! is_string($path) || ! self::isManagedPath($path)) {
            return;
        }

        Storage::disk('local')->delete($path);
    }

    public static function inlineImage(string $path): StreamedResponse
    {
        $extension = self::assertManagedImage($path);

        return Storage::disk('local')->response($path, 'image.'.$extension, [
            'Content-Type' => self::imageMime($extension),
            'Content-Disposition' => 'inline; filename="image.'.$extension.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public static function download(string $path, string $downloadName): StreamedResponse
    {
        abort_unless(self::isManagedPath($path) && Storage::disk('local')->exists($path), 404);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $allowed = config('website.artwork_extensions', []);
        abort_unless(in_array($extension, $allowed, true), 404);

        $safeName = self::downloadName($downloadName, $extension);
        $mime = $extension === 'pdf' ? 'application/pdf' : self::imageMime($extension);

        return Storage::disk('local')->response($path, $safeName, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="'.$safeName.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * @param  list<string>  $extensions
     */
    public static function assertAllowed(UploadedFile $file, array $extensions): string
    {
        $original = strtolower($file->getClientOriginalName());
        $clientExtension = strtolower($file->getClientOriginalExtension());
        $guessed = strtolower((string) $file->guessExtension());

        if (preg_match('/\.(php|phtml|phar|svg|exe|js|html|htm|htaccess)(\.|$)/i', $original)) {
            abort(422, 'This file type is not allowed.');
        }

        if (! in_array($clientExtension, $extensions, true) || ! in_array($guessed, $extensions, true)) {
            abort(422, 'Upload a JPG, PNG, WEBP, or PDF file.');
        }

        if ($clientExtension === 'jpeg') {
            $clientExtension = 'jpg';
        }

        if ($guessed === 'jpeg') {
            $guessed = 'jpg';
        }

        $normalized = array_map(fn (string $ext) => $ext === 'jpeg' ? 'jpg' : $ext, $extensions);

        abort_unless(in_array($guessed, $normalized, true), 422);

        return $guessed === 'jpg' ? 'jpg' : $guessed;
    }

    public static function originalName(UploadedFile $file): string
    {
        $base = basename($file->getClientOriginalName());
        $base = str_replace(["\0", '/', '\\'], '', $base);
        $base = trim(strip_tags($base));

        return mb_substr($base !== '' ? $base : 'upload', 0, 180);
    }

    private static function assertManagedImage(string $path): string
    {
        abort_unless(self::isManagedPath($path) && Storage::disk('local')->exists($path), 404);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        abort_unless(in_array($extension, ['jpg', 'png', 'webp'], true), 404);

        return $extension;
    }

    private static function isManagedPath(string $path): bool
    {
        if (str_contains($path, '..') || str_contains($path, '\\') || str_starts_with($path, '/')) {
            return false;
        }

        return (bool) preg_match('#^website/(listings|gallery|enquiries)/[A-Za-z0-9._-]+$#', $path);
    }

    private static function imageMime(string $extension): string
    {
        return match ($extension) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    private static function downloadName(string $name, string $extension): string
    {
        $name = basename(str_replace(["\0", '"', "\r", "\n"], '', $name));
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?: 'artwork';

        if (! str_ends_with(strtolower($name), '.'.$extension)) {
            $name .= '.'.$extension;
        }

        return $name;
    }
}
