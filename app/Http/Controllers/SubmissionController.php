<?php

namespace App\Http\Controllers;

use App\Mail\SubmissionStatusChanged;
use App\Models\Submission;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SubmissionController extends Controller
{
    protected function authorizeAccess(Request $request, Submission $submission): void
    {
        $user = $request->user();

        if ($user->isAdminDesa() && $submission->village_id !== $user->village_id) {
            abort(403, 'Anda tidak memiliki akses ke submission desa lain.');
        }
    }

    public function show(Request $request, Submission $submission)
    {
        $this->authorizeAccess($request, $submission);

        return view('submissions.show', [
            'submission' => $submission,
        ]);
    }

    public function assignCategory(Request $request, Submission $submission)
    {
        $this->authorizeAccess($request, $submission);

        $request->validate(['category' => 'required|string']);

        $submission->update(['category' => $request->category]);

        return back()->with('status', 'Kategori berhasil disimpan.');
    }

    public function uploadSurveyPhoto(Request $request, Submission $submission, CloudinaryService $cloudinary)
    {
        $this->authorizeAccess($request, $submission);

        $request->validate(['survey_photo' => 'required|image|max:4096']);

        $path = $cloudinary->upload($request->file('survey_photo'), 'survey-photos');

        $submission->update([
            'survey_photo_url' => $path,
            'visited' => true,
        ]);

        return back()->with('status', 'Foto survey berhasil diupload. Status kunjungan otomatis diperbarui.');
    }

    public function saveVillageNotes(Request $request, Submission $submission)
    {
        $this->authorizeAccess($request, $submission);

        $request->validate(['village_notes' => 'nullable|string']);

        $submission->update(['village_notes' => $request->village_notes]);

        return back()->with('status', 'Catatan desa berhasil disimpan.');
    }

    public function saveDistrictNotes(Request $request, Submission $submission)
    {
        $this->authorizeAccess($request, $submission);

        $request->validate(['district_notes' => 'nullable|string']);

        $submission->update(['district_notes' => $request->district_notes]);

        return back()->with('status', 'Catatan kecamatan berhasil disimpan.');
    }

    /**
     * Bangun link wa.me dengan pesan otomatis ke nomor WhatsApp pelaku usaha.
     */
    protected function waLink(Submission $submission, string $message): ?string
    {
        if (! $submission->whatsapp_number) {
            return null;
        }

        $phone = preg_replace('/[^0-9]/', '', $submission->whatsapp_number);
        if (substr($phone, 0, 1) === '0') {
            $phone = '62'.substr($phone, 1);
        }

        return 'https://wa.me/'.$phone.'?text='.urlencode($message);
    }

    /**
     * Ubah nama status teknis jadi kalimat yang enak dibaca orang biasa.
     */
    protected function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'menunggu verifikasi desa',
            'verified_village' => 'sudah diverifikasi desa dan sedang menunggu persetujuan kecamatan',
            'approved' => 'sudah disetujui final oleh kecamatan',
            'rejected_by_village' => 'sudah ditolak oleh desa',
            'rejected_by_district' => 'sudah ditolak oleh kecamatan',
            default => 'dalam status "'.$status.'"',
        };
    }

    public function approveVillage(Request $request, Submission $submission)
    {
        $this->authorizeAccess($request, $submission);
        abort_unless($request->user()->isAdminDesa(), 403, 'Hanya admin desa yang berwenang.');

        if ($submission->status !== 'pending') {
            return back()->with('info', 'Pendaftaran ini '.$this->statusLabel($submission->status).', jadi tidak bisa diverifikasi ulang.');
        }

        $submission->update([
            'status' => 'verified_village',
            'verified_by_village_id' => $request->user()->id,
        ]);

        if ($submission->email) {
            Mail::to($submission->email)->send(new SubmissionStatusChanged($submission));
        }

        $waUrl = $this->waLink($submission, "Halo {$submission->full_name}, pendaftaran usaha \"{$submission->product_name}\" (No. Reg {$submission->registration_number}) Anda telah diverifikasi oleh Desa dan diteruskan ke Kecamatan untuk persetujuan akhir. Mohon ditunggu ya.");

        if ($waUrl) {
            return redirect()->away($waUrl);
        }

        return back()->with('status', 'Submission diverifikasi desa, diteruskan ke kecamatan.');
    }

    public function rejectVillage(Request $request, Submission $submission)
    {
        $this->authorizeAccess($request, $submission);
        abort_unless($request->user()->isAdminDesa(), 403, 'Hanya admin desa yang berwenang.');

        if ($submission->status !== 'pending') {
            return back()->with('info', 'Pendaftaran ini '.$this->statusLabel($submission->status).', jadi tidak bisa diproses ulang.');
        }

        $request->validate(['rejection_reason' => 'required|string']);
        $submission->update([
            'status' => 'rejected_by_village',
            'rejection_reason' => $request->rejection_reason,
            'verified_by_village_id' => $request->user()->id,
        ]);

        if ($submission->email) {
            Mail::to($submission->email)->send(new SubmissionStatusChanged($submission));
        }

        return back()->with('status', 'Submission ditolak oleh desa.');
    }

    public function approveDistrict(Request $request, Submission $submission)
    {
        abort_unless($request->user()->isAdminKecamatan(), 403, 'Hanya admin kecamatan yang berwenang.');

        if ($submission->status !== 'verified_village') {
            return back()->with('info', 'Pendaftaran ini '.$this->statusLabel($submission->status).', jadi belum bisa disetujui kecamatan.');
        }

        $submission->update([
            'status' => 'approved',
            'verified_by_district_id' => $request->user()->id,
        ]);

        if ($submission->email) {
            Mail::to($submission->email)->send(new SubmissionStatusChanged($submission));
        }

        $waUrl = $this->waLink($submission, "Halo {$submission->full_name}, selamat! Pendaftaran usaha \"{$submission->product_name}\" (No. Reg {$submission->registration_number}) Anda telah berhasil DISETUJUI oleh Kecamatan Windusari. Mohon ditunggu, tim kami akan menghubungi Anda kembali untuk proses selanjutnya. Terima kasih.");

        if ($waUrl) {
            return redirect()->away($waUrl);
        }

        return back()->with('status', 'Submission disetujui final oleh kecamatan.');
    }

    public function rejectDistrict(Request $request, Submission $submission)
    {
        abort_unless($request->user()->isAdminKecamatan(), 403, 'Hanya admin kecamatan yang berwenang.');

        if ($submission->status !== 'verified_village') {
            return back()->with('info', 'Pendaftaran ini '.$this->statusLabel($submission->status).', jadi belum bisa diproses kecamatan.');
        }

        $request->validate(['rejection_reason' => 'required|string']);
        $submission->update([
            'status' => 'rejected_by_district',
            'rejection_reason' => $request->rejection_reason,
            'verified_by_district_id' => $request->user()->id,
        ]);

        if ($submission->email) {
            Mail::to($submission->email)->send(new SubmissionStatusChanged($submission));
        }

        return back()->with('status', 'Submission ditolak oleh kecamatan.');
    }

    public function archive(Request $request, Submission $submission)
    {
        abort_unless($request->user()->isAdminKecamatan(), 403, 'Hanya admin kecamatan yang berwenang menghapus/mengarsipkan data.');

        $submission->delete(); // soft delete, data tidak hilang permanen

        return back()->with('status', 'Pengajuan berhasil diarsipkan.');
    }

    public function editIdentity(Request $request, Submission $submission)
    {
        $this->authorizeAccess($request, $submission);
        abort_unless($request->user()->isAdminDesa(), 403, 'Hanya admin desa yang berwenang mengedit data ini.');

        if ($submission->status !== 'pending') {
            return back()->with('info', 'Data sudah diverifikasi, tidak bisa diedit lagi. Hubungi Kecamatan jika ada kesalahan data.');
        }

        return view('submissions.edit-identitas', ['submission' => $submission]);
    }

    public function updateIdentity(Request $request, Submission $submission)
    {
        $this->authorizeAccess($request, $submission);
        abort_unless($request->user()->isAdminDesa(), 403, 'Hanya admin desa yang berwenang mengedit data ini.');

        if ($submission->status !== 'pending') {
            return back()->with('info', 'Data sudah diverifikasi, tidak bisa diedit lagi.');
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'address' => 'required|string',
        ]);

        $submission->update($validated);

        return redirect()->route('submissions.show', $submission)->with('status', 'Data berhasil diperbarui.');
    }
}
