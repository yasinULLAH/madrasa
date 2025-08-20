
VERSION 1.0 CLASS
BEGIN
  MultiUse = -1  'True
END
Attribute VB_Name = "clsSettings"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = True
Attribute VB_PredeclaredId = False
Attribute VB_Exposed = True
' Application Settings Class
Public CompanyName As String
Public CompanyAddress As String
Public CompanyNTN As String
Public CompanyLogoPath As String
Public CompanyNameUrdu As String
Public CompanyAddressUrdu As String

Public CurrencySymbol As String
Public CurrencyPosition As String ' "Before" or "After"
Public DecimalPlaces As Long

Public DefaultTaxRate As Double    ' Global default
Public DefaultServiceCharge As Double ' Global default

Public NextReceiptCounter As Long
Public ReceiptIdPrefix As String

Public UrduMode As Boolean
Public EnglishFont As String
Public UrduFont As String

Public DefaultTemplate As String ' "A4" or "Thermal80"

Private Sub Class_Initialize()
    ' Set default values
    CompanyName = "Your Company Name"
    CompanyAddress = "123 Main Street, City, Country"
    CompanyNTN = "1234567-8"
    CompanyLogoPath = modUtils.GetAppFolderPath & "sample_logo.png" ' Default path
    CompanyNameUrdu = "آپ کی کمپنی کا نام"
    CompanyAddressUrdu = "123 مین اسٹریٹ، شہر، ملک"
    
    CurrencySymbol = "PKR "
    CurrencyPosition = "Before"
    DecimalPlaces = 2
    
    DefaultTaxRate = 10.0 ' 10%
    DefaultServiceCharge = 0.0 ' 0%
    
    NextReceiptCounter = 1
    ReceiptIdPrefix = "INV"
    
    UrduMode = False
    EnglishFont = "Calibri"
    UrduFont = "Noto Nastaliq Urdu" ' Ensure this font is installed or provide a fallback
    
    DefaultTemplate = "A4"
End Sub

Function ToJsonString() As String
    On Error GoTo ErrorHandler
    Dim dict As Object
    Set dict = CreateObject("Scripting.Dictionary")
    
    dict.Add "CompanyName", CompanyName
    dict.Add "CompanyAddress", CompanyAddress
    dict.Add "CompanyNTN", CompanyNTN
    dict.Add "CompanyLogoPath", CompanyLogoPath
    dict.Add "CompanyNameUrdu", CompanyNameUrdu
    dict.Add "CompanyAddressUrdu", CompanyAddressUrdu
    
    dict.Add "CurrencySymbol", CurrencySymbol
    dict.Add "CurrencyPosition", CurrencyPosition
    dict.Add "DecimalPlaces", DecimalPlaces
    
    dict.Add "DefaultTaxRate", DefaultTaxRate
    dict.Add "DefaultServiceCharge", DefaultServiceCharge
    
    dict.Add "NextReceiptCounter", NextReceiptCounter
    dict.Add "ReceiptIdPrefix", ReceiptIdPrefix
    
    dict.Add "UrduMode", UrduMode
    dict.Add "EnglishFont", EnglishFont
    dict.Add "UrduFont", UrduFont
    
    dict.Add "DefaultTemplate", DefaultTemplate
    
    ToJsonString = modUtils.ToJsonString(dict)
    Exit Function
ErrorHandler:
    modUtils.LogError "clsSettings.ToJsonString", Err
    ToJsonString = "{}"
End Function

Sub LoadSettingsFromCustomXML()
    On Error GoTo ErrorHandler
    Dim xmlPart As CustomXMLPart
    Dim jsonString As String
    
    Set xmlPart = ActiveDocument.CustomXMLParts.SelectByID(CUSTOM_XML_PART_ID).Item(1)
    
    If Not xmlPart Is Nothing Then
        jsonString = xmlPart.XML
        If Left(jsonString, 1) = "<" Then ' Remove root XML tag if present
             jsonString = Mid(jsonString, InStr(jsonString, ">") + 1)
             jsonString = Left(jsonString, InStrRev(jsonString, "<") - 1)
        End If
        
        If Trim(jsonString) <> "" Then
            Dim dict As Object
            Set dict = modUtils.ParseJson(jsonString)
            If Not dict Is Nothing Then
                CompanyName = dict.Item("CompanyName")
                CompanyAddress = dict.Item("CompanyAddress")
                CompanyNTN = dict.Item("CompanyNTN")
                CompanyLogoPath = dict.Item("CompanyLogoPath")
                CompanyNameUrdu = dict.Item("CompanyNameUrdu")
                CompanyAddressUrdu = dict.Item("CompanyAddressUrdu")
                
                CurrencySymbol = dict.Item("CurrencySymbol")
                CurrencyPosition = dict.Item("CurrencyPosition")
                DecimalPlaces = CLng(dict.Item("DecimalPlaces"))
                
                DefaultTaxRate = CDbl(dict.Item("DefaultTaxRate"))
                DefaultServiceCharge = CDbl(dict.Item("DefaultServiceCharge"))
                
                NextReceiptCounter = CLng(dict.Item("NextReceiptCounter"))
                ReceiptIdPrefix = dict.Item("ReceiptIdPrefix")
                
                UrduMode = CBool(dict.Item("UrduMode"))
                EnglishFont = dict.Item("EnglishFont")
                UrduFont = dict.Item("UrduFont")
                
                DefaultTemplate = dict.Item("DefaultTemplate")
                modUtils.LogAction "Settings loaded from CustomXMLPart."
            End If
        End If
    Else
        modUtils.LogAction "CustomXMLPart not found, using default settings."
    End If
    Exit Sub
ErrorHandler:
    modUtils.LogError "clsSettings.LoadSettingsFromCustomXML", Err
    ' Use default settings if loading fails
End Sub

Sub SaveSettingsToCustomXML()
    On Error GoTo ErrorHandler
    Dim xmlPart As CustomXMLPart
    Dim jsonString As String
    
    jsonString = ToJsonString()
    
    ' Delete existing settings part if it exists
    On Error Resume Next
    Set xmlPart = ActiveDocument.CustomXMLParts.SelectByID(CUSTOM_XML_PART_ID).Item(1)
    If Not xmlPart Is Nothing Then
        xmlPart.Delete
    End If
    On Error GoTo ErrorHandler
    
    ' Add new settings part
    Set xmlPart = ActiveDocument.CustomXMLParts.Add(jsonString)
    modUtils.LogAction "Settings saved to CustomXMLPart."
    Exit Sub
ErrorHandler:
    modUtils.LogError "clsSettings.SaveSettingsToCustomXML", Err
End Sub
