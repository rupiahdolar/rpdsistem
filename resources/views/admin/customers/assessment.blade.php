@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto pb-20">
    
    {{-- HEADER & FILTER TAHUN --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            
            {{-- Judul Halaman --}}
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"></path></svg>
                    Assessment Risiko (APU-PPT)
                </h1>
                <p class="text-sm text-gray-500 mt-1">Peta profil risiko transaksi dan nasabah Tahun {{ $year }}</p>
            </div>
            
            {{-- Filter Tahun --}}
            <form action="{{ route('nasabah.assessment') }}" method="GET">
                <div class="relative">
                    <select name="year" onchange="this.form.submit()" class="appearance-none bg-gray-50 border border-gray-300 text-gray-700 text-sm font-bold rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5 pr-8 shadow-sm cursor-pointer hover:bg-white transition">
                        @for($y = date('Y'); $y >= 2024; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>Tahun Laporan: {{ $y }}</option>
                        @endfor
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- TABEL 1: TRANSAKSI KUPVA BB --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col h-full">
            <div class="px-6 py-4 border-b border-gray-100 bg-white">
                <h3 class="font-bold text-primary text-sm uppercase tracking-wider flex items-center gap-2">
                    <span class="bg-primary text-white w-6 h-6 rounded flex items-center justify-center text-xs">1</span>
                    Transaksi KUPVA BB (Nominal)
                </h3>
            </div>
            <div class="flex-1 overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-bold">
                        <tr>
                            <th class="px-6 py-3">Mata Uang</th>
                            <th class="px-6 py-3 text-right">Nominal (IDR)</th>
                            <th class="px-6 py-3 text-right">Porsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($currencyReport as $curr => $val)
                            @php $persen = $totalCurrencyNominal > 0 ? ($val / $totalCurrencyNominal) * 100 : 0; @endphp
                            <tr class="hover:bg-blue-50/50 transition {{ $curr == 'Lainnya' ? 'bg-gray-50/50 italic text-gray-500' : '' }}">
                                <td class="px-6 py-3 font-bold text-gray-700">{{ $curr }}</td>
                                <td class="px-6 py-3 text-right font-mono text-gray-600">
                                    {{ number_format($val, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <span class="text-xs font-bold px-2 py-1 rounded {{ $persen > 20 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ number_format($persen, 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-blue-50 border-t border-blue-100">
                        <tr>
                            <td class="px-6 py-4 font-bold text-primary uppercase text-xs">Total Transaksi</td>
                            <td class="px-6 py-4 text-right font-black font-mono text-primary text-base">{{ number_format($totalCurrencyNominal, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right font-bold text-primary">100%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- TABEL 2: MITRA BERDASARKAN NEGARA --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col h-full">
            <div class="px-6 py-4 border-b border-gray-100 bg-white">
                <h3 class="font-bold text-primary text-sm uppercase tracking-wider flex items-center gap-2">
                    <span class="bg-primary text-white w-6 h-6 rounded flex items-center justify-center text-xs">2</span>
                    Mitra Kerja Sama (Negara)
                </h3>
            </div>
            <div class="flex-1 overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-bold">
                        <tr>
                            <th class="px-6 py-3">Kategori Negara</th>
                            <th class="px-6 py-3 text-right">Jumlah Orang</th>
                            <th class="px-6 py-3 text-right">Porsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($countryReport as $key => $val)
                            @php $persen = $totalPeopleCountry > 0 ? ($val / $totalPeopleCountry) * 100 : 0; @endphp
                            <tr class="hover:bg-blue-50/50 transition">
                                <td class="px-6 py-3 font-medium text-gray-700 capitalize">
                                    {{ $key }}
                                </td>
                                <td class="px-6 py-3 text-right font-mono text-gray-600">
                                    {{ number_format($val, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <span class="text-xs font-bold px-2 py-1 rounded bg-gray-100 text-gray-600">
                                        {{ number_format($persen, 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-blue-50 border-t border-blue-100">
                        <tr>
                            <td class="px-6 py-4 font-bold text-primary uppercase text-xs">Total Nasabah</td>
                            <td class="px-6 py-4 text-right font-black font-mono text-primary text-base">{{ number_format($totalPeopleCountry, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right font-bold text-primary">100%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- TABEL 3: PENGGUNA JASA PERORANGAN --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden lg:col-span-2">
            <div class="px-6 py-4 border-b border-gray-100 bg-white">
                <h3 class="font-bold text-primary text-sm uppercase tracking-wider flex items-center gap-2">
                    <span class="bg-primary text-white w-6 h-6 rounded flex items-center justify-center text-xs">3</span>
                    Profil Pekerjaan (Perorangan)
                </h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 divide-x divide-gray-100">
                <div class="md:col-span-2 overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-bold">
                            <tr>
                                <th class="px-6 py-3">Pekerjaan</th>
                                <th class="px-6 py-3 text-right">Jumlah</th>
                                <th class="px-6 py-3 text-right">Porsi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($jobReport as $key => $val)
                                @php $persen = $totalPeopleJob > 0 ? ($val / $totalPeopleJob) * 100 : 0; @endphp
                                <tr class="hover:bg-blue-50/50 transition">
                                    <td class="px-6 py-2.5 font-medium text-gray-700">{{ $key }}</td>
                                    <td class="px-6 py-2.5 text-right font-mono text-gray-600">{{ number_format($val, 0, ',', '.') }}</td>
                                    <td class="px-6 py-2.5 text-right text-xs font-bold text-gray-400">{{ number_format($persen, 1) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="bg-blue-50/30 p-8 flex flex-col justify-center items-center text-center">
                    <h4 class="text-gray-500 font-bold uppercase text-xs tracking-widest mb-1">Total Individu</h4>
                    <div class="text-5xl font-black text-primary my-2 tracking-tight">{{ number_format($totalPeopleJob) }}</div>
                    <p class="text-xs text-gray-400 max-w-xs mx-auto">
                        Jumlah nasabah perorangan unik yang bertransaksi tahun {{ $year }}.
                    </p>
                </div>
            </div>
        </div>

        {{-- TABEL 4: PENGGUNA JASA BADAN USAHA (REAL DATA) --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden lg:col-span-2">
            <div class="px-6 py-4 border-b border-gray-100 bg-white">
                <h3 class="font-bold text-primary text-sm uppercase tracking-wider flex items-center gap-2">
                    <span class="bg-primary text-white w-6 h-6 rounded flex items-center justify-center text-xs">4</span>
                    Pengguna Jasa Badan Usaha
                </h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 divide-x divide-gray-100">
                <div class="md:col-span-2 overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-bold">
                            <tr>
                                <th class="px-6 py-3">Bentuk Badan Usaha</th>
                                <th class="px-6 py-3 text-right">Jumlah Entitas</th>
                                <th class="px-6 py-3 text-right">Porsi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($corporateReport as $key => $val)
                                @php $persen = $totalCorporate > 0 ? ($val / $totalCorporate) * 100 : 0; @endphp
                                <tr class="hover:bg-blue-50/50 transition">
                                    <td class="px-6 py-2.5 font-medium text-gray-700">{{ $key }}</td>
                                    <td class="px-6 py-2.5 text-right font-mono text-gray-600">{{ number_format($val, 0, ',', '.') }}</td>
                                    <td class="px-6 py-2.5 text-right text-xs font-bold text-gray-400">{{ number_format($persen, 1) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="bg-blue-50/30 p-8 flex flex-col justify-center items-center text-center">
                    <h4 class="text-gray-500 font-bold uppercase text-xs tracking-widest mb-1">Total Korporasi</h4>
                    <div class="text-5xl font-black text-primary my-2 tracking-tight">{{ number_format($totalCorporate) }}</div>
                    <p class="text-xs text-gray-400 max-w-xs mx-auto">
                        Jumlah entitas badan usaha unik yang bertransaksi tahun {{ $year }}.
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection