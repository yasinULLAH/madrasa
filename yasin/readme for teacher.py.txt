This specific app is **hardcoded** to use a specific driver file (`dpfpdd.dll`). It will **not work** if you just buy a random scanner or plug it in without the specific software below.

Here is the exact, step-by-step guide to make this work, including where to buy the hardware and where to get the files.

### 1. The Hardware

You must buy this exact device. The code is written for **DigitalPersona** scanners.

* **Device Name:** DigitalPersona U.are.U 4500 Fingerprint Reader
* **Where to Buy:**
* **Amazon:** Search for "DigitalPersona 4500" (approx $70-$100 USD).
* **Local Tech Store:** Ask for "Crossmatch" or "DigitalPersona" readers.
* **Alternative:** The **U.are.U 5100** or **5300** will also work with this code.
* *Warning:* Do **not** buy ZKTeco or generic unbranded scanners; they will not work with this script.



### 2. The Driver (Crucial Step)

The Python script needs `dpfpdd.dll` to talk to the scanner. This file is **not** installed by Windows automatically. You must install the "Run Time Environment" (RTE).

**Option A: The Direct Download (Easiest)**
This is an older but reliable version of the driver hosted by Dell (works on most Windows 10/11 PCs).

* **Download Link:** [DigitalPersona Fingerprint Reader Driver (Dell Support)](https://www.dell.com/support/home/en-us/drivers/driversdetails?driverid=r260116)
* **File Name:** `dp06_01_008_x64.exe` (for 64-bit Windows)
* **Installation Style:**
1. Download the file.
2. Double-click to run. It will unzip to a folder (usually `C:\Dell\Drivers\...`).
3. Go to that folder and run `Setup.exe`.
4. Click "Next" through the installer. Select "Default" or "Typical" installation.
5. **Restart your computer.**



**Option B: If Option A fails**
Search Google exactly for: `"DigitalPersona One Touch for Windows SDK 1.6.1 download"`

* Look for the **RTE** (Run Time Environment) installer.
* Install it. Check `C:\Windows\System32` to confirm `dpfpdd.dll` is there.

### 3. Python Setup

You need the correct libraries to run the interface and generate PDF/Excel reports.

**Open your Command Prompt (cmd) and run this exact block:**

```bash
pip install PyQt6 reportlab openpyxl

```

**Critical 32-bit vs 64-bit check:**

* If you installed **64-bit Python**, you **MUST** install the **64-bit Driver** (Option A above).
* If you installed **32-bit Python**, you must find the **32-bit Driver** (often named `x86` instead of `x64`).
* *How to check:* Type `python` in cmd. It will say `[MSC v... 64 bit (AMD64)]` if it is 64-bit.

### 4. Running the App

1. Create a folder on your Desktop named `AttendanceSystem`.
2. Save the Python code I gave you earlier as `teacher.py` inside that folder.
3. Connect your DigitalPersona Scanner to a USB port.
4. Open Command Prompt in that folder and run:
```bash
python teacher.py

```



### 5. Troubleshooting (If it crashes)

* **Error:** `AttributeError: function 'dpfpdd_init' not found` or `OSError: [WinError 126] The specified module could not be found`.
* **Fix:** This means the driver is missing. Re-install the driver from Step 2. Ensure `dpfpdd.dll` exists in `C:\Windows\System32`.


* **Error:** `Device Disconnected` (Red text in app).
* **Fix:** Unplug the scanner, wait 5 seconds, and plug it back in. Restart the Python app.



### Summary Checklist

1. [ ] Buy **DigitalPersona 4500**.
2. [ ] Download & Install **DigitalPersona RTE Driver** (from Dell link).
3. [ ] Run `pip install PyQt6 reportlab openpyxl`.
4. [ ] Plug in device.
5. [ ] Run `python teacher.py`.