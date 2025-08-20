
Attribute VB_Name = "modStorage"

Function ReadReceiptsFromJsonl() As Collection
    On Error GoTo ErrorHandler
    Set ReadReceiptsFromJsonl = New Collection
    Dim fso As Object
    Dim ts As Object
    Dim filePath As String
    filePath = modUtils.GetAppFolderPath & "Receipts\db\receipts.jsonl"
    
    Set fso = CreateObject("Scripting.FileSystemObject")
    
    If Not fso.FileExists(filePath) Then
        Exit Function ' No file, return empty collection
    End If
    
    Set ts = fso.OpenTextFile(filePath, 1) ' ForReading
    
    Dim jsonLine As String
    Dim receiptDict As Object
    Do While Not ts.AtEndOfStream
        jsonLine = ts.ReadLine
        If Trim(jsonLine) <> "" Then
            Set receiptDict = modUtils.ParseJson(jsonLine)
            If Not receiptDict Is Nothing Then
                Dim r As New clsReceipt
                r.FromJsonDictionary receiptDict
                ReadReceiptsFromJsonl.Add r, r.Id ' Add with ID as key
            End If
        End If
    Loop
    ts.Close
    
    modUtils.LogAction "Loaded " & ReadReceiptsFromJsonl.Count & " receipts from JSONL."
    Exit Function
ErrorHandler:
    modUtils.LogError "ReadReceiptsFromJsonl", Err
    Set ReadReceiptsFromJsonl = New Collection
End Function

Sub SaveReceipt(ByVal receipt As clsReceipt)
    On Error GoTo ErrorHandler
    ' 1. Save to CustomXMLPart
    Dim xmlPart As CustomXMLPart
    Dim xmlNode As CustomXMLNode
    Dim xmlString As String
    
    xmlString = receipt.ToJsonString()
    
    ' Check if the CustomXMLPart already exists
    Set xmlPart = ActiveDocument.CustomXMLParts.SelectByID(CUSTOM_XML_PART_ID).Item(1)
    
    If Not xmlPart Is Nothing Then
        ' Update existing part
        xmlPart.Delete
        modUtils.LogAction "Existing CustomXMLPart deleted."
    End If
    
    ' Add new part
    Set xmlPart = ActiveDocument.CustomXMLParts.Add(xmlString)
    modUtils.LogAction "Receipt saved to CustomXMLPart."
    
    ' 2. Append to receipts.jsonl
    Dim fso As Object
    Dim ts As Object
    Dim filePath As String
    filePath = modUtils.GetAppFolderPath & "Receipts\db\receipts.jsonl"
    
    Set fso = CreateObject("Scripting.FileSystemObject")
    Set ts = fso.OpenTextFile(filePath, 8, True) ' ForAppending, CreateIfNotExist
    ts.WriteLine receipt.ToJsonString()
    ts.Close
    
    modUtils.LogAction "Receipt appended to receipts.jsonl: " & receipt.Id
    
    ' Update next receipt counter
    If receipt.Number >= AppSettings.NextReceiptCounter Then
        AppSettings.NextReceiptCounter = receipt.Number + 1
        AppSettings.SaveSettingsToCustomXML ' Save updated settings
    End If
    
    Exit Sub
ErrorHandler:
    modUtils.LogError "SaveReceipt", Err
End Sub
