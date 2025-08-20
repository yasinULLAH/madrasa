
VERSION 5.00
Begin {C62A69F0-16DC-11CE-9E98-00AA00574A4F} frmSettings 
   Caption         =   "Settings"
   ClientHeight    =   4905
   ClientLeft      =   120
   ClientTop       =   465
   ClientWidth     =   6900
   StartUpPosition =   1  'CenterOwner
   Begin MSForms.MultiPage MultiPage1 
      Height          =   4185
      Left            =   120
      TabIndex        =   0
      Top             =   120
      Width           =   6675
      Begin MSForms.Page PageCompany 
         Caption         =   "Company Settings"
         Height          =   3900
         Left            =   6
         TabIndex        =   0
         Top             =   28
         Width           =   6663
         Begin MSForms.CommandButton cmdBrowseLogo 
            Caption         =   "..."
            Height          =   240
            Left            =   6300
            TabIndex        =   4
            Top             =   1335
            Width           =   255
         End
         Begin MSForms.TextBox txtLogoPath 
            Height          =   240
            Left            =   1500
            TabIndex        =   3
            Top             =   1335
            Width           =   4755
         End
         Begin MSForms.TextBox txtNTN 
            Height          =   240
            Left            =   1500
            TabIndex        =   2
            Top             =   945
            Width           =   4980
         End
         Begin MSForms.TextBox txtAddress 
            Height          =   240
            Left            =   1500
            TabIndex        =   1
            Top             =   555
            Width           =   4980
         End
         Begin MSForms.TextBox txtCompanyName 
            Height          =   240
            Left            =   1500
            TabIndex        =   0
            Top             =   165
            Width           =   4980
         End
         Begin MSForms.TextBox txtCompanyNameUrdu 
            Height          =   240
            Left            =   1500
            TabIndex        =   5
            Top             =   1725
            Width           =   4980
         End
         Begin MSForms.TextBox txtAddressUrdu 
            Height          =   240
            Left            =   1500
            TabIndex        =   6
            Top             =   2115
            Width           =   4980
         End
         Begin MSForms.Label Label11 
            Caption         =   "Address (Urdu):"
            Height          =   150
            Left            =   120
            TabIndex        =   11
            Top             =   2115
            Width           =   1185
         End
         Begin MSForms.Label Label10 
            Caption         =   "Company Name (Urdu):"
            Height          =   150
            Left            =   120
            TabIndex        =   10
            Top             =   1725
            Width           =   1395
         End
         Begin MSForms.Label Label4 
            Caption         =   "Logo Path:"
            Height          =   150
            Left            =   120
            TabIndex        =   9
            Top             =   1335
            Width           =   1185
         End
         Begin MSForms.Label Label3 
            Caption         =   "NTN:"
            Height          =   150
            Left            =   120
            TabIndex        =   8
            Top             =   945
            Width           =   1185
         End
         Begin MSForms.Label Label2 
            Caption         =   "Address:"
            Height          =   150
            Left            =   120
            TabIndex        =   7
            Top             =   555
            Width           =   1185
         End
         Begin MSForms.Label Label1 
            Caption         =   "Company Name:"
            Height          =   150
            Left            =   120
            TabIndex        =   5
            Top             =   165
            Width           =   1185
         End
      End
      Begin MSForms.Page PageCurrency 
         Caption         =   "Currency Settings"
         Height          =   3900
         Left            =   6
         TabIndex        =   1
         Top             =   28
         Width           =   6663
         Begin MSForms.ComboBox cmbPosition 
            Height          =   240
            Left            =   1500
            TabIndex        =   2
            Top             =   585
            Width           =   1290
         End
         Begin MSForms.TextBox txtDecimalPlaces 
            Height          =   240
            Left            =   1500
            TabIndex        =   1
            Top             =   975
            Width           =   1290
         End
         Begin MSForms.TextBox txtSymbol 
            Height          =   240
            Left            =   1500
            TabIndex        =   0
            Top             =   195
            Width           =   1290
         End
         Begin MSForms.Label Label7 
            Caption         =   "Decimal Places:"
            Height          =   150
            Left            =   120
            TabIndex        =   5
            Top             =   975
            Width           =   1185
         End
         Begin MSForms.Label Label6 
            Caption         =   "Position:"
            Height          =   150
            Left            =   120
            TabIndex        =   4
            Top             =   585
            Width           =   1185
         End
         Begin MSForms.Label Label5 
            Caption         =   "Symbol:"
            Height          =   150
            Left            =   120
            TabIndex        =   3
            Top             =   195
            Width           =   1185
         End
      End
      Begin MSForms.Page PageTaxDiscount 
         Caption         =   "Tax & Discount Settings"
         Height          =   3900
         Left            =   6
         TabIndex        =   2
         Top             =   28
         Width           =   6663
         Begin MSForms.TextBox txtServiceCharge 
            Height          =   240
            Left            =   1680
            TabIndex        =   1
            Top             =   570
            Width           =   1290
         End
         Begin MSForms.TextBox txtTaxRate 
            Height          =   240
            Left            =   1680
            TabIndex        =   0
            Top             =   180
            Width           =   1290
         End
         Begin MSForms.Label Label9 
            Caption         =   "Service Charge (%):"
            Height          =   150
            Left            =   120
            TabIndex        =   3
            Top             =   570
            Width           =   1455
         End
         Begin MSForms.Label Label8 
            Caption         =   "Default Tax Rate (%):"
            Height          =   150
            Left            =   120
            TabIndex        =   2
            Top             =   180
            Width           =   1455
         End
      End
      Begin MSForms.Page PageNumbering 
         Caption         =   "Receipt Numbering"
         Height          =   3900
         Left            =   6
         TabIndex        =   3
         Top             =   28
         Width           =   6663
         Begin MSForms.TextBox txtNextCounter 
            Height          =   240
            Left            =   1830
            TabIndex        =   1
            Top             =   555
            Width           =   1290
         End
         Begin MSForms.TextBox txtPrefix 
            Height          =   240
            Left            =   1830
            TabIndex        =   0
            Top             =   165
            Width           =   1290
         End
         Begin MSForms.Label Label13 
            Caption         =   "Next Receipt Counter:"
            Height          =   150
            Left            =   120
            TabIndex        =   3
            Top             =   555
            Width           =   1545
         End
         Begin MSForms.Label Label12 
            Caption         =   "Receipt ID Prefix (e.g., INV):"
            Height          =   150
            Left            =   120
            TabIndex        =   2
            Top             =   165
            Width           =   1815
         End
      End
      Begin MSForms.Page PageLocalization 
         Caption         =   "Localization & Template"
         Height          =   3900
         Left            =   6
         TabIndex        =   4
         Top             =   28
         Width           =   6663
         Begin MSForms.CheckBox chkUrduMode 
            Caption         =   "Enable Urdu Mode (Right-to-Left, Urdu Labels)"
            Height          =   240
            Left            =   120
            TabIndex        =   0
            Top             =   165
            Width           =   3495
         End
         Begin MSForms.OptionButton optA4 
            Caption         =   "A4 Layout"
            Height          =   240
            Left            =   120
            TabIndex        =   1
            Top             =   570
            Width           =   1290
         End
         Begin MSForms.OptionButton optThermal80 
            Caption         =   "Thermal 80mm"
            Height          =   240
            Left            =   120
            TabIndex        =   2
            Top             =   870
            Width           =   1290
         End
         Begin MSForms.Label Label14 
            Caption         =   "Default Template:"
            Height          =   150
            Left            =   120
            TabIndex        =   3
            Top             =   420
            Width           =   1185
         End
      End
   End
   Begin MSForms.CommandButton cmdCancel 
      Cancel          =   -1  'True
      Caption         =   "Cancel"
      Height          =   315
      Left            =   5655
      TabIndex        =   2
      Top             =   4380
      Width           =   1140
   End
   Begin MSForms.CommandButton cmdSave 
      Caption         =   "Save Settings"
      Height          =   315
      Left            =   4410
      TabIndex        =   1
      Top             =   4380
      Width           =   1140
   End
End
'
' --- FRM File Properties (part of FRM export) ---
'
' End

Private Sub UserForm_Initialize()
    On Error GoTo ErrorHandler
    modUtils.LogAction "frmSettings Initialized."
    LoadSettingsToForm
    UpdateLabels
    ApplyFormDirection
    Exit Sub
ErrorHandler:
    modUtils.LogError "frmSettings.UserForm_Initialize", Err
End Sub

Private Sub UserForm_Terminate()
    On Error GoTo ErrorHandler
    modUtils.LogAction "frmSettings Terminated."
    Set frmSettings = Nothing
    Exit Sub
ErrorHandler:
    modUtils.LogError "frmSettings.UserForm_Terminate", Err
End Sub

Private Sub UpdateLabels()
    Me.Caption = modLocalization.GetLabel("Settings")
    MultiPage1.Pages(0).Caption = modLocalization.GetLabel("Company Settings")
    MultiPage1.Pages(1).Caption = modLocalization.GetLabel("Currency Settings")
    MultiPage1.Pages(2).Caption = modLocalization.GetLabel("Tax & Discount Settings")
    MultiPage1.Pages(3).Caption = modLocalization.GetLabel("Receipt Numbering")
    MultiPage1.Pages(4).Caption = modLocalization.GetLabel("Localization") & " & " & modLocalization.GetLabel("Default Template")
    
    Label1.Caption = modLocalization.GetLabel("Company Name") & ":"
    Label2.Caption = modLocalization.GetLabel("Address") & ":"
    Label3.Caption = modLocalization.GetLabel("NTN") & ":"
    Label4.Caption = modLocalization.GetLabel("Logo Path") & ":"
    Label10.Caption = modLocalization.GetLabel("Company Name") & " (Urdu):"
    Label11.Caption = modLocalization.GetLabel("Address") & " (Urdu):"
    
    Label5.Caption = modLocalization.GetLabel("Symbol") & ":"
    Label6.Caption = modLocalization.GetLabel("Position") & ":"
    Label7.Caption = modLocalization.GetLabel("Decimal Places") & ":"
    
    Label8.Caption = modLocalization.GetLabel("Tax Rate") & ":"
    Label9.Caption = modLocalization.GetLabel("Service Charge") & ":"
    
    Label12.Caption = modLocalization.GetLabel("Receipt ID Prefix (e.g., INV)") & ":"
    Label13.Caption = modLocalization.GetLabel("Next Receipt Counter") & ":"
    
    Label14.Caption = modLocalization.GetLabel("Default Template") & ":"
    chkUrduMode.Caption = modLocalization.GetLabel("Enable Urdu Mode")
    optA4.Caption = modLocalization.GetLabel("A4 Layout")
    optThermal80.Caption = modLocalization.GetLabel("Thermal 80mm")
    
    cmdSave.Caption = modLocalization.GetLabel("Save Settings")
    cmdCancel.Caption = modLocalization.GetLabel("Cancel")
    
    cmbPosition.Clear
    cmbPosition.AddItem modLocalization.GetLabel("Before")
    cmbPosition.AddItem modLocalization.GetLabel("After")
    
    If AppSettings.CurrencyPosition = "Before" Then
        cmbPosition.Value = modLocalization.GetLabel("Before")
    Else
        cmbPosition.Value = modLocalization.GetLabel("After")
    End If
End Sub

Private Sub ApplyFormDirection()
    On Error GoTo ErrorHandler
    If AppSettings.UrduMode Then
        Me.RightToLeft = True
        For Each ctrl In Me.Controls
            On Error Resume Next
            If TypeOf ctrl Is MSForms.TextBox Or TypeOf ctrl Is MSForms.ComboBox Or TypeOf ctrl Is MSForms.CheckBox Or TypeOf ctrl Is MSForms.OptionButton Then
                ctrl.ReadingOrder = fmReadingOrderRightToLeft
            End If
            On Error GoTo 0
        Next ctrl
        
        For i = 0 To MultiPage1.Pages.Count - 1
            MultiPage1.Pages(i).RightToLeft = True
        Next i
    Else
        Me.RightToLeft = False
        For Each ctrl In Me.Controls
            On Error Resume Next
            If TypeOf ctrl Is MSForms.TextBox Or TypeOf ctrl Is MSForms.ComboBox Or TypeOf ctrl Is MSForms.CheckBox Or TypeOf ctrl Is MSForms.OptionButton Then
                ctrl.ReadingOrder = fmReadingOrderLeftToRight
            End If
            On Error GoTo 0
        Next ctrl
        
        For i = 0 To MultiPage1.Pages.Count - 1
            MultiPage1.Pages(i).RightToLeft = False
        Next i
    End If
    Exit Sub
ErrorHandler:
    modUtils.LogError "frmSettings.ApplyFormDirection", Err
End Sub

Private Sub LoadSettingsToForm()
    On Error GoTo ErrorHandler
    With AppSettings
        txtCompanyName.Text = .CompanyName
        txtAddress.Text = .CompanyAddress
        txtNTN.Text = .CompanyNTN
        txtLogoPath.Text = .CompanyLogoPath
        txtCompanyNameUrdu.Text = .CompanyNameUrdu
        txtAddressUrdu.Text = .CompanyAddressUrdu
        
        txtSymbol.Text = .CurrencySymbol
        cmbPosition.Clear
        cmbPosition.AddItem "Before"
        cmbPosition.AddItem "After"
        cmbPosition.Value = .CurrencyPosition
        txtDecimalPlaces.Text = .DecimalPlaces
        
        txtTaxRate.Text = .DefaultTaxRate
        txtServiceCharge.Text = .DefaultServiceCharge
        
        txtPrefix.Text = .ReceiptIdPrefix
        txtNextCounter.Text = .NextReceiptCounter
        
        chkUrduMode.Value = .UrduMode
        If .DefaultTemplate = "A4" Then
            optA4.Value = True
        Else
            optThermal80.Value = True
        End If
    End With
    Exit Sub
ErrorHandler:
    modUtils.LogError "frmSettings.LoadSettingsToForm", Err
End Sub

Private Sub cmdSave_Click()
    On Error GoTo ErrorHandler
    If ValidateSettings Then
        With AppSettings
            .CompanyName = txtCompanyName.Text
            .CompanyAddress = txtAddress.Text
            .CompanyNTN = txtNTN.Text
            .CompanyLogoPath = txtLogoPath.Text
            .CompanyNameUrdu = txtCompanyNameUrdu.Text
            .CompanyAddressUrdu = txtAddressUrdu.Text
            
            .CurrencySymbol = txtSymbol.Text
            .CurrencyPosition = cmbPosition.Value
            .DecimalPlaces = CLng(txtDecimalPlaces.Text)
            
            .DefaultTaxRate = CDbl(txtTaxRate.Text)
            .DefaultServiceCharge = CDbl(txtServiceCharge.Text)
            
            .ReceiptIdPrefix = txtPrefix.Text
            .NextReceiptCounter = CLng(txtNextCounter.Text)
            
            Dim oldUrduMode As Boolean
            oldUrduMode = .UrduMode
            .UrduMode = chkUrduMode.Value
            
            If optA4.Value = True Then
                .DefaultTemplate = "A4"
            Else
                .DefaultTemplate = "Thermal80"
            End If
            
            .SaveSettingsToCustomXML ' Save to XML
            
            ' Apply localization and template changes if Urdu mode changed
            If oldUrduMode <> AppSettings.UrduMode Then
                modLocalization.ApplyLocalization AppSettings.UrduMode
                ' If Urdu mode changes, form captions need re-updating
                UpdateLabels
                ApplyFormDirection
            End If
            modPDFPrint.ApplyTemplate AppSettings.DefaultTemplate
        End With
        MsgBox "Settings saved successfully!", vbInformation, "Settings"
        modUtils.LogAction "Settings saved."
        Me.Hide
    End If
    Exit Sub
ErrorHandler:
    modUtils.LogError "cmdSave_Click", Err
End Sub

Private Sub cmdCancel_Click()
    On Error GoTo ErrorHandler
    modUtils.LogAction "Settings cancelled."
    Me.Hide
    Exit Sub
ErrorHandler:
    modUtils.LogError "cmdCancel_Click", Err
End Sub

Private Sub cmdBrowseLogo_Click()
    On Error GoTo ErrorHandler
    Dim fDialog As FileDialog
    Set fDialog = Application.FileDialog(msoFileDialogFilePicker)
    With fDialog
        .AllowMultiSelect = False
        .Title = "Select Company Logo"
        .Filters.Clear
        .Filters.Add "Image Files", "*.png;*.jpg;*.jpeg;*.gif;*.bmp"
        .FilterIndex = 1
        
        If .Show = True Then
            txtLogoPath.Text = .SelectedItems(1)
        End If
    End With
    Set fDialog = Nothing
    Exit Sub
ErrorHandler:
    modUtils.LogError "cmdBrowseLogo_Click", Err
End Sub

Private Function ValidateSettings() As Boolean
    On Error GoTo ErrorHandler
    ValidateSettings = False
    
    If Trim(txtCompanyName.Text) = "" Then
        MsgBox "Company Name cannot be empty.", vbExclamation, "Validation Error"
        MultiPage1.Value = 0
        txtCompanyName.SetFocus
        Exit Function
    End If
    
    If Trim(txtAddress.Text) = "" Then
        MsgBox "Company Address cannot be empty.", vbExclamation, "Validation Error"
        MultiPage1.Value = 0
        txtAddress.SetFocus
        Exit Function
    End If
    
    If Not IsNumeric(txtDecimalPlaces.Text) Or CLng(txtDecimalPlaces.Text) < 0 Or CLng(txtDecimalPlaces.Text) > 4 Then
        MsgBox "Decimal Places must be a number between 0 and 4.", vbExclamation, "Validation Error"
        MultiPage1.Value = 1
        txtDecimalPlaces.SetFocus
        Exit Function
    End If
    
    If Not IsNumeric(txtTaxRate.Text) Or CDbl(txtTaxRate.Text) < 0 Then
        MsgBox "Default Tax Rate must be a non-negative number.", vbExclamation, "Validation Error"
        MultiPage1.Value = 2
        txtTaxRate.SetFocus
        Exit Function
    End If
    
    If Not IsNumeric(txtServiceCharge.Text) Or CDbl(txtServiceCharge.Text) < 0 Then
        MsgBox "Service Charge must be a non-negative number.", vbExclamation, "Validation Error"
        MultiPage1.Value = 2
        txtServiceCharge.SetFocus
        Exit Function
    End If
    
    If Trim(txtPrefix.Text) = "" Then
        MsgBox "Receipt ID Prefix cannot be empty.", vbExclamation, "Validation Error"
        MultiPage1.Value = 3
        txtPrefix.SetFocus
        Exit Function
    End If
    
    If Not IsNumeric(txtNextCounter.Text) Or CLng(txtNextCounter.Text) < 1 Then
        MsgBox "Next Receipt Counter must be a positive integer.", vbExclamation, "Validation Error"
        MultiPage1.Value = 3
        txtNextCounter.SetFocus
        Exit Function
    End If
    
    ValidateSettings = True
    Exit Function
ErrorHandler:
    modUtils.LogError "ValidateSettings", Err
    ValidateSettings = False
End Function
