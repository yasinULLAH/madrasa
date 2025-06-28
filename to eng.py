import os
from bs4 import BeautifulSoup
import re # <--- Add this line!

def prepare_for_google_translate_widget(input_file, output_file):
    with open(input_file, 'r', encoding='utf-8') as f:
        html_content = f.read()

    soup = BeautifulSoup(html_content, 'html.parser')

    # Change html tag lang and dir
    html_tag = soup.find('html')
    if html_tag:
        html_tag['lang'] = 'en'
        html_tag['dir'] = 'ltr'

    # Change body style direction in CSS
    style_tag = soup.find('style')
    if style_tag and style_tag.string:
        css_content = style_tag.string
        css_content = css_content.replace('direction: rtl;', 'direction: ltr;')
        # Also, if any font-family specifies 'Noto Nastaliq Urdu' or similar explicitly,
        # it might need to be replaced with a more generic English-friendly font or removed
        # if Google Translate might have issues with it.
        css_content = re.sub(r"font-family: 'Noto Nastaliq Urdu', 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif !important;", "font-family: 'Segoe UI', Arial, sans-serif !important;", css_content)
        css_content = re.sub(r"font-family: Calibri, Calibri, 'Jameel Noori Nastaleeq', Arial, sans-serif;", "font-family: 'Segoe UI', Arial, sans-serif;", css_content)
        css_content = re.sub(r"font-family: calibri;", "font-family: 'Segoe UI', Arial, sans-serif;", css_content)
        css_content = re.sub(r"font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif;", "font-family: 'Segoe UI', Arial, sans-serif;", css_content)
        css_content = re.sub(r"font-family: 'Noto Nastaliq Urdu', sans-serif;", "font-family: 'Segoe UI', Arial, sans-serif;", css_content)

        # IMPORTANT: Fix CSS rules that are direction-dependent
        # These are examples, you might need to find more specific ones in your CSS
        # Removing specific Urdu text alignment that conflicts with LTR layout
        css_content = re.sub(r"\.form-grid div span \{[\s\S]*?\}", "", css_content)
        css_content = re.sub(r"\.cert-body \{[\s\S]*?text-align: right;\}", """
            .cert-body {
                width: 100%;
                margin-top: 30px;
                font-size: 19px;
                line-height: 2.2;
                text-align: left; /* Changed for LTR */
            }
        """, css_content)
        css_content = re.sub(r"\.cert-footer \.date-section \{[\s\S]*?\}", """
            .cert-footer .date-section {
                text-align: left; /* Changed for LTR */
                flex: 1;
            }
        """, css_content)
        css_content = re.sub(r"\.cert-footer \.signature-section \{[\s\S]*?\}", """
            .cert-footer .signature-section {
                text-align: right; /* Changed for LTR */
                flex: 1;
            }
        """, css_content)
        css_content = re.sub(r"\.id-card\.front \{[\s\S]*?flex-direction: row;\}", """
            .id-card.front {
                flex-direction: row; /* Usually left-to-right */
            }
        """, css_content)
        css_content = re.sub(r"\.id-card \.content \{[\s\S]*?text-align: right;\}", """
            .id-card .content {
                flex: 1;
                padding: 5px;
                font-size: 12px;
                color: #333;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                text-align: left; /* Changed for LTR */
            }
        """, css_content)
        css_content = re.sub(r"\.id-card \.footer \{[\s\S]*?text-align: center;\}", """
            .id-card .footer {
                font-size: 10px;
                color: #666;
                text-align: left; /* Changed for LTR */
                position: absolute;
                bottom: 2px;
                width: 86%;
            }
        """, css_content)
        css_content = re.sub(r"text-align: right;", "text-align: left;", css_content) # General text-align right to left
        css_content = re.sub(r"margin-left: 10px;", "margin-right: 10px;", css_content) # Adjust for mobile nav button
        css_content = re.sub(r"margin-right: 23px;", "margin-left: 23px;", css_content) # Adjust for form-grid span
        css_content = re.sub(r"border-left: 4px solid #007bff;", "border-right: 4px solid #007bff;", css_content) # For dm-modal project message
        css_content = re.sub(r"border-right: 4px solid #007bff;", "border-left: 4px solid #007bff;", css_content) # For dm-modal project message (for LTR content)
        css_content = re.sub(r"border-left: 4px solid #6f42c1;", "border-right: 4px solid #6f42c1;", css_content) # For dm-modal verse
        css_content = re.sub(r"border-right: 4px solid #6f42c1;", "border-left: 4px solid #6f42c1;", css_content) # For dm-modal verse (for LTR content)
        css_content = re.sub(r"text-align: right;", "text-align: left;", css_content) # For mobile menu buttons
        css_content = re.sub(r"top: 100%;\s*right: 10px;", "top: 100%; left: 10px;", css_content) # For mobile nav dropdown
        css_content = re.sub(r"right: 20px;", "left: 20px;", css_content) # For excel export icon
        css_content = re.sub(r"right: 10px;", "left: 10px;", css_content) # For fixed save offline icon
        css_content = re.sub(r"right: 0;", "left: 0;", css_content) # For mobile menu absolute positioning


        style_tag.string = css_content

    # Remove Urdu font link (Noto Nastaliq Urdu)
    for link_tag in soup.find_all('link', href=re.compile(r'fonts\.googleapis\.com.*Noto\+Nastaliq\+Urdu')):
        link_tag.decompose()

    # Remove Urdu font import in CSS (if any still there after main style tag processing)
    for tag in soup.find_all('style'):
        if tag.string:
            tag.string = re.sub(r"@import url\('https://fonts\.googleapis\.com/css2\?family=Noto\+Nastaliq\+Urdu:wght@400;700&display=swap'\);", "", tag.string)

    # Add Google Translate Widget Script
    head_tag = soup.find('head')
    if head_tag:
        # Placeholder for Google Translate element - Can be placed anywhere, but usually top-right or top-left.
        # Placing it just inside the body or a top container is common for visibility.
        # For this script, let's put it at the very top of the body for simplicity.
        body_tag = soup.find('body')
        if body_tag:
            google_translate_div = soup.new_tag("div", id="google_translate_element")
            google_translate_div['style'] = "position: fixed; top: 10px; right: 10px; z-index: 9999;" # Position it visibly
            body_tag.insert(0, google_translate_div) # Insert at the beginning of the body


        # Google Translate Widget JS
        gt_script = soup.new_tag("script")
        gt_script.string = """
            function googleTranslateElementInit() {
                new google.translate.TranslateElement({
                    pageLanguage: 'ur', // Original language of your page
                    includedLanguages: 'en', // Languages you want to offer for translation
                    layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                    autoDisplay: false // Important: set to false to prevent immediate display.
                                        // However, this often doesn't work as expected for hiding the initial bar.
                                        // Hiding completely is very difficult and can break.
                }, 'google_translate_element');
            }

            // Function to trigger translation (you'd call this if you want to auto-translate)
            // Note: This is an unofficial/hacky way and might not work reliably.
            function triggerGoogleTranslate() {
                var selectElement = document.querySelector('.goog-te-combo');
                if (selectElement) {
                    selectElement.value = 'en'; // Set target language to English
                    selectElement.dispatchEvent(new Event('change')); // Trigger change event
                }
            }

            // To run translation on page load (try after a short delay)
            window.onload = function() {
                setTimeout(function() {
                    triggerGoogleTranslate();
                    // Optionally try to hide the bar, but it's very unreliable.
                    // var gt_bar = document.querySelector('.goog-te-banner-frame');
                    // if (gt_bar) {
                    //     gt_bar.style.display = 'none';
                    //     gt_bar.style.height = '0px';
                    //     gt_bar.style.overflow = 'hidden';
                    // }
                }, 1500); // Give widget time to load and populate. Increased delay slightly.
            };
        """
        head_tag.append(gt_script)

        # Google Translate widget loader script
        gt_loader_script = soup.new_tag("script", src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit")
        head_tag.append(gt_loader_script)

    # --- Write the modified HTML to the output file ---
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(str(soup))

    print(f"Preparation complete for Google Translate widget. Check '{output_file}'.")
    print("WARNING: Client-side translation widgets often cause conflicts and layout issues.")
    print("The auto-translation attempt is hacky and unreliable. Full hidden translation is not supported.")
    print("You will likely still see a Google Translate bar/widget on the page.")


# Run the script
input_html_file = 'index.html'
output_html_file = 'index_gt.html' # New output file name

if os.path.exists(input_html_file):
    prepare_for_google_translate_widget(input_html_file, output_html_file)
else:
    print(f"Error: Input file '{input_html_file}' not found.")
    print("Please make sure 'index.html' is in the same directory as this script.")