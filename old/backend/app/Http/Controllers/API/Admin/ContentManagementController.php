<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\Page;
use App\Models\ContentBlock;
use App\Models\NavigationMenu;
use App\Models\NavigationItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ContentManagementController extends Controller
{
    public function __construct()
    {
        // No middleware needed - it's handled at the route level
    }

    // ============ SITE SETTINGS ============

    /**
     * Get all site settings
     */
    public function getSiteSettings(): JsonResponse
    {
        $settings = SiteSetting::all()->pluck('value', 'key');
        return response()->json($settings);
    }

    /**
     * Update site settings
     */
    public function updateSiteSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($request->settings as $key => $value) {
            $type = 'text';
            $description = null;

            // Determine type based on key
            if (str_contains($key, 'logo') || str_contains($key, 'image')) {
                $type = 'image';
            } elseif (str_contains($key, 'enable') || str_contains($key, 'show')) {
                $type = 'boolean';
            } elseif (is_array($value)) {
                $type = 'json';
            }

            SiteSetting::set($key, $value, $type, $description);
        }

        return response()->json(['message' => 'Settings updated successfully']);
    }

    /**
     * Upload site logo
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $path = $request->file('logo')->store('site', 'public');
        
        SiteSetting::set('site_logo', $path, 'image', 'Main site logo');

        return response()->json([
            'message' => 'Logo uploaded successfully',
            'url' => asset('storage/' . $path)
        ]);
    }

    // ============ PAGES MANAGEMENT ============

    /**
     * Get all pages
     */
    public function getPages(Request $request): JsonResponse
    {
        $pages = Page::withCount('contentBlocks')
            ->orderBy('is_homepage', 'desc')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate($request->get('per_page', 15));

        return response()->json($pages);
    }

    /**
     * Get a specific page
     */
    public function getPage(Page $page): JsonResponse
    {
        $page->load('contentBlocks');
        return response()->json($page);
    }

    /**
     * Create a new page
     */
    public function createPage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|array',
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'template' => 'nullable|string|max:50',
            'is_homepage' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Page::generateUniqueSlug($data['title']);
        }

        // If setting as homepage, unset other homepages
        if ($data['is_homepage'] ?? false) {
            Page::where('is_homepage', true)->update(['is_homepage' => false]);
        }

        $page = Page::create($data);

        return response()->json($page, 201);
    }

    /**
     * Update a page
     */
    public function updatePage(Request $request, Page $page): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug,' . $page->id,
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|array',
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'template' => 'nullable|string|max:50',
            'is_homepage' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Page::generateUniqueSlug($data['title'], $page->id);
        }

        // If setting as homepage, unset other homepages
        if ($data['is_homepage'] ?? false) {
            Page::where('is_homepage', true)->where('id', '!=', $page->id)->update(['is_homepage' => false]);
        }

        $page->update($data);

        return response()->json($page);
    }

    /**
     * Delete a page
     */
    public function deletePage(Page $page): JsonResponse
    {
        $page->delete();
        return response()->json(['message' => 'Page deleted successfully']);
    }

    // ============ CONTENT BLOCKS ============

    /**
     * Get content blocks for a page
     */
    public function getContentBlocks(Page $page): JsonResponse
    {
        $blocks = $page->contentBlocks()->orderBy('sort_order')->get();
        return response()->json($blocks);
    }

    /**
     * Create a content block
     */
    public function createContentBlock(Request $request, Page $page): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', Rule::in(array_keys(ContentBlock::getAvailableTypes()))],
            'name' => 'nullable|string|max:255',
            'content' => 'required|array',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['page_id'] = $page->id;

        $block = ContentBlock::create($data);

        return response()->json($block, 201);
    }

    /**
     * Update a content block
     */
    public function updateContentBlock(Request $request, ContentBlock $block): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => ['sometimes', Rule::in(array_keys(ContentBlock::getAvailableTypes()))],
            'name' => 'nullable|string|max:255',
            'content' => 'required|array',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $block->update($validator->validated());

        return response()->json($block);
    }

    /**
     * Delete a content block
     */
    public function deleteContentBlock(ContentBlock $block): JsonResponse
    {
        $block->delete();
        return response()->json(['message' => 'Content block deleted successfully']);
    }

    /**
     * Reorder content blocks
     */
    public function reorderContentBlocks(Request $request, Page $page): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'blocks' => 'required|array',
            'blocks.*.id' => 'required|exists:content_blocks,id',
            'blocks.*.sort_order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($request->blocks as $blockData) {
            ContentBlock::where('id', $blockData['id'])
                ->where('page_id', $page->id)
                ->update(['sort_order' => $blockData['sort_order']]);
        }

        return response()->json(['message' => 'Blocks reordered successfully']);
    }

    // ============ NAVIGATION ============

    /**
     * Get navigation menus
     */
    public function getNavigationMenus(): JsonResponse
    {
        $menus = NavigationMenu::with('activeItems')->active()->get();
        return response()->json($menus);
    }

    /**
     * Update navigation menu
     */
    public function updateNavigationMenu(Request $request, NavigationMenu $menu): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.label' => 'required|string|max:255',
            'items.*.url' => 'required|string|max:255',
            'items.*.target' => 'required|in:_self,_blank',
            'items.*.sort_order' => 'required|integer|min:0',
            'items.*.is_active' => 'boolean',
            'items.*.parent_id' => 'nullable|exists:navigation_items,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Delete existing items
        $menu->items()->delete();

        // Create new items
        foreach ($request->items as $itemData) {
            $itemData['menu_id'] = $menu->id;
            NavigationItem::create($itemData);
        }

        return response()->json(['message' => 'Navigation updated successfully']);
    }

    // ============ UTILITIES ============

    /**
     * Get available content block types
     */
    public function getContentBlockTypes(): JsonResponse
    {
        return response()->json(ContentBlock::getAvailableTypes());
    }

    /**
     * Get default content for block type
     */
    public function getDefaultContent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', Rule::in(array_keys(ContentBlock::getAvailableTypes()))],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return response()->json(ContentBlock::getDefaultContent($request->type));
    }
}
