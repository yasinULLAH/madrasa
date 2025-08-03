# pwa_builder.py

import os
import json
import re
from PIL import Image
from bs4 import BeautifulSoup

# --- ⚙️ CONFIGURATION: UPDATE THESE VALUES ---
SOURCE_LOGO_PATH = r"C:\Users\Yasin\Downloads\Yasin Soft\logo.png"
PROJECT_DIR = os.path.dirname(os.path.abspath(__file__))
APP_NAME = "My Web App"
SHORT_NAME = "WebApp"
APP_DESCRIPTION = "A description of the web application."
BACKGROUND_COLOR = "#ffffff"
THEME_COLOR = "#007bff"
# --- END OF CONFIGURATION ---


def generate_pwa_icons(source_path, output_dir):
    print("--- 1. Generating PWA Icons ---")
    if not os.path.exists(source_path):
        print(f"❌ Error: Source logo not found at '{source_path}'.")
        return []
    icon_sizes = [72, 96, 128, 144, 152, 192, 384, 512]
    generated_icons = []
    try:
        with Image.open(source_path) as logo:
            logo = logo.convert("RGBA")
            for size in icon_sizes:
                filename = f"icon-{size}.png"
                output_path = os.path.join(output_dir, filename)
                canvas = Image.new("RGBA", (size, size), (0, 0, 0, 0))
                logo_copy = logo.copy()
                logo_copy.thumbnail((size, size))
                left = (size - logo_copy.width) // 2
                top = (size - logo_copy.height) // 2
                canvas.paste(logo_copy, (left, top))
                canvas.save(output_path, "PNG")
                print(f"✅ Created: {filename}")
                generated_icons.append({"src": filename, "sizes": f"{size}x{size}", "type": "image/png"})
        return generated_icons
    except Exception as e:
        print(f"❌ Error generating icons: {e}")
        return []

def discover_assets(project_dir):
    print(f"\n--- 2. Discovering App Files (Local & External) ---")
    local_assets = set(['./', 'offline.html']) # Start with root and offline page
    external_assets = set()
    html_files = []
    for root, _, files in os.walk(project_dir):
        if any(part.startswith('.') for part in root.split(os.sep)): continue
        for file in files:
            if file.endswith(".html"): html_files.append(os.path.join(root, file))
    if not html_files:
        print("❌ Error: No HTML files found.")
        return [], [], []
    for html_path in html_files:
        relative_path = os.path.relpath(html_path, project_dir).replace("\\", "/")
        local_assets.add(relative_path)
        print(f"🔎 Scanning: {relative_path}")
        with open(html_path, 'r', encoding='utf-8') as f:
            soup = BeautifulSoup(f.read(), 'html.parser')
            for tag in soup.find_all(['link', 'script', 'img', 'source']):
                attr = 'href' if tag.has_attr('href') else 'src'
                if tag.has_attr(attr):
                    path = tag[attr]
                    if not path or path.startswith(('#', 'mailto:', 'tel:')): continue
                    
                    # **FIX:** Differentiate between local and external assets
                    if path.startswith('http'):
                        external_assets.add(path)
                    else:
                        asset_path = os.path.normpath(os.path.join(os.path.dirname(relative_path), path)).replace("\\", "/")
                        if os.path.exists(os.path.join(project_dir, asset_path)):
                            local_assets.add(asset_path)

    print(f"✅ Discovered {len(local_assets)} local files and {len(external_assets)} external libraries.")
    return sorted(list(local_assets)), sorted(list(external_assets)), html_files

def create_manifest(output_dir, icons, html_files):
    print("\n--- 3. Creating manifest.json ---")
    start_url, app_title_from_html = "index.html", None
    potential_mains = [f for f in html_files if "madrasa finance.html" in f] or \
                      [f for f in html_files if "index.html" in f] or html_files
    start_file_path = potential_mains[0]
    start_url = os.path.relpath(start_file_path, output_dir).replace("\\", "/")
    try:
        with open(start_file_path, 'r', encoding='utf-8') as f:
            soup = BeautifulSoup(f.read(), 'html.parser')
            if soup.title and soup.title.string:
                app_title_from_html = soup.title.string.strip()
                print(f"✅ Detected App Title: '{app_title_from_html}'")
    except Exception as e: print(f"⚠️ Could not read title from HTML: {e}")
    manifest = {"name": app_title_from_html or APP_NAME, "short_name": app_title_from_html or SHORT_NAME,
                "description": APP_DESCRIPTION, "start_url": start_url, "display": "standalone",
                "background_color": BACKGROUND_COLOR, "theme_color": THEME_COLOR,
                "orientation": "portrait-primary", "icons": icons}
    with open(os.path.join(output_dir, "manifest.json"), 'w', encoding='utf-8') as f:
        json.dump(manifest, f, indent=2)
    print(f"✅ Created: manifest.json")

def create_service_worker(output_dir, local_assets, external_assets):
    print("\n--- 4. Creating sw.js (Service Worker) ---")
    cache_version = os.urandom(4).hex()
    
    # **FIX:** This new service worker template is much smarter.
    # It uses different caching strategies for local files and external libraries.
    sw_template = f"""
// This file is auto-generated by pwa_builder.py. Do not edit.
importScripts('https://storage.googleapis.com/workbox-cdn/releases/7.0.0/workbox-sw.js');

const CACHE_NAME_PREFIX = 'pwa-cache';
const CACHE_VERSION = '{cache_version}';

if (workbox) {{
    console.log(`Workbox is loaded.`);
    
    // Set up precaching for all our local files.
    // This makes the app load instantly offline.
    workbox.precaching.precacheAndRoute({json.dumps(local_assets, indent=4)});

    // Strategy for external CSS, JS libraries (e.g., from a CDN)
    // StaleWhileRevalidate: Serve from cache first (fast!), then check for updates in the background.
    workbox.routing.registerRoute(
        ({{request}}) => request.destination === 'script' || request.destination === 'style',
        new workbox.strategies.StaleWhileRevalidate({{
            cacheName: `${{CACHE_NAME_PREFIX}}-external-assets-${{CACHE_VERSION}}`,
        }})
    );
    
    // Strategy for fonts
    // CacheFirst: Once a font is downloaded, serve it from the cache forever.
    workbox.routing.registerRoute(
        ({{request}}) => request.destination === 'font',
        new workbox.strategies.CacheFirst({{
            cacheName: `${{CACHE_NAME_PREFIX}}-fonts-${{CACHE_VERSION}}`,
            plugins: [
                new workbox.expiration.ExpirationPlugin({{ maxEntries: 20, maxAgeSeconds: 365 * 24 * 60 * 60 }}), // Cache for 1 year
            ],
        }})
    );

    // Offline fallback for pages.
    // If you're offline and try to go to a page you've never visited, it shows offline.html
    const offlineFallback = 'offline.html';
    workbox.routing.setCatchHandler(async ({{event}}) => {{
        if (event.request.destination === 'document') {{
            return await caches.match(offlineFallback);
        }}
        return Response.error();
    }});

}} else {{
    console.log(`Workbox failed to load.`);
}}
"""
    with open(os.path.join(output_dir, "sw.js"), 'w', encoding='utf-8') as f:
        f.write(sw_template)
    print("✅ Created: sw.js")

def update_html_files(html_files):
    print("\n--- 5. Updating HTML Files ---")
    manifest_link_str = '<link rel="manifest" href="manifest.json">'
    sw_script_str = """<script>if ('serviceWorker' in navigator) { window.addEventListener('load', () => { navigator.serviceWorker.register('./sw.js').then(r => console.log('ServiceWorker registered.')).catch(e => console.log('ServiceWorker registration failed: ', e)); }); }</script>"""
    for html_path in html_files:
        with open(html_path, 'r+', encoding='utf-8') as f:
            content = f.read()
            soup = BeautifulSoup(content, 'html.parser')
            updated = False
            if soup.head and not soup.find('link', {'rel': 'manifest'}):
                soup.head.append(BeautifulSoup(manifest_link_str, 'html.parser'))
                updated = True
                print(f"   - Added manifest link to {os.path.basename(html_path)}")
            if soup.body and "navigator.serviceWorker.register" not in content:
                soup.body.append(BeautifulSoup(sw_script_str, 'html.parser'))
                updated = True
                print(f"   - Added service worker script to {os.path.basename(html_path)}")
            if updated:
                f.seek(0); f.write(str(soup)); f.truncate()
    print("✅ HTML files are up to date.")

if __name__ == "__main__":
    print("🚀 Starting PWA Automation Script...")
    # Make sure the offline.html file exists
    if not os.path.exists(os.path.join(PROJECT_DIR, 'offline.html')):
        with open(os.path.join(PROJECT_DIR, 'offline.html'), 'w', encoding='utf-8') as f:
            f.write("<!DOCTYPE html><html><head><title>Offline</title></head><body><h1>You are offline.</h1><p>Please check your connection.</p></body></html>")
        print("✅ Created a basic 'offline.html' fallback page.")

    generated_icons = generate_pwa_icons(SOURCE_LOGO_PATH, PROJECT_DIR)
    local_assets, external_assets, html_files = discover_assets(PROJECT_DIR)
    
    if not html_files:
        print("\n❌ Script stopped because no HTML files were found.")
    else:
        if generated_icons:
            local_assets.append(generated_icons[-1]['src'])
        create_manifest(PROJECT_DIR, generated_icons, html_files)
        create_service_worker(PROJECT_DIR, local_assets, external_assets)
        update_html_files(html_files)
        print("\n🎉 PWA setup is complete! Your app is now fully functional offline.")