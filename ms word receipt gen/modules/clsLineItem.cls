
VERSION 1.0 CLASS
BEGIN
  MultiUse = -1  'True
END
Attribute VB_Name = "clsLineItem"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = True
Attribute VB_PredeclaredId = False
Attribute VB_Exposed = True
' Line Item Data Class
Public SKU As String
Public Name As String
Public UrduName As String
Public Quantity As Long
Public UnitPrice As Double
Public DiscountPercentage As Double ' Per line discount
Public TaxPercentage As Double      ' Per line tax
Public LineTotal As Double

Private Sub Class_Initialize()
    Quantity = 0
    UnitPrice = 0
    DiscountPercentage = 0
    TaxPercentage = 0
    LineTotal = 0
End Sub

Sub CalculateLineTotal()
    On Error GoTo ErrorHandler
    Dim subtotalBeforeDisc As Double
    subtotalBeforeDisc = Quantity * UnitPrice
    
    Dim discountAmount As Double
    discountAmount = modMath.CalculatePercentage(subtotalBeforeDisc, DiscountPercentage)
    
    Dim totalBeforeTax As Double
    totalBeforeTax = subtotalBeforeDisc - discountAmount
    
    Dim taxAmount As Double
    taxAmount = modMath.CalculatePercentage(totalBeforeTax, TaxPercentage)
    
    LineTotal = modMath.RoundToDecimal(totalBeforeTax + taxAmount, AppSettings.DecimalPlaces)
    Exit Sub
ErrorHandler:
    modUtils.LogError "clsLineItem.CalculateLineTotal", Err
    LineTotal = 0
End Sub

Function ToJsonDictionary() As Object
    On Error GoTo ErrorHandler
    Dim dict As Object
    Set dict = CreateObject("Scripting.Dictionary")
    dict.Add "sku", SKU
    dict.Add "name", Name
    dict.Add "urdu_name", UrduName ' Add Urdu name
    dict.Add "qty", Quantity
    dict.Add "unit", UnitPrice
    dict.Add "disc", DiscountPercentage
    dict.Add "tax", TaxPercentage
    dict.Add "total", LineTotal
    Set ToJsonDictionary = dict
    Exit Function
ErrorHandler:
    modUtils.LogError "clsLineItem.ToJsonDictionary", Err
    Set ToJsonDictionary = Nothing
End Function

Sub FromJsonDictionary(ByVal dict As Object)
    On Error GoTo ErrorHandler
    SKU = dict.Item("sku")
    Name = dict.Item("name")
    UrduName = dict.Item("urdu_name")
    Quantity = CLng(dict.Item("qty"))
    UnitPrice = CDbl(dict.Item("unit"))
    DiscountPercentage = CDbl(dict.Item("disc"))
    TaxPercentage = CDbl(dict.Item("tax"))
    LineTotal = CDbl(dict.Item("total"))
    Exit Sub
ErrorHandler:
    modUtils.LogError "clsLineItem.FromJsonDictionary", Err
End Sub
