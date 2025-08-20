
Attribute VB_Name = "modUtils"

Public Const LOG_FILE_NAME As String = "app.log"

Function GetAppFolderPath() As String
    ' Get the path of the current document's folder
    On Error GoTo ErrorHandler
    Dim fso As Object
    Set fso = CreateObject("Scripting.FileSystemObject")
    GetAppFolderPath = fso.GetParentFolderName(ActiveDocument.FullName) & "\"
    Exit Function
ErrorHandler:
    MsgBox "Error getting application folder path: " & Err.Description, vbCritical
    GetAppFolderPath = ""
End Function

Sub LogAction(ByVal action As String)
    On Error GoTo ErrorHandler
    Dim fso As Object
    Dim ts As Object
    Dim logFilePath As String
    
    logFilePath = GetAppFolderPath & "logs\" & LOG_FILE_NAME
    
    Set fso = CreateObject("Scripting.FileSystemObject")
    Set ts = fso.OpenTextFile(logFilePath, 8, True) ' 8 = ForAppending, True = CreateIfNotExist
    ts.WriteLine Format(Now, "yyyy-MM-dd hh:mm:ss") & " - " & action
    ts.Close
    Set ts = Nothing
    Set fso = Nothing
    Exit Sub
ErrorHandler:
    ' Avoid endless logging if logging itself causes an error
    Debug.Print "Error in LogAction: " & Err.Description
End Sub

Sub LogError(ByVal moduleName As String, ByVal errObj As ErrObject)
    On Error GoTo ErrorHandler
    Dim fso As Object
    Dim ts As Object
    Dim logFilePath As String
    
    logFilePath = GetAppFolderPath & "logs\" & LOG_FILE_NAME
    
    Set fso = CreateObject("Scripting.FileSystemObject")
    Set ts = fso.OpenTextFile(logFilePath, 8, True) ' 8 = ForAppending, True = CreateIfNotExist
    ts.WriteLine Format(Now, "yyyy-MM-dd hh:mm:ss") & " - ERROR in " & moduleName & ": " & errObj.Description & " (Err No: " & errObj.Number & ")"
    ts.Close
    Set ts = Nothing
    Set fso = Nothing
    
    ' Optionally show a user-friendly message for unhandled errors
    ' MsgBox "An unexpected error occurred. Please check the log file for details.", vbCritical, "Application Error"
    Exit Sub
ErrorHandler:
    Debug.Print "Critical error in LogError: " & Err.Description
End Sub

Function FileExists(ByVal filePath As String) As Boolean
    On Error GoTo ErrorHandler
    Dim fso As Object
    Set fso = CreateObject("Scripting.FileSystemObject")
    FileExists = fso.FileExists(filePath)
    Exit Function
ErrorHandler:
    FileExists = False
End Function

Function ReadAllText(ByVal filePath As String) As String
    On Error GoTo ErrorHandler
    Dim fso As Object
    Dim ts As Object
    Set fso = CreateObject("Scripting.FileSystemObject")
    Set ts = fso.OpenTextFile(filePath, 1) ' ForReading
    ReadAllText = ts.ReadAll
    ts.Close
    Exit Function
ErrorHandler:
    modUtils.LogError "ReadAllText", Err
    ReadAllText = ""
End Function

Sub WriteAllText(ByVal filePath As String, ByVal content As String)
    On Error GoTo ErrorHandler
    Dim fso As Object
    Dim ts As Object
    Set fso = CreateObject("Scripting.FileSystemObject")
    Set ts = fso.OpenTextFile(filePath, 2, True) ' ForWriting, CreateIfNotExist
    ts.Write content
    ts.Close
    Exit Sub
ErrorHandler:
    modUtils.LogError "WriteAllText", Err
End Sub

Function FileToBase64(ByVal filePath As String) As String
    On Error GoTo ErrorHandler
    Dim Stream As Object
    Set Stream = CreateObject("ADODB.Stream")
    Stream.Type = 1 ' Binary
    Stream.Open
    Stream.LoadFromFile filePath
    
    Dim arrBytes() As Byte
    arrBytes = Stream.Read
    Stream.Close
    Set Stream = Nothing
    
    FileToBase64 = EncodeBase64(arrBytes)
    Exit Function
ErrorHandler:
    modUtils.LogError "FileToBase64", Err
    FileToBase64 = ""
End Function

Sub Base64ToFile(ByVal base64String As String, ByVal filePath As String)
    On Error GoTo ErrorHandler
    Dim Stream As Object
    Set Stream = CreateObject("ADODB.Stream")
    Stream.Type = 1 ' Binary
    Stream.Open
    Stream.Write DecodeBase64(base64String)
    Stream.SaveToFile filePath, 2 ' adSaveCreateOverWrite
    Stream.Close
    Set Stream = Nothing
    Exit Sub
ErrorHandler:
    modUtils.LogError "Base64ToFile", Err
End Sub

' Base64 Encoding/Decoding (simplified, might need robust library for full spec)
' Requires reference to Microsoft Active Scripting Library (scrrun.dll) or ADODB.Stream
' This implementation uses MSXML2.DOMDocument.6.0 which is usually present
Private Function EncodeBase64(ByRef arrData() As Byte) As String
    Dim objXML As Object
    Dim objNode As Object
    Set objXML = CreateObject("MSXML2.DOMDocument.6.0")
    Set objNode = objXML.createElement("b64")
    objNode.DataType = "bin.base64"
    objNode.nodeTypedValue = arrData
    EncodeBase64 = objNode.Text
    Set objNode = Nothing
    Set objXML = Nothing
End Function

Private Function DecodeBase64(ByVal strBase64 As String) As Byte()
    Dim objXML As Object
    Dim objNode As Object
    Set objXML = CreateObject("MSXML2.DOMDocument.6.0")
    Set objNode = objXML.createElement("b64")
    objNode.DataType = "bin.base64"
    objNode.Text = strBase64
    DecodeBase64 = objNode.nodeTypedValue
    Set objNode = Nothing
    Set objXML = Nothing
End Function

' --- Simple JSON Parser/Serializer ---
' Basic JSON utilities for settings and receipt data.
' For full JSON compliance, a robust JSON parser class would be needed.
' This is a very minimal implementation assuming flat key-value pairs or simple nested objects.

Function ParseJson(ByVal jsonString As String) As Object
    On Error GoTo ErrorHandler
    Set ParseJson = CreateObject("Scripting.Dictionary")
    
    ' Remove leading/trailing braces
    jsonString = Trim(jsonString)
    If Left(jsonString, 1) = "{" And Right(jsonString, 1) = "}" Then
        jsonString = Mid(jsonString, 2, Len(jsonString) - 2)
    Else
        Exit Function ' Not a valid object string
    End If
    
    Dim parts() As String
    parts = Split(jsonString, ",")
    
    Dim part As Variant
    Dim keyValuePair() As String
    Dim key As String
    Dim value As String
    
    For Each part In parts
        keyValuePair = Split(part, ":", 2)
        If UBound(keyValuePair) = 1 Then
            key = Trim(Replace(keyValuePair(0), Chr(34), "")) ' Remove quotes
            value = Trim(Replace(keyValuePair(1), Chr(34), "")) ' Remove quotes
            
            ' Try to convert to number or boolean
            If IsNumeric(value) Then
                ParseJson.Add key, CDbl(value)
            ElseIf LCase(value) = "true" Then
                ParseJson.Add key, True
            ElseIf LCase(value) = "false" Then
                ParseJson.Add key, False
            ElseIf LCase(value) = "null" Then
                ParseJson.Add key, Null
            Else
                ParseJson.Add key, value
            End If
        End If
    Next part
    Exit Function
ErrorHandler:
    modUtils.LogError "ParseJson", Err
    Set ParseJson = Nothing
End Function

Function ToJsonString(ByVal dict As Object) As String
    On Error GoTo ErrorHandler
    Dim key As Variant
    Dim value As Variant
    Dim tempArray As New Collection
    
    For Each key In dict.Keys
        Set tempArray = New Collection
        value = dict.Item(key)
        Dim sValue As String
        
        If IsObject(value) Then
            ' Handle nested dictionaries (objects)
            sValue = ToJsonString(value)
        ElseIf IsArray(value) Or TypeName(value) = "Collection" Then
            ' Handle arrays/collections (for line items)
            Dim item As Variant
            Dim itemStrings As New Collection
            For Each item In value
                If TypeName(item) = "clsLineItem" Then ' Specific handling for line items
                    itemStrings.Add item.ToJsonString()
                ElseIf IsObject(item) Then ' Generic object in array
                    itemStrings.Add ToJsonString(item)
                Else ' Primitive in array
                    itemStrings.Add QuoteJsonString(CStr(item))
                End If
            Next item
            sValue = "[" & JoinCollection(itemStrings, ",") & "]"
        Else
            Select Case VarType(value)
                Case vbString
                    sValue = QuoteJsonString(value)
                Case vbBoolean
                    sValue = LCase(CStr(value))
                Case vbNull
                    sValue = "null"
                Case Else ' Numbers
                    sValue = CStr(value)
            End Select
        End If
        tempArray.Add QuoteJsonString(CStr(key)) & ":" & sValue
    Next key
    
    ToJsonString = "{" & JoinCollection(tempArray, ",") & "}"
    Exit Function
ErrorHandler:
    modUtils.LogError "ToJsonString", Err
    ToJsonString = "{}"
End Function

Private Function JoinCollection(col As Collection, delimiter As String) As String
    Dim s As String
    Dim i As Long
    For i = 1 To col.Count
        s = s & col.Item(i)
        If i < col.Count Then s = s & delimiter
    Next i
    JoinCollection = s
End Function

Private Function QuoteJsonString(ByVal s As String) As String
    QuoteJsonString = Chr(34) & Replace(s, Chr(34), """") & Chr(34) ' Simple escaping
End Function

' UUID Generator for Receipt ID
Function CreateUUID() As String
    Dim obj As Object
    Set obj = CreateObject("Scriptlet.TypeLib")
    CreateUUID = obj.CreateGuid
    Set obj = Nothing
End Function
