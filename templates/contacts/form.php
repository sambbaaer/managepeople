<?php
// templates/contacts/form.php
$isEdit = isset($contact);
$title = $isEdit ? 'Kontakt bearbeiten' : 'Neuer Kontakt';
$action = $isEdit ? 'index.php?page=contact_update&id=' . $contact['id'] : 'index.php?page=contact_create';

// Helper to safe echo value
function val($key, $data)
{
    return htmlspecialchars($data[$key] ?? '');
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <?php include __DIR__ . '/../layout/head.php'; ?>
</head>

<body class="min-h-screen flex bg-gray-50 items-center justify-center p-4">

    <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl overflow-hidden relative my-8">
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-primary via-accent to-secondary"></div>

        <div class="p-8 max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-2xl font-bold font-display text-gray-800"><?= $title ?></h1>
                <a href="<?= $isEdit ? 'index.php?page=contact_detail&id=' . $contact['id'] : 'index.php?page=contacts' ?>"
                    class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </a>
            </div>

            <form action="<?= $action ?>" method="POST" class="space-y-8">

                <!-- Basic Info -->
                <div>
                    <h3
                        class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">
                        Basis Informationen</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Voller Name</label>
                            <input type="text" name="name" value="<?= val('name', $isEdit ? $contact : []) ?>" required
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                        </div>

                        <!-- Beziehung -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Beziehung</label>
                            <input type="text" name="beziehung" value="<?= val('beziehung', $isEdit ? $contact : []) ?>"
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                        </div>

                        <!-- Empfohlen von -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Empfohlen von</label>
                            <input type="text" name="recommended_by"
                                value="<?= val('recommended_by', $isEdit ? $contact : []) ?>"
                                list="contactNamesList" autocomplete="off"
                                placeholder="Name eingeben oder auswählen"
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                            <datalist id="contactNamesList">
                                <?php if (!empty($contactNames)):
                                    foreach ($contactNames as $cn): ?>
                                        <option value="<?= htmlspecialchars($cn['name']) ?>">
                                    <?php endforeach;
                                endif; ?>
                            </datalist>
                        </div>

                        <!-- Initial Status (Only on Create or manual override) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status"
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none bg-white">
                                <?php
                                $sopts = ['Offen', 'Interessent', 'Kundin', 'Partnerin', 'Stillgelegt'];
                                $scurr = $isEdit ? ($contact['status'] ?? 'Offen') : 'Offen';
                                foreach ($sopts as $opt): ?>
                                    <option value="<?= $opt ?>" <?= $scurr === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Birthday -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Geburtstag</label>
                            <input type="date" name="birthday" value="<?= val('birthday', $isEdit ? $contact : []) ?>"
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                        </div>
                    </div>
                </div>

                <!-- Contact Details -->
                <div>
                    <h3
                        class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">
                        Kontaktwege</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Phone with Country Code -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefon / Handy</label>
                            <?php
                            $phoneCC = '+41';
                            $phoneLocalVal = '';
                            $knownCodes = ['+49', '+43', '+41', '+39', '+33', '+44', '+1'];
                            if ($isEdit && !empty($contact['phone'])) {
                                $rawPhone = $contact['phone'];
                                foreach ($knownCodes as $code) {
                                    if (strpos($rawPhone, $code) === 0) {
                                        $phoneCC = $code;
                                        $phoneLocalVal = substr($rawPhone, strlen($code));
                                        break;
                                    }
                                }
                                if (empty($phoneLocalVal) && !empty($rawPhone)) {
                                    $phoneLocalVal = $rawPhone;
                                }
                            }
                            ?>
                            <div class="flex gap-2">
                                <select id="phoneCountryCode"
                                    class="px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none bg-white text-sm w-[100px] shrink-0">
                                    <?php
                                    $codes = [
                                        '+41' => 'CH +41',
                                        '+49' => 'DE +49',
                                        '+43' => 'AT +43',
                                        '+39' => 'IT +39',
                                        '+33' => 'FR +33',
                                        '+44' => 'UK +44',
                                        '+1'  => 'US +1',
                                    ];
                                    foreach ($codes as $code => $label): ?>
                                        <option value="<?= $code ?>" <?= $phoneCC === $code ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="relative flex-1">
                                    <i data-lucide="phone" class="absolute left-3 top-2.5 w-5 h-5 text-gray-400"></i>
                                    <input type="tel" id="phoneLocal" value="<?= htmlspecialchars($phoneLocalVal) ?>"
                                        placeholder="664 1234567"
                                        class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                                </div>
                            </div>
                            <input type="hidden" name="phone" id="phoneHidden" value="<?= val('phone', $isEdit ? $contact : []) ?>">
                            <div id="phoneHint" class="mt-1.5 text-xs min-h-[1.25rem] text-gray-400"></div>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-Mail</label>
                            <div class="relative">
                                <i data-lucide="mail" class="absolute left-3 top-2.5 w-5 h-5 text-gray-400"></i>
                                <input type="email" name="email" value="<?= val('email', $isEdit ? $contact : []) ?>"
                                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                            <div class="relative">
                                <i data-lucide="map-pin" class="absolute left-3 top-2.5 w-5 h-5 text-gray-400"></i>
                                <input type="text" name="address" value="<?= val('address', $isEdit ? $contact : []) ?>"
                                    placeholder="Strasse, PLZ Ort"
                                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Social Media -->
                <div>
                    <h3
                        class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">
                        Social Media</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <!-- Instagram -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Instagram</label>
                            <div class="relative">
                                <i data-lucide="instagram" class="absolute left-3 top-2.5 w-5 h-5 text-gray-400"></i>
                                <input type="text" name="social_instagram"
                                    value="<?= val('social_instagram', $isEdit ? $contact : []) ?>"
                                    placeholder="@username"
                                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                            </div>
                        </div>

                        <!-- TikTok -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">TikTok</label>
                            <div class="relative">
                                <i data-lucide="video" class="absolute left-3 top-2.5 w-5 h-5 text-gray-400"></i>
                                <input type="text" name="social_tiktok"
                                    value="<?= val('social_tiktok', $isEdit ? $contact : []) ?>" placeholder="@username"
                                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                            </div>
                        </div>

                        <!-- Facebook -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Facebook</label>
                            <div class="relative">
                                <i data-lucide="facebook" class="absolute left-3 top-2.5 w-5 h-5 text-gray-400"></i>
                                <input type="text" name="social_facebook"
                                    value="<?= val('social_facebook', $isEdit ? $contact : []) ?>"
                                    placeholder="Profil URL oder Name"
                                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                            </div>
                        </div>

                        <!-- LinkedIn -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">LinkedIn</label>
                            <div class="relative">
                                <i data-lucide="linkedin" class="absolute left-3 top-2.5 w-5 h-5 text-gray-400"></i>
                                <input type="text" name="social_linkedin"
                                    value="<?= val('social_linkedin', $isEdit ? $contact : []) ?>"
                                    placeholder="Profil URL"
                                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Categories & Tags -->
                <div>
                    <h3
                        class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">
                        Kategorisierung</h3>
                    <div class="grid grid-cols-1 gap-6">
                        <!-- Tags -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                            <div class="relative">
                                <i data-lucide="tag" class="absolute left-3 top-2.5 w-5 h-5 text-gray-400"></i>
                                <input type="text" name="tags" value="<?= val('tags', $isEdit ? $contact : []) ?>"
                                    placeholder="z.B. Sport, Familie, Network (kommagetrennt)"
                                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1">Tags mit Kommas trennen für Smart-Lists.</p>
                        </div>
                    </div>
                </div>

                <?php if (!$isEdit): // Only show initial note on create ?>
                    <!-- Notes -->
                    <div>
                        <h3
                            class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">
                            Notizen</h3>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Erste Notiz</label>
                        <textarea name="notes" rows="3"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none resize-none"></textarea>
                    </div>
                <?php endif; ?>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 mt-8">
                    <a href="<?= $isEdit ? 'index.php?page=contact_detail&id=' . $contact['id'] : 'index.php?page=contacts' ?>"
                        class="px-6 py-2 rounded-xl text-gray-600 hover:bg-gray-100 font-medium transition-colors">Abbrechen</a>
                    <button type="submit"
                        class="px-6 py-2 rounded-xl bg-primary text-white font-bold shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all">
                        Speichern
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // --- Phone Validation & Smart Auto-Correction ---
        const phoneCC = document.getElementById('phoneCountryCode');
        const phoneLocal = document.getElementById('phoneLocal');
        const phoneHidden = document.getElementById('phoneHidden');
        const phoneHint = document.getElementById('phoneHint');

        function renderHint(text, type) {
            const colors = {
                ok: 'text-green-600',
                warn: 'text-amber-500',
                error: 'text-red-500',
                info: 'text-teal-600'
            };
            phoneHint.className = 'mt-1.5 text-xs min-h-[1.25rem] ' + (colors[type] || 'text-gray-400');
            phoneHint.textContent = text;
        }

        function updatePhone(autoCorrect) {
            let raw = phoneLocal.value;
            let cc = phoneCC.value;
            let clean = raw.replace(/[\s\-\(\)\.\/]/g, '');
            let messages = [];

            if (!clean) {
                phoneHidden.value = '';
                renderHint('', '');
                return;
            }

            // 1. Detect pasted full international number (+49... or 0049...)
            if (clean.startsWith('+') || clean.startsWith('00')) {
                let full = clean.startsWith('00') ? '+' + clean.substring(2) : clean;
                const codes = ['+49', '+43', '+41', '+39', '+33', '+44', '+1'];
                for (let code of codes) {
                    if (full.startsWith(code)) {
                        cc = code;
                        clean = full.substring(code.length);
                        phoneCC.value = code;
                        phoneLocal.value = clean;
                        messages.push('Vorwahl ' + code + ' erkannt');
                        break;
                    }
                }
            }

            // 2. Strip leading 0 (common local format like 0664 -> 664)
            if (clean.startsWith('0') && autoCorrect) {
                clean = clean.substring(1);
                phoneLocal.value = clean;
                messages.push('Fuehrende 0 entfernt');
            } else if (clean.startsWith('0') && !autoCorrect) {
                // Preview without modifying input
                clean = clean.substring(1);
                messages.push('Fuehrende 0 wird beim Speichern entfernt');
            }

            // 3. Validate: only digits allowed
            if (/\D/.test(clean)) {
                renderHint('Nur Ziffern erlaubt (keine Buchstaben oder Sonderzeichen)', 'error');
                phoneHidden.value = '';
                return;
            }

            if (!clean) {
                phoneHidden.value = '';
                renderHint('', '');
                return;
            }

            let combined = cc + clean;
            phoneHidden.value = combined;

            // 4. Length validation
            if (clean.length < 4) {
                renderHint('Vorschau: ' + combined + ' \u2013 Nummer scheint zu kurz', 'warn');
            } else if (clean.length > 15) {
                renderHint('Nummer zu lang \u2013 bitte pruefen', 'error');
                phoneHidden.value = '';
            } else if (messages.length) {
                renderHint(messages.join(', ') + ' \u2192 ' + combined, 'info');
            } else {
                renderHint('\u2713 ' + combined, 'ok');
            }
        }

        phoneLocal.addEventListener('input', function () { updatePhone(true); });
        phoneLocal.addEventListener('paste', function () { setTimeout(function () { updatePhone(true); }, 50); });
        phoneCC.addEventListener('change', function () { updatePhone(false); });

        // Initial preview on page load
        updatePhone(false);

        // Prevent form submit with invalid phone
        document.querySelector('form').addEventListener('submit', function (e) {
            var local = phoneLocal.value.replace(/[\s\-\(\)\.\/]/g, '');
            if (local && !phoneHidden.value) {
                e.preventDefault();
                renderHint('Bitte gueltige Telefonnummer eingeben', 'error');
                phoneLocal.focus();
            }
        });
    </script>
</body>

</html>