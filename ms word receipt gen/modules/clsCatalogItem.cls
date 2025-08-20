
VERSION 1.0 CLASS
BEGIN
  MultiUse = -1  'True
END
Attribute VB_Name = "clsCatalogItem"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = True
Attribute VB_PredeclaredId = False
Attribute VB_Exposed = True
' Catalog Item Class
Public SKU As String
Public Name As String
Public Price As Double
Public TaxClass As String
Public UrduName As String

Private Sub Class_Initialize()
    Price = 0
End Sub
