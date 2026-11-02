# Checklist test end-to-end Phase 7

Lakukan setelah paket dipasang ke staging/hosting dan konfigurasi rahasia sudah benar. Jangan masukkan token atau secret ke laporan yang dibagikan.

## A. Pemeriksaan awal

- [ ] `api/whatsapp/health.php` mengembalikan `ready_for_inbound_test: true` setelah header/secret yang tepat disediakan.
- [ ] `base_url` mengarah ke domain yang aktif.
- [ ] Token Fonnte dan `whatsapp_webhook_secret` sudah diisi dari hosting.
- [ ] Pada Device → Edit, Auto Read aktif dan Inbox aktif.
- [ ] Webhook ditetapkan ke `https://DOMAIN-AKTUAL/api/whatsapp/webhook.php?key=SECRET_WEBHOOK_ANDA`.
- [ ] Pastikan hanya satu mekanisme balasan aktif untuk pesan yang sama; custom webhook menangani balasan saat webhook digunakan.

## B. Uji pesan

1. Kirim `Ada ban FDR untuk Vario?` — bot harus meminta varian motor sebelum merekomendasikan produk.
2. Balas `125` — bot harus menggunakan klarifikasi terakhir dan menampilkan rekomendasi yang berasal dari peta katalog.
3. Balas `nomor 2` — bot harus mengirim halaman produk aktif nomor 2 dari balasan assistant terbaru.
4. Kirim pesan yang sama dua kali dengan jeda singkat — periksa apakah event dengan ID/inbox ID yang sama tidak membalas dua kali.
5. Kirim `Cari lampu OSRAM` — hasil seharusnya dari katalog OSRAM.
6. Kirim gambar tanpa caption — bot seharusnya meminta keterangan motor/produk, tidak mengarang analisis gambar.
7. Kirim lokasi tanpa teks — bot meminta customer menulis tipe motor/nama produk, tidak menampilkan koordinat lokasi.
8. Putuskan sementara token Fonnte di staging untuk menguji kegagalan, lalu pulihkan. Pastikan event failed/retry tercatat dan pesan tidak terkirim berkali-kali.

## C. Verifikasi log dan hasil

- [ ] Balasan WhatsApp hanya muncul satu kali untuk event yang sama.
- [ ] Semua link mengarah ke domain dan slug produk yang aktif.
- [ ] Pesan bot tidak mengklaim transaksi/order telah tercatat.
- [ ] Tidak ada token, secret, URL attachment pribadi, atau koordinat mentah pada log aplikasi.
- [ ] Catat waktu, skenario, HTTP status dari webhook, dan status di Fonnte Message History tanpa membagikan nomor customer.

Jika terjadi kegagalan, simpan response yang sudah disamarkan dari health check, status HTTP endpoint, timestamp, dan status event. Jangan menyalin token atau URL webhook penuh yang mengandung secret.
