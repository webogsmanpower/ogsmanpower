<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\NavigationMenu;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PublicContentController extends Controller
{
    /**
     * Get public site settings
     */
    public function getSiteSettings(): JsonResponse
    {
        $settings = [
            'site_title' => SiteSetting::get('site_title', 'Overseas Global Solutions'),
            'site_description' => SiteSetting::get('site_description', 'Connect with Global Opportunities'),
            'site_logo' => SiteSetting::getLogoUrl(),
            'contact_email' => SiteSetting::get('contact_email'),
            'contact_phone' => SiteSetting::get('contact_phone'),
            'social_links' => SiteSetting::get('social_links', []),
        ];

        return response()->json($settings);
    }

    /**
     * Get published page by slug
     */
    public function getPage(string $slug): JsonResponse
    {
        $page = Page::getBySlug($slug);

        if (!$page) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        return response()->json($page);
    }

    /**
     * Get homepage content
     */
    public function getHomepage(): JsonResponse
    {
        $page = Page::getHomepage();

        if (!$page) {
            return response()->json(['error' => 'Homepage not found'], 404);
        }

        return response()->json($page);
    }

    /**
     * Get navigation menus
     */
    public function getNavigation(): JsonResponse
    {
        $mainMenu = NavigationMenu::getMain();
        $footerMenu = NavigationMenu::getFooter();

        return response()->json([
            'main' => $mainMenu,
            'footer' => $footerMenu,
        ]);
    }

    /**
     * Get all published pages
     */
    public function getAllPages(): JsonResponse
    {
        $pages = Page::published()
            ->select('id', 'title', 'slug', 'meta_description', 'template')
            ->orderBy('sort_order')
            ->get();

        return response()->json($pages);
    }
}
