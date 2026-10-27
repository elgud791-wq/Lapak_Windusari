<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Pengajuan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen py-10">
    <div class="max-w-lg mx-auto bg-white shadow-sm rounded-lg p-8">
        <h1 class="text-xl font-bold mb-1">Edit Data Pemohon</h1>
        <p class="text-sm text-gray-500 mb-6">{{ $submission->registration_number }} — hanya bisa diedit sebelum diverifikasi.</p>

        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('submissions.update-identitas', $submission) }}" class="space-y-4">
            @csrf
            @method('PATCH')
            <div>
                <label class="block text-sm font-medium mb-1">Nama Lengkap</label>
                <input type="text" name="full_name" value="{{ old('full_name', $submission->full_name) }}" required class="w-full border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Alamat</label>
                <textarea name="address" required class="w-full border-gray-300 rounded-md">{{ old('address', $submission->address) }}</textarea>
            </div>
            <p class="text-xs text-gray-400">NIK tidak dapat diubah untuk menjaga integritas identitas ({{ $submission->nik }}).</p>
            <button type="submit" class="w-full bg-teal-700 text-white py-2 rounded-md font-medium">Simpan Perubahan</button>
        </form>

        <a href="{{ route('submissions.show', $submission) }}" class="block text-center text-sm text-gray-500 mt-4 hover:underline">← Batal, kembali ke detail</a>
    </div>
</body>
</html>