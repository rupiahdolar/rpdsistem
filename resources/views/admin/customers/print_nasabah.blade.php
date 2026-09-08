<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export Data Nasabah</title>
    <style>
        /* KUNCI: KERTAS LANDSCAPE AGAR TABEL MUAT */
        @page { size: landscape; margin: 10mm; }
        
        body { 
            font-family: Arial, sans-serif; 
            font-size: 9px; /* Font kecil */
            margin: 0;
        }
        .header { 
            text-align: center; 
            margin-bottom: 15px; 
            border-bottom: 2px solid #000; 
            padding-bottom: 10px; 
        }
        h2 { margin: 0; font-size: 14px; text-transform: uppercase; }
        p { margin: 2px 0; font-size: 10px; }
        
        /* STYLE TABEL RAPAT */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 5px; 
        }
        th, td { 
            border: 1px solid #444; 
            padding: 3px 4px; 
            text-align: left; 
            vertical-align: top; 
            word-wrap: break-word;
        }
        th { 
            background-color: #f0f0f0; 
            font-weight: bold; 
            text-transform: uppercase;
            font-size: 8px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h2>PT. Rupiah Dollar Valuta</h2>
        <p>LAPORAN DATA TRANSAKSI NASABAH (DETAIL LENGKAP)</p>
        <p>Dicetak Tanggal: {{ date('d/m/Y H:i') }} | Oleh: {{ Auth::user()->name }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="3%">No</th>
                <th width="5%">Tgl</th>
                <th width="6%">Nota</th>
                <th width="10%">Nama Lengkap</th>
                <th width="3%" class="text-center">L/P</th>
                <th width="5%">Lahir</th>
                <th width="8%">Identitas</th>
                <th width="10%">Alamat</th>
                <th width="6%">Pekerjaan</th>
                <th width="5%">Negara</th>
                <th width="6%">Sumber</th>
                <th width="6%">Tujuan</th>
                <th width="3%" class="text-center">Tipe</th>
                <th width="3%" class="text-center">Valas</th>
                <th width="6%" class="text-right">Jml</th>
                <th width="5%" class="text-right">Rate</th>
                <th width="8%" class="text-right">Total (IDR)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $row)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $row->created_at->format('d/m/y') }}</td>
                <td>{{ $row->no_nota }}</td>
                <td>{{ $row->customer_name }}</td>
                <td class="text-center">{{ $row->customer_gender }}</td>
                <td>{{ $row->customer_dob ? date('d/m/y', strtotime($row->customer_dob)) : '-' }}</td>
                <td>
                    <b>{{ $row->customer_id_type }}</b><br>
                    {{ $row->customer_identity_no }}
                </td>
                <td>{{ $row->customer_address }}</td>
                <td>{{ $row->customer_job }}</td>
                <td>{{ $row->customer_country }}</td>
                <td>{{ $row->source_of_funds }}</td>
                <td>{{ $row->transaction_purpose }}</td>
                <td class="text-center">{{ strtoupper($row->type) }}</td>
                <td class="text-center font-bold">{{ $row->currency }}</td>
                <td class="text-right">{{ number_format($row->amount_foreign, 2) }}</td>
                <td class="text-right">{{ number_format($row->rate) }}</td>
                <td class="text-right font-bold">{{ number_format($row->total_idr) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>