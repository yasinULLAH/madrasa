; 🔐 Auto-run as admin if not already
if not A_IsAdmin {
    Run *RunAs "%A_ScriptFullPath%"
    ExitApp
}

SetWorkingDir, %A_ScriptDir%
SetBatchLines, -1
DetectHiddenWindows, On

; 🔐 Step 0: License Check
licensePath := "C:\Windows\System32\.hidden_sys_lic.txt"
expectedKey := "allah-1"
oneMonth := 2592000 ; 30 days in seconds

; Create license file if not exist
if !FileExist(licensePath) {
    FormatTime, nowTimestamp,, yyyyMMddHHmmss
    FileAppend, created=%nowTimestamp%, %licensePath%
}

FileRead, licenseData, %licensePath%

if (InStr(licenseData, "activated=1")) {
    ; License already activated
} else {
    RegExMatch(licenseData, "created=(\d+)", m)
    createdTimestamp := m1

    FormatTime, nowTimestamp,, yyyyMMddHHmmss

    EnvAdd, createdTimestamp, 0, Seconds
    EnvAdd, nowTimestamp, 0, Seconds

    timeElapsed := nowTimestamp - createdTimestamp

    if (timeElapsed > oneMonth) {
        InputBox, licenseInput, License Required, Please enter your license key to continue., , 300, 130

        if (licenseInput = expectedKey) {
            FileAppend, `nactivated=1, %licensePath%
            MsgBox, ✅ License activated. Thank you.
        } else {
            MsgBox, ❌ Invalid license key. Exiting.
            ExitApp
        }
    }
}

; 👟 Step 1: Close Chrome
Process, Close, chrome.exe
;Process, Close, msedge.exe
Sleep, 1000

; 🚀 Step 2: Launch Chrome in kiosk mode
Run, "C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk "D:\yasin\indexnewone.html"
;Run, "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" --app="file:///D:/yasin/indexnewone.html" --user-data-dir="C:\EdgeProfiles\MyApp" --start-fullscreen
;Run, "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" --app="file:///C:/yasin/indexnewone.html" --profile-directory="Default" --start-fullscreen --no-first-run


Sleep, 3000
Send, {F11}
ExitApp
