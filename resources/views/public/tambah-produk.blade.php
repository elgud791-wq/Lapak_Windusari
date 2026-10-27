<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - Lapak Windusari</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen py-10">
    <div class="max-w-2xl mx-auto bg-white shadow-sm rounded-lg p-8">
        <h1 class="text-2xl font-bold mb-1">Tambah Produk Baru</h1>
        <p class="text-gray-500 mb-6">Atas nama: <strong>{{ $existing->full_name }}</strong> — {{ $existing->village->name ?? '' }}</p>

        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('public.tambah-produk.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="registration_number" value="{{ $existing->registration_number }}">
            <input type="hidden" name="nik" value="{{ $existing->nik }}">

            <div>
                <label class="block text-sm font-medium mb-1">Nama Produk</label>
                <input type="text" name="product_name" value="{{ old('product_name') }}" required class="w-full border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Deskripsi Produk & Harga</label>
                <textarea name="product_description" class="w-full border-gray-300 rounded-md">{{ old('product_description') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Foto Produk</label>
                <input type="file" name="product_photo" accept="image/*" class="w-full text-sm">
            </div>
                        <div>
                <label class="block text-sm font-medium mb-1">Upload NIB (opsional)</label>
                <input type="file" name="nib_file" class="w-full text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Upload Sertifikat Halal (opsional)</label>
                <input type="file" name="halal_cert_file" class="w-full text-sm">
            </div>
            <button type="submit" class="w-full bg-teal-700 text-white py-2 rounded-md font-medium">Tambah Produk</button>
        </form>
    </div>
</body>
</html>