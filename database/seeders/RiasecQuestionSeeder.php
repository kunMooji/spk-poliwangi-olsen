<?php

namespace Database\Seeders;

use App\Models\RiasecQuestion;
use Illuminate\Database\Seeder;

/**
 * 42 pernyataan kuesioner RIASEC, diadaptasi dari lembar "RIASEC Career Interest
 * Test" (skala Likert 1-5), dengan kalimat diperhalus agar lebih mudah dipahami
 * responden SMA/SMK.
 *
 * Urutan tampil mengikuti urutan asli lembar tes (sudah tercampur antar dimensi
 * secara alami) sehingga responden tidak menebak pola dan menjawab seragam per blok.
 */
class RiasecQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['dimension' => 'R', 'statement' => 'Saya senang mengutak-atik atau memperbaiki mobil dan kendaraan bermotor lainnya.'],
            ['dimension' => 'I', 'statement' => 'Saya suka memecahkan teka-teki atau puzzle yang menantang cara berpikir saya.'],
            ['dimension' => 'I', 'statement' => 'Saya cukup mampu menyelesaikan pekerjaan sendiri tanpa harus terus-menerus dibimbing orang lain.'],
            ['dimension' => 'S', 'statement' => 'Saya lebih senang mengerjakan sesuatu bersama tim daripada mengerjakannya sendirian.'],
            ['dimension' => 'E', 'statement' => 'Saya termasuk orang yang punya ambisi tinggi dan selalu menetapkan target-target pribadi untuk dicapai.'],
            ['dimension' => 'C', 'statement' => 'Saya senang merapikan dan mengatur barang-barang di sekitar saya, misalnya berkas, meja kerja, atau ruangan.'],
            ['dimension' => 'R', 'statement' => 'Saya senang membuat atau membangun sesuatu dengan tangan saya sendiri, seperti kerajinan atau konstruksi sederhana.'],
            ['dimension' => 'A', 'statement' => 'Saya senang membaca artikel, buku, atau berita seputar dunia seni dan musik.'],
            ['dimension' => 'C', 'statement' => 'Saya merasa lebih nyaman bekerja jika ada instruksi atau panduan yang jelas untuk diikuti.'],
            ['dimension' => 'E', 'statement' => 'Saya senang mencoba meyakinkan atau memengaruhi pendapat orang lain agar sejalan dengan pandangan saya.'],
            ['dimension' => 'I', 'statement' => 'Saya tertarik melakukan percobaan atau eksperimen untuk membuktikan sesuatu.'],
            ['dimension' => 'S', 'statement' => 'Saya senang mengajari atau melatih orang lain agar mereka bisa memahami atau menguasai sesuatu yang baru.'],
            ['dimension' => 'S', 'statement' => 'Saya senang membantu orang lain mencari jalan keluar ketika mereka menghadapi masalah.'],
            ['dimension' => 'R', 'statement' => 'Saya senang merawat dan mengurus hewan peliharaan atau ternak.'],
            ['dimension' => 'C', 'statement' => 'Saya tidak keberatan jika harus bekerja secara rutin selama 8 jam sehari di dalam kantor.'],
            ['dimension' => 'E', 'statement' => 'Saya senang menawarkan atau menjual suatu produk maupun jasa kepada orang lain.'],
            ['dimension' => 'A', 'statement' => 'Saya menikmati kegiatan menulis yang bersifat kreatif, seperti cerita, puisi, atau naskah.'],
            ['dimension' => 'I', 'statement' => 'Saya menikmati mempelajari ilmu pengetahuan alam atau sains pada umumnya.'],
            ['dimension' => 'E', 'statement' => 'Saya cukup sigap dan berani ketika diberi tanggung jawab baru yang belum pernah saya kerjakan sebelumnya.'],
            ['dimension' => 'S', 'statement' => 'Saya tertarik pada pekerjaan yang berhubungan dengan menyembuhkan atau merawat kesehatan orang lain.'],
            ['dimension' => 'I', 'statement' => 'Saya senang mencari tahu dan memahami bagaimana suatu alat atau sistem bisa bekerja.'],
            ['dimension' => 'R', 'statement' => 'Saya senang merakit, memasang, atau menyusun bagian-bagian menjadi satu barang yang utuh.'],
            ['dimension' => 'A', 'statement' => 'Saya menganggap diri saya sebagai orang yang kreatif dan suka menghasilkan ide-ide baru.'],
            ['dimension' => 'C', 'statement' => 'Saya cukup teliti dan selalu memperhatikan hal-hal kecil agar tidak ada yang terlewat.'],
            ['dimension' => 'C', 'statement' => 'Saya tidak keberatan mengerjakan pekerjaan administratif seperti mengarsipkan dokumen atau mengetik.'],
            ['dimension' => 'I', 'statement' => 'Saya senang menganalisis suatu masalah atau situasi secara mendalam sebelum mengambil kesimpulan.'],
            ['dimension' => 'A', 'statement' => 'Saya senang bermain alat musik atau bernyanyi.'],
            ['dimension' => 'S', 'statement' => 'Saya senang mempelajari dan mengenal budaya dari daerah atau negara lain.'],
            ['dimension' => 'E', 'statement' => 'Saya punya keinginan untuk memulai dan menjalankan usaha atau bisnis sendiri suatu saat nanti.'],
            ['dimension' => 'R', 'statement' => 'Saya senang memasak atau mengolah bahan makanan menjadi hidangan.'],
            ['dimension' => 'A', 'statement' => 'Saya senang tampil berakting dalam drama atau pementasan seni.'],
            ['dimension' => 'R', 'statement' => 'Saya termasuk orang yang praktis dan lebih suka cara-cara sederhana yang langsung bisa dikerjakan.'],
            ['dimension' => 'C', 'statement' => 'Saya senang bekerja dengan angka, data, atau grafik.'],
            ['dimension' => 'S', 'statement' => 'Saya senang berdiskusi atau bertukar pikiran tentang berbagai isu dan permasalahan yang sedang terjadi.'],
            ['dimension' => 'C', 'statement' => 'Saya cukup rapi dalam menyimpan dan mengelola catatan atau dokumen pekerjaan saya.'],
            ['dimension' => 'E', 'statement' => 'Saya senang mengambil peran sebagai pemimpin dalam suatu kelompok atau kegiatan.'],
            ['dimension' => 'R', 'statement' => 'Saya lebih senang bekerja di luar ruangan daripada terus-menerus berada di dalam ruangan.'],
            ['dimension' => 'C', 'statement' => 'Saya membayangkan diri saya bekerja di lingkungan kantor yang teratur dan terstruktur.'],
            ['dimension' => 'I', 'statement' => 'Saya cukup pandai dan menikmati pelajaran atau perhitungan matematika.'],
            ['dimension' => 'S', 'statement' => 'Saya senang membantu orang lain, apa pun bentuk bantuannya.'],
            ['dimension' => 'A', 'statement' => 'Saya senang menggambar atau membuat sketsa.'],
            ['dimension' => 'E', 'statement' => 'Saya cukup percaya diri dan senang berbicara atau memberikan pidato di depan banyak orang.'],
        ];

        $seeded = [];

        foreach ($items as $index => $item) {
            $seeded[] = $item['statement'];

            RiasecQuestion::query()->updateOrCreate(
                ['statement' => $item['statement']],
                [
                    'dimension' => $item['dimension'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }

        // Butir di luar daftar ini dinonaktifkan, bukan dihapus, supaya jawaban
        // pada sesi tes yang sudah selesai tetap utuh.
        RiasecQuestion::query()->whereNotIn('statement', $seeded)->update(['is_active' => false]);
    }
}
