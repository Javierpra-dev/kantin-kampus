<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Show canteen profile edit form (/admin/profil).
     */
    public function edit()
    {
        $admin = Auth::guard('admin')->user();
        $canteen = $admin->canteen;

        return view('admin.profile', compact('admin', 'canteen'));
    }

    /**
     * Update canteen profile.
     */
    public function update(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $canteen = $admin->canteen;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:120'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'open_time' => ['nullable', 'string', 'max:10'],
            'close_time' => ['nullable', 'string', 'max:10'],
            'whatsapp' => ['nullable', 'string', 'regex:/^(\+62|62|0)8[1-9][0-9]{6,11}$/'],
            'is_open' => ['nullable'],
        ], [
            'name.required' => 'Nama kantin wajib diisi.',
            'description.max' => 'Deskripsi singkat maksimal 120 karakter.',
            'photo.image' => 'File harus berupa foto/gambar.',
            'photo.mimes' => 'Format foto harus berupa JPG, JPEG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran foto maksimal 3 MB.',
            'whatsapp.regex' => 'Format nomor WhatsApp tidak valid. Gunakan format nomor Indonesia (contoh: 081234567890).',
        ]);

        // Process and re-encode photo with GD to JPEG, longest side max 1600px
        if ($request->hasFile('photo')) {
            $newPhotoPath = $this->processCanteenPhoto($request->file('photo'));

            if ($newPhotoPath) {
                // Delete old photo if on public disk and not remote URL
                if ($canteen->photo && !str_starts_with($canteen->photo, 'http') && Storage::disk('public')->exists($canteen->photo)) {
                    Storage::disk('public')->delete($canteen->photo);
                }
                $canteen->photo = $newPhotoPath;
            }
        }

        $canteen->name = $validated['name'];
        $canteen->description = $validated['description'] ?? null;

        if ($request->filled('open_time') && $request->filled('close_time')) {
            $canteen->open_time = $request->input('open_time');
            $canteen->close_time = $request->input('close_time');
            $canteen->opening_hours = $request->input('open_time') . ' - ' . $request->input('close_time');
        }

        if ($request->has('whatsapp')) {
            $cleanWa = $request->filled('whatsapp') ? preg_replace('/[\s\-]/', '', $request->input('whatsapp')) : null;
            $canteen->whatsapp = $cleanWa;
            if ($cleanWa) {
                $canteen->phone = $cleanWa;
            }
        }

        $canteen->is_open = $request->boolean('is_open');
        $canteen->save();

        return redirect()->route('admin.profile')->with('success', 'Profil kantin diperbarui');
    }

    /**
     * Process canteen photo using PHP GD:
     * - Re-encode to JPEG
     * - Longest side max 1600px
     * - Random file name on public disk
     */
    protected function processCanteenPhoto($file): ?string
    {
        if (!$file || !$file->isValid()) {
            return null;
        }

        $filePath = $file->getRealPath();
        $imageInfo = @getimagesize($filePath);
        if (!$imageInfo) {
            return null;
        }

        $mime = $imageInfo['mime'];
        $srcImg = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($filePath),
            'image/png' => @imagecreatefrompng($filePath),
            'image/webp' => @imagecreatefromwebp($filePath),
            default => null,
        };

        if (!$srcImg) {
            return null;
        }

        $origWidth = imagesx($srcImg);
        $origHeight = imagesy($srcImg);
        $maxDim = 1600;

        if ($origWidth > $maxDim || $origHeight > $maxDim) {
            if ($origWidth >= $origHeight) {
                $newWidth = $maxDim;
                $newHeight = (int) round(($origHeight * $maxDim) / $origWidth);
            } else {
                $newHeight = $maxDim;
                $newWidth = (int) round(($origWidth * $maxDim) / $origHeight);
            }
        } else {
            $newWidth = $origWidth;
            $newHeight = $origHeight;
        }

        $dstImg = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($dstImg, 255, 255, 255);
        imagefilledrectangle($dstImg, 0, 0, $newWidth, $newHeight, $white);
        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        ob_start();
        imagejpeg($dstImg, null, 88);
        $jpegData = ob_get_clean();

        imagedestroy($srcImg);
        imagedestroy($dstImg);

        $filename = 'canteens/' . Str::uuid() . '.jpg';
        Storage::disk('public')->put($filename, $jpegData);

        return $filename;
    }
}

