import os
import re
import requests
from bs4 import BeautifulSoup
from urllib.parse import urlparse, urljoin

def download_file(url, folder):
    """Downloads a file from a URL to a specified folder."""
    if not url:
        return None
    
    try:
        local_filename = os.path.join(folder, os.path.basename(urlparse(url).path))
        if not os.path.exists(folder):
            os.makedirs(folder)

        with requests.get(url, stream=True) as r:
            r.raise_for_status()
            with open(local_filename, 'wb') as f:
                for chunk in r.iter_content(chunk_size=8192):
                    f.write(chunk)
        return os.path.basename(local_filename)
    except requests.exceptions.RequestException as e:
        print(f"Error downloading {url}: {e}")
        return None
    except Exception as e:
        print(f"An unexpected error occurred while downloading {url}: {e}")
        return None

def backup_app(html_file_path, backup_folder_name="yasin"):
    """
    Backs up the HTML application, downloads external resources,
    and updates paths for offline use.
    """
    if not os.path.exists(html_file_path):
        print(f"Error: HTML file not found at {html_file_path}")
        return

    base_dir = os.path.dirname(html_file_path)
    backup_path = os.path.join(base_dir, backup_folder_name)
    
    if not os.path.exists(backup_path):
        os.makedirs(backup_path)
        print(f"Created backup directory: {backup_path}")
    else:
        print(f"Backup directory already exists: {backup_path}")

    # Copy the main HTML file
    new_html_file_path = os.path.join(backup_path, os.path.basename(html_file_path))
    with open(html_file_path, 'r', encoding='utf-8') as f:
        html_content = f.read()

    soup = BeautifulSoup(html_content, 'html.parser')
    
    # Track downloaded files and their new paths
    downloaded_resources = {}

    # --- Process CSS files ---
    for link in soup.find_all('link', rel='stylesheet'):
        href = link.get('href')
        if href and not href.startswith('data:'):
            full_url = urljoin(html_file_path, href)
            if urlparse(full_url).scheme in ['http', 'https']:
                print(f"Downloading CSS: {full_url}")
                local_name = download_file(full_url, backup_path)
                if local_name:
                    link['href'] = local_name
                    downloaded_resources[href] = local_name
            elif os.path.exists(os.path.join(base_dir, href)): # Local CSS
                local_name = os.path.basename(href)
                os.makedirs(os.path.dirname(os.path.join(backup_path, href)), exist_ok=True) # Ensure subdir exists
                os.system(f'copy "{os.path.join(base_dir, href)}" "{os.path.join(backup_path, href)}"')
                downloaded_resources[href] = local_name
    
    # --- Process JS files ---
    for script in soup.find_all('script', src=True):
        src = script.get('src')
        if src and not src.startswith('data:'):
            full_url = urljoin(html_file_path, src)
            if urlparse(full_url).scheme in ['http', 'https']:
                print(f"Downloading JS: {full_url}")
                local_name = download_file(full_url, backup_path)
                if local_name:
                    script['src'] = local_name
                    downloaded_resources[src] = local_name
            elif os.path.exists(os.path.join(base_dir, src)): # Local JS
                local_name = os.path.basename(src)
                os.makedirs(os.path.dirname(os.path.join(backup_path, src)), exist_ok=True)
                os.system(f'copy "{os.path.join(base_dir, src)}" "{os.path.join(backup_path, src)}"')
                downloaded_resources[src] = local_name

    # --- Process Images ---
    for img in soup.find_all('img', src=True):
        src = img.get('src')
        if src and not src.startswith('data:'):
            full_url = urljoin(html_file_path, src)
            if urlparse(full_url).scheme in ['http', 'https']:
                print(f"Downloading Image: {full_url}")
                local_name = download_file(full_url, backup_path)
                if local_name:
                    img['src'] = local_name
                    downloaded_resources[src] = local_name
            elif os.path.exists(os.path.join(base_dir, src)): # Local Image
                local_name = os.path.basename(src)
                os.makedirs(os.path.dirname(os.path.join(backup_path, src)), exist_ok=True)
                os.system(f'copy "{os.path.join(base_dir, src)}" "{os.path.join(backup_path, src)}"')
                downloaded_resources[src] = local_name

    # --- Process Favicon ---
    for link in soup.find_all('link', rel=re.compile(r'icon', re.I)):
        href = link.get('href')
        if href:
            full_url = urljoin(html_file_path, href)
            if urlparse(full_url).scheme in ['http', 'https']:
                print(f"Downloading Favicon: {full_url}")
                local_name = download_file(full_url, backup_path)
                if local_name:
                    link['href'] = local_name
                    downloaded_resources[href] = local_name
            elif os.path.exists(os.path.join(base_dir, href)): # Local Favicon
                local_name = os.path.basename(href)
                os.makedirs(os.path.dirname(os.path.join(backup_path, href)), exist_ok=True)
                os.system(f'copy "{os.path.join(base_dir, href)}" "{os.path.join(backup_path, href)}"')
                downloaded_resources[href] = local_name

    # Write the modified HTML to the new location
    with open(new_html_file_path, 'w', encoding='utf-8') as f:
        f.write(str(soup))

    print(f"\nBackup complete! Check the '{backup_folder_name}' folder.")
    print(f"Main HTML file copied and updated: {new_html_file_path}")
    print("Downloaded resources:")
    for original, local in downloaded_resources.items():
        print(f"- {original} -> {local}")

# --- Example Usage ---
if __name__ == "__main__":
    # IMPORTANT: Replace 'indexnewone.html' with the actual path to your HTML file
    # If the script is in the same directory as indexnewone.html, use just the filename.
    # Otherwise, provide the full path: e.g., 'C:/Users/YourUser/Desktop/YourApp/indexnewone.html'
    html_file = 'indexnewone.html' 
    backup_app(html_file)