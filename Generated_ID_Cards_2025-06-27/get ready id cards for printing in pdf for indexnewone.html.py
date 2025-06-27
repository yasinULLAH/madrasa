import os
from PIL import Image
from reportlab.pdfgen import canvas
from reportlab.lib.units import inch
from reportlab.lib.pagesizes import letter

# --- CONFIGURATION ---
#INPUT_FOLDER = 'id_cards'       # The folder where you extracted the ZIP file
INPUT_FOLDER = os.path.dirname(os.path.abspath(__file__))
OUTPUT_PDF = 'id_cards_for_print.pdf'

# INCREASED DIMENSIONS: Changed from 3.375x2.125 to 3.4x2.2
CARD_WIDTH = 3.9 * inch
CARD_HEIGHT = 2.5 * inch

CARDS_PER_ROW = 2 
ROWS_PER_PAGE = 4 

# You can adjust the gutter space if needed
GUTTER_X = 0.1 * inch  # Space between front and back cards
GUTTER_Y = 0.15 * inch # Reduced vertical space to help fit the larger cards
# --- END CONFIGURATION ---

def find_and_pair_images(folder_path):
    """Finds all PNGs and pairs them by their ID."""
    pairs = {}
    print(f"Scanning for images in '{folder_path}'...")
    
    if not os.path.isdir(folder_path):
        print(f"Error: Input folder '{folder_path}' not found.")
        print("Please create it and extract your ID card images there.")
        return []

    for filename in os.listdir(folder_path):
        if filename.lower().endswith('.png'):
            try:
                parts = filename.rsplit('_', 2)
                image_id = parts[0]
                side = parts[2].split('.')[0].lower() # 'front' or 'back'

                if image_id not in pairs:
                    pairs[image_id] = {}
                
                pairs[image_id][side] = os.path.join(folder_path, filename)
            except IndexError:
                print(f"Warning: Could not parse filename '{filename}'. Skipping.")

    complete_pairs = []
    for image_id, sides in sorted(pairs.items()):
        if 'front' in sides and 'back' in sides:
            complete_pairs.append({
                'id': image_id,
                'front': sides['front'],
                'back': sides['back']
            })
        else:
            print(f"Warning: Incomplete pair for ID '{image_id}'. Skipping.")
            
    print(f"Found {len(complete_pairs)} complete front/back pairs.")
    return complete_pairs

def create_pdf(image_pairs):
    """Creates the PDF document with the arranged ID cards."""
    if not image_pairs:
        print("No image pairs to process. PDF not created.")
        return

    c = canvas.Canvas(OUTPUT_PDF, pagesize=letter)
    page_width, page_height = letter

    total_cards_width = (CARD_WIDTH * CARDS_PER_ROW) + (GUTTER_X * (CARDS_PER_ROW - 1))
    total_cards_height = (CARD_HEIGHT * ROWS_PER_PAGE) + (GUTTER_Y * (ROWS_PER_PAGE - 1))

    margin_x = (page_width - total_cards_width) / 2
    margin_y = (page_height - total_cards_height) / 2
    
    if margin_y < 0.25 * inch:
        print("Warning: Vertical margins are very small. The layout might be too tight for some printers.")
    
    if margin_x < 0 or margin_y < 0:
        print("Error: Card dimensions and gutters are too large to fit on the page.")
        print("Please reduce CARD_WIDTH, CARD_HEIGHT, or GUTTER values.")
        return

    pairs_per_page = ROWS_PER_PAGE
    for i in range(0, len(image_pairs), pairs_per_page):
        chunk = image_pairs[i:i + pairs_per_page]
        
        print(f"Processing page {c.getPageNumber()} with {len(chunk)} pairs...")

        for row_index, pair in enumerate(chunk):
            y = page_height - margin_y - CARD_HEIGHT - (row_index * (CARD_HEIGHT + GUTTER_Y))
            x_front = margin_x
            x_back = margin_x + CARD_WIDTH + GUTTER_X

            try:
                c.drawImage(pair['front'], x_front, y, width=CARD_WIDTH, height=CARD_HEIGHT, preserveAspectRatio=True, anchor='c')
                c.drawImage(pair['back'], x_back, y, width=CARD_WIDTH, height=CARD_HEIGHT, preserveAspectRatio=True, anchor='c')
            except Exception as e:
                print(f"Error drawing image for ID {pair['id']}: {e}")

        c.showPage()

    c.save()
    print(f"\nSuccessfully created '{OUTPUT_PDF}' with {c.getPageNumber() - 1} page(s).")

# --- Main Execution ---
if __name__ == "__main__":
    pairs = find_and_pair_images(INPUT_FOLDER)
    create_pdf(pairs)