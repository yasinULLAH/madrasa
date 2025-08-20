
Attribute VB_Name = "modCatalog"

Private Const CATALOG_FILE_NAME As String = "items_catalog.xlsx"

Function LoadCatalogItems() As Collection
    On Error GoTo ErrorHandler
    Set LoadCatalogItems = New Collection
    
    Dim excelApp As Object
    Dim excelWorkbook As Object
    Dim excelSheet As Object
    Dim lastRow As Long
    Dim i As Long
    
    Set excelApp = CreateObject("Excel.Application")
    excelApp.Visible = False
    excelApp.DisplayAlerts = False
    
    Dim filePath As String
    filePath = modUtils.GetAppFolderPath & CATALOG_FILE_NAME
    
    If Not modUtils.FileExists(filePath) Then
        MsgBox "Catalog file not found: " & filePath & vbCrLf & "Please ensure 'items_catalog.xlsx' is in the application folder.", vbCritical, "Catalog Error"
        excelApp.Quit
        Set excelApp = Nothing
        Exit Function
    End If
    
    Set excelWorkbook = excelApp.Workbooks.Open(filePath, ReadOnly:=True)
    Set excelSheet = excelWorkbook.Sheets(1)
    
    lastRow = excelSheet.Cells(excelSheet.Rows.Count, "A").End(-4162).Row ' xlUp
    
    If lastRow < 2 Then ' Only header row
        modUtils.LogAction "Catalog is empty."
        excelWorkbook.Close False
        excelApp.Quit
        Set excelApp = Nothing
        Exit Function
    End If
    
    For i = 2 To lastRow ' Skip header row
        Dim item As New clsCatalogItem
        item.SKU = CStr(excelSheet.Cells(i, 1).Value)
        item.Name = CStr(excelSheet.Cells(i, 2).Value)
        item.Price = CDbl(excelSheet.Cells(i, 3).Value)
        item.TaxClass = CStr(excelSheet.Cells(i, 4).Value)
        item.UrduName = CStr(excelSheet.Cells(i, 5).Value)
        
        LoadCatalogItems.Add item, item.SKU
    Next i
    
    excelWorkbook.Close False
    excelApp.Quit
    
    Set excelSheet = Nothing
    Set excelWorkbook = Nothing
    Set excelApp = Nothing
    
    modUtils.LogAction "Loaded " & LoadCatalogItems.Count & " items from catalog."
    Exit Function
ErrorHandler:
    modUtils.LogError "LoadCatalogItems", Err
    If Not excelWorkbook Is Nothing Then excelWorkbook.Close False
    If Not excelApp Is Nothing Then excelApp.Quit
    Set LoadCatalogItems = New Collection
End Function

Sub SaveCatalogItems(ByVal catalogItems As Collection)
    On Error GoTo ErrorHandler
    Dim excelApp As Object
    Dim excelWorkbook As Object
    Dim excelSheet As Object
    Dim i As Long
    Dim filePath As String
    filePath = modUtils.GetAppFolderPath & CATALOG_FILE_NAME
    
    Set excelApp = CreateObject("Excel.Application")
    excelApp.Visible = False
    excelApp.DisplayAlerts = False
    
    If modUtils.FileExists(filePath) Then
        Set excelWorkbook = excelApp.Workbooks.Open(filePath)
        Set excelSheet = excelWorkbook.Sheets(1)
        
        ' Clear existing data, keep header
        Dim lastRow As Long
        lastRow = excelSheet.Cells(excelSheet.Rows.Count, "A").End(-4162).Row ' xlUp
        If lastRow > 1 Then
            excelSheet.Range("A2:E" & lastRow).ClearContents
        End If
    Else
        Set excelWorkbook = excelApp.Workbooks.Add
        Set excelSheet = excelWorkbook.Sheets(1)
        ' Add headers
        excelSheet.Cells(1, 1).Value = "SKU"
        excelSheet.Cells(1, 2).Value = "Name"
        excelSheet.Cells(1, 3).Value = "Price"
        excelSheet.Cells(1, 4).Value = "TaxClass"
        excelSheet.Cells(1, 5).Value = "UrduName"
        excelSheet.Range("A1:E1").Font.Bold = True
    End If
    
    i = 2 ' Start from second row for data
    For Each item In catalogItems
        With excelSheet
            .Cells(i, 1).Value = item.SKU
            .Cells(i, 2).Value = item.Name
            .Cells(i, 3).Value = item.Price
            .Cells(i, 4).Value = item.TaxClass
            .Cells(i, 5).Value = item.UrduName
        End With
        i = i + 1
    Next item
    
    excelWorkbook.SaveAs filePath
    excelWorkbook.Close False
    excelApp.Quit
    
    Set excelSheet = Nothing
    Set excelWorkbook = Nothing
    Set excelApp = Nothing
    
    modUtils.LogAction "Saved " & catalogItems.Count & " items to catalog."
    Exit Sub
ErrorHandler:
    modUtils.LogError "SaveCatalogItems", Err
    If Not excelWorkbook Is Nothing Then excelWorkbook.Close False
    If Not excelApp Is Nothing Then excelApp.Quit
End Sub
