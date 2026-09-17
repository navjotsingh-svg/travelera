<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\TravelPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function index(): View
    {
        $packages = TravelPackage::query()
            ->with('destination')
            ->latest()
            ->paginate(20);

        return view('admin.packages.index', compact('packages'));
    }

    public function create(): View
    {
        $destinations = Destination::query()->orderBy('city')->get();

        return view('admin.packages.create', compact('destinations'));
    }

    public function store(Request $request): RedirectResponse
    {
        TravelPackage::query()->create($this->payload($request));

        return redirect()->route('admin.packages.index')->with('status', 'Package created.');
    }

    public function edit(TravelPackage $package): View
    {
        $destinations = Destination::query()->orderBy('city')->get();

        return view('admin.packages.edit', compact('package', 'destinations'));
    }

    public function update(Request $request, TravelPackage $package): RedirectResponse
    {
        $package->update($this->payload($request, $package));

        return redirect()->route('admin.packages.index')->with('status', 'Package updated.');
    }

    public function destroy(TravelPackage $package): RedirectResponse
    {
        $this->deleteStoredImage($package->image);
        $package->delete();

        return redirect()->route('admin.packages.index')->with('status', 'Package deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request, ?TravelPackage $package = null): array
    {
        $validated = $request->validate([
            'destination_id' => ['required', 'exists:destinations,id'],
            'title' => ['required', 'string', 'max:255'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:60'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['required', 'string'],
            'image_mode' => ['nullable', 'in:url,upload'],
            'image' => ['nullable', 'string', 'max:500'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'includes_text' => ['nullable', 'string'],
        ]);

        $mode = $validated['image_mode'] ?? ($request->hasFile('image_file') ? 'upload' : 'url');
        $image = $package?->image;

        if ($mode === 'upload') {
            if ($request->hasFile('image_file')) {
                $this->deleteStoredImage($package?->image);
                $path = $request->file('image_file')->store('packages', 'uploads');
                $image = asset('uploads/'.$path);
            } elseif (! $package) {
                throw ValidationException::withMessages([
                    'image_file' => 'Please upload a package image.',
                ]);
            }
        } else {
            $url = trim((string) ($validated['image'] ?? ''));

            if ($url !== '') {
                $this->deleteStoredImage($package?->image);
                $image = $url;
            } elseif (! $package) {
                throw ValidationException::withMessages([
                    'image' => 'Please provide an image URL or upload a file.',
                ]);
            }
        }

        return [
            'destination_id' => $validated['destination_id'],
            'title' => $validated['title'],
            'slug' => $this->uniqueSlug($validated['title'], $package?->id),
            'duration_days' => $validated['duration_days'],
            'price' => $validated['price'],
            'description' => $validated['description'],
            'image' => $image,
            'includes' => $this->parseIncludes($validated['includes_text'] ?? null),
            'is_featured' => $request->boolean('is_featured'),
        ];
    }

    /**
     * @return list<string>
     */
    private function parseIncludes(?string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n|,/', (string) $text) ?: [])
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'package';
        $slug = $base;
        $i = 1;

        while (
            TravelPackage::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function deleteStoredImage(?string $image): void
    {
        if (! $image) {
            return;
        }

        $path = $image;

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://') || str_starts_with($image, '//')) {
            $path = parse_url($image, PHP_URL_PATH) ?: '';
        }

        $path = ltrim((string) $path, '/');

        if (str_starts_with($path, 'uploads/')) {
            $relative = substr($path, strlen('uploads/'));
            if ($relative !== '' && Storage::disk('uploads')->exists($relative)) {
                Storage::disk('uploads')->delete($relative);
            }

            return;
        }

        if (str_contains($path, '/storage/')) {
            $path = preg_replace('#^.*?/storage/#', '', $path) ?? $path;
        }

        $path = str_replace('storage/', '', $path);
        $path = ltrim($path, '/');

        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
