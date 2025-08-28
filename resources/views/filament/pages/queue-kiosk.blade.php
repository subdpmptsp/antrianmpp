<div class="py-6">

    <!-- Fullscreen Button -->
    <button id="connect-button" class="fixed top-6 left-6 z-50 w-14 h-14 bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 flex items-center justify-center hover:bg-gradient-to-br hover:from-blue-600 hover:to-indigo-600 hover:text-white transition-all duration-300 group">
        <img src="{{asset('printer.svg')}}" class="w-7 h-7 text-gray-700 group-hover:text-white transition-colors" alt="">
    </button>

    <!-- Fullscreen Button -->
    <button id="fullscreen-btn" class="fixed top-6 right-6 z-50 w-14 h-14 bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 flex items-center justify-center hover:bg-gradient-to-br hover:from-blue-600 hover:to-indigo-600 hover:text-white transition-all duration-300 group">
        <svg class="w-7 h-7 text-gray-700 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
        </svg>
    </button>
    <!-- Instructions -->
    <div class="text-center my-6">
        <div class="bg-white/80 backdrop-blur-sm rounded-3xl p-8 shadow-xl border border-gray-200/50 max-w-3xl mx-auto">
            <h4 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">Petunjuk Penggunaan</h4>
            <p class="text-gray-600 text-base md:text-lg leading-relaxed">
                Sentuh layanan yang diinginkan untuk mengambil nomor antrian.
            </p>
        </div>
    </div>

    <!-- Main Content Area -->
    <main class="relative flex-1 flex justify-center px-6 lg:px-8">
        <div class="w-full max-w-6xl">

            <!-- Service Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-5xl mx-auto">
                @foreach($services as $service)
                <button
                    wire:click="print({{ $service->id }})"
                    class="service-card group relative bg-white rounded-2xl shadow-lg hover:shadow-xl p-8 border border-gray-100 transition-all duration-300 transform hover:-translate-y-2 hover:scale-105 overflow-hidden">

                    <!-- Hover Background Effect -->
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-purple-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                    <div class="relative">
                        <div class="flex items-center justify-between ">
                            <div class="flex items-center space-x-4">
                                <!-- Dynamic Icon Container -->
                                <div class="service-icon w-20 h-20 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>

                                <!-- Service Info -->
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-800 mb-3 group-hover:text-indigo-600 transition-colors duration-200">
                                        {{ $service->name }}
                                    </h3>
                                    <p class="text-gray-600 text-base">
                                        Tekan untuk mengambil nomor antrian
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Subtle Hover Effect -->
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 to-purple-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-2xl"></div>

                    <!-- Active State Indicator -->
                    <div class="absolute top-4 right-4 w-3 h-3 bg-green-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-200 animate-pulse"></div>

                    <!-- Click Ripple Effect -->
                    <div class="absolute inset-0 rounded-2xl overflow-hidden">
                        <div class="ripple-effect"></div>
                    </div>
                </button>
                @endforeach
            </div>
        </div>
    </main>
</div>

@push('styles')
<style>
    .service-card {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .service-card:hover {
        transform: translateY(-12px) scale(1.03);
        box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.3);
    }

    .service-card:active {
        transform: translateY(-8px) scale(1.01);
    }

    .service-icon {
        transition: all 0.5s ease;
    }

    .status-indicator {
        width: 14px;
        height: 14px;
        background: #10b981;
        border-radius: 50%;
        position: relative;
    }

    .status-indicator::before {
        content: '';
        position: absolute;
        width: 14px;
        height: 14px;
        background: #10b981;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        }

        70% {
            transform: scale(1);
            box-shadow: 0 0 0 15px rgba(16, 185, 129, 0);
        }

        100% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
        }
    }

    .clock-display {
        font-family: 'Inter', monospace;
        font-weight: 600;
        letter-spacing: 0.1em;
    }

    .ripple-effect {
        position: absolute;
        border-radius: 50%;
        background: rgba(59, 130, 246, 0.3);
        transform: scale(0);
        animation: ripple 0.6s linear;
        pointer-events: none;
    }

    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }

    .service-card:active .ripple-effect {
        animation: ripple 0.6s linear;
    }

    /* Kiosk Mode Styles */
    body {
        overflow: hidden;
        user-select: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
    }

    /* Floating Animation */
    .floating-shape {
        position: absolute;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(147, 51, 234, 0.1));
        animation: float 6s ease-in-out infinite;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-20px);
        }
    }

    /* Responsive Design for Kiosk */
    @media (max-height: 768px) {
        .text-6xl {
            font-size: 3rem;
        }

        .text-7xl {
            font-size: 4rem;
        }

        .p-12 {
            padding: 2rem;
        }

        .mb-16 {
            margin-bottom: 2rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        const connectButton = document.getElementById('connect-button');

        if (connectButton) {
            connectButton.addEventListener('click', async () => {
                window.connectedPrinter = await getPrinter()
            })
        }

        Livewire.on("print-start", async (text) => {
            await printThermal(text)
        })
    })
</script>
@endpush