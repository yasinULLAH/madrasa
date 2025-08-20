
Attribute VB_Name = "modPDFPrint"

Sub ExportToPDF(ByVal receiptId As String)
    On Error GoTo ErrorHandler
    Dim doc As Document
    Set doc = ActiveDocument
    
    Dim fso As Object
    Set fso = CreateObject("Scripting.FileSystemObject")
    
    Dim exportFolder As String
    exportFolder = modUtils.GetAppFolderPath & "Receipts\" & Format(Date, "yyyy-MM-dd") & "\"
    
    If Not fso.FolderExists(exportFolder) Then
        fso.CreateFolder exportFolder
        modUtils.LogAction "Created export folder: " & exportFolder
    End If
    
    Dim pdfFileName As String
    pdfFileName = "INV-" & receiptId & ".pdf"
    
    Dim fullPath As String
    fullPath = exportFolder & pdfFileName
    
    doc.ExportAsFixedFormat OutputFileName:=fullPath, _
                              ExportFormat:=wdExportFormatPDF, _
                              OpenAfterExport:=True, _
                              OptimizeFor:=wdExportOptimizeForPrint, _
                              Range:=wdExportAllDocument, _
                              Item:=wdExportDocumentContent, _
                              IncludeDocProps:=False, _
                              KeepIRM:=True, _
                              CreateBookmarks:=wdExportCreateNoBookmarks, _
                              DocStructureTags:=False, _
                              BitmapMissingFonts:=True, _
                              UseISO19005_1:=False
    
    modUtils.LogAction "Receipt exported to PDF: " & fullPath
    Exit Sub
ErrorHandler:
    modUtils.LogError "ExportToPDF", Err
End Sub

Sub PrintReceipt()
    On Error GoTo ErrorHandler
    Dim doc As Document
    Set doc = ActiveDocument
    
    doc.PrintOut
    modUtils.LogAction "Receipt sent to printer."
    Exit Sub
ErrorHandler:
    modUtils.LogError "PrintReceipt", Err
End Sub

Sub ApplyTemplate(ByVal templateName As String)
    On Error GoTo ErrorHandler
    Dim doc As Document
    Set doc = ActiveDocument
    
    With doc.PageSetup
        If templateName = "A4" Then
            .PaperSize = wdPaperA4
            .Orientation = wdPortrait
            .TopMargin = InchesToPoints(0.5)
            .BottomMargin = InchesToPoints(0.5)
            .LeftMargin = InchesToPoints(0.5)
            .RightMargin = InchesToPoints(0.5)
            modUtils.LogAction "Applied A4 template settings."
        ElseIf templateName = "Thermal80" Then
            .PaperSize = wdPaperCustom
            .PageWidth = InchesToPoints(3.15) ' 80mm
            .PageHeight = InchesToPoints(11)  ' Long roll, can be adjusted based on content
            .Orientation = wdPortrait
            .TopMargin = InchesToPoints(0.2)
            .BottomMargin = InchesToPoints(0.2)
            .LeftMargin = InchesToPoints(0.1)
            .RightMargin = InchesToPoints(0.1)
            modUtils.LogAction "Applied Thermal80 template settings."
        End If
    End With
    Exit Sub
ErrorHandler:
    modUtils.LogError "ApplyTemplate", Err
End Sub
