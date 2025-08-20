
Attribute VB_Name = "modReceipt"
Public CurrentReceipt As clsReceipt

Sub GenerateReceiptInDocument(ByVal receiptData As clsReceipt)
    On Error GoTo ErrorHandler
    Set CurrentReceipt = receiptData ' Store the current receipt data
    
    Dim doc As Document
    Set doc = ActiveDocument
    
    ' Clear existing content
    If doc.Content.Start <> doc.Content.End Then
        doc.Content.Delete
    End If
    
    ' Set page margins to minimal for layout flexibility
    With doc.PageSetup
        .TopMargin = InchesToPoints(0.5)
        .BottomMargin = InchesToPoints(0.5)
        .LeftMargin = InchesToPoints(0.5)
        .RightMargin = InchesToPoints(0.5)
        .Gutter = InchesToPoints(0)
        .HeaderDistance = InchesToPoints(0.2)
        .FooterDistance = InchesToPoints(0.2)
    End With
    
    ' Set document direction based on Urdu mode
    If AppSettings.UrduMode Then
        doc.Content.ParagraphFormat.Bidi = True ' Enable Bi-directional text
        doc.DefaultTabStop = InchesToPoints(0.5)
        doc.Content.ParagraphFormat.Alignment = wdAlignParagraphRight
    Else
        doc.Content.ParagraphFormat.Bidi = False
        doc.DefaultTabStop = InchesToPoints(0.5)
        doc.Content.ParagraphFormat.Alignment = wdAlignParagraphLeft
    End If
    
    Dim rng As Range
    Set rng = doc.Content
    
    ' --- Company Logo and Header ---
    Dim logoShape As Shape
    Dim logoPath As String
    logoPath = AppSettings.CompanyLogoPath
    
    If Dir(logoPath) <> "" Then
        On Error Resume Next
        Set logoShape = doc.Shapes.AddPicture(FileName:=logoPath, _
                                              LinkToFile:=False, _
                                              SaveWithDocument:=True)
        On Error GoTo ErrorHandler
        
        With logoShape
            .WrapFormat.Type = wdWrapSquare
            .RelativeHorizontalPosition = wdRelativeHorizontalPage
            .RelativeVerticalPosition = wdRelativeVerticalPage
            .Left = wdShapeCenter
            .Top = InchesToPoints(0.5) ' Adjust as needed
            .Width = InchesToPoints(1.5) ' Adjust as needed
            .Height = InchesToPoints(1.5) ' Adjust as needed
            If AppSettings.UrduMode Then
                .Left = wdShapeRight
            Else
                .Left = wdShapeLeft
            End If
            .ZOrder msoBringInFrontOfText
        End With
    End If
    
    rng.Collapse wdCollapseEnd
    rng.InsertParagraphAfter
    Set rng = doc.Content.Paragraphs.Last.Range
    rng.Font.Size = 18
    rng.Font.Bold = True
    rng.Font.Name = IIf(AppSettings.UrduMode, AppSettings.UrduFont, AppSettings.EnglishFont)
    rng.Text = IIf(AppSettings.UrduMode, AppSettings.CompanyNameUrdu, AppSettings.CompanyName)
    rng.ParagraphFormat.Alignment = wdAlignParagraphCenter
    rng.InsertParagraphAfter
    
    rng.Collapse wdCollapseEnd
    Set rng = doc.Content.Paragraphs.Last.Range
    rng.Font.Size = 10
    rng.Font.Bold = False
    rng.Font.Name = IIf(AppSettings.UrduMode, AppSettings.UrduFont, AppSettings.EnglishFont)
    rng.Text = IIf(AppSettings.UrduMode, AppSettings.CompanyAddressUrdu, AppSettings.CompanyAddress) & vbCrLf & _
               IIf(AppSettings.UrduMode, "این ٹی این: ", "NTN: ") & AppSettings.CompanyNTN
    rng.ParagraphFormat.Alignment = wdAlignParagraphCenter
    rng.InsertParagraphAfter
    
    ' --- Receipt Metadata ---
    rng.Collapse wdCollapseEnd
    rng.InsertParagraphAfter
    Set rng = doc.Content.Paragraphs.Last.Range
    rng.Text = String(60, "-") ' Separator
    rng.Font.Size = 9
    rng.ParagraphFormat.Alignment = wdAlignParagraphCenter
    rng.InsertParagraphAfter
    
    Dim tblMeta As Table
    Set tblMeta = doc.Tables.Add(Range:=doc.Content.Paragraphs.Last.Range, NumRows:=4, NumColumns:=4)
    tblMeta.Borders.Enable = False
    tblMeta.AutoFitBehavior wdAutoFitWindow
    
    With tblMeta
        .Cell(1, 1).Range.Text = modLocalization.GetLabel("lblReceiptNo") & ":"
        .Cell(1, 2).Range.Text = receiptData.Id
        .Cell(1, 3).Range.Text = modLocalization.GetLabel("lblDate") & ":"
        .Cell(1, 4).Range.Text = Format(receiptData.DateCreated, "yyyy-MM-dd hh:mm:ss")
        
        .Cell(2, 1).Range.Text = modLocalization.GetLabel("lblCustomer") & ":"
        .Cell(2, 2).Range.Text = receiptData.CustomerName
        .Cell(2, 3).Range.Text = modLocalization.GetLabel("lblPhone") & ":"
        .Cell(2, 4).Range.Text = receiptData.CustomerPhone
        
        .Cell(3, 1).Range.Text = modLocalization.GetLabel("lblEmail") & ":"
        .Cell(3, 2).Range.Text = receiptData.CustomerEmail
        .Cell(3, 3).Range.Text = modLocalization.GetLabel("lblCashier") & ":"
        .Cell(3, 4).Range.Text = receiptData.CashierName
        
        .Cell(4, 1).Range.Text = modLocalization.GetLabel("lblPayment") & ":"
        .Cell(4, 2).Range.Text = modLocalization.GetLabel(receiptData.PaymentMethod)
        .Cell(4, 3).Range.Text = modLocalization.GetLabel("lblNotes") & ":"
        .Cell(4, 4).Range.Text = receiptData.Notes
        
        ' Apply formatting to metadata table
        Dim cell As Cell
        For Each cell In .Range.Cells
            With cell.Range
                .Font.Size = 9
                .Font.Name = IIf(AppSettings.UrduMode, AppSettings.UrduFont, AppSettings.EnglishFont)
                .ParagraphFormat.Alignment = IIf(AppSettings.UrduMode, wdAlignParagraphRight, wdAlignParagraphLeft)
                If cell.Column Mod 2 = 1 Then ' Labels
                    .Font.Bold = True
                Else ' Values
                    .Font.Bold = False
                End If
            End With
        Next cell
    End With
    
    rng.Collapse wdCollapseEnd
    rng.InsertParagraphAfter
    Set rng = doc.Content.Paragraphs.Last.Range
    rng.Text = String(60, "-") ' Separator
    rng.Font.Size = 9
    rng.ParagraphFormat.Alignment = wdAlignParagraphCenter
    rng.InsertParagraphAfter
    
    ' --- Items Table ---
    Dim tblItems As Table
    Set tblItems = doc.Tables.Add(Range:=doc.Content.Paragraphs.Last.Range, _
                                   NumRows:=receiptData.LineItems.Count + 1, _
                                   NumColumns:=7) ' SKU, Item Name, Qty, Unit Price, Disc%, Tax%, Line Total
    tblItems.Borders.Enable = True
    tblItems.AutoFitBehavior wdAutoFitWindow
    
    ' Table Header
    With tblItems.Rows(1).Range
        .Font.Size = 9
        .Font.Bold = True
        .Font.Name = IIf(AppSettings.UrduMode, AppSettings.UrduFont, AppSettings.EnglishFont)
    End With
    
    tblItems.Cell(1, 1).Range.Text = modLocalization.GetLabel("lblSKU")
    tblItems.Cell(1, 2).Range.Text = modLocalization.GetLabel("lblItemName")
    tblItems.Cell(1, 3).Range.Text = modLocalization.GetLabel("lblQty")
    tblItems.Cell(1, 4).Range.Text = modLocalization.GetLabel("lblUnitPrice")
    tblItems.Cell(1, 5).Range.Text = modLocalization.GetLabel("lblDiscount")
    tblItems.Cell(1, 6).Range.Text = modLocalization.GetLabel("lblTax")
    tblItems.Cell(1, 7).Range.Text = modLocalization.GetLabel("lblLineTotal")
    
    ' Apply alignment to header cells
    tblItems.Cell(1, 1).Range.ParagraphFormat.Alignment = IIf(AppSettings.UrduMode, wdAlignParagraphRight, wdAlignParagraphLeft)
    tblItems.Cell(1, 2).Range.ParagraphFormat.Alignment = IIf(AppSettings.UrduMode, wdAlignParagraphRight, wdAlignParagraphLeft)
    tblItems.Cell(1, 3).Range.ParagraphFormat.Alignment = wdAlignParagraphCenter
    tblItems.Cell(1, 4).Range.ParagraphFormat.Alignment = wdAlignParagraphRight
    tblItems.Cell(1, 5).Range.ParagraphFormat.Alignment = wdAlignParagraphRight
    tblItems.Cell(1, 6).Range.ParagraphFormat.Alignment = wdAlignParagraphRight
    tblItems.Cell(1, 7).Range.ParagraphFormat.Alignment = wdAlignParagraphRight
    
    ' Items Data
    Dim i As Long
    Dim lineItem As clsLineItem
    For i = 1 To receiptData.LineItems.Count
        Set lineItem = receiptData.LineItems.Item(i)
        With tblItems.Rows(i + 1).Range
            .Font.Size = 9
            .Font.Bold = False
            .Font.Name = IIf(AppSettings.UrduMode, AppSettings.UrduFont, AppSettings.EnglishFont)
        End With
        
        tblItems.Cell(i + 1, 1).Range.Text = lineItem.SKU
        tblItems.Cell(i + 1, 2).Range.Text = IIf(AppSettings.UrduMode, lineItem.UrduName, lineItem.Name)
        tblItems.Cell(i + 1, 3).Range.Text = Format(lineItem.Quantity, "0")
        tblItems.Cell(i + 1, 4).Range.Text = modMath.FormatCurrency(lineItem.UnitPrice)
        tblItems.Cell(i + 1, 5).Range.Text = Format(lineItem.DiscountPercentage, "0.00") & "%"
        tblItems.Cell(i + 1, 6).Range.Text = Format(lineItem.TaxPercentage, "0.00") & "%"
        tblItems.Cell(i + 1, 7).Range.Text = modMath.FormatCurrency(lineItem.LineTotal)
        
        ' Apply alignment to data cells
        tblItems.Cell(i + 1, 1).Range.ParagraphFormat.Alignment = IIf(AppSettings.UrduMode, wdAlignParagraphRight, wdAlignParagraphLeft)
        tblItems.Cell(i + 1, 2).Range.ParagraphFormat.Alignment = IIf(AppSettings.UrduMode, wdAlignParagraphRight, wdAlignParagraphLeft)
        tblItems.Cell(i + 1, 3).Range.ParagraphFormat.Alignment = wdAlignParagraphCenter
        tblItems.Cell(i + 1, 4).Range.ParagraphFormat.Alignment = wdAlignParagraphRight
        tblItems.Cell(i + 1, 5).Range.ParagraphFormat.Alignment = wdAlignParagraphRight
        tblItems.Cell(i + 1, 6).Range.ParagraphFormat.Alignment = wdAlignParagraphRight
        tblItems.Cell(i + 1, 7).Range.ParagraphFormat.Alignment = wdAlignParagraphRight
    Next i
    
    rng.Collapse wdCollapseEnd
    rng.InsertParagraphAfter
    Set rng = doc.Content.Paragraphs.Last.Range
    rng.Text = String(60, "-") ' Separator
    rng.Font.Size = 9
    rng.ParagraphFormat.Alignment = wdAlignParagraphCenter
    rng.InsertParagraphAfter
    
    ' --- Totals Block ---
    Dim tblTotals As Table
    Set tblTotals = doc.Tables.Add(Range:=doc.Content.Paragraphs.Last.Range, NumRows:=7, NumColumns:=2)
    tblTotals.Borders.Enable = False
    tblTotals.AutoFitBehavior wdAutoFitWindow
    
    ' Set column widths for alignment
    With tblTotals.Columns(1)
        .PreferredWidth = InchesToPoints(2)
        .PreferredWidthType = wdPreferredWidthPoints
    End With
    With tblTotals.Columns(2)
        .PreferredWidth = InchesToPoints(1.5)
        .PreferredWidthType = wdPreferredWidthPoints
    End With
    
    ' Merge cells for wider labels if needed, or just set alignment
    Dim rowNum As Long
    rowNum = 1
    
    With tblTotals
        .Cell(rowNum, 1).Range.Text = modLocalization.GetLabel("lblSubtotal") & ":"
        .Cell(rowNum, 2).Range.Text = modMath.FormatCurrency(receiptData.Subtotal)
        rowNum = rowNum + 1
        
        If receiptData.TotalDiscount > 0 Then
            .Cell(rowNum, 1).Range.Text = modLocalization.GetLabel("lblTotalDiscount") & ":"
            .Cell(rowNum, 2).Range.Text = "- " & modMath.FormatCurrency(receiptData.TotalDiscount)
            rowNum = rowNum + 1
        End If
        
        If receiptData.TotalTax > 0 Then
            .Cell(rowNum, 1).Range.Text = modLocalization.GetLabel("lblTotalTax") & ":"
            .Cell(rowNum, 2).Range.Text = modMath.FormatCurrency(receiptData.TotalTax)
            rowNum = rowNum + 1
        End If
        
        If receiptData.ServiceCharge > 0 Then
            .Cell(rowNum, 1).Range.Text = modLocalization.GetLabel("lblServiceCharge") & ":"
            .Cell(rowNum, 2).Range.Text = modMath.FormatCurrency(receiptData.ServiceCharge)
            rowNum = rowNum + 1
        End If
        
        ' Grand Total
        With .Cell(rowNum, 1).Range
            .Text = modLocalization.GetLabel("lblGrandTotal") & ":"
            .Font.Bold = True
            .Font.Size = 11
        End With
        With .Cell(rowNum, 2).Range
            .Text = modMath.FormatCurrency(receiptData.GrandTotal)
            .Font.Bold = True
            .Font.Size = 11
        End With
        rowNum = rowNum + 1
        
        ' Amount in words
        Dim amountInWordsEng As String
        Dim amountInWordsUrdu As String
        amountInWordsEng = modMath.NumberToWordsEnglish(receiptData.GrandTotal)
        amountInWordsUrdu = modMath.NumberToWordsUrdu(receiptData.GrandTotal)
        
        With .Cell(rowNum, 1).Range
            .Text = modLocalization.GetLabel("lblAmountInWords") & ":"
            .Font.Bold = True
            .Font.Size = 9
            .ParagraphFormat.Alignment = IIf(AppSettings.UrduMode, wdAlignParagraphRight, wdAlignParagraphLeft)
            .Cells.Merge ' Merge for full width
        End With
        With .Cell(rowNum, 1).Range
            If AppSettings.UrduMode Then
                .Text = .Text & vbCrLf & amountInWordsUrdu & IIf(AppSettings.UrduMode, " روپے فقط", " only")
            Else
                .Text = .Text & vbCrLf & amountInWordsEng & " only"
            End If
            .Font.Bold = False
            .Font.Size = 9
            .ParagraphFormat.Alignment = IIf(AppSettings.UrduMode, wdAlignParagraphRight, wdAlignParagraphLeft)
        End With
        rowNum = rowNum + 1
        
        ' Apply general formatting to totals table
        For Each cell In .Range.Cells
            With cell.Range
                .Font.Size = 9
                .Font.Name = IIf(AppSettings.UrduMode, AppSettings.UrduFont, AppSettings.EnglishFont)
                ' Align labels Left/Right based on language, values always Right
                If cell.Column = 1 Then
                    .ParagraphFormat.Alignment = IIf(AppSettings.UrduMode, wdAlignParagraphRight, wdAlignParagraphLeft)
                Else
                    .ParagraphFormat.Alignment = wdAlignParagraphRight
                End If
            End With
        Next cell
        
        ' Ensure the totals table is right-aligned in the document if Urdu mode is active
        If AppSettings.UrduMode Then
            .Rows.Last.Cells(1).Range.ParagraphFormat.Alignment = wdAlignParagraphRight
            .Rows.Last.Cells(2).Range.ParagraphFormat.Alignment = wdAlignParagraphRight
        End If
        
        ' Move the entire totals table to the right, respecting Urdu RTL
        Dim totalTableRng As Range
        Set totalTableRng = .Range
        totalTableRng.ParagraphFormat.Alignment = wdAlignParagraphRight
        
    End With
    
    rng.Collapse wdCollapseEnd
    rng.InsertParagraphAfter
    Set rng = doc.Content.Paragraphs.Last.Range
    rng.Text = String(60, "-") ' Separator
    rng.Font.Size = 9
    rng.ParagraphFormat.Alignment = wdAlignParagraphCenter
    rng.InsertParagraphAfter
    
    ' --- QR Code and Barcode ---
    Dim qrCodeBase64 As String
    Dim barcodeBase64 As String
    
    qrCodeBase64 = modQRBarcode.GenerateQRCode(receiptData.Id & "|" & Format(receiptData.GrandTotal, "0.00") & "|" & Format(receiptData.DateCreated, "yyyyMMdd"))
    barcodeBase64 = modQRBarcode.GenerateCode128(receiptData.Id)
    
    If qrCodeBase64 <> "" Then
        modQRBarcode.InsertBase64Image doc, qrCodeBase64, IIf(AppSettings.UrduMode, wdShapeRight, wdShapeLeft)
    End If
    
    If barcodeBase64 <> "" Then
        modQRBarcode.InsertBase64Image doc, barcodeBase64, IIf(AppSettings.UrduMode, wdShapeLeft, wdShapeRight)
    End If
    
    ' --- Footer ---
    rng.Collapse wdCollapseEnd
    rng.InsertParagraphAfter
    Set rng = doc.Content.Paragraphs.Last.Range
    rng.Font.Size = 8
    rng.Font.Bold = False
    rng.Font.Name = IIf(AppSettings.UrduMode, AppSettings.UrduFont, AppSettings.EnglishFont)
    rng.Text = IIf(AppSettings.UrduMode, "شکریہ! دوبارہ تشریف لائیں۔", "Thank you for your business!") & vbCrLf & _
               IIf(AppSettings.UrduMode, "واپسی کی پالیسی: 7 دنوں کے اندر اصلی رسید کے ساتھ۔", "Return Policy: Within 7 days with original receipt.")
    rng.ParagraphFormat.Alignment = wdAlignParagraphCenter
    rng.InsertParagraphAfter
    
    doc.Content.InsertAfter Chr(12) ' Page break for next receipt if needed
    
    modUtils.LogAction "Receipt generated for ID: " & receiptData.Id
    Exit Sub
ErrorHandler:
    modUtils.LogError "GenerateReceiptInDocument", Err
End Sub
