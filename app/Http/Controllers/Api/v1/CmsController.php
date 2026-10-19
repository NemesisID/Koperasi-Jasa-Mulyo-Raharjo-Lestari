<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CmsController extends Controller
{
    /**
     * GET /api/v1/cms/pages/{slug} (Public)
     */
    public function getPage(string $slug = 'home'): JsonResponse
    {
        $page = CmsPage::with(['sections' => function ($q) {
            $q->where('is_active', true)->orderBy('sort_order', 'asc');
        }])->where('slug', $slug)->where('is_published', true)->first();

        if (! $page) {
            return response()->json([
                'success' => false,
                'message' => 'Halaman tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Konten halaman berhasil dimuat.',
            'data' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title,
                'meta_description' => $page->meta_description,
                'sections' => $page->sections->map(fn ($sec) => [
                    'id' => $sec->id,
                    'key' => $sec->key,
                    'title' => $sec->title,
                    'content' => $sec->content,
                    'sort_order' => $sec->sort_order,
                ]),
            ],
        ]);
    }

    /**
     * GET /api/v1/cms/site-config (Public)
     */
    public function getSiteConfig(): JsonResponse
    {
        $settings = SiteSetting::all()->mapWithKeys(function ($item) {
            $val = $item->value;
            if ($item->type === 'json') {
                $val = json_decode($item->value, true);
            } elseif ($item->type === 'boolean') {
                $val = filter_var($item->value, FILTER_VALIDATE_BOOLEAN);
            }
            return [$item->key => $val];
        });

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi situs berhasil dimuat.',
            'data' => $settings,
        ]);
    }

    /**
     * Admin: POST/PUT /api/v1/admin/cms/pages
     */
    public function savePage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'meta_description' => 'nullable|string',
            'is_published' => 'boolean',
        ]);

        $page = CmsPage::updateOrCreate(
            ['slug' => $validated['slug']],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Halaman CMS berhasil disimpan.',
            'data' => $page,
        ]);
    }

    /**
     * Admin: POST/PUT /api/v1/admin/cms/sections
     */
    public function saveSection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page_slug' => 'required|string|exists:cms_pages,slug',
            'key' => 'required|string|max:100',
            'title' => 'nullable|string|max:255',
            'content' => 'required|array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $page = CmsPage::where('slug', $validated['page_slug'])->firstOrFail();

        $section = CmsSection::updateOrCreate(
            [
                'cms_page_id' => $page->id,
                'key' => $validated['key'],
            ],
            [
                'title' => $validated['title'] ?? null,
                'content' => $validated['content'],
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Section CMS berhasil diperbarui.',
            'data' => $section,
        ]);
    }

    /**
     * Admin: PUT /api/v1/admin/cms/site-config
     */
    public function updateSiteConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($validated['settings'] as $key => $value) {
            $type = is_array($value) ? 'json' : (is_bool($value) ? 'boolean' : 'string');
            SiteSetting::set($key, $value, $type);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan situs berhasil diperbarui.',
        ]);
    }
}
