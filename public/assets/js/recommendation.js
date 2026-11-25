(function () {
    'use strict';

    var CFG = Object.assign({
        apiUrl: '/api/recommend',
        imageUrl: '',
        requestTimeoutMs: 45000,
        minBudget: 10000,
        maxBudget: 1000000,
        packageCount: 1,
        showSampleOnLoad: true
    }, window.NUTRIMATE_CONFIG || {});

    var params = new URLSearchParams(window.location.search);
    var SHOW_SAMPLE = params.has('preview') || CFG.showSampleOnLoad;
    var REDUCED_MOTION = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var ICONS = {
        sun: '<svg viewBox="280.83 1153.33 18.33 18.34" width="18.33" height="18.34" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M290 1158.33C287.7 1158.33 285.833 1160.2 285.833 1162.5C285.833 1164.8 287.7 1166.67 290 1166.67C292.3 1166.67 294.167 1164.8 294.167 1162.5C294.167 1160.2 292.3 1158.33 290 1158.33M281.667 1163.33H283.333M296.667 1163.33H298.333M289.167 1154.17V1155.83M289.167 1169.17V1170.83" fill="none" stroke="#C48305" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        info: '<svg viewBox="281.67 1227.17 16.67 16.66" width="16.67" height="16.66" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><circle cx="290" cy="1235.5" r="7.5" fill="none" stroke="#556E16" stroke-width="1.8"/><path d="M290 1234v4M290 1231.5h.01" stroke="#556E16" stroke-width="1.8" stroke-linecap="round"/></svg>',
        chat: '<svg viewBox="282.5 1300.17 15 15.92" width="15" height="15.92" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M284 1308c0-3.3 2.7-6 6-6s6 2.7 6 6-2.7 6-6 6c-1.1 0-2.1-.3-3-.8l-2.5.8.8-2.3c-.8-.9-1.3-2.3-1.3-3.7z" fill="none" stroke="#D64A25" stroke-width="1.6"/></svg>'
    };

    var SLOTS = {
        sarapan: { label: 'SARAPAN', time: '07:30', icon: 'sun' },
        makan_siang: { label: 'MAKAN SIANG', time: '12:30', icon: 'info' },
        makan_malam: { label: 'MAKAN MALAM', time: '18:30', icon: 'chat' }
    };

    var form = document.getElementById('planner-form');
    if (!form) return;

    var budgetInput = document.getElementById('budget');
    var budgetField = document.getElementById('budget-field');
    var budgetError = document.getElementById('budget-error');
    var generateBtn = document.getElementById('generate');
    var generateLabel = generateBtn.querySelector('.btn-label');
    var notice = document.getElementById('notice');
    var results = document.getElementById('results');
    var resultsTitle = document.getElementById('results-title');
    var resultsTitleText = document.getElementById('results-title-text');
    var resultsLimit = document.getElementById('results-limit');
    var packageList = document.getElementById('package-list');
    var tplPackage = document.getElementById('tpl-package');
    var tplMeal = document.getElementById('tpl-meal');
    var chips = Array.prototype.slice.call(document.querySelectorAll('.chip'));

    var loading = false;

    function formatNumber(n) {
        return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    function rupiah(n) { return 'Rp ' + formatNumber(n); }

    // Token CSRF dari <meta name="csrf-token">; Laravel menolak POST tanpa token.
    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }
    function cleanText(v, max) {
        return typeof v === 'string' ? v.replace(/\s+/g, ' ').trim().slice(0, max || 140) : '';
    }
    function toNumber(v) {
        var n = Number(v);
        return isFinite(n) && n >= 0 ? Math.round(n) : 0;
    }
    function ref(root, name) { return root.querySelector('[data-ref="' + name + '"]'); }

    function selectedFocus() {
        return chips.filter(function (c) { return c.classList.contains('is-selected'); })
            .map(function (c) { return c.dataset.focus; });
    }
    function readBudget() {
        return parseInt(budgetInput.value.replace(/\D/g, ''), 10) || 0;
    }

    budgetInput.addEventListener('input', function () {
        var digits = budgetInput.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '');
        if (digits !== budgetInput.value) budgetInput.value = digits;
        clearBudgetError();
    });

    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            var on = !chip.classList.contains('is-selected');
            chip.classList.toggle('is-selected', on);
            chip.setAttribute('aria-pressed', String(on));
        });
    });

    function showBudgetError(msg) {
        budgetError.textContent = msg;
        budgetError.hidden = false;
        budgetField.classList.add('is-invalid');
        budgetInput.setAttribute('aria-invalid', 'true');
        budgetInput.focus();
    }
    function clearBudgetError() {
        budgetError.hidden = true;
        budgetField.classList.remove('is-invalid');
        budgetInput.removeAttribute('aria-invalid');
    }

    function setLoading(state) {
        loading = state;
        generateBtn.disabled = state;
        generateBtn.classList.toggle('is-loading', state);
        generateBtn.setAttribute('aria-busy', String(state));
        generateLabel.textContent = state ? 'Menyusun rekomendasi...' : 'Generate Rekomendasi';
    }

    function showNotice(message, type) {
        notice.textContent = '';
        notice.classList.toggle('is-error', type === 'error');
        if (type === 'error') {
            var strong = document.createElement('strong');
            strong.textContent = 'Rekomendasi belum berhasil. ';
            notice.appendChild(strong);
        }
        notice.appendChild(document.createTextNode(message));
        notice.hidden = false;
    }
    function hideNotice() { notice.hidden = true; }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (loading) return;

        var budget = readBudget();
        if (!budget) { showBudgetError('Masukkan batas budget harian kamu.'); return; }
        if (budget < CFG.minBudget) { showBudgetError('Budget minimal ' + rupiah(CFG.minBudget) + ' per hari.'); return; }
        if (budget > CFG.maxBudget) { showBudgetError('Budget maksimal ' + rupiah(CFG.maxBudget) + ' per hari.'); return; }
        clearBudgetError();

        generate(budget, selectedFocus());
    });

    function generate(budget, focus) {
        setLoading(true);
        showNotice('Menyusun menu sehat yang pas dengan budget kamu...');

        var controller = window.AbortController ? new AbortController() : null;
        var timer = setTimeout(function () {
            if (controller) controller.abort();
        }, CFG.requestTimeoutMs);

        fetch(CFG.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                budget: budget,
                focus: focus,
                target_calories: CFG.targetCalories,
                count: CFG.packageCount
            }),
            signal: controller ? controller.signal : undefined
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, status: res.status, data: data };
                });
            })
            .then(function (result) {
                clearTimeout(timer);
                setLoading(false);

                if (!result.ok || !result.data || result.data.ok !== true) {
                    var msg = (result.data && result.data.error) ? result.data.error : 'Terjadi kesalahan saat menyusun rekomendasi.';
                    showNotice(msg, 'error');
                    results.hidden = true;
                    return;
                }

                var packages = normalize(result.data, budget);
                if (!packages.length) {
                    var note = result.data.meta && result.data.meta.note
                        ? result.data.meta.note
                        : 'Belum ada paket yang muat dalam budget ini.';
                    showNotice(note, 'error');
                    results.hidden = true;
                    return;
                }

                render(packages, budget, focus, { announce: true });
                hideNotice();
            })
            .catch(function (err) {
                clearTimeout(timer);
                setLoading(false);
                results.hidden = true;
                if (err && err.name === 'AbortError') {
                    showNotice('Permintaan terlalu lama. Coba lagi.', 'error');
                } else {
                    showNotice('Tidak dapat menghubungi server. Periksa koneksi lalu coba lagi.', 'error');
                }
            });
    }

    function normalize(data, budget) {
        var list = data && Array.isArray(data.packages) ? data.packages : [];
        return list.slice(0, Math.max(1, CFG.packageCount)).map(function (p) {
            if (!p || typeof p !== 'object') return null;
            var meals = (Array.isArray(p.meals) ? p.meals : []).slice(0, 6).map(function (m) {
                return {
                    slot: cleanText(m && m.slot, 20).toLowerCase(),
                    label: cleanText(m && m.label, 30),
                    time: '',
                    portion: '',
                    menu: cleanText(m && m.menu, 160),
                    price: toNumber(m && m.price),
                    calories: toNumber(m && m.calories)
                };
            }).filter(function (m) { return m.menu; });
            if (!meals.length) return null;

            var total = p.total !== undefined ? toNumber(p.total)
                : meals.reduce(function (sum, m) { return sum + m.price; }, 0);

            if (total <= 0 || total > budget) return null;

            var n = p.nutrition || {};
            return {
                name: cleanText(p.name, 60) || 'Paket Hemat Harian',
                description: cleanText(p.description, 200),
                meals: meals,
                total: total,
                saving: toNumber(p.saving),
                targetReached: p.target_reached === true,
                payload: Array.isArray(p.payload) ? p.payload : [],
                nutrition: {
                    calories: toNumber(n.calories), protein: toNumber(n.protein),
                    carbs: toNumber(n.carbs), fat: toNumber(n.fat)
                }
            };
        }).filter(Boolean);
    }

    function render(packages, budget, focus, opts) {
        packageList.textContent = '';
        packages.forEach(function (pkg) { packageList.appendChild(buildPackage(pkg, budget, focus)); });

        resultsTitleText.textContent = packages.length + ' Paket Rekomendasi Siap Pakai';
        resultsLimit.textContent = 'Batas Budget: ' + rupiah(budget);
        results.hidden = false;

        if (opts && opts.announce) {
            resultsTitle.focus({ preventScroll: true });
            results.scrollIntoView({ behavior: REDUCED_MOTION ? 'auto' : 'smooth', block: 'start' });
        }
    }

    function buildPackage(pkg, budget, focus) {
        var node = tplPackage.content.firstElementChild.cloneNode(true);
        var saving = budget - pkg.total;

        ref(node, 'budget').textContent = 'Budget ' + rupiah(budget);
        var savingEl = ref(node, 'saving');
        savingEl.textContent = (saving >= 0 ? 'Hemat ' : 'Lebih ') + rupiah(Math.abs(saving));
        savingEl.classList.toggle('is-over', saving < 0);

        var img = ref(node, 'image');
        if (CFG.imageUrl) {
            img.src = CFG.imageUrl;
        } else {
            img.remove();
        }

        ref(node, 'name').textContent = pkg.name;
        ref(node, 'total').textContent = rupiah(pkg.total);
        ref(node, 'old').textContent = '/ ' + rupiah(budget);
        ref(node, 'remain').textContent = 'Sisa Saldo: ' + rupiah(Math.max(saving, 0));

        var desc = ref(node, 'desc');
        if (pkg.description) {
            desc.textContent = pkg.description;
            if (!pkg.targetReached) {
                desc.classList.add('is-under-target');
            }
        } else {
            desc.remove();
        }

        var mealsEl = ref(node, 'meals');
        pkg.meals.forEach(function (m) { mealsEl.appendChild(buildMeal(m)); });

        var nu = pkg.nutrition;
        ref(node, 'kcal').textContent = nu.calories ? formatNumber(nu.calories) + ' kkal' : '-';
        ref(node, 'protein').textContent = nu.protein ? nu.protein + 'g' : '-';
        ref(node, 'carbs').textContent = nu.carbs ? nu.carbs + 'g' : '-';
        ref(node, 'fat').textContent = nu.fat ? nu.fat + 'g' : '-';

        // Payload nyata: daftar food_id + slot untuk disimpan ke Meal Planner.
        var payloadInput = ref(node, 'payload');
        if (payloadInput) {
            payloadInput.value = JSON.stringify(pkg.payload);
        }

        var useForm = ref(node, 'use');
        if (useForm && useForm.form) {
            useForm.form.addEventListener('submit', function () {
                var label = useForm.querySelector('.btn-label');
                if (label) label.textContent = 'Menyimpan...';
            });
        }

        return node;
    }

    function buildMeal(m) {
        var li = tplMeal.content.firstElementChild.cloneNode(true);
        var slot = SLOTS[m.slot] || { label: (m.label || m.slot || 'MENU').toUpperCase(), time: '', icon: 'info' };

        var icon = ref(li, 'icon');
        icon.classList.add('is-' + slot.icon);
        icon.innerHTML = ICONS[slot.icon];

        var detail = [];
        if (m.calories) detail.push(formatNumber(m.calories) + ' kkal');
        var label = slot.label + (detail.length ? ' \u2022 ' + detail.join(' \u2022 ') : '');
        ref(li, 'label').textContent = label;
        ref(li, 'name').textContent = m.menu;
        ref(li, 'price').textContent = rupiah(m.price);
        return li;
    }

    if (SHOW_SAMPLE && !CFG.showSampleOnLoad) {
        // Mode pratinjau tanpa server (mis. ?preview=1): pakai budget awal saja.
        // Data tetap diambil dari API, jadi tidak ada angka karangan.
        generate(readBudget() || 30000, selectedFocus());
    }
})();
