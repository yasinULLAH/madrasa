
Attribute VB_Name = "modUI"
Public RibbonUI As IRibbonUI

Sub OnRibbonLoad(ribbon As IRibbonUI)
    Set RibbonUI = ribbon
    modUtils.LogAction "Ribbon loaded."
End Sub

Sub GetVisible(control As IRibbonControl, ByRef returnedVal)
    returnedVal = True ' All controls visible by default
End Sub

Sub GetEnabled(control As IRibbonControl, ByRef returnedVal)
    Select Case control.ID
        ' Case specific controls for enabling/disabling
        Case Else
            returnedVal = True
    End Select
End Sub

Sub UpdateRibbonState()
    If Not RibbonUI Is Nothing Then
        RibbonUI.Invalidate ' Invalidate all controls
        modUtils.LogAction "Ribbon state updated."
    End If
End Sub

Sub NewReceipt_Click(control As IRibbonControl)
    On Error GoTo ErrorHandler
    modUtils.LogAction "New Receipt button clicked."
    If Not modReceipt.CurrentReceipt Is Nothing Then
        If MsgBox("A receipt is currently being drafted. Do you want to clear it and start a new one?", vbYesNo + vbQuestion, "New Receipt") = vbNo Then
            Exit Sub
        End If
    End If
    Set frmNewReceipt = New frmNewReceipt
    frmNewReceipt.Show
    Exit Sub
ErrorHandler:
    modUtils.LogError "NewReceipt_Click", Err
End Sub

Sub AddItem_Click(control As IRibbonControl)
    On Error GoTo ErrorHandler
    modUtils.LogAction "Add Item button clicked (direct). Not used in current workflow."
    If Not frmNewReceipt Is Nothing Then
        frmNewReceipt.AddItemLine
        frmNewReceipt.lstItems.ListIndex = frmNewReceipt.lstItems.ListCount - 1 ' Select new line
        frmNewReceipt.lstItems.SetFocus
    Else
        MsgBox "Please start a New Receipt first.", vbExclamation, "Add Item"
    End If
    Exit Sub
ErrorHandler:
    modUtils.LogError "AddItem_Click", Err
End Sub

Sub RemoveItem_Click(control As IRibbonControl)
    On Error GoTo ErrorHandler
    modUtils.LogAction "Remove Item button clicked (direct). Not used in current workflow."
    If Not frmNewReceipt Is Nothing Then
        If frmNewReceipt.lstItems.ListIndex >= 0 Then
            If MsgBox("Are you sure you want to remove the selected item?", vbYesNo + vbQuestion, "Remove Item") = vbYes Then
                frmNewReceipt.RemoveItemLine
            End If
        Else
            MsgBox "No item selected to remove.", vbExclamation, "Remove Item"
        End If
    Else
        MsgBox "Please start a New Receipt first.", vbExclamation, "Remove Item"
    End If
    Exit Sub
ErrorHandler:
    modUtils.LogError "RemoveItem_Click", Err
End Sub

Sub LoadCatalog_Click(control As IRibbonControl)
    On Error GoTo ErrorHandler
    modUtils.LogAction "Load Catalog button clicked."
    Dim frm As New frmCatalog
    frm.Show
    Exit Sub
ErrorHandler:
    modUtils.LogError "LoadCatalog_Click", Err
End Sub

Sub SaveReceipt_Click(control As IRibbonControl)
    On Error GoTo ErrorHandler
    modUtils.LogAction "Save Receipt button clicked."
    If Not modReceipt.CurrentReceipt Is Nothing Then
        modStorage.SaveReceipt modReceipt.CurrentReceipt
        MsgBox "Receipt saved successfully!", vbInformation, "Save Receipt"
    Else
        MsgBox "No receipt currently active to save.", vbExclamation, "Save Receipt"
    End If
    Exit Sub
ErrorHandler:
    modUtils.LogError "SaveReceipt_Click", Err
End Sub

Sub ExportPDF_Click(control As IRibbonControl)
    On Error GoTo ErrorHandler
    modUtils.LogAction "Export PDF button clicked."
    If Not modReceipt.CurrentReceipt Is Nothing Then
        modPDFPrint.ExportToPDF modReceipt.CurrentReceipt.Id
    Else
        MsgBox "No receipt currently active to export.", vbExclamation, "Export PDF"
    End If
    Exit Sub
ErrorHandler:
    modUtils.LogError "ExportPDF_Click", Err
End Sub

Sub PrintReceipt_Click(control As IRibbonControl)
    On Error GoTo ErrorHandler
    modUtils.LogAction "Print Receipt button clicked."
    If Not modReceipt.CurrentReceipt Is Nothing Then
        modPDFPrint.PrintReceipt
    Else
        MsgBox "No receipt currently active to print.", vbExclamation, "Print Receipt"
    End If
    Exit Sub
ErrorHandler:
    modUtils.LogError "PrintReceipt_Click", Err
End Sub

Sub FindReceipts_Click(control As IRibbonControl)
    On Error GoTo ErrorHandler
    modUtils.LogAction "Find Receipts button clicked."
    Dim frm As New frmFind
    frm.Show
    Exit Sub
ErrorHandler:
    modUtils.LogError "FindReceipts_Click", Err
End Sub

Sub Settings_Click(control As IRibbonControl)
    On Error GoTo ErrorHandler
    modUtils.LogAction "Settings button clicked."
    Dim frm As New frmSettings
    frm.Show
    Exit Sub
ErrorHandler:
    modUtils.LogError "Settings_Click", Err
End Sub

Sub ApplyDiscount_Click(control As IRibbonControl)
    On Error GoTo ErrorHandler
    modUtils.LogAction "Apply Discount button clicked (overall)."
    If Not frmNewReceipt Is Nothing Then
        Dim sInput As String
        sInput = InputBox("Enter overall discount percentage (e.g., 5 for 5%):", "Apply Overall Discount", "0")
        If IsNumeric(sInput) And CDbl(sInput) >= 0 Then
            frmNewReceipt.ApplyOverallDiscount CDbl(sInput)
        ElseIf sInput <> "" Then
            MsgBox "Invalid input. Please enter a non-negative number.", vbCritical
        End If
    Else
        MsgBox "Please start a New Receipt first.", vbExclamation, "Apply Discount"
    End If
    Exit Sub
ErrorHandler:
    modUtils.LogError "ApplyDiscount_Click", Err
End Sub

Sub Taxes_Click(control As IRibbonControl)
    On Error GoTo ErrorHandler
    modUtils.LogAction "Taxes button clicked (placeholder for tax configuration)."
    MsgBox "Tax rates are configured in Settings. This button can be used for advanced tax options.", vbInformation, "Taxes"
    Exit Sub
ErrorHandler:
    modUtils.LogError "Taxes_Click", Err
End Sub

Sub ToggleTemplate_Click(control As IRibbonControl)
    On Error GoTo ErrorHandler
    modUtils.LogAction "Toggle Template button clicked."
    If AppSettings.DefaultTemplate = "A4" Then
        AppSettings.DefaultTemplate = "Thermal80"
    Else
        AppSettings.DefaultTemplate = "A4"
    End If
    AppSettings.SaveSettingsToCustomXML
    modPDFPrint.ApplyTemplate AppSettings.DefaultTemplate
    MsgBox "Template switched to " & AppSettings.DefaultTemplate & ". Please regenerate receipt to see changes.", vbInformation, "Template Toggle"
    Exit Sub
ErrorHandler:
    modUtils.LogError "ToggleTemplate_Click", Err
End Sub
