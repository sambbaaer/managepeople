<!-- templates/contacts/_workflow_ui.php -->
<div class="glass-card p-6 border-t-4 border-secondary shadow-sm">
    <h3 class="font-bold text-gray-800 mb-6 flex items-center font-display">
        <i data-lucide="git-merge" class="w-5 h-5 mr-2 text-secondary"></i> Status & Phase
    </h3>

    <!-- Stepper -->
    <div class="flex justify-between items-center mb-8 relative">
        <!-- Connecting Line -->
        <div class="absolute top-1/2 left-0 w-full h-0.5 bg-gray-200 -z-10 transform -translate-y-1/2">
        </div>

        <?php
        $mainStatuses = Contact::getMainStatuses();
        $currentStatus = $contact['status'] ?? 'Offen';
        foreach ($mainStatuses as $index => $step):
            $isCompleted = array_search($currentStatus, $mainStatuses) > $index;
            $isCurrent = $currentStatus === $step;
            ?>
            <?php if (!Auth::isMentor()): ?>
                <form method="POST" action="index.php?page=contact_update&id=<?= $contact['id'] ?>"
                    class="relative z-10 workflow-status-form">
                    <input type="hidden" name="status" value="<?= $step ?>">
                    <input type="hidden" name="sub_status" value="">
                    <button type="submit" class="flex flex-col items-center group cursor-pointer focus:outline-none"
                        title="Status ändern zu <?= $step ?>">
                        <div
                            class="w-8 h-8 rounded-full border-4 flex items-center justify-center text-xs font-bold transition-all bg-white
                        <?= $isCurrent ? 'border-secondary text-secondary scale-110 shadow-md' : ($isCompleted ? 'border-secondary bg-secondary text-white' : 'border-gray-200 text-gray-300 group-hover:border-gray-300') ?>">
                            <?php if ($isCompleted): ?>
                                <i data-lucide="check" class="w-4 h-4"></i>
                            <?php else: ?>
                                <?= $index + 1 ?>
                            <?php endif; ?>
                        </div>
                        <span
                            class="text-[10px] font-bold uppercase mt-2 transition-colors <?= $isCurrent ? 'text-secondary' : 'text-gray-400 group-hover:text-gray-600' ?>">
                            <?= $step ?>
                        </span>
                    </button>
                </form>
            <?php else: ?>
                <div class="relative z-10 flex flex-col items-center">
                    <div
                        class="w-8 h-8 rounded-full border-4 flex items-center justify-center text-xs font-bold bg-white
                    <?= $isCurrent ? 'border-secondary text-secondary scale-110 shadow-md' : ($isCompleted ? 'border-secondary bg-secondary text-white' : 'border-gray-200 text-gray-300') ?>">
                        <?php if ($isCompleted): ?>
                            <i data-lucide="check" class="w-4 h-4"></i>
                        <?php else: ?>
                            <?= $index + 1 ?>
                        <?php endif; ?>
                    </div>
                    <span class="text-[10px] font-bold uppercase mt-2 <?= $isCurrent ? 'text-secondary' : 'text-gray-400' ?>">
                        <?= $step ?>
                    </span>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- PHASEN-SYSTEM -->
    <div class="bg-gray-50 rounded-xl p-4 mb-4">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Phase im Prozess</p>

        <?php
        $availablePhases = PhaseConfig::getPhasesForStatus($currentStatus);
        $currentPhase = $contact['phase'] ?? '';
        $currentPhaseDate = $contact['phase_date'] ?? null;

        if ($currentStatus === 'Stillgelegt' || $currentStatus === 'Abgeschlossen'): ?>
            <div class="p-3 bg-gray-200 rounded-lg text-center text-gray-600 text-sm font-medium">
                <?= $currentStatus === 'Stillgelegt' ? 'Kontakt ist stillgelegt.' : 'Kontakt ist abgeschlossen.' ?>
            </div>
            <?php if (!Auth::isMentor()): ?>
                <form method="POST" action="index.php?page=contact_update&id=<?= $contact['id'] ?>"
                    class="mt-2 text-center workflow-status-form">
                    <input type="hidden" name="status" value="Offen">
                    <input type="hidden" name="phase" value="Vorgemerkt">
                    <button class="text-xs text-primary underline">Reaktivieren (Status: Offen)</button>
                </form>
            <?php endif; ?>
        <?php elseif (!empty($availablePhases)): ?>
            <div class="space-y-2">
                <?php foreach ($availablePhases as $phaseName => $phaseConfig):
                    $isPhaseActive = $currentPhase === $phaseName;
                    $requiresDate = $phaseConfig['requires_date'] ?? false;
                    $phaseIcon = $phaseConfig['icon'] ?? 'circle';
                    ?>
                    <?php if ($requiresDate): ?>
                        <!-- Phase mit Datum-Anforderung -->
                        <div
                            class="w-full text-left px-3 py-3 rounded-lg border flex justify-between items-center bg-white
                            <?= $isPhaseActive ? 'border-secondary shadow-md ring-1 ring-secondary' : 'border-gray-200 shadow-sm' ?>">
                            <div class="flex items-center">
                                <i data-lucide="<?= $phaseIcon ?>"
                                    class="w-4 h-4 mr-2 <?= $isPhaseActive ? 'text-secondary' : 'text-gray-400' ?>"></i>
                                <div>
                                    <div class="text-sm font-semibold <?= $isPhaseActive ? 'text-secondary' : 'text-gray-700' ?>">
                                        <?= htmlspecialchars($phaseName) ?>
                                        <?php if ($isPhaseActive && $currentPhaseDate): ?>
                                            <span class="font-normal text-xs text-gray-500 ml-1">(
                                                <?= PhaseConfig::formatPhaseDate($currentStatus, $phaseName, $currentPhaseDate) ?>)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-[10px] text-gray-400">
                                        <?= htmlspecialchars($phaseConfig['description']) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if ($isPhaseActive): ?>
                                    <i data-lucide="check-circle" class="w-4 h-4 text-secondary"></i>
                                <?php elseif (!Auth::isMentor()): ?>
                                    <button type="button"
                                        onclick="openPhaseModal('<?= $contact['id'] ?>', '<?= $currentStatus ?>', '<?= htmlspecialchars($phaseName, ENT_QUOTES) ?>', '<?= $phaseConfig['date_label'] ?? '' ?>', '<?= $phaseConfig['date_format'] ?? 'full_date' ?>')"
                                        class="p-1 hover:bg-secondary/10 rounded-full transition-colors text-gray-300 hover:text-secondary"
                                        title="Phase mit Datum setzen">
                                        <i data-lucide="calendar" class="w-4 h-4"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Phase ohne Datum -->
                        <?php if (!Auth::isMentor()): ?>
                            <form method="POST" action="index.php?page=contact_update&id=<?= $contact['id'] ?>"
                                class="workflow-status-form">
                                <input type="hidden" name="status" value="<?= $currentStatus ?>">
                                <input type="hidden" name="phase" value="<?= htmlspecialchars($phaseName) ?>">
                                <button type="submit"
                                    class="w-full text-left px-3 py-3 rounded-lg border transition-all flex justify-between items-center group
                                <?= $isPhaseActive ? 'bg-white border-secondary shadow-md ring-1 ring-secondary' : 'bg-white border-gray-200 hover:border-secondary/50' ?>">
                                    <div class="flex items-center">
                                        <i data-lucide="<?= $phaseIcon ?>"
                                            class="w-4 h-4 mr-2 <?= $isPhaseActive ? 'text-secondary' : 'text-gray-400' ?>"></i>
                                        <div>
                                            <div
                                                class="text-sm font-semibold <?= $isPhaseActive ? 'text-secondary' : 'text-gray-700' ?>">
                                                <?= htmlspecialchars($phaseName) ?>
                                            </div>
                                            <div class="text-[10px] text-gray-400">
                                                <?= htmlspecialchars($phaseConfig['description']) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($isPhaseActive): ?>
                                        <i data-lucide="check-circle" class="w-4 h-4 text-secondary"></i>
                                    <?php endif; ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <div
                                class="w-full text-left px-3 py-3 rounded-lg border flex justify-between items-center bg-white
                            <?= $isPhaseActive ? 'border-secondary shadow-md ring-1 ring-secondary' : 'border-gray-200 shadow-sm' ?>">
                                <div class="flex items-center">
                                    <i data-lucide="<?= $phaseIcon ?>"
                                        class="w-4 h-4 mr-2 <?= $isPhaseActive ? 'text-secondary' : 'text-gray-400' ?>"></i>
                                    <div>
                                        <div class="text-sm font-semibold <?= $isPhaseActive ? 'text-secondary' : 'text-gray-700' ?>">
                                            <?= htmlspecialchars($phaseName) ?>
                                        </div>
                                        <div class="text-[10px] text-gray-400">
                                            <?= htmlspecialchars($phaseConfig['description']) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($isPhaseActive): ?>
                                    <i data-lucide="check-circle" class="w-4 h-4 text-secondary"></i>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-sm text-gray-500 italic">Keine Phasen für diesen Status definiert.</div>
        <?php endif; ?>
    </div>

    <!-- Next Status Button -->
    <?php
    $currentIndex = array_search($currentStatus, $mainStatuses);
    if ($currentIndex !== false && $currentIndex < count($mainStatuses) - 1):
        $nextStatus = $mainStatuses[$currentIndex + 1];
        ?>
        <div class="pt-4 border-t border-gray-200">
            <?php if (!Auth::isMentor()): ?>
                <form method="POST" action="index.php?page=contact_update&id=<?= $contact['id'] ?>"
                    class="workflow-status-form">
                    <input type="hidden" name="status" value="<?= $nextStatus ?>">
                    <button
                        class="w-full bg-gray-800 hover:bg-black text-white py-3 rounded-xl font-medium shadow-lg transition-all flex items-center justify-center">
                        Status
                        <?= $nextStatus ?> starten <i data-lucide="arrow-right" class="w-4 h-4 ml-2"></i>
                    </button>
                </form>
            <?php else: ?>
                <div
                    class="w-full bg-gray-100 text-gray-400 py-3 rounded-xl font-medium flex items-center justify-center italic text-xs">
                    Nächster Status: <?= $nextStatus ?> (Nur Lesen)
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>