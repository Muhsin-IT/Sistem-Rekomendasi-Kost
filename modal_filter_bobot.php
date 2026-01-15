<div class="modal fade" id="modalFilterBobot" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-sliders"></i> Atur Prioritas (Bobot SAW)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="search.php" method="GET" id="formBobot">
                <div class="modal-body bg-light">
                    <input type="hidden" name="keyword" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">

                    <p class="small text-muted mb-3 text-center">
                        Geser slider untuk mengatur prioritas. <br>
                        Total bobot akan otomatis seimbang (100%).
                    </p>

                    <div class="vstack gap-3">
                        <?php
                        // Konfigurasi Kriteria & Default Value
                        // Kita ambil dari URL jika ada, jika tidak pakai Default dari soal
                        $kriteria = [
                            ['code' => 'w1', 'label' => 'Harga (C1)', 'type' => 'Cost', 'color' => 'danger', 'def' => 16.88],
                            ['code' => 'w2', 'label' => 'Jarak (C2)', 'type' => 'Cost', 'color' => 'danger', 'def' => 15.58],
                            ['code' => 'w3', 'label' => 'Fasilitas (C3)', 'type' => 'Benefit', 'color' => 'success', 'def' => 18.18],
                            ['code' => 'w4', 'label' => 'Peraturan (C4)', 'type' => 'Benefit', 'color' => 'success', 'def' => 16.88],
                            ['code' => 'w5', 'label' => 'Akurasi (C5)', 'type' => 'Benefit', 'color' => 'success', 'def' => 15.59],
                            ['code' => 'w6', 'label' => 'Ulasan (C6)', 'type' => 'Benefit', 'color' => 'success', 'def' => 16.88],
                        ];
                        ?>

                        <?php foreach ($kriteria as $k):
                            $val = isset($_GET[$k['code']]) ? $_GET[$k['code']] : $k['def'];
                        ?>
                            <div class="bg-white p-2 rounded shadow-sm border container-slider">
                                <div class="d-flex justify-content-between mb-1 align-items-center">
                                    <div>
                                        <span class="fw-bold small"><?= $k['label'] ?></span>
                                        <span class="badge bg-<?= $k['color'] ?>-subtle text-<?= $k['color'] ?> border border-<?= $k['color'] ?>" style="font-size: 0.6rem;"><?= $k['type'] ?></span>
                                    </div>
                                    <span class="fw-bold text-primary small"><span id="txt_<?= $k['code'] ?>"><?= $val ?></span>%</span>
                                </div>
                                <input type="range" class="form-range slider-bobot"
                                    id="<?= $k['code'] ?>" name="<?= $k['code'] ?>"
                                    min="0" max="100" step="0.01"
                                    value="<?= $val ?>">
                            </div>
                        <?php endforeach; ?>

                        <div class="d-flex justify-content-between border-top pt-2 mt-2">
                            <span class="fw-bold text-muted small">Total Bobot:</span>
                            <span class="fw-bold text-success small" id="totalBobot">100.00%</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <a href="search.php?keyword=<?= htmlspecialchars($_GET['keyword'] ?? '') ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Default
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-check-lg"></i> Terapkan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sliders = Array.from(document.querySelectorAll('.slider-bobot'));
        const totalDisplay = document.getElementById('totalBobot');

        sliders.forEach(slider => {
            // Event saat digeser
            slider.addEventListener('input', function(e) {
                const currentId = e.target.id;
                let currentVal = parseFloat(e.target.value);

                // Update teks angka sendiri
                document.getElementById('txt_' + currentId).innerText = currentVal.toFixed(2);

                // === LOGIKA EQUALIZER ===
                // 1. Hitung sisa kuota (100 - nilai slider yang sedang digeser)
                let remainingQuota = 100 - currentVal;

                // 2. Cari slider LAINNYA
                let otherSliders = sliders.filter(s => s.id !== currentId);

                // 3. Hitung total nilai slider LAINNYA saat ini
                let currentSumOthers = otherSliders.reduce((sum, s) => sum + parseFloat(s.value), 0);

                // 4. Distribusikan sisa kuota secara proporsional ke slider lain
                otherSliders.forEach(s => {
                    let oldVal = parseFloat(s.value);
                    let proportion = 0;

                    if (currentSumOthers > 0) {
                        proportion = oldVal / currentSumOthers;
                    } else {
                        // Jika semua slider lain 0, bagi rata
                        proportion = 1 / otherSliders.length;
                    }

                    let newVal = remainingQuota * proportion;

                    // Update nilai slider lain
                    s.value = newVal;
                    document.getElementById('txt_' + s.id).innerText = newVal.toFixed(2);
                });

                // Update Total Display (Harusnya selalu mendekati 100)
                totalDisplay.innerText = "100.00%";
            });
        });
    });
</script>