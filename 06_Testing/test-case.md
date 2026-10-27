# Test Case - Lapak Verification Seller Windusari

## Unit Testing (Unit 9)

| No  | Method / Fitur               | Input                       | Expected Result   | Actual Result | Status |
| --- | ---------------------------- | --------------------------- | ----------------- | ------------- | ------ |
| 1   | generateRegistrationNumber() | Database kosong             | LPW-TAHUN-001     | LPW-TAHUN-001 | Pass   |
| 2   | generateRegistrationNumber() | Format nomor                | Pola LPW-YYYY-XXX | Sesuai        | Pass   |
| 3   | isAdminDesa()                | User role = admin_desa      | true              | true          | Pass   |
| 4   | isAdminKecamatan()           | User role = admin_kecamatan | true              | true          | Pass   |
| 5   | isAdminDesa()                | User role = admin_kecamatan | false             | false         | Pass   |
| 6   | Pengecekan role              | User tanpa role             | false / aman      | Sesuai        | Pass   |

## Keterangan

- Method `generateRegistrationNumber()` ada di Model `Submission`
- Method `isAdminDesa()` dan `isAdminKecamatan()` ada di Model `User`
- Pengujian dilakukan untuk memastikan fungsi terkecil berjalan sesuai spesifikasi
