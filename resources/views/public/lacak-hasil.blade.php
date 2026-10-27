<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include("partials.pwa-head")
    @include("partials.pwa-head")
    <title>Status Pengajuan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen py-10">
    <div class="max-w-md mx-auto bg-white shadow-sm rounded-lg p-8">
        <h1 class="text-xl font-bold mb-1">{{ $submission->full_name }}</h1>
        <p class="text-sm text-gray-500 mb-4">{{ $submission->registration_number }} · {{ $submission->product_name }}</p>

        @php
            $statusLabels = [
                'pending' => ['Menunggu Verifikasi Desa', 'bg-yellow-100 text-yellow-800'],
                'verified_village' => ['Diverifikasi Desa, Menunggu Kecamatan', 'bg-blue-100 text-blue-800'],
                'approved' => ['Disetujui', 'bg-green-100 text-green-800'],
                'rejected_by_village' => ['Ditolak oleh Desa', 'bg-red-100 text-red-800'],
                'rejected_by_district' => ['Ditolak oleh Kecamatan', 'bg-red-100 text-red-800'],
            ];
            [$label, $color] = $statusLabels[$submission->status] ?? ['-', 'bg-gray-50'];
        @endphp

        <span class="{{ $color }} px-3 py-1 rounded-full text-sm font-semibold inline-block mb-4">{{ $label }}</span>

               @if ($submission->rejection_reason)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700 text-sm mb-4">
                <strong>Alasan:</strong> {{ $submission->rejection_reason }}
            </div>
        @endif

        @if (in_array($submission->status, ['verified_village', 'approved']))
            <div class="bg-teal-50 border border-teal-200 rounded-lg p-4 mb-4">
                <p class="text-sm text-teal-800 mb-3">Identitas Anda sudah terverifikasi. Punya produk lain? Tidak perlu isi data diri ulang.</p>
                <form method="POST" action="{{ route('public.tambah-produk.form') }}">
                    @csrf
                    <input type="hidden" name="registration_number" value="{{ $submission->registration_number }}">
                    <input type="hidden" name="nik" value="{{ $submission->nik }}">
                    <button type="submit" class="w-full bg-teal-700 text-white py-2 rounded-md font-medium text-sm">+ Tambah Produk Lain</button>
                </form>
            </div>
        @endif

        <a href="{{ route('public.lacak') }}" class="text-blue-600 hover:underline text-sm">← Cek nomor lain</a>
    </div>
</body>
</html>
