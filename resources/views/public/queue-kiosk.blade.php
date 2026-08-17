<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Antrian - MPP Siola</title>
    @vite(['resources/css/app.css'])
    <style>
        html, body { 
            height: 100%; 
            overflow-y: auto !important; 
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }

        .container-main {
            min-height: 100vh;
            padding: 0;
        }

        /* ====== ZONA CARDS NEW STYLING ====== */
        .zona-card-new {
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .zona-card-new:hover {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
        }
        
        .zona-label-new {
            background: #ffffff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .zona-card-new:hover .zona-label-new {
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }

        /* ====== RESPONSIVE DESIGN FOR NEW ZONA CARDS ====== */
        @media (max-width: 1024px) {
            .zona-card-new {
                min-height: 280px;
            }
        }
        
        @media (max-width: 768px) {
            .zona-card-new {
                min-height: 260px;
                padding: 1.25rem;
            }
            
            .zona-label-new {
                font-size: 1rem;
                padding: 0.5rem 1.25rem;
            }
        }
        
        @media (max-width: 480px){
            .zona-card-new {
                min-height: 240px;
                padding: 1rem;
            }
            
            .zona-label-new {
                font-size: 0.9rem;
                padding: 0.4rem 1rem;
            }
        }

        /* ====== ANIMATIONS FOR NEW ZONA CARDS ====== */
        @keyframes slideInScale {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .zona-card-new {
            animation: slideInScale 0.5s ease-out;
        }
        
        .zona-card-new:nth-child(1) { animation-delay: 0.1s; }
        .zona-card-new:nth-child(2) { animation-delay: 0.2s; }
        .zona-card-new:nth-child(3) { animation-delay: 0.3s; }

        /* ====== INSTANSI BOARD WITH BACKGROUND ====== */
        .mpp-board-instansi {
            margin-top: 1rem;
            background: linear-gradient(180deg, rgba(13,0,154,.08), rgba(13,0,154,.08));
            border: 1px solid rgba(255,255,255,.08);
            min-height: 450px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .mpp-bg-image-instansi {
            background-image: url('{{ asset("img/bg.png") }}');
            background-size: cover;
            background-position: center;
        }

        /* ====== CENTERING CONTAINER FOR INSTANSI ====== */
        .instansi-container-centered {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            align-content: center;
            gap: 1rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            min-height: 300px;
        }

        /* ====== INSTANSI CHIPS BLUE STYLING ====== */
        .instansi-chip-blue {
            width: 15.5rem;
            height: 4rem;
            border-radius: 1rem;
            background: linear-gradient(180deg, #9DB0FF 0%, #7E8DFF 100%);
            box-shadow: 0 8px 20px rgba(0,0,0,.15);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .75rem 1rem;
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
            border: 2px solid rgba(255,255,255,.65);
            backdrop-filter: blur(2px);
            cursor: pointer;
            text-decoration: none;
        }
        
        .instansi-chip-blue:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0,0,0,.25);
            filter: brightness(1.05);
        }
        
        .instansi-chip-blue:active { 
            transform: translateY(-1px) scale(.995); 
        }
        
        .instansi-chip-label-blue {
            color: #0d0d0d;
            font-weight: 700;
            text-align: center;
            line-height: 1.2;
            font-size: .95rem;
        }

        /* ====== RESPONSIVE FOR INSTANSI CHIPS ====== */
        @media (max-width: 768px) {
            .instansi-chip-blue {
                width: 13rem;
                height: 3.5rem;
            }
            
            .instansi-chip-label-blue {
                font-size: .9rem;
            }
            
            .instansi-container-centered {
                padding: 1.5rem;
                gap: 0.8rem;
            }
        }
        
        @media (max-width: 480px) {
            .instansi-chip-blue {
                width: 12rem;
                height: 3rem;
            }
            
            .instansi-chip-label-blue {
                font-size: .85rem;
            }
            
            .instansi-container-centered {
                padding: 1rem;
                gap: 0.6rem;
            }
            
            .mpp-board-instansi {
                min-height: 350px;
            }
        }

        /* ====== ANIMATIONS FOR INSTANSI CHIPS ====== */
        @keyframes slideInFromBottom {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .instansi-chip-blue {
            animation: slideInFromBottom 0.6s ease-out;
        }
        
        .instansi-chip-blue:nth-child(1) { animation-delay: 0.1s; }
        .instansi-chip-blue:nth-child(2) { animation-delay: 0.2s; }
        .instansi-chip-blue:nth-child(3) { animation-delay: 0.3s; }
        .instansi-chip-blue:nth-child(4) { animation-delay: 0.4s; }
        .instansi-chip-blue:nth-child(5) { animation-delay: 0.5s; }
        .instansi-chip-blue:nth-child(6) { animation-delay: 0.6s; }

        /* ====== BACK BUTTONS BLUE STYLING ====== */
        .back-btn-neutral {
            background: linear-gradient(180deg, #9DB0FF 0%, #7E8DFF 100%);
            border: 2px solid rgba(255,255,255,0.65);
            backdrop-filter: blur(2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
            cursor: pointer;
        }
        
        .back-btn-neutral:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.25);
            filter: brightness(1.05);
        }
        
        .back-btn-neutral:active {
            transform: translateY(-1px) scale(0.995);
        }

        /* ====== BOARD ala mockup ====== */
        .mpp-board {
            margin-top: .5rem;
            background: linear-gradient(180deg, rgba(13,0,154,.08), rgba(13,0,154,.08));
            border: 1px solid rgba(255,255,255,.08);
        }
        .mpp-bg-image {
            background-image: url('{{ asset("img/bg.png") }}');
        }
        .mpp-title{
            margin-top: 1.25rem;
            background: #0D009A;
            border-radius: 1.25rem;
            padding: .9rem 1.25rem;
            min-width: 320px;
            max-width: 720px;
            width: fit-content;
            border: 6px solid rgba(255,255,255,.92);
        }
        .mpp-chip{
            width: 15.5rem;
            height: 4.5rem;
            border-radius: 1rem;
            background: linear-gradient(180deg, #9DB0FF 0%, #7E8DFF 100%);
            box-shadow: 0 10px 18px rgba(0,0,0,.18);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .75rem 1rem;
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
            border: 2px solid rgba(255,255,255,.65);
            backdrop-filter: blur(2px);
            cursor: pointer;
            text-decoration: none;
        }
        .mpp-chip:hover{
            transform: translateY(-3px);
            box-shadow: 0 14px 24px rgba(0,0,0,.22);
            filter: brightness(1.03);
        }
        .mpp-chip:active{ transform: translateY(-1px) scale(.995); }
        .mpp-chip-label{
            color: #0d0d0d;
            font-weight: 700;
            text-align: center;
            line-height: 1.2;
            font-size: .98rem;
        }
        @media (max-width: 480px){
            .mpp-chip{ width: 12.5rem; height: 4rem; }
            .mpp-chip-label{ font-size: .92rem; }
        }

        /* Header Full Width */
        .header-full-width {
            width: 100vw;
            margin-left: calc(-50vw + 50%);
        }
    </style>
</head>
<body>
    <div class="container-main">
        @if(session('error'))
            <div class="mx-auto mt-4 max-w-3xl rounded-xl bg-red-100 px-5 py-3 text-center font-semibold text-red-800">
                {{ session('error') }}
            </div>
        @endif

        @if(!$selectedCounter)
            {{-- ===== PILIH ZONA ===== --}}
            <div class="header-full-width flex items-center justify-between px-6 py-4" style="background-color:#0D009A;">
                <div class="w-24 flex justify-center">
                    <img src="{{ asset('img/logopemkot_white.png') }}" alt="Logo Kiri" class="h-24 object-contain">
                </div>
                <div class="text-center flex-1 text-white">
                    <h1 class="text-3xl font-bold tracking-wide">MALL PELAYANAN PUBLIK</h1>
                    <p class="mt-2 text-base">Jl. Tunjungan No.1-3, Genteng, Kec. Genteng, Surabaya, Jawa Timur 60275</p>
                </div>
                <div class="w-28 flex justify-center">
                    <img src="{{ asset('img/dpmptsp.png') }}" alt="Logo DPMPTSP" class="h-24 object-contain">
                </div>
            </div>

            <p class="text-center text-gray-600 mt-6">Silakan pilih Zona untuk melihat instansi</p>

            {{-- Layout Zona --}}
            <div class="max-w-6xl mx-auto px-4 mt-8">
                {{-- Baris Pertama: 3 Zona --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    @foreach(['1' => 1, '2' => 2, '3' => 3] as $label => $id)
                        @if(isset($counters[$id]))
                            <a href="{{ route('public.queue-kiosk', ['zona' => $id]) }}" 
                                class="zona-card-new relative rounded-3xl shadow-lg cursor-pointer hover:shadow-xl transition-all duration-300 hover:scale-[1.02]" 
                                style="background: #8A8CFF; min-height: 300px; padding: 1.5rem; text-decoration: none;">
                                
                                {{-- Label Zona --}}
                                <div class="absolute -top-4 left-1/2 -translate-x-1/2 z-10">
                                    <div class="zona-label-new px-6 py-2 bg-white rounded-full border-2 border-black shadow-md">
                                        <span class="font-bold text-lg text-black">ZONA {{ $label }}</span>
                                    </div>
                                </div>
                                
                                {{-- Content Area --}}
                                <div class="pt-8 pb-4">
                                    {{-- Daftar Instansi --}}
                                    <ul class="space-y-2 text-black">
                                        @foreach($counters[$id]['services'] as $service)
                                            <li class="flex items-start">
                                                <span class="text-black mr-2 mt-1 text-lg">•</span>
                                                <span class="font-semibold text-base leading-snug underline">
                                                    {{ is_array($service) ? ($service['nama_service'] ?? $service['name'] ?? '-') : $service }}
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </a>
                        @endif
                    @endforeach
                </div>

                {{-- Baris Kedua: 2 Zona (Centered) --}}
                <div class="flex justify-center gap-6">
                    @foreach(['4' => 4, '5' => 5] as $label => $id)
                        @if(isset($counters[$id]))
                            <a href="{{ route('public.queue-kiosk', ['zona' => $id]) }}" 
                                class="zona-card-new relative rounded-3xl shadow-lg cursor-pointer hover:shadow-xl transition-all duration-300 hover:scale-[1.02]" 
                                style="background: #8A8CFF; width: 300px; min-height: 300px; padding: 1.5rem; text-decoration: none;">
                                
                                {{-- Label Zona --}}
                                <div class="absolute -top-4 left-1/2 -translate-x-1/2 z-10">
                                    <div class="zona-label-new px-6 py-2 bg-white rounded-full border-2 border-black shadow-md">
                                        <span class="font-bold text-lg text-black">ZONA {{ $label }}</span>
                                    </div>
                                </div>
                                
                                {{-- Content Area --}}
                                <div class="pt-8 pb-4">
                                    {{-- Daftar Instansi --}}
                                    <ul class="space-y-2 text-black">
                                        @foreach($counters[$id]['services'] as $service)
                                            <li class="flex items-start">
                                                <span class="text-black mr-2 mt-1 text-lg">•</span>
                                                <span class="font-semibold text-base leading-snug underline">
                                                    {{ is_array($service) ? ($service['nama_service'] ?? $service['name'] ?? '-') : $service }}
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>

        @else
            {{-- ===== Sudah pilih ZONA ===== --}}
            <div class="header-full-width flex items-center justify-between px-6 py-4" style="background-color:#0D009A;">
                <div class="w-24 flex justify-center">
                    <img src="{{ asset('img/logopemkot_white.png') }}" alt="Logo Kiri" class="h-24 object-contain">
                </div>
                <div class="text-center flex-1 text-white">
                    <h1 class="text-3xl font-bold tracking-wide">MALL PELAYANAN PUBLIK</h1>
                    <p class="mt-2 text-base">Jl. Tunjungan No.1-3, Genteng, Kec. Genteng, Surabaya, Jawa Timur 60275</p>
                </div>
                <div class="w-28 flex justify-center">
                    <img src="{{ asset('img/dpmptsp.png') }}" alt="Logo DPMPTSP" class="h-24 object-contain">
                </div>
            </div>

            <div class="max-w-6xl mx-auto px-4 mt-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">{{ $counters[$selectedCounter]['name'] ?? 'Zona ' . $selectedCounter }}</h2>

                    <div class="flex gap-3">
                        @if($selectedInstansi && $selectedCounter != 1)
                            <a href="{{ route('public.queue-kiosk', ['zona' => $selectedCounter]) }}"
                                class="back-btn-neutral font-bold text-black px-6 py-3 rounded-2xl">
                                ← Kembali ke Instansi
                            </a>
                        @endif
                        <a href="{{ route('public.queue-kiosk') }}"
                            class="back-btn-neutral font-bold text-black px-6 py-3 rounded-2xl ml-4">
                            ← Kembali ke Zona
                        </a>
                    </div>
                </div>

                {{-- ===== Tampilan Instansi dengan Background Gedung ===== --}}
                @if(!$selectedInstansi && $instansis->count() > 1 && $services->count() == 0)
                    <div class="mpp-board-instansi relative overflow-hidden rounded-2xl mt-6">
                        {{-- Background foto gedung --}}
                        <div class="absolute inset-0 bg-cover bg-center opacity-90 mpp-bg-image-instansi"></div>
                        <div class="absolute inset-0 bg-black/30"></div>

                        {{-- Content Area dengan Centering yang Lebih Baik --}}
                        <div class="instansi-container-centered">
                            @forelse($instansis as $instansi)
                                <a href="{{ route('public.queue-kiosk', ['zona' => $selectedCounter, 'instansi' => $instansi->instansi_id]) }}"
                                    class="instansi-chip-blue group">
                                    <span class="instansi-chip-label-blue">
                                        {{ $instansi->nama_instansi }}
                                    </span>
                                </a>
                            @empty
                                <div class="w-full text-center text-white/90 py-12">
                                    <div class="bg-black/20 rounded-2xl p-8 backdrop-blur-sm">
                                        <p class="text-xl font-semibold mb-2">Belum ada instansi</p>
                                        <p class="text-white/70">Belum ada instansi untuk zona ini.</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                {{-- ===== daftar LAYANAN ===== --}}
                @elseif($selectedInstansi)
                    @php
                        $instansiNow  = $instansis->firstWhere('instansi_id', $selectedInstansi);
                        $instansiName = $instansiNow?->nama_instansi ?? 'Instansi';
                        $prettyTitle  = strtoupper($instansiName) === 'UPTSP'
                            ? 'Unit Pelayanan Terpadu Satu Pintu (UPTSP)'
                            : $instansiName;
                    @endphp

                    <div class="mpp-board relative overflow-hidden rounded-2xl">
                        {{-- background foto gedung --}}
                        <div class="absolute inset-0 bg-cover bg-center opacity-90 mpp-bg-image"></div>
                        <div class="absolute inset-0 bg-black/20"></div>

                        {{-- banner judul instansi --}}
                        <div class="relative flex justify-center">
                            <div class="mpp-title shadow-lg">
                                <span class="block text-white text-2xl md:text-3xl font-extrabold text-center leading-tight">
                                    {{ $prettyTitle }}
                                </span>
                            </div>
                        </div>

                        {{-- grid kartu layanan --}}
                        <div class="relative mx-auto max-w-6xl px-6 pb-10 pt-6">
                            <div class="flex flex-wrap gap-6 md:gap-8 justify-center">
                                @forelse($services as $service)
                                    <form method="POST" action="{{ route('public.queue-kiosk.select-service', ['serviceId' => $service->id, 'zona' => $selectedCounter]) }}">
                                        @csrf
                                        <input type="hidden" name="queue_request_token" value="{{ $queueRequestToken }}">
                                        <button type="submit" class="mpp-chip group">
                                        <span class="mpp-chip-label">
                                            {{ $service->name ?? $service->nama_service ?? '-' }}
                                        </span>
                                        </button>
                                    </form>
                                @empty
                                    <div class="text-center text-white/90 py-10">
                                        Belum ada layanan untuk instansi ini.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</body>
</html>
