
import barcode
from barcode.writer import ImageWriter
import sys
import os

if __name__ == "__main__":
    if len(sys.argv) < 3:
        sys.exit(1)
    
    data = sys.argv[1]
    output_path = sys.argv[2]
    
    try:
        # Generate Code128 barcode
        code128 = barcode.Code128(data, writer=ImageWriter())
        # Save the barcode image with specific options for better quality
        # Pass the filename without extension, ImageWriter adds .png
        code128.save(output_path.rsplit('.', 1)[0], options={'module_width':0.2, 'module_height':6, 'quiet_zone': 2.0})
        sys.exit(0)
    except Exception as e:
        sys.exit(1)
