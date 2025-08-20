
VERSION 5.00
Begin {C62A69F0-16DC-11CE-9E98-00AA00574A4F} frmCatalog 
   Caption         =   "Catalog Manager"
   ClientHeight    =   4905
   ClientLeft      =   120
   ClientTop       =   465
   ClientWidth     =   6900
   StartUpPosition =   1  'CenterOwner
   Begin MSForms.TextBox txtUrduName 
      Height          =   240
      Left            =   1500
      TabIndex        =   4
      Top             =   1305
      Width           =   2535
   End
   Begin MSForms.TextBox txtTaxClass 
      Height          =   240
      Left            =   1500
      TabIndex        =   3
      Top             =   1005
      Width           =   1290
   End
   Begin MSForms.TextBox txtPrice 
      Height          =   240
      Left            =   1500
      TabIndex        =   2
      Top             =   705
      Width           =   1290
   End
   Begin MSForms.TextBox txtName 
      Height          =   240
      Left            =   1500
      TabIndex        =   1
      Top             =   405
      Width           =   2535
   End
   Begin MSForms.TextBox txtSKU 
      Height          =   240
      Left            =   1500
      TabIndex        =   0
      Top             =   105
      Width           =   1290
   End
   Begin MSForms.CommandButton cmdDelete 
      Caption         =   "Delete Item"
      Height          =   315
      Left            =   4965
      TabIndex        =   10
      Top             =   105
      Width           =   1455
   End
   Begin MSForms.CommandButton cmdSaveItem 
      Caption         =   "Save Item"
      Height          =   315
      Left            =   3495
      TabIndex        =   9
      Top             =   105
      Width           =   1290
   End
   Begin MSForms.ListBox lstCatalog 
      Height          =   2895
      Left            =   120
      TabIndex        =   5
      Top             =   1815
      Width           =   6660
   End
   Begin MSForms.CommandButton cmdClose 
      Caption         =   "Close"
      Height          =   315
      Left            =   5655
      TabIndex        =   7
      Top             =   4485
      Width           =   1140
   End
   Begin MSForms.CommandButton cmdSaveAll 
      Caption         =   "Save All Changes"
      Height          =   315
      Left            =   4215
      TabIndex        =   6
      Top             =   4485
      Width           =   1410
   End
   Begin MSForms.Label Label5 
      Caption         =   "Urdu Name:"
      Height          =   150
      Left            =   120
      TabIndex        =   15
      Top             =   1305
      Width           =   1185
   End
   Begin MSForms.Label Label4 
      Caption         =   "Tax Class:"
      Height          =   150
      Left            =   120
      TabIndex        =   14
      Top             =   1005
      Width           =   1185
   End
   Begin MSForms.Label Label3 
      Caption         =   "Price:"
      Height          =   150
      Left            =   120
      TabIndex        =   13
      Top             =   705
      Width           =   1185
   End
   Begin MSForms.Label Label2 
      Caption         =   "Item Name:"
      Height          =   150
      Left            =   120
      TabIndex        =   12
      Top             =   405
      Width           =   1185
   End
   Begin MSForms.Label Label1 
      Caption         =   "SKU:"
      Height          =   150
      Left            =   120
      TabIndex        =   11
      Top             =   105
      Width           =   1185
   End
End
'
' --- FRM File Properties (part of FRM export) ---
'
' End

Private CurrentCatalog As Collection ' Stores clsCatalogItem objects

Private Sub UserForm_Initialize()
    On Error GoTo ErrorHandler
    modUtils.LogAction "frmCatalog Initialized."
    Set CurrentCatalog = modCatalog.LoadCatalogItems
    
    With lstCatalog
        .ColumnCount = 5
        .ColumnWidths = "50;150;50;50;100" ' SKU, Name, Price, TaxClass, UrduName
        .ListIndex = -1 ' No selection initially
    End With
    
    UpdateLabels
    ApplyFormDirection
    PopulateCatalogList
    Exit Sub
ErrorHandler:
    modUtils.LogError "frmCatalog.UserForm_Initialize", Err
End Sub

Private Sub UserForm_Terminate()
    On Error GoTo ErrorHandler
    modUtils.LogAction "frmCatalog Terminated."
    Set CurrentCatalog = Nothing
    Set frmCatalog = Nothing
    Exit Sub
ErrorHandler:
    modUtils.LogError "frmCatalog.UserForm_Terminate", Err
End Sub

Private Sub UpdateLabels()
    Me.Caption = modLocalization.GetLabel("Catalog Manager")
    Label1.Caption = modLocalization.GetLabel("SKU") & ":"
    Label2.Caption = modLocalization.GetLabel("Item Name") & ":"
    Label3.Caption = modLocalization.GetLabel("Price") & ":"
    Label4.Caption = modLocalization.GetLabel("TaxClass") & ":"
    Label5.Caption = modLocalization.GetLabel("Urdu Name") & ":"
    
    cmdSaveItem.Caption = modLocalization.GetLabel("Save Item")
    cmdDelete.Caption = modLocalization.GetLabel("Delete Item")
    cmdSaveAll.Caption = modLocalization.GetLabel("Save All Changes")
    cmdClose.Caption = modLocalization.GetLabel("Close")
End Sub

Private Sub ApplyFormDirection()
    On Error GoTo ErrorHandler
    If AppSettings.UrduMode Then
        Me.RightToLeft = True
        For Each ctrl In Me.Controls
            On Error Resume Next
            If TypeOf ctrl Is MSForms.TextBox Then
                ctrl.ReadingOrder = fmReadingOrderRightToLeft
            End If
            On Error GoTo 0
        Next ctrl
    Else
        Me.RightToLeft = False
        For Each ctrl In Me.Controls
            On Error Resume Next
            If TypeOf ctrl Is MSForms.TextBox Then
                ctrl.ReadingOrder = fmReadingOrderLeftToRight
            End If
            On Error GoTo 0
        Next ctrl
    End If
    Exit Sub
ErrorHandler:
    modUtils.LogError "frmCatalog.ApplyFormDirection", Err
End Sub

Private Sub PopulateCatalogList()
    On Error GoTo ErrorHandler
    lstCatalog.Clear
    Dim item As clsCatalogItem
    For Each item In CurrentCatalog
        lstCatalog.AddItem item.SKU
        lstCatalog.List(lstCatalog.ListCount - 1, 1) = IIf(AppSettings.UrduMode, item.UrduName, item.Name)
        lstCatalog.List(lstCatalog.ListCount - 1, 2) = Format(item.Price, "0.00")
        lstCatalog.List(lstCatalog.ListCount - 1, 3) = item.TaxClass
        lstCatalog.List(lstCatalog.ListCount - 1, 4) = item.UrduName
    Next item
    modUtils.LogAction "Catalog list populated with " & CurrentCatalog.Count & " items."
    ClearItemFields
    Exit Sub
ErrorHandler:
    modUtils.LogError "frmCatalog.PopulateCatalogList", Err
End Sub

Private Sub lstCatalog_Click()
    On Error GoTo ErrorHandler
    If lstCatalog.ListIndex = -1 Then Exit Sub
    
    Dim selectedSKU As String
    selectedSKU = lstCatalog.List(lstCatalog.ListIndex, 0)
    
    If CurrentCatalog.Exists(selectedSKU) Then
        Dim item As clsCatalogItem
        Set item = CurrentCatalog.Item(selectedSKU)
        txtSKU.Text = item.SKU
        txtName.Text = item.Name
        txtPrice.Text = Format(item.Price, "0.00")
        txtTaxClass.Text = item.TaxClass
        txtUrduName.Text = item.UrduName
        txtSKU.Enabled = False ' SKU cannot be changed for existing items
    Else
        MsgBox "Item not found in catalog collection.", vbCritical, "Error"
        ClearItemFields
    End If
    Exit Sub
ErrorHandler:
    modUtils.LogError "lstCatalog_Click", Err
End Sub

Private Sub cmdSaveItem_Click()
    On Error GoTo ErrorHandler
    If Not ValidateItemInput Then Exit Sub
    
    Dim newItem As Boolean
    newItem = False
    Dim item As clsCatalogItem
    
    If CurrentCatalog.Exists(txtSKU.Text) Then
        Set item = CurrentCatalog.Item(txtSKU.Text)
        modUtils.LogAction "Updating catalog item: " & txtSKU.Text
    Else
        Set item = New clsCatalogItem
        item.SKU = txtSKU.Text
        newItem = True
        modUtils.LogAction "Adding new catalog item: " & txtSKU.Text
    End If
    
    With item
        .Name = txtName.Text
        .Price = CDbl(txtPrice.Text)
        .TaxClass = txtTaxClass.Text
        .UrduName = txtUrduName.Text
    End With
    
    If newItem Then
        CurrentCatalog.Add item, item.SKU
    End If
    
    PopulateCatalogList ' Refresh the listbox
    ClearItemFields
    Exit Sub
ErrorHandler:
    modUtils.LogError "cmdSaveItem_Click", Err
End Sub

Private Sub cmdDelete_Click()
    On Error GoTo ErrorHandler
    If lstCatalog.ListIndex = -1 Then
        MsgBox "Please select an item to delete.", vbExclamation, "No Selection"
        Exit Sub
    End If
    
    If MsgBox("Are you sure you want to delete this item?", vbYesNo + vbQuestion, "Delete Item") = vbYes Then
        Dim selectedSKU As String
        selectedSKU = lstCatalog.List(lstCatalog.ListIndex, 0)
        
        If CurrentCatalog.Exists(selectedSKU) Then
            CurrentCatalog.Remove selectedSKU
            modUtils.LogAction "Deleted catalog item: " & selectedSKU
            PopulateCatalogList
            ClearItemFields
        Else
            MsgBox "Selected item not found.", vbCritical, "Error"
        End If
    End If
    Exit Sub
ErrorHandler:
    modUtils.LogError "cmdDelete_Click", Err
End Sub

Private Sub cmdSaveAll_Click()
    On Error GoTo ErrorHandler
    modCatalog.SaveCatalogItems CurrentCatalog
    MsgBox "All catalog changes saved to Excel file.", vbInformation, "Save Catalog"
    modUtils.LogAction "All catalog changes saved to Excel."
    Exit Sub
ErrorHandler:
    modUtils.LogError "cmdSaveAll_Click", Err
End Sub

Private Sub cmdClose_Click()
    On Error GoTo ErrorHandler
    If MsgBox("Do you want to save all pending changes to the catalog before closing?", vbYesNo + vbQuestion, "Close Catalog") = vbYes Then
        cmdSaveAll_Click
    End If
    Me.Hide
    Exit Sub
ErrorHandler:
    modUtils.LogError "cmdClose_Click", Err
End Sub

Private Sub ClearItemFields()
    On Error GoTo ErrorHandler
    txtSKU.Text = ""
    txtName.Text = ""
    txtPrice.Text = ""
    txtTaxClass.Text = ""
    txtUrduName.Text = ""
    txtSKU.Enabled = True
    txtSKU.SetFocus
    lstCatalog.ListIndex = -1 ' Deselect
    Exit Sub
ErrorHandler:
    modUtils.LogError "ClearItemFields", Err
End Sub

Private Function ValidateItemInput() As Boolean
    On Error GoTo ErrorHandler
    ValidateItemInput = False
    
    If Trim(txtSKU.Text) = "" Then
        MsgBox "SKU is required.", vbExclamation, "Validation Error"
        txtSKU.SetFocus
        Exit Function
    End If
    
    If Trim(txtName.Text) = "" Then
        MsgBox "Item Name is required.", vbExclamation, "Validation Error"
        txtName.SetFocus
        Exit Function
    End If
    
    If Not IsNumeric(txtPrice.Text) Or CDbl(txtPrice.Text) < 0 Then
        MsgBox "Price must be a non-negative number.", vbExclamation, "Validation Error"
        txtPrice.SetFocus
        Exit Function
    End If
    
    ValidateItemInput = True
    Exit Function
ErrorHandler:
    modUtils.LogError "ValidateItemInput", Err
    ValidateItemInput = False
End Function
