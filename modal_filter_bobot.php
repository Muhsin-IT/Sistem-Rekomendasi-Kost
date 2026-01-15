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

                    <style>
                        /* Styling Slider dengan Warna Gradient */
                        .slider-bobot {
                            -webkit-appearance: none;
                            appearance: none;
                            height: 8px;
                            border-radius: 5px;
                            outline: none;
                            transition: background 0.1s;
                        }

                        /* Track bagian kiri (yang sudah digeser) */
                        .slider-bobot::-webkit-slider-runnable-track {
                            height: 8px;
                            border-radius: 5px;
                            background: transparent;
                        }

                        .slider-bobot::-moz-range-track {
                            height: 8px;
                            border-radius: 5px;
                            background: transparent;
                        }

                        /* Thumb (lingkaran geser) */
                        .slider-bobot::-webkit-slider-thumb {
                            -webkit-appearance: none;
                            appearance: none;
                            width: 20px;
                            height: 20px;
                            border-radius: 50%;
                            background: #0d6efd;
                            cursor: pointer;
                            border: 3px solid white;
                            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
                            transition: all 0.2s;
                            margin-top: -6px;
                        }

                        .slider-bobot::-moz-range-thumb {
                            width: 20px;
                            height: 20px;
                            border-radius: 50%;
                            background: #0d6efd;
                            cursor: pointer;
                            border: 3px solid white;
                            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
                            transition: all 0.2s;
                        }

                        /* Hover effect pada thumb */
                        .slider-bobot::-webkit-slider-thumb:hover {
                            background: #0b5ed7;
                            transform: scale(1.1);
                            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.4);
                        }

                        .slider-bobot::-moz-range-thumb:hover {
                            background: #0b5ed7;
                            transform: scale(1.1);
                            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.4);
                        }

                        /* Active/Dragging effect */
                        .slider-bobot:active::-webkit-slider-thumb {
                            background: #0a58ca;
                            transform: scale(1.15);
                            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
                        }

                        .slider-bobot:active::-moz-range-thumb {
                            background: #0a58ca;
                            transform: scale(1.15);
                            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
                        }

                        /* Progress bar (bagian kiri yang berwarna) untuk Firefox */
                        .slider-bobot::-moz-range-progress {
                            height: 8px;
                            border-radius: 5px 0 0 5px;
                            background: linear-gradient(90deg, #28a745 0%, #0d6efd 100%);
                        }
                    </style>

                    <div class="vstack gap-2">
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
                            <div class="bg-white px-3 py-2 rounded shadow-sm border container-slider">
                                <div class="d-flex justify-content-between mb-1 align-items-center">
                                    <div>
                                        <span class="fw-bold small"><?= $k['label'] ?></span>
                                        <span class="badge bg-<?= $k['color'] ?>-subtle text-<?= $k['color'] ?> border border-<?= $k['color'] ?> ms-1" style="font-size: 0.6rem;"><?= $k['type'] ?></span>
                                    </div>
                                    <span class="fw-bold text-primary small"><span id="txt_<?= $k['code'] ?>"><?= $val ?></span>%</span>
                                </div>
                                <input type="range" class="form-range slider-bobot"
                                    id="<?= $k['code'] ?>" name="<?= $k['code'] ?>"
                                    min="0" max="100" step="0.01"
                                    value="<?= $val ?>">
                            </div>
                        <?php endforeach; ?>

                        <div class="d-flex justify-content-between border-top pt-2 mt-1 px-2">
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

        // Fungsi untuk update warna background slider
        function updateSliderBackground(slider) {
            const value = parseFloat(slider.value);
            const percentage = value;
            // Gradient dari hijau ke biru untuk bagian yang terisi, abu-abu untuk sisanya
            slider.style.background = `linear-gradient(to right, #28a745 0%, #0d6efd ${percentage}%, #e9ecef ${percentage}%, #e9ecef 100%)`;
        }

        // Initialize semua slider background
        sliders.forEach(slider => {
            updateSliderBackground(slider);
        });

        sliders.forEach(slider => {
            slider.addEventListener('input', function(e) {
                const currentId = e.target.id;
                let currentVal = parseFloat(e.target.value);

                document.getElementById('txt_' + currentId).innerText = currentVal.toFixed(2);

                // Update background slider yang sedang digeser
                updateSliderBackground(e.target);

                let remainingQuota = 100 - currentVal;
                let otherSliders = sliders.filter(s => s.id !== currentId);
                let currentSumOthers = otherSliders.reduce((sum, s) => sum + parseFloat(s.value), 0);

                otherSliders.forEach(s => {
                    let oldVal = parseFloat(s.value);
                    let proportion = 0;

                    if (currentSumOthers > 0) {
                        proportion = oldVal / currentSumOthers;
                    } else {
                        proportion = 1 / otherSliders.length;
                    }

                    let newVal = remainingQuota * proportion;
                    s.value = newVal;
                    document.getElementById('txt_' + s.id).innerText = newVal.toFixed(2);

                    // Update background slider lainnya
                    updateSliderBackground(s);
                });

                totalDisplay.innerText = "100.00%";
            });
        });
    });
</script>