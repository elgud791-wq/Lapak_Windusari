<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Models\Village;
use App\Services\CloudinaryService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicSubmissionController extends Controller
{
    public function create()
    {
        $villages = Village::orderBy('name')->get();

        // Cegah browser menampilkan form basi (yang sudah terisi) saat user klik tombol Back
        return response()
            ->view('public.daftar', ['villages' => $villages])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function store(Request $request, CloudinaryService $cloudinary)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'nik' => 'required|regex:/^[0-9]{16}$/',
            'address' => 'required|string',
            'email' => 'nullable|email',
            'whatsapp_number' => ['required', 'regex:/^(\+62|62|0)[0-9]{8,13}$/'],
            'village_id' => 'required|exists:villages,id',
            'product_name' => 'required|string|max:255',
            'product_description' => 'nullable|string',
            'product_photo' => 'nullable|image|max:4096',
            'nib_file' => 'nullable|file|max:4096',
            'halal_cert_file' => 'nullable|file|max:4096',
        ], [
        ], [
            'nik.regex' => 'NIK harus berupa 16 digit angka.',
            'whatsapp_number.required' => 'Nomor WhatsApp wajib diisi, supaya calon pembeli bisa menghubungi Anda lewat katalog produk.',
            'whatsapp_number.regex' => 'Format nomor WhatsApp tidak valid. Contoh: 081234567890 atau 6281234567890.',
        ]);

        // Tolak tegas kalau NIK + nama produk yang sama sudah pernah didaftarkan
        // sebelumnya (dan belum ditolak). Ini mencegah data ganda akibat submit
        // ulang (klik Back lalu Kirim lagi, double-click, dsb) dengan pesan error
        // yang jelas ke pengguna, bukan diam-diam redirect ke data lama.
        $duplicate = Submission::where('nik', $validated['nik'])
            ->where('product_name', $validated['product_name'])
            ->whereNotIn('status', ['rejected_by_village', 'rejected_by_district'])
            ->first();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'nik' => "Produk \"{$validated['product_name']}\" dengan NIK ini sudah pernah didaftarkan sebelumnya (No. Registrasi: {$duplicate->registration_number}, status: {$duplicate->status}). Silakan cek status pendaftaran Anda, atau gunakan NIK/nama produk lain jika ini pendaftaran yang berbeda.",
            ])->redirectTo(url()->previous());
        }

        $validated['registration_number'] = Submission::generateRegistrationNumber();
        $validated['status'] = 'pending';

        if ($request->hasFile('product_photo')) {
            $validated['product_photo_url'] = $cloudinary->upload($request->file('product_photo'), 'product-photos');
        }
        if ($request->hasFile('nib_file')) {
            $validated['nib_url'] = $cloudinary->upload($request->file('nib_file'), 'nib-files');
        }
        if ($request->hasFile('halal_cert_file')) {
            $validated['halal_cert_url'] = $cloudinary->upload($request->file('halal_cert_file'), 'halal-certs');
        }

        // Jaring pengaman terakhir di level database: kalau dua request submit
        // benar-benar bersamaan (race condition) lolos dari pengecekan di atas,
        // constraint unique di database akan menolak salah satunya di sini.
        try {
            $submission = Submission::create($validated);
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                throw ValidationException::withMessages([
                    'nik' => 'Produk ini dengan NIK yang sama baru saja terdaftar. Silakan cek status pendaftaran Anda.',
                ])->redirectTo(url()->previous());
            }
            throw $e;
        }

        return redirect()->route('public.daftar.success', $submission->registration_number);
    }

    public function success($registrationNumber)
    {
        $submission = Submission::where('registration_number', $registrationNumber)->firstOrFail();

        return view('public.daftar-sukses', ['submission' => $submission]);
    }

    public function addProductForm(Request $request)
    {
        $request->validate([
            'registration_number' => 'required|string',
            'nik' => 'required|regex:/^[0-9]{16}$/',
        ]);

        $existing = Submission::where('registration_number', trim($request->registration_number))
            ->where('nik', trim($request->nik))
            ->first();

        if (! $existing) {
            return back()->withErrors(['registration_number' => 'Data tidak ditemukan.']);
        }

        if (! in_array($existing->status, ['verified_village', 'approved'])) {
            return back()->withErrors(['registration_number' => 'Identitas Anda belum diverifikasi Desa, jadi belum bisa menambah produk baru. Mohon tunggu verifikasi selesai terlebih dahulu.']);
        }

        return view('public.tambah-produk', ['existing' => $existing]);
    }

    public function addProductStore(Request $request, CloudinaryService $cloudinary)
    {
        $request->validate([
            'registration_number' => 'required|string',
            'nik' => 'required|regex:/^[0-9]{16}$/',
            'product_name' => 'required|string|max:255',
            'product_description' => 'nullable|string',
            'product_photo' => 'nullable|image|max:4096',
            'nib_file' => 'nullable|file|max:4096',
        ]);

        $existing = Submission::where('registration_number', trim($request->registration_number))
            ->where('nik', trim($request->nik))
            ->whereIn('status', ['verified_village', 'approved'])
            ->first();

        if (! $existing) {
            return back()->withErrors(['registration_number' => 'Data tidak ditemukan atau identitas belum terverifikasi.']);
        }

        $duplicate = Submission::where('nik', $existing->nik)
            ->where('product_name', $request->product_name)
            ->whereNotIn('status', ['rejected_by_village', 'rejected_by_district'])
            ->first();

        if ($duplicate) {
            return back()->withErrors(['product_name' => "Produk \"{$request->product_name}\" dengan NIK ini sudah pernah didaftarkan (No. Reg {$duplicate->registration_number})."]);
        }

        $data = [
            'full_name' => $existing->full_name,
            'nik' => $existing->nik,
            'address' => $existing->address,
            'email' => $existing->email,
            'whatsapp_number' => $existing->whatsapp_number,
            'village_id' => $existing->village_id,
            'product_name' => $request->product_name,
            'product_description' => $request->product_description,
            'registration_number' => Submission::generateRegistrationNumber(),
            // Identitas (NIK, alamat, dll) sudah pernah diverifikasi Desa di
            // pengajuan sebelumnya, jadi produk tambahan langsung berstatus
            // "verified_village" — lompat antre langsung ke Kecamatan, tidak
            // perlu diverifikasi ulang oleh Desa.
            'status' => 'verified_village',
            'verified_by_village_id' => $existing->verified_by_village_id,
        ];

        if ($request->hasFile('product_photo')) {
            $data['product_photo_url'] = $cloudinary->upload($request->file('product_photo'), 'product-photos');
        }
        if ($request->hasFile('nib_file')) {
            $data['nib_url'] = $cloudinary->upload($request->file('nib_file'), 'nib-files');
        }

        $submission = Submission::create($data);

        return redirect()->route('public.daftar.success', $submission->registration_number)
            ->with('status', 'Produk baru berhasil ditambahkan dengan identitas yang sama.');
    }

    public function trackForm()
    {
        return view('public.lacak');
    }

    public function trackResult(Request $request)
    {
        $request->validate([
            'registration_number' => 'required|string',
            'nik' => 'required|regex:/^[0-9]{16}$/',
        ], [
            'nik.regex' => 'NIK harus berupa 16 digit angka.',
        ]);

        $submission = Submission::where('registration_number', trim($request->registration_number))
            ->where('nik', trim($request->nik))
            ->first();

        if (! $submission) {
            return back()->withErrors([
                'registration_number' => 'Data tidak ditemukan. Periksa kembali Nomor Registrasi dan NIK Anda.',
            ]);
        }

        return view('public.lacak-hasil', ['submission' => $submission]);
    }
}
