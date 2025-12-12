<?php

namespace SilverStripePWA\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Control\Director;

class ManifestController extends Controller
{
    private static $allowed_actions = [
        'index'
    ];

    /**
     * Generate and return the web app manifest (manifest.json)
     * Follows W3C Web App Manifest specification (2025 standards)
     *
     * @param mixed $url
     * @return string JSON manifest
     */
    public function index($url)
    {
        $config = SiteConfig::current_site_config();
        $baseURL = Director::absoluteBaseURL();
        $manifest = [];

        // App Identity
        $startUrl = $config->ManifestStartUrl ?: '/';
        $manifest['start_url'] = $startUrl;
        $manifest['id'] = $config->ManifestId ?: $startUrl;

        if ($config->ManifestScope) {
            $manifest['scope'] = $config->ManifestScope;
        }

        // Core properties
        if ($config->ManifestName) {
            $manifest['name'] = $config->ManifestName;
        }

        if ($config->ManifestShortName) {
            $manifest['short_name'] = $config->ManifestShortName;
        }

        if ($config->ManifestDescription) {
            $manifest['description'] = $config->ManifestDescription;
        }

        // Display settings
        $manifest['display'] = $config->ManifestDisplay ?: 'standalone';

        if ($config->ManifestOrientation) {
            $manifest['orientation'] = $config->ManifestOrientation;
        }

        // Colors
        if ($config->ManifestColor) {
            $manifest['theme_color'] = $config->ManifestColor;
        }

        if ($config->ManifestBackgroundColor) {
            $manifest['background_color'] = $config->ManifestBackgroundColor;
        } elseif ($config->ManifestColor) {
            $manifest['background_color'] = $config->ManifestColor;
        }

        // Language & direction
        if ($config->ManifestLang) {
            $manifest['lang'] = $config->ManifestLang;
        }

        if ($config->ManifestDir && $config->ManifestDir !== 'auto') {
            $manifest['dir'] = $config->ManifestDir;
        }

        // Categories
        if ($config->ManifestCategories) {
            $manifest['categories'] = [$config->ManifestCategories];
        }

        // Icons - Generate multiple sizes from uploaded logo
        $icons = $this->generateIcons($config);
        if (!empty($icons)) {
            $manifest['icons'] = $icons;
        }

        // Screenshots
        $screenshots = $this->generateScreenshots($config);
        if (!empty($screenshots)) {
            $manifest['screenshots'] = $screenshots;
        }

        // Shortcuts
        $shortcuts = $this->generateShortcuts($config);
        if (!empty($shortcuts)) {
            $manifest['shortcuts'] = $shortcuts;
        }

        // Set response headers
        $this->getResponse()->addHeader('Content-Type', 'application/manifest+json; charset=utf-8');
        $this->getResponse()->addHeader('Cache-Control', 'public, max-age=86400');

        return json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generate icon array for manifest with multiple sizes
     * Includes both standard and maskable icons
     *
     * @param SiteConfig $config
     * @return array
     */
    private function generateIcons(SiteConfig $config): array
    {
        $icons = [];
        $sizes = [48, 72, 96, 128, 144, 152, 192, 384, 512];

        $logo = $config->ManifestLogo();
        if ($logo && $logo->exists()) {
            $mime = $logo->getMimeType();

            foreach ($sizes as $size) {
                $icons[] = [
                    'src' => $logo->Fill($size, $size)->getAbsoluteURL(),
                    'sizes' => "{$size}x{$size}",
                    'type' => $mime,
                    'purpose' => 'any'
                ];
            }
        }

        // Add maskable icon if provided
        $maskableIcon = $config->ManifestMaskableIcon();
        if ($maskableIcon && $maskableIcon->exists()) {
            $maskableSizes = [192, 512];
            $mime = $maskableIcon->getMimeType();

            foreach ($maskableSizes as $size) {
                $icons[] = [
                    'src' => $maskableIcon->Fill($size, $size)->getAbsoluteURL(),
                    'sizes' => "{$size}x{$size}",
                    'type' => $mime,
                    'purpose' => 'maskable'
                ];
            }
        }

        return $icons;
    }

    /**
     * Generate screenshots array for manifest
     *
     * @param SiteConfig $config
     * @return array
     */
    private function generateScreenshots(SiteConfig $config): array
    {
        $screenshots = [];

        $wideScreenshot = $config->ManifestScreenshotWide();
        if ($wideScreenshot && $wideScreenshot->exists()) {
            $screenshots[] = [
                'src' => $wideScreenshot->getAbsoluteURL(),
                'sizes' => $wideScreenshot->getWidth() . 'x' . $wideScreenshot->getHeight(),
                'type' => $wideScreenshot->getMimeType(),
                'form_factor' => 'wide',
                'label' => $config->ManifestName ?: 'App Screenshot'
            ];
        }

        $narrowScreenshot = $config->ManifestScreenshotNarrow();
        if ($narrowScreenshot && $narrowScreenshot->exists()) {
            $screenshots[] = [
                'src' => $narrowScreenshot->getAbsoluteURL(),
                'sizes' => $narrowScreenshot->getWidth() . 'x' . $narrowScreenshot->getHeight(),
                'type' => $narrowScreenshot->getMimeType(),
                'form_factor' => 'narrow',
                'label' => $config->ManifestName ?: 'App Screenshot'
            ];
        }

        return $screenshots;
    }

    /**
     * Generate shortcuts array for manifest
     *
     * @param SiteConfig $config
     * @return array
     */
    private function generateShortcuts(SiteConfig $config): array
    {
        $shortcuts = [];

        if ($config->hasMethod('ManifestShortcuts')) {
            foreach ($config->ManifestShortcuts()->limit(4) as $shortcut) {
                $shortcuts[] = $shortcut->toManifestArray();
            }
        }

        return $shortcuts;
    }
}
