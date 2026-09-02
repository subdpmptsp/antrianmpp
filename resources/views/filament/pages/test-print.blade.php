<x-filament-panels::page>
    <div class="p-6">
        <h1 class="text-2xl font-bold mb-4">Test Print Button</h1>
        
        <div class="space-y-4">
            <div>
                <h2 class="text-lg font-semibold mb-2">Test 1: Livewire Button</h2>
                <button 
                    wire:click="testPrint"
                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Test Print (Livewire)
                </button>
            </div>
            
            <div>
                <h2 class="text-lg font-semibold mb-2">Test 2: Direct JavaScript</h2>
                <button 
                    onclick="testDirectPrint()"
                    class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    Test Print (JavaScript)
                </button>
            </div>
        </div>
    </div>
</x-filament-panels::page>

@push('scripts')
<script>
    // Test direct print
    function testDirectPrint() {
        const pdfUrl = @js($previewUrl);
        if (!pdfUrl) {
            alert('Belum ada layanan aktif untuk diuji.');
            return;
        }

        window.open(pdfUrl, '_blank');
    }
    
    // Print struk function
    async function printStruk(data) {
        console.log('printStruk called with data:', data);
        
        const strukHtml = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>{{ $mppBranding['name'] }} — Struk Antrean</title>
                <style>
                    @page { margin: 0; }
                    body { 
                        font-family: 'Courier New', monospace; 
                        margin: 0; 
                        padding: 20px;
                        background: white;
                    }
                    .struk-container {
                        border: 2px solid #0b66c3;
                        padding: 20px;
                        text-align: center;
                        max-width: 300px;
                        margin: 0 auto;
                        background: white;
                    }
                    .logo {
                        font-size: 12px;
                        margin-bottom: 10px;
                        line-height: 1.2;
                    }
                    .mall-title {
                        font-size: 18px;
                        font-weight: bold;
                        margin: 8px 0;
                        line-height: 1.2;
                    }
                    .sub-info {
                        font-size: 14px;
                        margin: 4px 0;
                    }
                    .queue-number {
                        font-size: 72px;
                        font-weight: bold;
                        margin: 20px 0;
                        letter-spacing: 4px;
                    }
                    .footer-info {
                        display: flex;
                        justify-content: space-between;
                        font-size: 12px;
                        margin-top: 15px;
                    }
                </style>
            </head>
            <body>
                <div class="struk-container">
                    <div class="logo">
                        ████████<br>
                        ██&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;██<br>
                        ██&nbsp;&nbsp;&nbsp;&nbsp;██&nbsp;&nbsp;&nbsp;&nbsp;██<br>
                        ██&nbsp;&nbsp;&nbsp;&nbsp;██&nbsp;&nbsp;&nbsp;&nbsp;██<br>
                        ██&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;██<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;████████<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;████
                    </div>
                    
                    <div class="mall-title">${data.mall}</div>
                    <div class="mall-title">${data.kota}</div>
                    
                    <div class="sub-info">${data.zona} - ${data.loket}</div>
                    <div class="sub-info">Layanan ${data.layanan}</div>
                    
                    <div class="queue-number">${data.nomor}</div>
                    
                    <div class="footer-info">
                        <div>${data.tanggal}</div>
                        <div>${data.waktu}</div>
                    </div>
                </div>
            </body>
            </html>
        `;

        // Buka window baru untuk cetak
        const printWindow = window.open('', '_blank', 'width=400,height=600');
        printWindow.document.write(strukHtml);
        printWindow.document.close();
        printWindow.focus();
        
        // Tunggu sebentar lalu cetak
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 500);
    }
    
    // Event listener untuk Livewire
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('open-pdf', (payload) => {
            console.log('open-pdf event received:', payload);
            const url = payload?.url || payload
            if (url) {
                console.log('Opening PDF:', url);
                window.open(url, '_blank');
            }
        });
        
        Livewire.on('notify', (payload) => {
            console.log('notify event received:', payload);
            const msg = typeof payload === 'string' ? payload : (payload?.message ?? '')
            if (msg) alert(msg)
        });
    });
</script>
@endpush
