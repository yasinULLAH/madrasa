
VERSION 5.00
Begin {C62A69F0-16DC-11CE-9E98-00AA00574A4F} frmFind 
   Caption         =   "Find Receipts"
   ClientHeight    =   4905
   ClientLeft      =   120
   ClientTop       =   465
   ClientWidth     =   6900
   StartUpPosition =   1  'CenterOwner
   Begin MSForms.CommandButton cmdReset 
      Caption         =   "Reset"
      Height          =   315
      Left            =   4350
      TabIndex        =   10
      Top             =   1365
      Width           =   1140
   End
   Begin MSForms.CommandButton cmdSearch 
      Caption         =   "Search"
      Height          =   315
      Left            =   3150
      TabIndex        =   9
      Top             =   1365
      Width           =   1140
   End
   Begin MSForms.ListBox lstResults 
      Height          =   2895
      Left            =   120
      TabIndex        =   8
      Top             =   1950
      Width           =   6660
   End
   Begin MSForms.TextBox txtMinAmount 
      Height          =   240
      Left            =   1500
      TabIndex        =   7
      Top             =   1365
      Width           =   1380
   End
   Begin MSForms.TextBox txtMaxAmount 
      Height          =   240
      Left            =   1500
      TabIndex        =   6
      Top             =   1620
      Width           =   1380
   End
   Begin MSForms.TextBox txtCustomerName 
      Height          =   240
      Left            =   1500
      TabIndex        =   5
      Top             =   1080
      Width           =   2955
   End
   Begin MSForms.TextBox txtEndDate 
      Height          =   240
      Left            =   1500
      TabIndex        =   4
      Top             =   780
      Width           =   1380
   End
   Begin MSForms.TextBox txtStartDate 
      Height          =   240
      Left            =   1500
      TabIndex        =   3
      Top             =   525
      Width           =   1380
   End
   Begin MSForms.TextBox txtReceiptID 
      Height          =   240
      Left            =   1500
      TabIndex        =   2
      Top             =   240
      Width           =   2955
   End
   Begin MSForms.CommandButton cmdLoadReceipt 
      Caption         =   "Load Selected Receipt"
      Height          =   315
      Left            =   4965
      TabIndex        =   1
      Top             =   4485
      Width           =   1815
   End
   Begin MSForms.CommandButton cmdClose 
      Caption         =   "Close"
      Height          =   315
      Left            =   3720
      TabIndex        =   0
      Top             =   4485
      Width           =   1140
   End
   Begin MSForms.Label Label7 
      Caption         =   "Max Amount:"
      Height          =   150
      Left            =   120
      TabIndex        =   17
      Top             =   1620
      Width           =   1185
   End
   Begin MSForms.Label Label6 
      Caption         =   "Min Amount:"
      Height          =   150
      Left            =   120
      TabIndex        =   16
      Top             =   1365
      Width           =   1185
   End
   Begin MSForms.Label Label5 
      Caption         =   "Customer Name:"
      Height          =   150
      Left            =   120
      TabIndex        =   15
      Top             =   1080
      Width           =   1185
   End
   Begin MSForms.Label Label4 
      Caption         =   "End Date (YYYY-MM-DD):"
      Height          =   150
      Left            =   120
      TabIndex        =   14
      Top             =   780
      Width           =   1455
   End
   Begin MSForms.Label Label3 
      Caption         =   "Start Date (YYYY-MM-DD):"
      Height          =   150
      Left            =   120
      TabIndex        =   13
      Top             =   525
      Width           =   1530
   End
   Begin MSForms.Label Label2 
      Caption         =   "Receipt ID:"
      Height          =   150
      Left            =   120
      TabIndex        =   12
      Top             =   240
      Width           =   1185
   End
   Begin MSForms.Label Label1 
      Caption         =   "Search Criteria:"
      Height          =   150
      Left            =   120
      TabIndex        =   11
      Top             =   45
      Width           =   1185
   End
End
'
' --- FRM File Properties (part of FRM export) ---
'
' End

Private AllReceipts As Collection

Private Sub UserForm_Initialize()
    On Error GoTo ErrorHandler
    modUtils.LogAction "frmFind Initialized."
    Set AllReceipts = modStorage.ReadReceiptsFromJsonl
    
    With lstResults
        .ColumnCount = 5
        .ColumnWidths = "100;100;150;100;100" ' ID, Date, Customer, Grand Total, Payment
        .AddItem "" ' Add a blank line initially
    End With
    
    UpdateLabels
    ApplyFormDirection
    SearchReceipts ' Show all initially
    Exit Sub
ErrorHandler:
    modUtils.LogError "frmFind.UserForm_Initialize", Err
End Sub

Private Sub UserForm_Terminate()
    On Error GoTo ErrorHandler
    modUtils.LogAction "frmFind Terminated."
    Set AllReceipts = Nothing
    Set frmFind = Nothing
    Exit Sub
ErrorHandler:
    modUtils.LogError "frmFind.UserForm_Terminate", Err
End Sub

Private Sub UpdateLabels()
    Me.Caption = modLocalization.GetLabel("Find Receipts")
    
    Label1.Caption = modLocalization.GetLabel("Search") & " " & modLocalization.GetLabel("Search Criteria") & ":"
    Label2.Caption = modLocalization.GetLabel("Receipt No") & ":"
    Label3.Caption = modLocalization.GetLabel("Start Date") & " (YYYY-MM-DD):"
    Label4.Caption = modLocalization.GetLabel("End Date") & " (YYYY-MM-DD):"
    Label5.Caption = modLocalization.GetLabel("Customer") & ":"
    Label6.Caption = modLocalization.GetLabel("Min Amount") & ":"
    Label7.Caption = modLocalization.GetLabel("Max Amount") & ":"
    
    cmdSearch.Caption = modLocalization.GetLabel("Search")
    cmdReset.Caption = modLocalization.GetLabel("Reset")
    cmdLoadReceipt.Caption = modLocalization.GetLabel("Load Selected Receipt")
    cmdClose.Caption = modLocalization.GetLabel("Close")
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
            On Error GoTo 0
        Next ctrl
    Else
        Me.RightToLeft = False
        For Each ctrl In Me.Controls
            On Error Resume Next
            If TypeOf ctrl Is MSForms.TextBox Or TypeOf ctrl Is MSForms.ComboBox Then
                ctrl.ReadingOrder = fmReadingOrderLeftToRight
            End If
            On Error GoTo 0
        Next ctrl
    End If
    Exit Sub
ErrorHandler:
    modUtils.LogError "frmFind.ApplyFormDirection", Err
End Sub

Private Sub cmdSearch_Click()
    On Error GoTo ErrorHandler
    SearchReceipts
    Exit Sub
ErrorHandler:
    modUtils.LogError "cmdSearch_Click", Err
End Sub

Private Sub cmdReset_Click()
    On Error GoTo ErrorHandler
    txtReceiptID.Text = ""
    txtStartDate.Text = ""
    txtEndDate.Text = ""
    txtCustomerName.Text = ""
    txtMinAmount.Text = ""
    txtMaxAmount.Text = ""
    SearchReceipts ' Clear search and show all
    modUtils.LogAction "Find Receipts form reset."
    Exit Sub
ErrorHandler:
    modUtils.LogError "cmdReset_Click", Err
End Sub

Private Sub cmdLoadReceipt_Click()
    On Error GoTo ErrorHandler
    If lstResults.ListIndex = -1 Then
        MsgBox "Please select a receipt to load.", vbExclamation, "No Selection"
        Exit Sub
    End If
    
    Dim selectedId As String
    selectedId = lstResults.List(lstResults.ListIndex, 0)
    
    Dim receiptToLoad As clsReceipt
    Dim found As Boolean
    found = False
    For Each receiptToLoad In AllReceipts
        If receiptToLoad.Id = selectedId Then
            found = True
            Exit For
        End If
    Next receiptToLoad
    
    If found Then
        modReceipt.GenerateReceiptInDocument receiptToLoad
        MsgBox "Receipt " & selectedId & " loaded into document.", vbInformation, "Receipt Loaded"
        Me.Hide
    Else
        MsgBox "Selected receipt not found in database.", vbCritical, "Error"
    End If
    Exit Sub
ErrorHandler:
    modUtils.LogError "cmdLoadReceipt_Click", Err
End Sub

Private Sub cmdClose_Click()
    On Error GoTo ErrorHandler
    Me.Hide
    Exit Sub
ErrorHandler:
    modUtils.LogError "cmdClose_Click", Err
End Sub

Private Sub SearchReceipts()
    On Error GoTo ErrorHandler
    lstResults.Clear
    
    Dim searchId As String
    Dim searchStartDate As Date
    Dim searchEndDate As Date
    Dim searchCustomer As String
    Dim searchMinAmount As Double
    Dim searchMaxAmount As Double
    
    searchId = Trim(txtReceiptID.Text)
    searchCustomer = Trim(txtCustomerName.Text)
    
    If IsDate(txtStartDate.Text) Then
        searchStartDate = CDate(txtStartDate.Text)
    Else
        searchStartDate = #1/1/1900# ' Default to very old date
    End If
    
    If IsDate(txtEndDate.Text) Then
        searchEndDate = CDate(txtEndDate.Text)
    Else
        searchEndDate = #12/31/2999# ' Default to very future date
    End If
    
    If IsNumeric(txtMinAmount.Text) Then
        searchMinAmount = CDbl(txtMinAmount.Text)
    Else
        searchMinAmount = -1 ' To include all positive amounts
    End If
    
    If IsNumeric(txtMaxAmount.Text) Then
        searchMaxAmount = CDbl(txtMaxAmount.Text)
    Else
        searchMaxAmount = 1000000000# ' Arbitrarily large number
    End If
    
    Dim r As clsReceipt
    For Each r In AllReceipts
        Dim match As Boolean
        match = True
        
        If searchId <> "" And InStr(1, r.Id, searchId, vbTextCompare) = 0 Then
            match = False
        End If
        
        If searchCustomer <> "" And InStr(1, r.CustomerName, searchCustomer, vbTextCompare) = 0 Then
            match = False
        End If
        
        If r.DateCreated < searchStartDate Or r.DateCreated > (searchEndDate + 1) Then ' +1 to include full end day
            match = False
        End If
        
        If r.GrandTotal < searchMinAmount Or r.GrandTotal > searchMaxAmount Then
            match = False
        End If
        
        If match Then
            lstResults.AddItem r.Id
            lstResults.List(lstResults.ListCount - 1, 1) = Format(r.DateCreated, "yyyy-MM-dd hh:mm")
            lstResults.List(lstResults.ListCount - 1, 2) = r.CustomerName
            lstResults.List(lstResults.ListCount - 1, 3) = modMath.FormatCurrency(r.GrandTotal)
            lstResults.List(lstResults.ListCount - 1, 4) = modLocalization.GetLabel(r.PaymentMethod)
        End If
    Next r
    
    If lstResults.ListCount > 0 Then
        lstResults.ListIndex = 0 ' Select first item
    End If
    modUtils.LogAction "Receipt search completed. Found " & lstResults.ListCount & " results."
    Exit Sub
ErrorHandler:
    modUtils.LogError "SearchReceipts", Err
End Sub
