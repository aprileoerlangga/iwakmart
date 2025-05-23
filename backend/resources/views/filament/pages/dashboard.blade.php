<x-filament-panels::page>
    <x-filament::section>
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold tracking-tight">Dashboard</h1>
            
            <div>
                <span>{{ now()->format('l, d F Y') }}</span>
            </div>
        </div>
        
        <div class="p-4 mb-8 bg-white rounded-lg shadow-sm">
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <h2 class="text-lg font-semibold">Selamat datang, {{ auth()->user()->name }}</h2>
                    <p class="text-gray-600">
                        Gunakan dashboard ini untuk memantau dan mengelola aktivitas bisnis ikan Anda.
                    </p>
                </div>
                
                <div class="flex flex-col md:items-end">
                    <div class="flex space-x-2">
                        <a href="{{ route('filament.admin.resources.orders.index') }}" class="px-4 py-2 text-white transition bg-blue-600 rounded-md hover:bg-blue-700">
                            <span class="flex items-center space-x-1">
                                <x-heroicon-o-shopping-cart class="w-5 h-5" />
                                <span>Lihat Pesanan</span>
                            </span>
                        </a>
                        
                        <a href="{{ route('filament.admin.resources.products.index') }}" class="px-4 py-2 text-white transition bg-green-600 rounded-md hover:bg-green-700">
                            <span class="flex items-center space-x-1">
                                <x-heroicon-o-plus class="w-5 h-5" />
                                <span>Tambah Produk</span>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{ \Filament\Facades\Filament::renderHook('panels::page.header-widgets.start') }}

    @isset($headerWidgets)
        <x-filament-widgets::widgets
            :columns="$headerWidgetsColumns"
            :widgets="$headerWidgets"
        />
    @endisset

    {{ \Filament\Facades\Filament::renderHook('panels::page.header-widgets.end') }}

    {{ \Filament\Facades\Filament::renderHook('panels::page.footer-widgets.start') }}

    @isset($footerWidgets)
        <x-filament-widgets::widgets
            :columns="$footerWidgetsColumns"
            :widgets="$footerWidgets"
        />
    @endisset

    {{ \Filament\Facades\Filament::renderHook('panels::page.footer-widgets.end') }}
</x-filament-panels::page>