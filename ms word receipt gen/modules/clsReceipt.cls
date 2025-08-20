
VERSION 1.0 CLASS
BEGIN
  MultiUse = -1  'True
END
Attribute VB_Name = "clsReceipt"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = True
Attribute VB_PredeclaredId = False
Attribute VB_Exposed = True
' Receipt Data Class
Public Id As String
Public DateCreated As Date
Public CustomerName As String
Public CustomerPhone As String
Public CustomerEmail As String
Public CashierName As String
Public PaymentMethod As String
Public Notes As String
Public LineItems As Collection
Public Subtotal As Double
Public TotalDiscount As Double
Public TotalTax As Double
Public ServiceCharge As Double ' Overall service charge
Public GrandTotal As Double
Public CurrencySymbol As String
Public Number As Long ' The counter part of the receipt ID
Public UrduMode As Boolean
Public TemplateType As String ' "A4" or "Thermal80"

Private Sub Class_Initialize()
    Set LineItems = New Collection
    DateCreated = Now
    Id = "" ' Will be set by form or builder
    Subtotal = 0
    TotalDiscount = 0
    TotalTax = 0
    ServiceCharge = 0
    GrandTotal = 0
    CurrencySymbol = AppSettings.CurrencySymbol
    CashierName = ""
    PaymentMethod = "Cash"
    UrduMode = AppSettings.UrduMode
    TemplateType = AppSettings.DefaultTemplate
End Sub

Sub AddLineItem(ByVal item As clsLineItem)
    LineItems.Add item
End Sub

Function ToJsonString() As String
    On Error GoTo ErrorHandler
    Dim dict As Object
    Set dict = CreateObject("Scripting.Dictionary")
    
    dict.Add "id", Id
    dict.Add "date", Format(DateCreated, "yyyy-MM-ddThh:mm:ss")
    dict.Add "customer", CreateCustomerDict()
    
    Dim itemsArray As New Collection
    Dim item As clsLineItem
    For Each item In LineItems
        itemsArray.Add item.ToJsonDictionary()
    Next item
    dict.Add "items", itemsArray
    
    dict.Add "subtotal", Subtotal
    dict.Add "discount_total", TotalDiscount
    dict.Add "tax_total", TotalTax
    dict.Add "service_charge", ServiceCharge
    dict.Add "grand_total", GrandTotal
    dict.Add "currency", CurrencySymbol
    dict.Add "payment", PaymentMethod
    dict.Add "notes", Notes
    dict.Add "urduMode", UrduMode
    dict.Add "template", TemplateType
    dict.Add "cashier", CashierName ' Add cashier to JSON
    
    ToJsonString = modUtils.ToJsonString(dict)
    Exit Function
ErrorHandler:
    modUtils.LogError "clsReceipt.ToJsonString", Err
    ToJsonString = "{}"
End Function

Sub FromJsonDictionary(ByVal dict As Object)
    On Error GoTo ErrorHandler
    Id = dict.Item("id")
    DateCreated = CDate(dict.Item("date"))
    CustomerName = dict.Item("customer").Item("name")
    CustomerPhone = dict.Item("customer").Item("phone")
    CustomerEmail = dict.Item("customer").Item("email")
    CashierName = dict.Item("cashier")
    PaymentMethod = dict.Item("payment")
    Notes = dict.Item("notes")
    
    Set LineItems = New Collection
    Dim itemDict As Variant
    For Each itemDict In dict.Item("items")
        Dim li As New clsLineItem
        li.FromJsonDictionary itemDict
        LineItems.Add li
    Next itemDict
    
    Subtotal = dict.Item("subtotal")
    TotalDiscount = dict.Item("discount_total")
    TotalTax = dict.Item("tax_total")
    ServiceCharge = dict.Item("service_charge")
    GrandTotal = dict.Item("grand_total")
    CurrencySymbol = dict.Item("currency")
    UrduMode = dict.Item("urduMode")
    TemplateType = dict.Item("template")
    
    ' Extract number from ID (e.g., INV-YYYYMM-NNNNN)
    Dim parts() As String
    parts = Split(Id, "-")
    If UBound(parts) = 2 Then
        Number = CLng(parts(2))
    End If
    
    Exit Sub
ErrorHandler:
    modUtils.LogError "clsReceipt.FromJsonDictionary", Err
    ' Handle errors, possibly reset object or raise
End Sub

Private Function CreateCustomerDict() As Object
    Set CreateCustomerDict = CreateObject("Scripting.Dictionary")
    CreateCustomerDict.Add "name", CustomerName
    CreateCustomerDict.Add "phone", CustomerPhone
    CreateCustomerDict.Add "email", CustomerEmail
End Function
