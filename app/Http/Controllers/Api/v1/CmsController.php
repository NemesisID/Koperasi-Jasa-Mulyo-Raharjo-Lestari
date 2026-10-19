<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\ComproContent;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CmsController extends Controller
{
    /**
     * GET /api/v1/cms/contents (Public)
     * Query params:
     * - page: 'welcome' (default)
     * - section: optional filter (e.g. 'hero')
     */
    public function getContents(Request $request): JsonResponse
    {
        $page = $request->query('page', 'welcome');
        $section = $request->query('section');

        $query = ComproContent::where('page', $page)->orderBy('order', 'asc');

        if ($section) {
            $query->where('section', $section);
        }

        $rawContents = $query->get();

        // Grouping per section agar sangat mudah dikonsumsi frontend SPA
        $grouped = $rawContents->groupBy('section')->map(function ($items) {
            $sectionData = [];
            foreach ($items as $item) {
                $sectionData[$item->key] = $item->parsed_value;
            }
            return $sectionData;
        });

        return response()->json([
            'success' => true,
            'message' => 'Konten compro berhasil dimuat.',
            'data' => [
                'page' => $page,
                'sections' => $grouped,
                'raw' => $rawContents,
            ],
        ]);
    }

    /**
     * GET /api/v1/cms/pages/{slug} (Public - Backward compatibility & SPA landing page)
     * Support slug 'home', 'welcome', 'about', dsb.
     */
    public function getPage(string $slug = 'welcome'): JsonResponse
    {
        // Normalisasi slug: jika 'home' diarahkan ke 'welcome'
        $pageName = in_array($slug, ['home', 'welcome']) ? 'welcome' : $slug;

        $contents = ComproContent::where('page', $pageName)
            ->orderBy('order', 'asc')
            ->get();

        if ($contents->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Halaman tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        // Susun struktur sections agar kompatibel dengan SPA & dokumentasi
        $sectionsGrouped = $contents->groupBy('section')->map(function ($items, $sectionName) {
            $fields = [];
            foreach ($items as $item) {
                $fields[$item->key] = $item->parsed_value;
            }

            return [
                'section' => $sectionName,
                'title' => $fields['judul'] ?? ucfirst($sectionName),
                'content' => $fields,
                'items' => $items->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Konten halaman berhasil dimuat.',
            'data' => [
                'page' => $pageName,
                'slug' => $slug,
                'sections' => $sectionsGrouped,
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
     * Admin: POST/PUT /api/v1/admin/cms/contents
     */
    public function saveContent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => 'required|string|max:100',
            'section' => 'required|string|max:100',
            'key' => 'required|string|max:100',
            'type' => 'required|string|in:text,richtext,image,url,repeater,json',
            'value' => 'nullable',
            'order' => 'integer',
        ]);

        $val = is_array($validated['value']) ? json_encode($validated['value']) : $validated['value'];

        $content = ComproContent::updateOrCreate(
            [
                'page' => $validated['page'],
                'section' => $validated['section'],
                'key' => $validated['key'],
            ],
            [
                'type' => $validated['type'],
                'value' => $val,
                'order' => $validated['order'] ?? 1,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Konten berhasil disimpan.',
            'data' => $content,
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
