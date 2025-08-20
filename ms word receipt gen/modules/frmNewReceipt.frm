
VERSION 5.00
Begin {C62A69F0-16DC-11CE-9E98-00AA00574A4F} frmNewReceipt 
   Caption         =   "New Receipt"
   ClientHeight    =   4695
   ClientLeft      =   120
   ClientTop       =   465
   ClientWidth     =   6900
   StartUpPosition =   1  'CenterOwner
   Begin MSForms.MultiPage MultiPage1 
      Height          =   4095
      Left            =   120
      TabIndex        =   0
      Top             =   480
      Width           =   6675
      Begin MSForms.Page PageCustomer 
         Caption         =   "Customer & Meta"
         Height          =   3810
         Left            =   6
         TabIndex        =   0
         Top             =   28
         Width           =   6663
         Begin MSForms.TextBox txtNotes 
            Height          =   855
            Left            =   120
            MultiLine       =   -1  'True
            TabIndex        =   9
            Top             =   2880
            Width           =   6375
         End
         Begin MSForms.ComboBox cmbPaymentMethod 
            Height          =   240
            Left            =   4755
            TabIndex        =   8
            Top             =   2355
            Width           =   1740
         End
         Begin MSForms.TextBox txtCashierName 
            Height          =   240
            Left            =   120
            TabIndex        =   7
            Top             =   2355
            Width           =   1740
         End
         Begin MSForms.TextBox txtCustomerEmail 
            Height          =   240
            Left            =   4755
            TabIndex        =   6
            Top             =   1725
            Width           =   1740
         End
         Begin MSForms.TextBox txtCustomerPhone 
            Height          =   240
            Left            =   120
            TabIndex        =   5
            Top             =   1725
            Width           =   1740
         End
         Begin MSForms.TextBox txtCustomerName 
            Height          =   240
            Left            =   120
            TabIndex        =   4
            Top             =   1095
            Width           =   6375
         End
         Begin MSForms.TextBox txtReceiptNo 
            Enabled         =   0   'False
            Height          =   240
            Left            =   120
            TabIndex        =   1
            Top             =   465
            Width           =   2535
         End
         Begin MSForms.TextBox txtDate 
            Enabled         =   0   'False
            Height          =   240
            Left            =   3960
            TabIndex        =   2
            Top             =   465
            Width           =   2535
         End
         Begin MSForms.Label Label10 
            Caption         =   "Notes:"
            Height          =   150
            Left            =   120
            TabIndex        =   16
            Top             =   2730
            Width           =   855
         End
         Begin MSForms.Label Label9 
            Caption         =   "Payment Method:"
            Height          =   150
            Left            =   4755
            TabIndex        =   15
            Top             =   2205
            Width           =   1335
         End
         Begin MSForms.Label Label8 
            Caption         =   "Cashier Name:"
            Height          =   150
            Left            =   120
            TabIndex        =   14
            Top             =   2205
            Width           =   975
         End
         Begin MSForms.Label Label7 
            Caption         =   "Customer Email:"
            Height          =   150
            Left            =   4755
            TabIndex        =   13
            Top             =   1575
            Width           =   1065
         End
         Begin MSForms.Label Label6 
            Caption         =   "Customer Phone:"
            Height          =   150
            Left            =   120
            TabIndex        =   12
            Top             =   1575
            Width           =   1095
         End
         Begin MSForms.Label Label5 
            Caption         =   "Customer Name:"
            Height          =   150
            Left            =   120
            TabIndex        =   11
            Top             =   945
            Width           =   1170
         End
         Begin MSForms.Label Label4 
            Caption         =   "Date/Time:"
            Height          =   150
            Left            =   3960
            TabIndex        =   10
            Top             =   315
            Width           =   780
         End
         Begin MSForms.Label Label3 
            Caption         =   "Receipt No.:"
            Height          =   150
            Left            =   120
            TabIndex        =   3
            Top             =   315
            Width           =   810
         End
      End
      Begin MSForms.Page PageItems 
         Caption         =   "Items"
         Height          =   3810
         Left            =   6
         TabIndex        =   1
         Top             =   28
         Width           =   6663
         Begin MSForms.CommandButton cmdRemoveItem 
            Caption         =   "Remove Item"
            Height          =   315
            Left            =   150
            TabIndex        =   3
            Top             =   3435
            Width           =   1065
         End
         Begin MSForms.CommandButton cmdAddItem 
            Caption         =   "Add Item"
            Height          =   315
            Left            =   150
            TabIndex        =   2
            Top             =   3075
            Width           =   1065
         End
         Begin MSForms.ListBox lstItems 
            Height          =   2895
            Left            =   150
            MultiSelect     =   0  'fmMultiSelectSingle
            TabIndex        =   1
            Top             =   90
            Width           =   6375
         End
      End
      Begin MSForms.Page PageTotals 
         Caption         =   "Totals"
         Height          =   3810
         Left            =   6
         TabIndex        =   2
         Top             =   28
         Width           =   6663
         Begin MSForms.TextBox txtGrandTotal 
            Enabled         =   0   'False
            Font            =   "Calibri"
            Height          =   240
            Left            =   4905
            TabIndex        =   11
            Top             =   2085
            Width           =   1605
         End
         Begin MSForms.TextBox txtServiceCharge 
            Enabled         =   0   'False
            Height          =   240
            Left            =   4905
            TabIndex        =   10
            Top             =   1695
            Width           =   1605
         End
         Begin MSForms.TextBox txtTotalTax 
            Enabled         =   0   'False
            Height          =   240
            Left            =   4905
            TabIndex        =   9
            Top             =   1305
            Width           =   1605
         End
         Begin MSForms.TextBox txtTotalDiscount 
            Enabled         =   0   'False
            Height          =   240
            Left            =   4905
            TabIndex        =   8
            Top             =   915
            Width           =   1605
         End
         Begin MSForms.TextBox txtSubtotal 
            Enabled         =   0   'False
            Height          =   240
            Left            =   4905
            TabIndex        =   7
            Top             =   525
            Width           =   1605
         End
         Begin MSForms.Label Label16 
            Caption         =   "Grand Total:"
            Font            =   "Calibri"
            Height          =   210
            Left            =   3555
            TabIndex        =   6
            Top             =   2085
            Width           =   1170
         End
         Begin MSForms.Label Label15 
            Caption         =   "Service Charge (%):"
            Height          =   210
            Left            =   3555
            TabIndex        =   5
            Top             =   1695
            Width           =   1425
         End
         Begin MSForms.Label Label14 
            Caption         =   "Total Tax:"
            Height          =   210
            Left            =   3555
            TabIndex        =   4
            Top             =   1305
            Width           =   1050
         End
         Begin MSForms.Label Label13 
            Caption         =   "Total Discount:"
            Height          =   210
            Left            =   3555
            TabIndex        =   3
            Top             =   915
            Width           =   1200
         End
         Begin MSForms.Label Label12 
            Caption         =   "Subtotal:"
            Height          =   210
            Left            =   3555
            TabIndex        =   2
            Top             =   525
            Width           =   1005
         End
         Begin MSForms.Label lblAmountInWords 
            Caption         =   "Amount in Words:"
            Height          =   210
            Left            =   120
            TabIndex        =   1
            Top             =   2460
            Width           =   3495
         End
      End
   End
   Begin MSForms.CommandButton cmdCancel 
      Cancel          =   -1  'True
      Caption         =   "Cancel"
      Height          =   315
      Left            =   5655
      TabIndex        =   1
      Top             =   4320
      Width           =   1140
   End
   Begin MSForms.CommandButton cmdGenerate 
      Caption         =   "Generate Receipt"
      Height          =   315
      Left            =   4410
      TabIndex        =   0
      Top             =   4320
      Width           =   1140
   End
End
'
' --- FRM File Properties (part of FRM export) ---
'
' Top 
' Height 
' Width 
'
' End

Private CurrentCatalog As Collection ' Stores loaded catalog items

Private Sub UserForm_Initialize()
    On Error GoTo ErrorHandler
    modUtils.LogAction "frmNewReceipt Initialized."
    
    With lstItems
        .ColumnCount = 7
        .ColumnWidths = "50;150;30;60;50;50;70" ' SKU, Name, Qty, Unit Price, Disc%, Tax%, Line Total
        .AddItem "" ' Add a blank line initially
    End With
    
    PopulatePaymentMethods
    
    ' Set initial values
    txtReceiptNo.Text = GenerateReceiptId()
    txtDate.Text = Format(Now, "yyyy-MM-dd hh:mm:ss")
    txtCashierName.Text = AppSettings.CashierName
    
    Set CurrentCatalog = modCatalog.LoadCatalogItems
    If CurrentCatalog.Count = 0 Then
        MsgBox "Warning: Item catalog is empty or could not be loaded. Please load items via 'Catalog Manager'.", vbExclamation, "Catalog Warning"
    End If
    
    UpdateLabels
    ApplyFormDirection
    
    Exit Sub
ErrorHandler:
    modUtils.LogError "frmNewReceipt.UserForm_Initialize", Err
End Sub

Private Sub UserForm_Terminate()
    On Error GoTo ErrorHandler
    modUtils.LogAction "frmNewReceipt Terminated."
    Set CurrentCatalog = Nothing
    Set frmNewReceipt = Nothing
    Exit Sub
ErrorHandler:
    modUtils.LogError "frmNewReceipt.UserForm_Terminate", Err
End Sub

Private Sub UpdateLabels()
    Me.Caption = modLocalization.GetLabel("New Receipt")
    MultiPage1.Pages(0).Caption = modLocalization.GetLabel("Customer & Meta")
    MultiPage1.Pages(1).Caption = modLocalization.GetLabel("Items")
    MultiPage1.Pages(2).Caption = modLocalization.GetLabel("Totals")
    
    Label3.Caption = modLocalization.GetLabel("Receipt No") & ":"
    Label4.Caption = modLocalization.GetLabel("Date") & ":"
    Label5.Caption = modLocalization.GetLabel("Customer") & ":"
    Label6.Caption = modLocalization.GetLabel("Phone") & ":"
    Label7.Caption = modLocalization.GetLabel("Email") & ":"
    Label8.Caption = modLocalization.GetLabel("Cashier") & ":"
    Label9.Caption = modLocalization.GetLabel("Payment") & ":"
    Label10.Caption = modLocalization.GetLabel("Notes") & ":"
    
    cmdAddItem.Caption = modLocalization.GetLabel("Add Item")
    cmdRemoveItem.Caption = modLocalization.GetLabel("Remove Item")
    
    Label12.Caption = modLocalization.GetLabel("Subtotal") & ":"
    Label13.Caption = modLocalization.GetLabel("TotalDiscount") & ":"
    Label14.Caption = modLocalization.GetLabel("TotalTax") & ":"
    Label15.Caption = modLocalization.GetLabel("ServiceCharge") & ":"
    Label16.Caption = modLocalization.GetLabel("GrandTotal") & ":"
    lblAmountInWords.Caption = modLocalization.GetLabel("AmountInWords") & ":"
    
    cmdGenerate.Caption = modLocalization.GetLabel("Generate Receipt")
    cmdCancel.Caption = modLocalization.GetLabel("Cancel")
    
    ' Update ListBox headers manually if needed (not directly caption property)
    ' This would typically involve recreating/repopulating the listbox if headers are dynamic
    ' For this app, we hardcode column widths, so labels are for the form not listbox headers.
    
    ' Repopulate payment methods to apply localization
    PopulatePaymentMethods
End Sub

Private Sub ApplyFormDirection()
    On Error GoTo ErrorHandler
    If AppSettings.UrduMode Then
        Me.RightToLeft = True
        For Each ctrl In Me.Controls
            On Error Resume Next
            If TypeOf ctrl Is MSForms.TextBox Or TypeOf ctrl Is MSForms.ComboBox Then
                ctrl.ReadingOrder = fmReadingOrderRightToLeft
            End If
            If TypeOf ctrl Is MSForms.Label Or TypeOf ctrl Is MSForms.CommandButton Then
                ' No direct RightToLeft for these, relies on Caption update and implicit alignment
            End If
            On Error GoTo 0
        Next ctrl
        MultiPage1.Pages(0).RightToLeft = True
        MultiPage1.Pages(1).RightToLeft = True
        MultiPage1.Pages(2).RightToLeft = True
    Else
        Me.RightToLeft = False
        For Each ctrl In Me.Controls
            On Error Resume Next
            If TypeOf ctrl Is MSForms.TextBox Or TypeOf ctrl Is MSForms.ComboBox Then
                ctrl.ReadingOrder = fmReadingOrderLeftToRight
            End If
            On Error GoTo 0
        Next ctrl
        MultiPage1.Pages(0).RightToLeft = False
        MultiPage1.Pages(1).RightToLeft = False
        MultiPage1.Pages(2).RightToLeft = False
    End If
    Exit Sub
ErrorHandler:
    modUtils.LogError "frmNewReceipt.ApplyFormDirection", Err
End Sub

Private Sub PopulatePaymentMethods()
    cmbPaymentMethod.Clear
    cmbPaymentMethod.AddItem modLocalization.GetLabel("Cash")
    cmbPaymentMethod.AddItem modLocalization.GetLabel("Card")
    cmbPaymentMethod.AddItem modLocalization.GetLabel("Wallet")
    cmbPaymentMethod.ListIndex = 0 ' Default to Cash
End Sub

Private Function GenerateReceiptId() As String
    On Error GoTo ErrorHandler
    Dim prefix As String
    prefix = AppSettings.ReceiptIdPrefix
    Dim datePart As String
    datePart = Format(Date, "yyyymmdd")
    Dim counter As String
    counter = Format(AppSettings.NextReceiptCounter, "00000")
    
    GenerateReceiptId = prefix & "-" & datePart & "-" & counter
    Exit Function
ErrorHandler:
    modUtils.LogError "GenerateReceiptId", Err
    GenerateReceiptId = "ERR-ID"
End Function

Private Sub cmdAddItem_Click()
    On Error GoTo ErrorHandler
    AddItemLine
    Exit Sub
ErrorHandler:
    modUtils.LogError "cmdAddItem_Click", Err
End Sub

Private Sub cmdRemoveItem_Click()
    On Error GoTo ErrorHandler
    RemoveItemLine
    Exit Sub
ErrorHandler:
    modUtils.LogError "cmdRemoveItem_Click", Err
End Sub

Private Sub cmdGenerate_Click()
    On Error GoTo ErrorHandler
    If ValidateInputs Then
        modUtils.LogAction "Generating receipt."
        Dim receipt As New clsReceipt
        With receipt
            .Id = txtReceiptNo.Text
            .DateCreated = CDate(txtDate.Text)
            .CustomerName = txtCustomerName.Text
            .CustomerPhone = txtCustomerPhone.Text
            .CustomerEmail = txtCustomerEmail.Text
            .CashierName = txtCashierName.Text
            .PaymentMethod = cmbPaymentMethod.Value ' Store localized value
            .Notes = txtNotes.Text
            .UrduMode = AppSettings.UrduMode
            .TemplateType = AppSettings.DefaultTemplate
        End With
        
        Dim i As Long
        For i = 0 To lstItems.ListCount - 1
            If Trim(lstItems.List(i, 0)) <> "" Then ' Only add if SKU is not empty
                Dim lineItem As New clsLineItem
                With lineItem
                    .SKU = lstItems.List(i, 0)
                    .Name = lstItems.List(i, 1)
                    ' Find Urdu name from catalog if available
                    If CurrentCatalog.Exists(.SKU) Then
                        .UrduName = CurrentCatalog.Item(.SKU).UrduName
                    Else
                        .UrduName = "" ' Or some default
                    End If
                    .Quantity = CLng(lstItems.List(i, 2))
                    .UnitPrice = CDbl(lstItems.List(i, 3))
                    .DiscountPercentage = CDbl(lstItems.List(i, 4))
                    .TaxPercentage = CDbl(lstItems.List(i, 5))
                    .LineTotal = CDbl(lstItems.List(i, 6)) ' Use already calculated total
                End With
                receipt.AddLineItem lineItem
            End If
        Next i
        
        receipt.Subtotal = CDbl(txtSubtotal.Text)
        receipt.TotalDiscount = CDbl(txtTotalDiscount.Text)
        receipt.TotalTax = CDbl(txtTotalTax.Text)
        receipt.ServiceCharge = CDbl(txtServiceCharge.Text)
        receipt.GrandTotal = CDbl(txtGrandTotal.Text)
        
        modReceipt.GenerateReceiptInDocument receipt
        modStorage.SaveReceipt receipt ' Save the generated receipt
        
        MsgBox "Receipt generated and saved successfully!", vbInformation, "Receipt Generator"
        Me.Hide
    End If
    Exit Sub
ErrorHandler:
    modUtils.LogError "cmdGenerate_Click", Err
    MsgBox "An error occurred during receipt generation: " & Err.Description, vbCritical, "Error"
End Sub

Private Sub cmdCancel_Click()
    On Error GoTo ErrorHandler
    modUtils.LogAction "New Receipt cancelled."
    Me.Hide
    Exit Sub
ErrorHandler:
    modUtils.LogError "cmdCancel_Click", Err
End Sub

Public Function GetReceiptData() As clsReceipt
    On Error GoTo ErrorHandler
    Dim receipt As New clsReceipt
    With receipt
        .Id = txtReceiptNo.Text
        .DateCreated = CDate(txtDate.Text)
        .CustomerName = txtCustomerName.Text
        .CustomerPhone = txtCustomerPhone.Text
        .CustomerEmail = txtCustomerEmail.Text
        .CashierName = txtCashierName.Text
        .PaymentMethod = cmbPaymentMethod.Value
        .Notes = txtNotes.Text
        .UrduMode = AppSettings.UrduMode
        .TemplateType = AppSettings.DefaultTemplate
    End With
    
    Dim i As Long
    For i = 0 To lstItems.ListCount - 1
        If Trim(lstItems.List(i, 0)) <> "" Then ' Only add if SKU is not empty
            Dim lineItem As New clsLineItem
            With lineItem
                .SKU = lstItems.List(i, 0)
                .Name = lstItems.List(i, 1)
                If CurrentCatalog.Exists(.SKU) Then
                    .UrduName = CurrentCatalog.Item(.SKU).UrduName
                Else
                    .UrduName = ""
                End If
                .Quantity = CLng(lstItems.List(i, 2))
                .UnitPrice = CDbl(lstItems.List(i, 3))
                .DiscountPercentage = CDbl(lstItems.List(i, 4))
                .TaxPercentage = CDbl(lstItems.List(i, 5))
                .LineTotal = CDbl(lstItems.List(i, 6))
            End With
            receipt.AddLineItem lineItem
        End If
    Next i
    
    receipt.Subtotal = CDbl(txtSubtotal.Text)
    receipt.TotalDiscount = CDbl(txtTotalDiscount.Text)
    receipt.TotalTax = CDbl(txtTotalTax.Text)
    receipt.ServiceCharge = CDbl(txtServiceCharge.Text)
    receipt.GrandTotal = CDbl(txtGrandTotal.Text)
    
    Set GetReceiptData = receipt
    Exit Function
ErrorHandler:
    modUtils.LogError "frmNewReceipt.GetReceiptData", Err
    Set GetReceiptData = Nothing
End Function

Private Sub lstItems_DblClick(ByVal Cancel As MSForms.ReturnBoolean)
    On Error GoTo ErrorHandler
    If lstItems.ListIndex = -1 Then Exit Sub
    ' Allow editing of the selected row
    ' For simplicity, we directly allow changing values in listbox
    ' A separate "Edit Item" form could be used for more complex editing
    modUtils.LogAction "Editing item in ListBox: " & lstItems.List(lstItems.ListIndex, 0)
    Exit Sub
ErrorHandler:
    modUtils.LogError "lstItems_DblClick", Err
End Sub

Private Sub lstItems_Change()
    On Error GoTo ErrorHandler
    ' This event fires on any change, including text input
    ' We need to capture changes and update the line total
    If lstItems.ListIndex >= 0 Then
        Dim currentLine As Long
        currentLine = lstItems.ListIndex
        UpdateLineItem currentLine
        ComputeTotals
    End If
    Exit Sub
ErrorHandler:
    modUtils.LogError "lstItems_Change", Err
End Sub

Private Sub lstItems_GotFocus()
    On Error GoTo ErrorHandler
    ' This event fires when listbox gets focus.
    ' We need to capture input from the user and react.
    ' This is complex for a multi-column listbox.
    ' A better approach for data entry in a grid is usually:
    ' 1. Custom controls placed over the listbox for editing the current cell.
    ' 2. Use a different control like ListView with edit capabilities.
    ' For now, assume direct listbox value changes for simplicity, or
    ' capture input through an external textbox for autosuggest.
    Exit Sub
ErrorHandler:
    modUtils.LogError "lstItems_GotFocus", Err
End Sub

Private Sub lstItems_Exit(ByVal Cancel As MSForms.ReturnBoolean)
    On Error GoTo ErrorHandler
    ' This event fires when listbox loses focus.
    ' Finalize any pending edits.
    ComputeTotals
    Exit Sub
ErrorHandler:
    modUtils.LogError "lstItems_Exit", Err
End Sub

Private Sub lstItems_KeyDown(ByVal KeyCode As MSForms.ReturnInteger, ByVal Shift As Integer)
    On Error GoTo ErrorHandler
    ' Handle keyboard navigation and data entry
    Select Case KeyCode
        Case vbKeyReturn ' Enter key
            If lstItems.ListIndex = lstItems.ListCount - 1 Then ' Last row, add new
                AddItemLine
            End If
            ' Move to next cell or row
            ' This requires custom logic to determine current column and move to next
            ' Simple listbox doesn't support cell-by-cell navigation directly.
            ' For this example, we'll just add a new line on Enter if at the end.
        Case vbKeyDelete ' Delete key
            If lstItems.ListIndex >= 0 Then
                RemoveItemLine
            End If
    End Select
    Exit Sub
ErrorHandler:
    modUtils.LogError "lstItems_KeyDown", Err
End Sub

Private Sub lstItems_KeyPress(ByVal KeyAscii As MSForms.ReturnInteger)
    On Error GoTo ErrorHandler
    ' Autocomplete for SKU (Column 0) and Item Name (Column 1)
    If lstItems.ListIndex >= 0 Then
        Dim currentColumn As Long
        currentColumn = 0 ' Assume we are entering SKU
        
        If currentColumn = 0 Then ' SKU column
            Dim typedText As String
            typedText = lstItems.List(lstItems.ListIndex, currentColumn) & Chr(KeyAscii)
            
            Dim foundItem As clsCatalogItem
            For Each foundItem In CurrentCatalog
                If InStr(1, foundItem.SKU, typedText, vbTextCompare) = 1 Then ' Starts with
                    lstItems.List(lstItems.ListIndex, currentColumn) = foundItem.SKU
                    ' Fill other fields from catalog item
                    lstItems.List(lstItems.ListIndex, 1) = foundItem.Name
                    lstItems.List(lstItems.ListIndex, 3) = Format(foundItem.Price, "0.00")
                    lstItems.List(lstItems.ListIndex, 4) = Format(0, "0.00") ' Default discount
                    lstItems.List(lstItems.ListIndex, 5) = Format(AppSettings.DefaultTaxRate, "0.00") ' Default tax
                    lstItems.List(lstItems.ListIndex, 2) = "1" ' Default quantity
                    UpdateLineItem lstItems.ListIndex
                    Exit For
                End If
            Next foundItem
        End If
    End If
    Exit Sub
ErrorHandler:
    modUtils.LogError "lstItems_KeyPress", Err
End Sub

Public Sub AddItemLine()
    On Error GoTo ErrorHandler
    ' Ensure the last line isn't empty before adding a new one
    If lstItems.ListCount > 0 And Trim(lstItems.List(lstItems.ListCount - 1, 0)) = "" And _
        Trim(lstItems.List(lstItems.ListCount - 1, 1)) = "" And _
        Trim(lstItems.List(lstItems.ListCount - 1, 2)) = "" Then
        ' Don't add if the last line is completely empty
        modUtils.LogAction "Attempted to add item but last line is empty."
        Exit Sub
    End If
    
    lstItems.AddItem
    ' Initialize new line with defaults
    Dim newRow As Long
    newRow = lstItems.ListCount - 1
    lstItems.List(newRow, 0) = "" ' SKU
    lstItems.List(newRow, 1) = "" ' Item Name
    lstItems.List(newRow, 2) = "1" ' Qty
    lstItems.List(newRow, 3) = "0.00" ' Unit Price
    lstItems.List(newRow, 4) = "0.00" ' Disc %
    lstItems.List(newRow, 5) = Format(AppSettings.DefaultTaxRate, "0.00") ' Tax %
    lstItems.List(newRow, 6) = "0.00" ' Line Total
    
    lstItems.ListIndex = newRow ' Select the new row
    lstItems.SetFocus
    modUtils.LogAction "New item line added."
    Exit Sub
ErrorHandler:
    modUtils.LogError "AddItemLine", Err
End Sub

Public Sub RemoveItemLine()
    On Error GoTo ErrorHandler
    If lstItems.ListIndex >= 0 Then
        If lstItems.ListCount = 1 Then ' Prevent removing the last line
            MsgBox "Cannot remove the last item line. Please clear its content if not needed.", vbExclamation, "Remove Item"
            Exit Sub
        End If
        lstItems.RemoveItem (lstItems.ListIndex)
        ComputeTotals
        modUtils.LogAction "Item line removed."
    End If
    Exit Sub
ErrorHandler:
    modUtils.LogError "RemoveItemLine", Err
End Sub

Public Sub UpdateLineItem(ByVal rowIdx As Long)
    On Error GoTo ErrorHandler
    If rowIdx < 0 Or rowIdx >= lstItems.ListCount Then Exit Sub
    
    Dim sku As String
    Dim itemName As String
    Dim qty As Double
    Dim unitPrice As Double
    Dim discPct As Double
    Dim taxPct As Double
    Dim lineTotal As Double
    
    sku = Trim(lstItems.List(rowIdx, 0))
    itemName = Trim(lstItems.List(rowIdx, 1))
    
    If Not IsNumeric(lstItems.List(rowIdx, 2)) Or CDbl(lstItems.List(rowIdx, 2)) < 0 Then
        qty = 0
        MsgBox "Quantity must be a non-negative number.", vbExclamation, "Input Error"
        lstItems.List(rowIdx, 2) = "0"
    Else
        qty = CLng(lstItems.List(rowIdx, 2))
    End If
    
    If Not IsNumeric(lstItems.List(rowIdx, 3)) Or CDbl(lstItems.List(rowIdx, 3)) < 0 Then
        unitPrice = 0
        MsgBox "Unit Price must be a non-negative number.", vbExclamation, "Input Error"
        lstItems.List(rowIdx, 3) = "0.00"
    Else
        unitPrice = CDbl(lstItems.List(rowIdx, 3))
    End If
    
    If Not IsNumeric(lstItems.List(rowIdx, 4)) Or CDbl(lstItems.List(rowIdx, 4)) < 0 Then
        discPct = 0
        MsgBox "Discount percentage must be a non-negative number.", vbExclamation, "Input Error"
        lstItems.List(rowIdx, 4) = "0.00"
    Else
        discPct = CDbl(lstItems.List(rowIdx, 4))
    End If
    
    If Not IsNumeric(lstItems.List(rowIdx, 5)) Or CDbl(lstItems.List(rowIdx, 5)) < 0 Then
        taxPct = 0
        MsgBox "Tax percentage must be a non-negative number.", vbExclamation, "Input Error"
        lstItems.List(rowIdx, 5) = "0.00"
    Else
        taxPct = CDbl(lstItems.List(rowIdx, 5))
    End If
    
    ' Autosuggest / Lookup
    If sku <> "" And CurrentCatalog.Exists(sku) Then
        Dim catItem As clsCatalogItem
        Set catItem = CurrentCatalog.Item(sku)
        lstItems.List(rowIdx, 1) = catItem.Name
        lstItems.List(rowIdx, 3) = Format(catItem.Price, "0.00")
        unitPrice = catItem.Price ' Use catalog price
    ElseIf itemName <> "" Then
        ' Try to find by name if SKU not found or empty
        Dim itemFound As Boolean
        itemFound = False
        For Each catItem In CurrentCatalog
            If InStr(1, catItem.Name, itemName, vbTextCompare) > 0 Then
                lstItems.List(rowIdx, 0) = catItem.SKU
                lstItems.List(rowIdx, 3) = Format(catItem.Price, "0.00")
                unitPrice = catItem.Price
                itemFound = True
                Exit For
            End If
        Next catItem
        If Not itemFound And sku = "" Then ' If no SKU and name lookup failed, clear item name
            lstItems.List(rowIdx, 1) = ""
        End If
    End If
    
    Dim lineSubtotal As Double
    lineSubtotal = qty * unitPrice
    Dim discountAmount As Double
    discountAmount = modMath.CalculatePercentage(lineSubtotal, discPct)
    Dim taxableAmount As Double
    taxableAmount = lineSubtotal - discountAmount
    Dim taxAmount As Double
    taxAmount = modMath.CalculatePercentage(taxableAmount, taxPct)
    
    lineTotal = modMath.RoundToDecimal(taxableAmount + taxAmount, AppSettings.DecimalPlaces)
    lstItems.List(rowIdx, 6) = Format(lineTotal, "0.00")
    
    ComputeTotals
    Exit Sub
ErrorHandler:
    modUtils.LogError "UpdateLineItem", Err
End Sub

Public Sub ApplyOverallDiscount(ByVal discountPercent As Double)
    On Error GoTo ErrorHandler
    If discountPercent < 0 Then Exit Sub
    
    Dim i As Long
    For i = 0 To lstItems.ListCount - 1
        If Trim(lstItems.List(i, 0)) <> "" Then
            ' Apply overall discount by modifying per-line discount for all items
            lstItems.List(i, 4) = Format(discountPercent, "0.00")
            UpdateLineItem i ' Recalculate each line
        End If
    Next i
    ComputeTotals ' Recalculate overall totals
    modUtils.LogAction "Overall discount of " & discountPercent & "% applied."
    Exit Sub
ErrorHandler:
    modUtils.LogError "ApplyOverallDiscount", Err
End Sub

Public Sub ComputeTotals()
    On Error GoTo ErrorHandler
    Dim totalSub As Double
    Dim totalDisc As Double
    Dim totalTax As Double
    Dim totalServiceCharge As Double
    Dim finalGrandTotal As Double
    
    totalSub = 0
    totalDisc = 0
    totalTax = 0
    finalGrandTotal = 0
    
    Dim i As Long
    For i = 0 To lstItems.ListCount - 1
        If Trim(lstItems.List(i, 0)) <> "" Then
            Dim qty As Double
            Dim unitPrice As Double
            Dim discPct As Double
            Dim taxPct As Double
            Dim lineTotal As Double
            
            qty = CDbl(lstItems.List(i, 2))
            unitPrice = CDbl(lstItems.List(i, 3))
            discPct = CDbl(lstItems.List(i, 4))
            taxPct = CDbl(lstItems.List(i, 5))
            
            Dim lineSubtotalBeforeDisc As Double
            lineSubtotalBeforeDisc = qty * unitPrice
            
            Dim lineDiscountAmount As Double
            lineDiscountAmount = modMath.CalculatePercentage(lineSubtotalBeforeDisc, discPct)
            
            Dim lineTaxableAmount As Double
            lineTaxableAmount = lineSubtotalBeforeDisc - lineDiscountAmount
            
            Dim lineTaxAmount As Double
            lineTaxAmount = modMath.CalculatePercentage(lineTaxableAmount, taxPct)
            
            totalSub = totalSub + lineSubtotalBeforeDisc
            totalDisc = totalDisc + lineDiscountAmount
            totalTax = totalTax + lineTaxAmount
            
            finalGrandTotal = finalGrandTotal + (lineTaxableAmount + lineTaxAmount)
        End If
    Next i
    
    totalServiceCharge = modMath.CalculatePercentage(finalGrandTotal, AppSettings.DefaultServiceCharge)
    finalGrandTotal = finalGrandTotal + totalServiceCharge
    
    txtSubtotal.Text = modMath.FormatCurrency(modMath.RoundToDecimal(totalSub, AppSettings.DecimalPlaces))
    txtTotalDiscount.Text = modMath.FormatCurrency(modMath.RoundToDecimal(totalDisc, AppSettings.DecimalPlaces))
    txtTotalTax.Text = modMath.FormatCurrency(modMath.RoundToDecimal(totalTax, AppSettings.DecimalPlaces))
    txtServiceCharge.Text = modMath.FormatCurrency(modMath.RoundToDecimal(totalServiceCharge, AppSettings.DecimalPlaces))
    txtGrandTotal.Text = modMath.FormatCurrency(modMath.RoundToDecimal(finalGrandTotal, AppSettings.DecimalPlaces))
    
    Dim amountInWordsEng As String
    Dim amountInWordsUrdu As String
    amountInWordsEng = modMath.NumberToWordsEnglish(finalGrandTotal)
    amountInWordsUrdu = modMath.NumberToWordsUrdu(finalGrandTotal)
    
    If AppSettings.UrduMode Then
        lblAmountInWords.Caption = modLocalization.GetLabel("AmountInWords") & vbCrLf & amountInWordsUrdu & " روپے فقط"
        lblAmountInWords.TextAlign = fmTextAlignRight
    Else
        lblAmountInWords.Caption = modLocalization.GetLabel("AmountInWords") & vbCrLf & amountInWordsEng & " only"
        lblAmountInWords.TextAlign = fmTextAlignLeft
    End If
    
    modUtils.LogAction "Totals recomputed. Grand Total: " & finalGrandTotal
    Exit Sub
ErrorHandler:
    modUtils.LogError "ComputeTotals", Err
End Sub

Private Function ValidateInputs() As Boolean
    On Error GoTo ErrorHandler
    ValidateInputs = False
    
    If Trim(txtCustomerName.Text) = "" Then
        MsgBox "Customer Name is required.", vbExclamation, "Validation Error"
        MultiPage1.Value = 0
        txtCustomerName.SetFocus
        Exit Function
    End If
    
    If Not IsNumeric(Replace(txtCustomerPhone.Text, "-", "")) And Trim(txtCustomerPhone.Text) <> "" Then
        MsgBox "Customer Phone must be numeric.", vbExclamation, "Validation Error"
        MultiPage1.Value = 0
        txtCustomerPhone.SetFocus
        Exit Function
    End If
    
    If Trim(txtCashierName.Text) = "" Then
        MsgBox "Cashier Name is required.", vbExclamation, "Validation Error"
        MultiPage1.Value = 0
        txtCashierName.SetFocus
        Exit Function
    End If
    
    If lstItems.ListCount = 0 Or (lstItems.ListCount = 1 And Trim(lstItems.List(0, 0)) = "") Then
        MsgBox "Please add at least one item to the receipt.", vbExclamation, "Validation Error"
        MultiPage1.Value = 1
        AddItemLine
        Exit Function
    End If
    
    ' Validate each item line
    Dim i As Long
    For i = 0 To lstItems.ListCount - 1
        If Trim(lstItems.List(i, 0)) <> "" Or Trim(lstItems.List(i, 1)) <> "" Then ' If line has any data
            If Trim(lstItems.List(i, 0)) = "" Then
                MsgBox "SKU is required for item on line " & (i + 1) & ".", vbExclamation, "Validation Error"
                MultiPage1.Value = 1
                Exit Function
            End If
            If Trim(lstItems.List(i, 1)) = "" Then
                MsgBox "Item Name is required for item on line " & (i + 1) & ".", vbExclamation, "Validation Error"
                MultiPage1.Value = 1
                Exit Function
            End If
            If Not IsNumeric(lstItems.List(i, 2)) Or CDbl(lstItems.List(i, 2)) <= 0 Then
                MsgBox "Quantity must be a positive number for item on line " & (i + 1) & ".", vbExclamation, "Validation Error"
                MultiPage1.Value = 1
                Exit Function
            End If
            If Not IsNumeric(lstItems.List(i, 3)) Or CDbl(lstItems.List(i, 3)) < 0 Then
                MsgBox "Unit Price must be a non-negative number for item on line " & (i + 1) & ".", vbExclamation, "Validation Error"
                MultiPage1.Value = 1
                Exit Function
            End If
            If Not IsNumeric(lstItems.List(i, 4)) Or CDbl(lstItems.List(i, 4)) < 0 Or CDbl(lstItems.List(i, 4)) > 100 Then
                MsgBox "Discount % must be between 0 and 100 for item on line " & (i + 1) & ".", vbExclamation, "Validation Error"
                MultiPage1.Value = 1
                Exit Function
            End If
            If Not IsNumeric(lstItems.List(i, 5)) Or CDbl(lstItems.List(i, 5)) < 0 Then
                MsgBox "Tax % must be a non-negative number for item on line " & (i + 1) & ".", vbExclamation, "Validation Error"
                MultiPage1.Value = 1
                Exit Function
            End If
        End If
    Next i
    
    ValidateInputs = True
    Exit Function
ErrorHandler:
    modUtils.LogError "ValidateInputs", Err
    ValidateInputs = False
End Function
