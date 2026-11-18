<div class="max-w-4xl mx-auto space-y-6" x-data="quizApp()">
    <!-- Header Kuis & Timer Bar -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4 sticky top-4 z-30">
        <div>
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Kuis Pembelajaran</span>
            <h1 class="text-xl font-extrabold text-gray-800 line-clamp-1"><?= htmlspecialchars($kuis['judul']) ?></h1>
        </div>

        <div class="flex items-center gap-4">
            <!-- Timer Countdown -->
            <div class="flex items-center gap-2 px-4 py-2 bg-rose-50 border border-rose-200 text-rose-700 rounded-2xl font-black text-sm">
                <i class="fas fa-stopwatch animate-pulse"></i>
                <span x-text="formatTime(timeLeft)"></span>
            </div>

            <!-- Tombol Selesai & Kumpulkan -->
            <button type="button" @click="confirmSubmit()"
                    class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-2xl text-xs transition shadow-md flex items-center gap-2">
                <i class="fas fa-paper-plane"></i> Kumpulkan Jawaban
            </button>
        </div>
    </div>

    <!-- Layout: Navigasi Nomor & Soal Aktif -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Kolom Navigasi Nomor Soal -->
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
            <h3 class="font-extrabold text-gray-800 text-xs uppercase tracking-wider">Nomor Soal</h3>
            <div class="grid grid-cols-4 gap-2">
                <template x-for="(s, idx) in soalList" :key="s.id">
                    <button type="button" @click="currentIndex = idx"
                            class="w-10 h-10 rounded-xl font-bold text-xs flex items-center justify-center transition"
                            :class="{
                                'bg-primary-600 text-white shadow-md ring-2 ring-primary-300': currentIndex === idx,
                                'bg-emerald-100 text-emerald-800': jawaban[s.id] && currentIndex !== idx,
                                'bg-gray-100 text-gray-600 hover:bg-gray-200': !jawaban[s.id] && currentIndex !== idx
                            }"
                            x-text="idx + 1">
                    </button>
                </template>
            </div>

            <div class="pt-4 border-t border-gray-100 text-[11px] text-gray-400 space-y-1">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded bg-primary-600"></span> Soal Sedang Dibuka
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded bg-emerald-100"></span> Sudah Dijawab
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded bg-gray-100"></span> Belum Dijawab
                </div>
            </div>
        </div>

        <!-- Kolom Soal Aktif -->
        <div class="lg:col-span-3 space-y-6">
            <template x-if="currentSoal">
                <div class="bg-white rounded-3xl p-6 md:p-8 border border-gray-100 shadow-sm space-y-6">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                        <span class="px-3 py-1 bg-indigo-50 text-indigo-700 font-extrabold text-xs rounded-xl"
                              x-text="'Pertanyaan ' + (currentIndex + 1) + ' dari ' + soalList.length"></span>
                        <span class="text-xs text-gray-400 font-semibold">Bobot: 25 Poin</span>
                    </div>

                    <!-- Pertanyaan -->
                    <div class="text-gray-800 font-bold text-base md:text-lg leading-relaxed"
                         x-text="currentSoal.pertanyaan"></div>

                    <!-- Pilihan Jawaban A, B, C, D -->
                    <div class="space-y-3">
                        <template x-for="opt in ['A', 'B', 'C', 'D']" :key="opt">
                            <label class="p-4 rounded-2xl border-2 cursor-pointer flex items-center justify-between transition"
                                   :class="jawaban[currentSoal.id] === opt ? 'border-primary-600 bg-primary-50/50 text-primary-900 shadow-sm' : 'border-gray-100 hover:border-gray-200 text-gray-700'">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-8 h-8 rounded-xl font-black text-xs flex items-center justify-center transition"
                                         :class="jawaban[currentSoal.id] === opt ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600'"
                                         x-text="opt"></div>
                                    <span class="text-sm font-semibold" x-text="getOptionText(currentSoal, opt)"></span>
                                </div>
                                <input type="radio" :name="'soal_' + currentSoal.id" :value="opt"
                                       @change="pilihJawaban(currentSoal.id, opt)"
                                       :checked="jawaban[currentSoal.id] === opt"
                                       class="w-4 h-4 text-primary-600 focus:ring-primary-500">
                            </label>
                        </template>
                    </div>

                    <!-- Navigasi Prev/Next -->
                    <div class="pt-6 border-t border-gray-100 flex items-center justify-between">
                        <button type="button" @click="prevSoal()" :disabled="currentIndex === 0"
                                class="px-5 py-2.5 rounded-2xl text-xs font-bold transition"
                                :class="currentIndex === 0 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'">
                            <i class="fas fa-arrow-left mr-1"></i> Soal Sebelumnya
                        </button>

                        <button type="button" @click="nextSoal()" x-show="currentIndex < soalList.length - 1"
                                class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-2xl text-xs font-bold transition shadow-md">
                            Soal Berikutnya <i class="fas fa-arrow-right ml-1"></i>
                        </button>

                        <button type="button" @click="confirmSubmit()" x-show="currentIndex === soalList.length - 1"
                                class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-xs font-bold transition shadow-md">
                            Selesai & Kumpulkan <i class="fas fa-check ml-1"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function quizApp() {
    return {
        kuisId: <?= (int)$kuis['id'] ?>,
        csrfToken: '<?= $csrf_token ?>',
        durasiMenit: <?= (int)$kuis['durasi_menit'] ?>,
        soalList: <?= json_encode($soal) ?>,
        currentIndex: 0,
        jawaban: {},
        timeLeft: <?= (int)$kuis['durasi_menit'] * 60 ?>,
        timerInterval: null,
        isSubmitting: false,

        init() {
            this.startTimer();
            // Peringatan jika reload saat kuis
            window.addEventListener('beforeunload', (e) => {
                if (!this.isSubmitting) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
        },

        get currentSoal() {
            return this.soalList[this.currentIndex] || null;
        },

        getOptionText(soal, opt) {
            return soal['opsi_' + opt.toLowerCase()] || '';
        },

        pilihJawaban(soalId, opt) {
            this.jawaban[soalId] = opt;
        },

        nextSoal() {
            if (this.currentIndex < this.soalList.length - 1) {
                this.currentIndex++;
            }
        },

        prevSoal() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
            }
        },

        startTimer() {
            this.timerInterval = setInterval(() => {
                if (this.timeLeft > 0) {
                    this.timeLeft--;
                } else {
                    clearInterval(this.timerInterval);
                    this.autoSubmit();
                }
            }, 1000);
        },

        formatTime(seconds) {
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        },

        confirmSubmit() {
            const dijawab = Object.keys(this.jawaban).length;
            const total = this.soalList.length;

            Swal.fire({
                title: 'Kumpulkan Jawaban?',
                text: `Kamu telah menjawab ${dijawab} dari ${total} soal. Yakin ingin menyelesaikan kuis ini?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Selesaikan!',
                cancelButtonText: 'Periksa Lagi',
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#94a3b8',
                borderRadius: '1.25rem',
            }).then((res) => {
                if (res.isConfirmed) {
                    this.submitQuiz();
                }
            });
        },

        autoSubmit() {
            Swal.fire({
                title: 'Waktu Habis!',
                text: 'Waktu pengerjaan kuis telah habis. Jawabanmu sedang dikumpulkan otomatis...',
                icon: 'info',
                timer: 2000,
                showConfirmButton: false,
                borderRadius: '1.25rem',
            }).then(() => {
                this.submitQuiz();
            });
        },

        submitQuiz() {
            this.isSubmitting = true;
            clearInterval(this.timerInterval);

            Swal.fire({
                title: 'Memproses Nilai...',
                text: 'Sistem sedang mengkalkulasi skormu dan reward XP...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch('<?= base_url('siswa/kuisSubmit/' . $kuis['id']) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    csrf_token: this.csrfToken,
                    jawaban: this.jawaban,
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' || data.success) {
                    window.location.href = data.data.redirect;
                } else {
                    Swal.fire({ icon: 'error', title: 'Terjadi Kesalahan', text: data.message });
                }
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'Koneksi Gagal', text: 'Gagal mengirim jawaban.' });
            });
        }
    }
}
</script>
