<!-- templates/layout/phase_modal.php -->
<div id="phaseModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-2 flex items-center">
                <i data-lucide="calendar" class="w-5 h-5 mr-2 text-secondary"></i>
                <span id="phaseModalTitle">Phase wählen</span>
            </h3>
            <p class="text-gray-500 text-sm mb-6" id="phaseModalLabel">Wähle das Datum für diese Phase</p>

            <form id="phaseForm" method="POST" action="">
                <input type="hidden" name="status" id="phaseModalStatus">
                <input type="hidden" name="phase" id="phaseInput">

                <!-- Monat/Jahr Auswahl -->
                <div id="monthYearPicker" class="hidden">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Monat</label>
                            <select name="phase_month" id="phaseMonth"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-secondary focus:border-secondary outline-none">
                                <option value="01">Januar</option>
                                <option value="02">Februar</option>
                                <option value="03">März</option>
                                <option value="04">April</option>
                                <option value="05">Mai</option>
                                <option value="06">Juni</option>
                                <option value="07">Juli</option>
                                <option value="08">August</option>
                                <option value="09">September</option>
                                <option value="10">Oktober</option>
                                <option value="11">November</option>
                                <option value="12">Dezember</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Jahr</label>
                            <select name="phase_year" id="phaseYear"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-secondary focus:border-secondary outline-none">
                                <?php
                                $currentYear = (int) date('Y');
                                for ($y = $currentYear; $y <= $currentYear + 3; $y++):
                                    ?>
                                    <option value="<?= $y ?>">
                                        <?= $y ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Vollständiges Datum -->
                <div id="fullDatePicker" class="hidden mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Datum</label>
                    <input type="date" name="phase_full_date" id="phaseFullDate"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-secondary focus:border-secondary outline-none"
                        value="<?= date('Y-m-d') ?>">
                </div>

                <!-- Hidden field für finales Datum -->
                <input type="hidden" name="phase_date" id="phaseDateFinal">

                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="closePhaseModal()"
                        class="flex-1 px-4 py-3 rounded-xl border border-gray-200 text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                        Abbrechen
                    </button>
                    <button type="submit"
                        class="flex-1 px-4 py-3 rounded-xl bg-secondary text-white font-medium hover:bg-secondary/90 transition-colors shadow-lg">
                        Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="assets/js/phase-system.js"></script>