
Attribute VB_Name = "modLocalization"

Private labels As Collection

Private Sub Class_Initialize()
    Set labels = New Collection
    ' English Labels
    labels.Add "New Receipt", "New Receipt"
    labels.Add "Add Item", "Add Item"
    labels.Add "Remove Item", "Remove Item"
    labels.Add "Apply Discount", "Apply Discount"
    labels.Add "Taxes", "Taxes"
    labels.Add "Load Catalog", "Load Catalog"
    labels.Add "Save Receipt", "Save Receipt"
    labels.Add "Export PDF", "Export PDF"
    labels.Add "Print", "Print"
    labels.Add "Find Receipts", "Find Receipts"
    labels.Add "Settings", "Settings"
    labels.Add "SKU", "SKU"
    labels.Add "Item Name", "Item Name"
    labels.Add "Qty", "Qty"
    labels.Add "Unit Price", "Unit Price"
    labels.Add "Line Total", "Line Total"
    labels.Add "Receipt No", "Receipt No"
    labels.Add "Date", "Date"
    labels.Add "Customer", "Customer"
    labels.Add "Phone", "Phone"
    labels.Add "Email", "Email"
    labels.Add "Cashier", "Cashier"
    labels.Add "Payment", "Payment Method"
    labels.Add "Notes", "Notes"
    labels.Add "Cash", "Cash"
    labels.Add "Card", "Card"
    labels.Add "Wallet", "Wallet"
    labels.Add "Discount", "Disc. %"
    labels.Add "Tax", "Tax %"
    labels.Add "Subtotal", "Subtotal"
    labels.Add "TotalDiscount", "Total Discount"
    labels.Add "TotalTax", "Total Tax"
    labels.Add "ServiceCharge", "Service Charge"
    labels.Add "GrandTotal", "Grand Total"
    labels.Add "AmountInWords", "Amount in Words"
    labels.Add "ItemsCatalog", "Items Catalog"
    labels.Add "Price", "Price"
    labels.Add "TaxClass", "Tax Class"
    labels.Add "Catalog Manager", "Catalog Manager"
    labels.Add "Urdu Name", "Urdu Name"
    labels.Add "Next", "Next"
    labels.Add "Previous", "Previous"
    labels.Add "Search", "Search"
    labels.Add "Reset", "Reset"
    labels.Add "Company Settings", "Company Settings"
    labels.Add "Currency Settings", "Currency Settings"
    labels.Add "Tax & Discount Settings", "Tax & Discount Settings"
    labels.Add "Receipt Numbering", "Receipt Numbering"
    labels.Add "Localization", "Localization"
    labels.Add "Path", "Path"
    labels.Add "Next Receipt Counter", "Next Receipt Counter"
    labels.Add "Tax Rate", "Tax Rate (%)"
    labels.Add "Service Charge", "Service Charge (%)"
    labels.Add "Default Template", "Default Template"
    labels.Add "A4 Layout", "A4 Layout"
    labels.Add "Thermal 80mm", "Thermal 80mm"
    labels.Add "Company Name", "Company Name"
    labels.Add "Address", "Address"
    labels.Add "NTN", "NTN"
    labels.Add "Logo Path", "Logo Path"
    labels.Add "Symbol", "Symbol"
    labels.Add "Position", "Position"
    labels.Add "Decimal Places", "Decimal Places"
    labels.Add "Before", "Before"
    labels.Add "After", "After"
    labels.Add "Enable Urdu Mode", "Enable Urdu Mode (RTL)"
End Sub

Sub ApplyLocalization(ByVal isUrdu As Boolean)
    On Error GoTo ErrorHandler
    modUtils.LogAction "Applying localization: " & IIf(isUrdu, "Urdu", "English")
    
    If isUrdu Then
        ' Urdu Labels
        labels.Item("New Receipt") = "نئی رسید"
        labels.Item("Add Item") = "آئٹم شامل کریں"
        labels.Item("Remove Item") = "آئٹم ہٹائیں"
        labels.Item("Apply Discount") = "رعایت لاگو کریں"
        labels.Item("Taxes") = "ٹیکس"
        labels.Item("Load Catalog") = "کیٹلاگ لوڈ کریں"
        labels.Item("Save Receipt") = "رسید محفوظ کریں"
        labels.Item("Export PDF") = "پی ڈی ایف ایکسپورٹ کریں"
        labels.Item("Print") = "پرنٹ کریں"
        labels.Item("Find Receipts") = "رسیدیں تلاش کریں"
        labels.Item("Settings") = "ترتیبات"
        labels.Item("SKU") = "ایس کے یو"
        labels.Item("Item Name") = "آئٹم کا نام"
        labels.Item("Qty") = "مقدار"
        labels.Item("Unit Price") = "فی یونٹ قیمت"
        labels.Item("Line Total") = "کل لائن"
        labels.Item("Receipt No") = "رسید نمبر"
        labels.Item("Date") = "تاریخ"
        labels.Item("Customer") = "گاہک"
        labels.Item("Phone") = "فون"
        labels.Item("Email") = "ای میل"
        labels.Item("Cashier") = "کیشیئر"
        labels.Item("Payment") = "ادائیگی کا طریقہ"
        labels.Item("Notes") = "نوٹس"
        labels.Item("Cash") = "نقد"
        labels.Item("Card") = "کارڈ"
        labels.Item("Wallet") = "والٹ"
        labels.Item("Discount") = "رعایت ٪"
        labels.Item("Tax") = "ٹیکس ٪"
        labels.Item("Subtotal") = "سب ٹوٹل"
        labels.Item("TotalDiscount") = "کل رعایت"
        labels.Item("TotalTax") = "کل ٹیکس"
        labels.Item("ServiceCharge") = "سروس چارج"
        labels.Item("GrandTotal") = "کل رقم"
        labels.Item("AmountInWords") = "حروف میں رقم"
        labels.Item("ItemsCatalog") = "آئٹمز کیٹلاگ"
        labels.Item("Price") = "قیمت"
        labels.Item("TaxClass") = "ٹیکس کلاس"
        labels.Item("Catalog Manager") = "کیٹلاگ مینیجر"
        labels.Item("Urdu Name") = "اردو نام"
        labels.Item("Next") = "آگے"
        labels.Item("Previous") = "پیچھے"
        labels.Item("Search") = "تلاش کریں"
        labels.Item("Reset") = "دوبارہ ترتیب دیں"
        labels.Item("Company Settings") = "کمپنی کی ترتیبات"
        labels.Item("Currency Settings") = "کرنسی کی ترتیبات"
        labels.Item("Tax & Discount Settings") = "ٹیکس اور رعایت کی ترتیبات"
        labels.Item("Receipt Numbering") = "رسید نمبرنگ"
        labels.Item("Localization") = "مقامی سازی"
        labels.Item("Path") = "راستہ"
        labels.Item("Next Receipt Counter") = "اگلا رسید کاؤنٹر"
        labels.Item("Tax Rate") = "ٹیکس کی شرح (٪)"
        labels.Item("Service Charge") = "سروس چارج (٪)"
        labels.Item("Default Template") = "پہلے سے طے شدہ ٹیمپلیٹ"
        labels.Item("A4 Layout") = "اے فور لے آؤٹ"
        labels.Item("Thermal 80mm") = "تھرمل 80 ملی میٹر"
        labels.Item("Company Name") = "کمپنی کا نام"
        labels.Item("Address") = "پتہ"
        labels.Item("NTN") = "این ٹی این"
        labels.Item("Logo Path") = "لوگو کا راستہ"
        labels.Item("Symbol") = "علامت"
        labels.Item("Position") = "پوزیشن"
        labels.Item("Decimal Places") = "اعشاریہ کے مقامات"
        labels.Item("Before") = "پہلے"
        labels.Item("After") = "بعد"
        labels.Item("Enable Urdu Mode") = "اردو موڈ فعال کریں (RTL)"
    Else
        ' Reset to English labels (re-initialize or store English as default)
        Set labels = New Collection
        labels.Add "New Receipt", "New Receipt"
        labels.Add "Add Item", "Add Item"
        labels.Add "Remove Item", "Remove Item"
        labels.Add "Apply Discount", "Apply Discount"
        labels.Add "Taxes", "Taxes"
        labels.Add "Load Catalog", "Load Catalog"
        labels.Add "Save Receipt", "Save Receipt"
        labels.Add "Export PDF", "Export PDF"
        labels.Add "Print", "Print"
        labels.Add "Find Receipts", "Find Receipts"
        labels.Add "Settings", "Settings"
        labels.Add "SKU", "SKU"
        labels.Add "Item Name", "Item Name"
        labels.Add "Qty", "Qty"
        labels.Add "Unit Price", "Unit Price"
        labels.Add "Line Total", "Line Total"
        labels.Add "Receipt No", "Receipt No"
        labels.Add "Date", "Date"
        labels.Add "Customer", "Customer"
        labels.Add "Phone", "Phone"
        labels.Add "Email", "Email"
        labels.Add "Cashier", "Cashier"
        labels.Add "Payment", "Payment Method"
        labels.Add "Notes", "Notes"
        labels.Add "Cash", "Cash"
        labels.Add "Card", "Card"
        labels.Add "Wallet", "Wallet"
        labels.Add "Discount", "Disc. %"
        labels.Add "Tax", "Tax %"
        labels.Add "Subtotal", "Subtotal"
        labels.Add "TotalDiscount", "Total Discount"
        labels.Add "TotalTax", "Total Tax"
        labels.Add "ServiceCharge", "Service Charge"
        labels.Add "GrandTotal", "Grand Total"
        labels.Add "AmountInWords", "Amount in Words"
        labels.Add "ItemsCatalog", "Items Catalog"
        labels.Add "Price", "Price"
        labels.Add "TaxClass", "Tax Class"
        labels.Add "Catalog Manager", "Catalog Manager"
        labels.Add "Urdu Name", "Urdu Name"
        labels.Add "Next", "Next"
        labels.Add "Previous", "Previous"
        labels.Add "Search", "Search"
        labels.Add "Reset", "Reset"
        labels.Add "Company Settings", "Company Settings"
        labels.Add "Currency Settings", "Currency Settings"
        labels.Add "Tax & Discount Settings", "Tax & Discount Settings"
        labels.Add "Receipt Numbering", "Receipt Numbering"
        labels.Add "Localization", "Localization"
        labels.Add "Path", "Path"
        labels.Add "Next Receipt Counter", "Next Receipt Counter"
        labels.Add "Tax Rate", "Tax Rate (%)"
        labels.Add "Service Charge", "Service Charge (%)"
        labels.Add "Default Template", "Default Template"
        labels.Add "A4 Layout", "A4 Layout"
        labels.Add "Thermal 80mm", "Thermal 80mm"
        labels.Add "Company Name", "Company Name"
        labels.Add "Address", "Address"
        labels.Add "NTN", "NTN"
        labels.Add "Logo Path", "Logo Path"
        labels.Add "Symbol", "Symbol"
        labels.Add "Position", "Position"
        labels.Add "Decimal Places", "Decimal Places"
        labels.Add "Before", "Before"
        labels.Add "After", "After"
        labels.Add "Enable Urdu Mode", "Enable Urdu Mode (RTL)"
    End If
    
    ' Apply direction and font to active document
    With ActiveDocument
        If isUrdu Then
            .Content.ParagraphFormat.Bidi = True
            .DefaultTabStop = InchesToPoints(0.5)
            .Content.ParagraphFormat.Alignment = wdAlignParagraphRight
            .Content.Font.Name = AppSettings.UrduFont
        Else
            .Content.ParagraphFormat.Bidi = False
            .DefaultTabStop = InchesToPoints(0.5)
            .Content.ParagraphFormat.Alignment = wdAlignParagraphLeft
            .Content.Font.Name = AppSettings.EnglishFont
        End If
    End With
    
    ' Update all open forms
    For Each frm In UserForms
        Call UpdateFormControls(frm)
    Next frm
    
    Exit Sub
ErrorHandler:
    modUtils.LogError "ApplyLocalization", Err
End Sub

Function GetLabel(ByVal key As String) As String
    On Error GoTo ErrorHandler
    If labels.Exists(key) Then
        GetLabel = labels.Item(key)
    Else
        GetLabel = key ' Return key if label not found
    End If
    Exit Function
ErrorHandler:
    modUtils.LogError "GetLabel", Err
    GetLabel = key
End Function

Sub UpdateFormControls(ByVal frm As Object)
    On Error GoTo ErrorHandler
    Dim ctrl As Control
    Dim p As Property
    
    For Each ctrl In frm.Controls
        On Error Resume Next
        ' Update Caption for common controls
        If TypeOf ctrl Is MSForms.Label Then
            ctrl.Caption = GetLabel(ctrl.Name) ' Assume label name is the key
        ElseIf TypeOf ctrl Is MSForms.CommandButton Then
            ctrl.Caption = GetLabel(ctrl.Name)
        ElseIf TypeOf ctrl Is MSForms.TabStrip Then
            Dim tab As Variant
            For Each tab In ctrl.Tabs
                tab.Caption = GetLabel(tab.Caption) ' Use tab's current caption as key
            Next tab
        ElseIf TypeOf ctrl Is MSForms.Frame Then
             ctrl.Caption = GetLabel(ctrl.Name)
        End If
        
        ' Update font and direction for applicable controls
        If AppSettings.UrduMode Then
            ctrl.Font.Name = AppSettings.UrduFont
            If TypeOf ctrl Is MSForms.TextBox Or TypeOf ctrl Is MSForms.ComboBox Then
                ctrl.ReadingOrder = fmReadingOrderRightToLeft
            End If
        Else
            ctrl.Font.Name = AppSettings.EnglishFont
            If TypeOf ctrl Is MSForms.TextBox Or TypeOf ctrl Is MSForms.ComboBox Then
                ctrl.ReadingOrder = fmReadingOrderLeftToRight
            End If
        End If
        On Error GoTo 0
    Next ctrl
    
    Exit Sub
ErrorHandler:
    modUtils.LogError "UpdateFormControls", Err
End Sub

Function ConvertNumeralsToUrdu(ByVal text As String) As String
    On Error GoTo ErrorHandler
    Dim i As Long
    Dim char As String
    Dim result As String
    result = ""
    
    For i = 1 To Len(text)
        char = Mid(text, i, 1)
        Select Case char
            Case "0": result = result & "۰"
            Case "1": result = result & "۱"
            Case "2": result = result & "۲"
            Case "3": result = result & "۳"
            Case "4": result = result & "۴"
            Case "5": result = result & "۵"
            Case "6": result = result & "۶"
            Case "7": result = result & "۷"
            Case "8": result = result & "۸"
            Case "9": result = result & "۹"
            Case Else: result = result & char
        End Select
    Next i
    ConvertNumeralsToUrdu = result
    Exit Function
ErrorHandler:
    modUtils.LogError "ConvertNumeralsToUrdu", Err
    ConvertNumeralsToUrdu = text
End Function
