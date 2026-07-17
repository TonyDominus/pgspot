<?php

namespace App\Services;

use App\Models\Poi;
use App\Models\PoiPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PoiPhotoService
{
    private const DISK = 'public';

    public function storeForPoi(Poi $poi, UploadedFile $file, bool $isPrimary = false, ?User $user = null): PoiPhoto
    {
        $path = $file->store("pois/{$poi->id}", self::DISK);

        if ($isPrimary || ! $poi->photos()->exists()) {
            $poi->photos()->update(['is_primary' => false]);
            $isPrimary = true;
        }

        $sortOrder = (int) $poi->photos()->max('sort_order') + 1;

        return $poi->photos()->create([
            'path' => $path,
            'is_primary' => $isPrimary,
            'sort_order' => $sortOrder,
            'uploaded_by' => $user?->id,
        ]);
    }

    public function storePendingContribution(UploadedFile $file): string
    {
        return $file->store('contributions/pending', self::DISK);
    }

    public function attachFromPath(Poi $poi, string $path, bool $isPrimary = false, ?int $uploadedBy = null): ?PoiPhoto
    {
        if (! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';
        $newPath = "pois/{$poi->id}/".Str::uuid().'.'.$extension;

        Storage::disk(self::DISK)->move($path, $newPath);

        if ($isPrimary || ! $poi->photos()->exists()) {
            $poi->photos()->update(['is_primary' => false]);
            $isPrimary = true;
        }

        $sortOrder = (int) $poi->photos()->max('sort_order') + 1;

        return $poi->photos()->create([
            'path' => $newPath,
            'is_primary' => $isPrimary,
            'sort_order' => $sortOrder,
            'uploaded_by' => $uploadedBy,
        ]);
    }

    public function copyFromPath(Poi $poi, string $path, bool $isPrimary = false, ?int $uploadedBy = null): ?PoiPhoto
    {
        if (! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';
        $newPath = "pois/{$poi->id}/".Str::uuid().'.'.$extension;

        Storage::disk(self::DISK)->copy($path, $newPath);

        if ($isPrimary || ! $poi->photos()->exists()) {
            $poi->photos()->update(['is_primary' => false]);
            $isPrimary = true;
        }

        $sortOrder = (int) $poi->photos()->max('sort_order') + 1;

        return $poi->photos()->create([
            'path' => $newPath,
            'is_primary' => $isPrimary,
            'sort_order' => $sortOrder,
            'uploaded_by' => $uploadedBy,
        ]);
    }

    public function setPrimary(PoiPhoto $photo): void
    {
        $photo->poi->photos()->update(['is_primary' => false]);
        $photo->update(['is_primary' => true]);
    }

    public function delete(PoiPhoto $photo): void
    {
        Storage::disk(self::DISK)->delete($photo->path);
        $wasPrimary = $photo->is_primary;
        $poi = $photo->poi;
        $photo->delete();

        if ($wasPrimary) {
            $next = $poi->photos()->orderBy('sort_order')->first();
            $next?->update(['is_primary' => true]);
        }
    }

    public static function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk(self::DISK)->url($path);
    }
}
