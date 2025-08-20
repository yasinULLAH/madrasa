
Attribute VB_Name = "modMath"

' Number to Words (English)
Private Function GetHundreds(ByVal n As Long) As String
    Dim Words(9) As String
    Words(1) = "One": Words(2) = "Two": Words(3) = "Three": Words(4) = "Four"
    Words(5) = "Five": Words(6) = "Six": Words(7) = "Seven": Words(8) = "Eight": Words(9) = "Nine"
    
    Dim s As String
    If n >= 100 Then
        s = Words(n \ 100) & " Hundred"
        n = n Mod 100
        If n > 0 Then s = s & " "
    End If
    
    If n >= 20 Then
        Dim Tens(9) As String
        Tens(2) = "Twenty": Tens(3) = "Thirty": Tens(4) = "Forty": Tens(5) = "Fifty"
        Tens(6) = "Sixty": Tens(7) = "Seventy": Tens(8) = "Eighty": Tens(9) = "Ninety"
        s = s & Tens(n \ 10)
        n = n Mod 10
        If n > 0 Then s = s & " "
    End If
    
    If n > 0 Then
        Dim Teens(19) As String
        Teens(10) = "Ten": Teens(11) = "Eleven": Teens(12) = "Twelve": Teens(13) = "Thirteen"
        Teens(14) = "Fourteen": Teens(15) = "Fifteen": Teens(16) = "Sixteen"
        Teens(17) = "Seventeen": Teens(18) = "Eighteen": Teens(19) = "Nineteen"
        If n < 10 Then
            s = s & Words(n)
        Else
            s = s & Teens(n)
        End If
    End If
    GetHundreds = s
End Function

Function NumberToWordsEnglish(ByVal N As Double) As String
    On Error GoTo ErrorHandler
    Dim s As String
    Dim sign As String
    Dim intPart As Long
    Dim decPart As Long
    
    If N < 0 Then
        sign = "Negative "
        N = Abs(N)
    Else
        sign = ""
    End If
    
    intPart = Int(N)
    decPart = Round((N - intPart) * 100, 0)
    
    Dim Scale(5) As String
    Scale(1) = "": Scale(2) = "Thousand": Scale(3) = "Million": Scale(4) = "Billion": Scale(5) = "Trillion"
    
    Dim i As Long
    Dim v As Long
    Dim temp As String
    
    If intPart = 0 Then
        s = "Zero"
    Else
        i = 1
        Do While intPart > 0
            v = intPart Mod 1000
            If v > 0 Then
                temp = GetHundreds(v)
                If i > 1 Then temp = temp & " " & Scale(i)
                If s = "" Then
                    s = temp
                Else
                    s = temp & " " & s
                End If
            End If
            intPart = intPart \ 1000
            i = i + 1
        Loop
    End If
    
    NumberToWordsEnglish = sign & s
    
    If decPart > 0 Then
        NumberToWordsEnglish = NumberToWordsEnglish & " and " & GetHundreds(decPart) & " Paisa"
    End If
    
    Exit Function
ErrorHandler:
    modUtils.LogError "NumberToWordsEnglish", Err
    NumberToWordsEnglish = ""
End Function

' Number to Words (Urdu) - Simplified
Function NumberToWordsUrdu(ByVal N As Double) As String
    On Error GoTo ErrorHandler
    Dim strNum As String
    Dim intPart As Long
    Dim decPart As Long
    Dim arrUnits As Variant
    Dim arrTens As Variant
    Dim arrThousands As Variant
    Dim result As String
    Dim i As Long
    Dim numChunk As Long
    
    ' Urdu digits for formatting
    Const U_ZERO = "۰"
    Const U_ONE = "۱"
    Const U_TWO = "۲"
    Const U_THREE = "۳"
    Const U_FOUR = "۴"
    Const U_FIVE = "۵"
    Const U_SIX = "۶"
    Const U_SEVEN = "۷"
    Const U_EIGHT = "۸"
    Const U_NINE = "۹"
    
    ' Urdu number words
    arrUnits = Array("", "ایک", "دو", "تین", "چار", "پانچ", "چھ", "سات", "آٹھ", "نو")
    arrTens = Array("", "دس", "بیس", "تیس", "چالیس", "پچاس", "ساٹھ", "ستر", "اسی", "نوے")
    arrThousands = Array("", "ہزار", "لاکھ", "کروڑ", "ارب") ' For thousands, lakhs, crores, arb
    
    intPart = Int(N)
    decPart = Round((N - intPart) * 100, 0) ' Get paisa
    
    If intPart = 0 Then
        result = "صفر"
    Else
        result = ""
        strNum = CStr(intPart)
        
        Dim lenNum As Long
        lenNum = Len(strNum)
        Dim chunk As String
        Dim chunkVal As Long
        
        ' Process numbers from right to left in chunks (hundreds, then thousands/lakhs/crores)
        ' This is a simplified approach, full Urdu numeration is complex (e.g., 21 is "اکیس", not "دو دس ایک")
        ' For simplicity, we'll use a more direct translation for demonstration.
        
        If lenNum >= 7 Then ' Crores (10,000,000)
            chunkVal = CLng(Left(strNum, lenNum - 7 + 1)) ' e.g., 12,345,678 -> 1
            result = result & GetUrduChunk(chunkVal, arrUnits, arrTens) & " " & arrThousands(3) & " "
            strNum = Right(strNum, 7 - 1)
            lenNum = Len(strNum)
        End If
        
        If lenNum >= 5 Then ' Lakhs (100,000)
            chunkVal = CLng(Left(strNum, lenNum - 5 + 1)) ' e.g., 123,456 -> 12
            result = result & GetUrduChunk(chunkVal, arrUnits, arrTens) & " " & arrThousands(2) & " "
            strNum = Right(strNum, 5 - 1)
            lenNum = Len(strNum)
        End If
        
        If lenNum >= 3 Then ' Thousands (1,000)
            chunkVal = CLng(Left(strNum, lenNum - 3 + 1)) ' e.g., 123,456 -> 123 -> 123
            If chunkVal > 0 Then ' Only add 'thousand' if there's a thousand part
                result = result & GetUrduChunk(chunkVal, arrUnits, arrTens) & " " & arrThousands(1) & " "
            End If
            strNum = Right(strNum, 3 - 1) ' Remaining hundreds part
            lenNum = Len(strNum)
        End If
        
        If lenNum > 0 Then ' Hundreds and units
            chunkVal = CLng(strNum)
            result = result & GetUrduChunk(chunkVal, arrUnits, arrTens)
        End If
        
        result = Trim(result)
    End If
    
    If decPart > 0 Then
        result = result & " اور " & GetUrduChunk(decPart, arrUnits, arrTens) & " پیسے"
    End If
    
    NumberToWordsUrdu = result
    
    Exit Function
ErrorHandler:
    modUtils.LogError "NumberToWordsUrdu", Err
    NumberToWordsUrdu = ""
End Function

Private Function GetUrduChunk(ByVal n As Long, arrUnits As Variant, arrTens As Variant) As String
    Dim s As String
    If n = 0 Then
        GetUrduChunk = ""
        Exit Function
    End If
    
    If n >= 100 Then
        s = arrUnits(n \ 100) & " سو"
        n = n Mod 100
        If n > 0 Then s = s & " "
    End If
    
    If n >= 20 Or n = 10 Then ' Handle 10-19 and 20+
        If n >= 20 Then
            s = s & arrTens(n \ 10)
            n = n Mod 10
            If n > 0 Then s = s & " "
        ElseIf n = 10 Then
            s = s & arrTens(1)
            n = 0
        End If
    ElseIf n > 0 And n < 10 Then ' Handle 1-9
        s = s & arrUnits(n)
        n = 0
    End If
    
    GetUrduChunk = s
End Function

Function FormatCurrency(ByVal Value As Double) As String
    On Error GoTo ErrorHandler
    Dim formattedValue As String
    Dim symbol As String
    Dim position As String
    Dim decimalPlaces As Long
    
    symbol = AppSettings.CurrencySymbol
    position = AppSettings.CurrencyPosition
    decimalPlaces = AppSettings.DecimalPlaces
    
    formattedValue = Format(Round(Value, decimalPlaces), "#,##0." & String(decimalPlaces, "0"))
    
    Select Case position
        Case "Before"
            FormatCurrency = symbol & formattedValue
        Case "After"
            FormatCurrency = formattedValue & symbol
        Case Else ' Default to before
            FormatCurrency = symbol & formattedValue
    End Select
    
    If AppSettings.UrduMode Then
        FormatCurrency = modLocalization.ConvertNumeralsToUrdu(FormatCurrency)
    End If
    Exit Function
ErrorHandler:
    modUtils.LogError "FormatCurrency", Err
    FormatCurrency = CStr(Value)
End Function

Function RoundToDecimal(ByVal value As Double, ByVal decimalPlaces As Long) As Double
    On Error GoTo ErrorHandler
    RoundToDecimal = Round(value, decimalPlaces)
    Exit Function
ErrorHandler:
    modUtils.LogError "RoundToDecimal", Err
    RoundToDecimal = value
End Function

Function CalculatePercentage(ByVal baseValue As Double, ByVal percentage As Double) As Double
    On Error GoTo ErrorHandler
    CalculatePercentage = baseValue * (percentage / 100)
    Exit Function
ErrorHandler:
    modUtils.LogError "CalculatePercentage", Err
    CalculatePercentage = 0
End Function
