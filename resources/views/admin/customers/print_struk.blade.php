<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Struk #{{ $transaction->no_nota }}</title>
    <style>
        /* ================================================================= */
        /* GAYA CSS (HASIL DISINKRONKAN DENGAN INDEX.BLADE.PHP) */
        /* ================================================================= */
        
        /* Reset bawaan browser */
        body, html {
            margin: 0;
            padding: 0;
            background: #fff;
            width: 100%;
        }

        /* 1. SETTING KERTAS: Auto (Ikut Driver) & Lebar Penuh */
        @page { 
            size: auto;   
            margin: 0; /* PENTING: Set 0 agar tidak konflik dengan Driver Printer */
        }

        /* 2. PENGATURAN AREA STRUK */
        #receipt-area {
            display: block;
            position: relative; /* Diubah jadi relative karena ini halaman khusus print */
            width: 72mm; /* Diubah dari 100% agar pas di kertas Roll 76mm TM-U220 */
            
            padding-top: 5px;
            padding-right: 2mm; 

            /* Memicu penggantian font ke internal printer via Utility Epson */
            color: black !important;
            font-weight: normal !important; 
            font-family: Arial, Helvetica, 'FontA11', 'FontB11', 'Control', sans-serif !important; 
            font-size: 9pt;  
            line-height: 1.15; 
            letter-spacing: 0px;
        }

        #receipt-area table, 
        #receipt-area td, 
        #receipt-area th, 
        #receipt-area div,
        #receipt-area span {
            font-family: inherit !important;
        }

        /* 3. HEADER & INFO */
        .receipt-header { 
            text-align: center; 
            margin-bottom: 5px; 
            border-bottom: 1px dashed #000; 
            padding-bottom: 5px; 
        }
        
        .receipt-title { 
            font-size: 11pt;
            margin: 0; 
            text-transform: uppercase;
        }

        .receipt-info { margin-bottom: 5px; }

        /* 4. TABEL TRANSAKSI */
        .receipt-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 5px; 
        }
        
        .receipt-table th { 
            text-align: right; 
            border-bottom: 1px dashed #000; 
            border-top: 1px dashed #000;
            padding: 3px 0;
            font-size: 8pt;
        }
        .receipt-table th:first-child { text-align: left; }

        .receipt-table td { 
            padding: 1px 0; 
            text-align: right;
            vertical-align: top; 
            font-size: 8pt;
        }
        .receipt-table td:first-child { text-align: left; }
        
        /* 5. TOTAL & FOOTER */
        .dashed-top { 
            border-top: 1px dashed #000; 
            padding-top: 5px; 
            margin-top: 5px;
        }
        
        .sign-area {
            margin-top: 15px; 
            display: flex; 
            justify-content: space-between; 
            font-size: 8pt;
        }
        
        .sign-box { text-align: center; width: 40%; }
        
        .sign-line {
            border-top: 1px solid #000; 
            width: 100%; 
            margin: 55px auto 0 auto; 
        }
        
        .receipt-footer { 
            text-align: center; 
            margin-top: 10px; 
            font-size: 8pt; 
            font-style: italic;
        }
    </style>
</head>
{{-- AUTO PRINT SAAT DIBUKA --}}
<body onload="window.print();"> 

    <div id="receipt-area">
        <div style="padding: 0 2px;">
            
            {{-- HEADER CABANG --}}
            <div class="receipt-header">
                <div class="receipt-title" style="font-weight: bold; font-size: 12pt;">PT. Rupiah Dollar Valuta</div>
                <div class="receipt-title">{{ $transaction->branch->name ?? 'MONEY CHANGER' }}</div>
                <div style="font-size: 8pt;">{{ $transaction->branch->address ?? 'Alamat Cabang' }}</div>
            </div>

            {{-- INFO NOTA --}}
            <div class="receipt-info" style="display: flex; justify-content: space-between; border-bottom: 1px dashed #000; padding-bottom: 3px;">
                <div>No: {{ $transaction->no_nota }}</div>
                <div>{{ $transaction->created_at->format('d/m/Y H:i') }}</div>
            </div>

            {{-- INFO NASABAH --}}
            <div class="receipt-info" style="margin-top: 5px;">
                <table style="width: 100%;">
                    <tr>
                        <td width="15%">Name</td>
                        <td width="2%">:</td>
                        <td>{{ \Illuminate\Support\Str::limit($transaction->customer_name, 25) }}</td>
                    </tr>
                    <tr>
                        <td>ID No</td>
                        <td>:</td>
                        <td>{{ $transaction->customer_identity_no }}</td>
                    </tr>
                    @if($transaction->customer_type == 'CORPORATE' && $transaction->representative_name)
                    <tr>
                        <td>PIC</td>
                        <td>:</td>
                        <td>{{ $transaction->representative_name }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td>Addr</td>
                        <td>:</td>
                        <td>{{ \Illuminate\Support\Str::limit($transaction->customer_address, 30) }}</td>
                    </tr>
                    <tr>
                        <td>Ctry</td>
                        <td>:</td>
                        <td>{{ $transaction->customer_country }}</td>
                    </tr>
                    
                    {{-- TYPE TRANSAKSI --}}
                    <tr>
                        <td style="padding-top: 5px;">TYPE</td>
                        <td style="padding-top: 5px;">:</td>
                        <td style="padding-top: 5px;">
                            <span style="text-transform: uppercase;">
                                {{ $transaction->type == 'buy' ? 'PEMBELIAN (BUY)' : 'PENJUALAN (SELL)' }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>

            {{-- TABEL 4 KOLOM (CURR | AMT | RATE | TOTAL) --}}
            <table class="receipt-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">CURR</th>
                        <th style="width: 25%;">AMOUNT</th>
                        <th style="width: 25%;">RATE</th>
                        <th style="width: 35%;">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $item)
                    <tr>
                        <td>{{ $item->currency }}</td>
                        <td>{{ number_format($item->amount_foreign, 0) }}</td> 
                        <td>{{ number_format($item->rate, 0) }}</td>
                        <td>{{ number_format($item->total_idr, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- TOTAL --}}
            <div class="dashed-top">
                <div style="display: flex; justify-content: space-between; font-size: 9pt;">
                    <span>TOTAL IDR</span>
                    <span>Rp {{ number_format($transactions->sum('total_idr'), 0) }}</span>
                </div>
                <div style="font-size: 8pt; margin-top: 2px;">
                    Metode: {{ $transaction->payment_method }}
                </div>
            </div>

            {{-- TANDA TANGAN --}}
            <div class="sign-area">
                <div class="sign-box">
                    <div>Customer</div> 
                    <div class="sign-line">{{ \Illuminate\Support\Str::limit($transaction->customer_name, 15) }}</div>
                </div>

                <div class="sign-box">
                    <div>Customer Service</div> 
                    <div class="sign-line">{{ $transaction->user->name ?? 'Admin' }}</div>
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="receipt-footer">
                "This transaction was conducted below the threshold / equivalent to USD 10,000"
            </div>
            <div class="receipt-footer">
                "Please recount your money before leaving the outlet/office. No complaints or claims regarding a shortfall will be entertained after leaving the counter."
            </div>

        </div>
    </div>

    {{-- SCRIPT TUTUP TAB OTOMATIS SETELAH PRINT --}}
    <script>
        window.onafterprint = function() {
            setTimeout(function() { window.close(); }, 500);
        };
    </script>
</body>
</html>