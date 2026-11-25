/* ============================================================
   NUTRIMATE — Global Script (shared.js + landing + foodDatabase)
   ------------------------------------------------------------
   - Sidebar mobile/tablet & navbar scroll shadow
   - Modal autentikasi (Masuk & Daftar)
   - Password visibility toggle
   - IntersectionObserver scroll reveal animation
   - Food Database detail modal popup & filter pills
   ============================================================ */
(function () {
    'use strict';

    /* ---------- Sidebar (mobile & tablet) ---------- */
    function setSidebar(open) {
        var menuBtn = document.getElementById('menuBtn');
        var sidebar = document.getElementById('sidebar');
        if (!menuBtn || !sidebar) return;

        document.body.classList.toggle('sidebar-open', open);
        menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');
    }

    window.NutriMate = window.NutriMate || {};
    window.NutriMate.closeSidebar = function () { setSidebar(false); };
    window.NutriMate.isSidebarOpen = function () {
        return document.body.classList.contains('sidebar-open');
    };

    function initNutriMate() {
        var body = document.body;
        var navbar = document.getElementById('navbar');
        var menuBtn = document.getElementById('menuBtn');
        var overlay = document.getElementById('overlay');
        var sidebar = document.getElementById('sidebar');

        if (menuBtn && overlay && sidebar) {
            menuBtn.addEventListener('click', function () {
                setSidebar(!window.NutriMate.isSidebarOpen());
            });

            overlay.addEventListener('click', function () {
                setSidebar(false);
            });

            sidebar.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    setSidebar(false);
                });
            });

            window.addEventListener('resize', function () {
                if (window.innerWidth >= 1024) setSidebar(false);
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') setSidebar(false);
            });
        }

        /* ---------- Bayangan navbar saat di-scroll ---------- */
        if (navbar) {
            var onScroll = function () {
                navbar.classList.toggle('scrolled', window.scrollY > 10);
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        }

        /* ---------- Pop up Masuk / Daftar ---------- */
        var authModal = document.getElementById('authModal');
        var lastFocus = null;

        function firstAuthInput() {
            if (!authModal) return null;
            return authModal.querySelector('.auth-panel:not([hidden]) input');
        }

        function showAuthPanel(name) {
            if (!authModal) return;
            authModal.querySelectorAll('.auth-panel').forEach(function (panel) {
                panel.hidden = panel.getAttribute('data-panel') !== name;
            });
        }

        function isAuthModalOpen() {
            return authModal && authModal.classList.contains('is-open');
        }

        function openAuthModal(name) {
            if (!authModal) return;
            var alreadyOpen = isAuthModalOpen();
            if (!alreadyOpen) lastFocus = document.activeElement;

            if (window.NutriMate) window.NutriMate.closeSidebar();
            showAuthPanel(name);
            authModal.classList.add('is-open');
            authModal.setAttribute('aria-hidden', 'false');
            body.classList.add('modal-open');

            setTimeout(function () {
                var input = firstAuthInput();
                if (input) input.focus();
            }, 60);
        }

        function closeAuthModal() {
            if (!isAuthModalOpen()) return;
            authModal.classList.remove('is-open');
            authModal.setAttribute('aria-hidden', 'true');
            body.classList.remove('modal-open');
            if (lastFocus && lastFocus.focus) lastFocus.focus();
        }

        window.NutriMate.openAuthModal = openAuthModal;
        window.NutriMate.closeAuthModal = closeAuthModal;

        if (authModal) {
            document.querySelectorAll('[data-open-modal]').forEach(function (el) {
                el.addEventListener('click', function (e) {
                    e.preventDefault();
                    openAuthModal(el.getAttribute('data-open-modal'));
                });
            });

            authModal.querySelectorAll('[data-switch-panel]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    var target = btn.getAttribute('data-switch-panel');
                    showAuthPanel(target);
                    setTimeout(function () {
                        var input = firstAuthInput();
                        if (input) input.focus();
                    }, 60);
                });
            });

            authModal.querySelectorAll('[data-close-modal]').forEach(function (el) {
                el.addEventListener('click', closeAuthModal);
            });

            authModal.addEventListener('click', function (e) {
                if (e.target === authModal) closeAuthModal();
            });

            authModal.querySelectorAll('.pw-toggle').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var input = btn.parentElement.querySelector('input');
                    var show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    btn.classList.toggle('on', show);
                    btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
                });
            });

            // Modal bisa dirender dalam keadaan terbuka oleh server
            // (mis. setelah validasi gagal); panel aktif ditentukan server.
            if (isAuthModalOpen()) {
                showAuthPanel(authModal.getAttribute('data-open-panel') || 'login');
                body.classList.add('modal-open');
                var input = firstAuthInput();
                if (input) input.focus();
            }
        }

        /* ---------- Pop up Nutrition Calculator ---------- */
        var calcModal = document.getElementById('calcModal');

        function isCalcModalOpen() {
            return calcModal && calcModal.classList.contains('is-open');
        }

        function openCalculator() {
            if (!calcModal) return;
            if (window.NutriMate) window.NutriMate.closeSidebar();
            calcModal.classList.add('is-open');
            calcModal.setAttribute('aria-hidden', 'false');
            body.classList.add('modal-open');
        }

        function closeCalculator() {
            if (!isCalcModalOpen()) return;
            calcModal.classList.remove('is-open');
            calcModal.setAttribute('aria-hidden', 'true');
            if (!isAuthModalOpen() && !isFoodModalOpen()) body.classList.remove('modal-open');
        }

        window.NutriMate.openCalculator = openCalculator;
        window.NutriMate.closeCalculator = closeCalculator;

        if (calcModal) {
            calcModal.querySelectorAll('[data-close-modal]').forEach(function (btn) {
                btn.addEventListener('click', closeCalculator);
            });

            calcModal.addEventListener('click', function (e) {
                if (e.target === calcModal) closeCalculator();
            });
        }

        /**
         * Hitung BMR/TDEE/IMT/makro di sisi klien (pratinjau cepat).
         * Rumus sama dengan NutritionCalculation::calculate() di server.
         */
        window.NutriMate.calcNutrition = function () {
            var genderEl = document.getElementById('calcGender');
            var ageEl = document.getElementById('calcAge');
            var weightEl = document.getElementById('calcWeight');
            var heightEl = document.getElementById('calcHeight');
            var activityEl = document.getElementById('calcActivity');

            if (!genderEl || !ageEl || !weightEl || !heightEl || !activityEl) return;

            var gender = genderEl.value;
            var age = parseFloat(ageEl.value) || 20;
            var weight = parseFloat(weightEl.value) || 60;
            var height = parseFloat(heightEl.value) || 170;
            var activity = parseFloat(activityEl.value) || 1.2;

            // Rumus Mifflin-St Jeor:
            // Pria   : 10*W + 6.25*H - 5*A + 5
            // Wanita : 10*W + 6.25*H - 5*A - 161
            var bmr = (10 * weight) + (6.25 * height) - (5 * age);
            bmr = gender === 'male' ? (bmr + 5) : (bmr - 161);
            bmr = Math.max(0, bmr);

            var tdee = Math.round(bmr * activity);
            bmr = Math.round(bmr);

            // Makronutrisi: 15% protein, 55% karbo, 30% lemak
            var proteinGrams = Math.round((tdee * 0.15) / 4);
            var carbsGrams = Math.round((tdee * 0.55) / 4);
            var fatGrams = Math.round((tdee * 0.30) / 9);

            // IMT = berat (kg) / tinggi (m)^2
            var heightM = height / 100;
            var bmi = heightM > 0 ? weight / (heightM * heightM) : 0;

            function setText(id, value) {
                var el = document.getElementById(id);
                if (el) el.textContent = value;
            }

            setText('calcBmr', bmr.toLocaleString('id-ID') + ' kkal');
            setText('calcTdee', tdee.toLocaleString('id-ID') + ' kkal/hari');
            setText('calcProtein', proteinGrams + 'g');
            setText('calcCarbs', carbsGrams + 'g');
            setText('calcFat', fatGrams + 'g');
            setText('calcBmi', bmi.toLocaleString('id-ID', { maximumFractionDigits: 2 }));
            setText('calcBmiCategory', '(' + bmiCategory(bmi) + ')');

            var resBox = document.getElementById('calcResults');
            if (resBox) resBox.hidden = false;
        };

        /**
         * Kategori IMT ambang batas Asia-Pasifik (sama dengan sisi server).
         */
        function bmiCategory(bmi) {
            if (!bmi || bmi <= 0) return 'Tidak diketahui';
            if (bmi < 18.5) return 'Kurang berat badan';
            if (bmi < 23.0) return 'Berat badan ideal';
            if (bmi < 25.0) return 'Berisiko kelebihan';
            if (bmi < 30.0) return 'Obesitas I';
            return 'Obesitas II';
        }

        /* ---------- Pop up Detail Makanan (Food Database) ---------- */
        var foodModal = document.getElementById('modalOverlay');
        var btnCloseFoodModal = document.getElementById('btnCloseModal');
        var modalImg = document.getElementById('modalImg');

        var currentFood = null;
        var portions = 1;

        function fmtNum(val) {
            return val.toLocaleString('id-ID', { maximumFractionDigits: 1 });
        }

        function setPortions(p) {
            portions = Math.max(1, Math.min(2, p));

            var portionValue = document.getElementById('portionValue');
            var portionMinus = document.getElementById('portionMinus');
            var portionPlus = document.getElementById('portionPlus');
            var modalSimNote = document.getElementById('modalSimNote');
            var addPortions = document.getElementById('addPortions');

            if (portionValue) portionValue.textContent = portions;
            if (portionMinus) portionMinus.disabled = portions <= 1;
            if (portionPlus) portionPlus.disabled = portions >= 2;
            if (modalSimNote) modalSimNote.textContent = 'Estimasi gizi untuk simulasi · ' + portions + ' porsi';
            if (addPortions) addPortions.value = portions;

            if (currentFood) {
                var modalCal = document.getElementById('modalCal');
                var modalProtein = document.getElementById('modalProtein');
                var modalCarbs = document.getElementById('modalCarbs');
                var modalFat = document.getElementById('modalFat');

                if (modalCal) modalCal.textContent = fmtNum(currentFood.cal * portions) + ' kcal';
                if (modalProtein) modalProtein.textContent = fmtNum(currentFood.protein * portions) + 'g';
                if (modalCarbs) modalCarbs.textContent = fmtNum(currentFood.carbs * portions) + 'g';
                if (modalFat) modalFat.textContent = fmtNum(currentFood.fat * portions) + 'g';
            }
        }

        function isFoodModalOpen() {
            return foodModal && foodModal.classList.contains('is-open');
        }

        function openFoodModal(btn) {
            if (!foodModal) return;
            lastFocus = document.activeElement;

            var name = btn.getAttribute('data-food-name') || '-';
            var category = btn.getAttribute('data-food-category') || 'Lainnya';
            var price = btn.getAttribute('data-food-price') || 'Rp 0';
            var unit = btn.getAttribute('data-food-unit') || '';
            var desc = btn.getAttribute('data-food-desc') || 'Tidak ada deskripsi rinci.';
            var image = btn.getAttribute('data-food-image') || '';
            var editUrl = btn.getAttribute('data-food-edit');

            document.getElementById('modalBadge').textContent = category;
            document.getElementById('modalTitle').textContent = name;
            document.getElementById('modalPrice').textContent = price;
            document.getElementById('modalUnit').textContent = unit;
            document.getElementById('modalDesc').textContent = desc;

            var modalEditLink = document.getElementById('modalEditLink');
            if (modalEditLink && editUrl) {
                modalEditLink.href = editUrl;
            }

            var modalShowLink = document.getElementById('modalShowLink');
            if (modalShowLink) {
                modalShowLink.href = btn.getAttribute('data-food-show') || '#';
            }

            /* Simulasi porsi: simpan angka mentah per 1 porsi */
            currentFood = {
                id: btn.getAttribute('data-food-id') || '',
                cal: parseFloat(btn.getAttribute('data-food-cal-raw')) || 0,
                protein: parseFloat(btn.getAttribute('data-food-protein-raw')) || 0,
                carbs: parseFloat(btn.getAttribute('data-food-carbs-raw')) || 0,
                fat: parseFloat(btn.getAttribute('data-food-fat-raw')) || 0
            };

            var addFoodId = document.getElementById('addFoodId');
            if (addFoodId) addFoodId.value = currentFood.id;

            setPortions(1);

            if (modalImg) {
                if (image && image.trim() !== '') {
                    modalImg.src = image;
                    modalImg.alt = name;
                    modalImg.onerror = function () { this.style.display = 'none'; };
                    modalImg.style.display = 'block';
                } else {
                    modalImg.removeAttribute('src');
                    modalImg.alt = '';
                    modalImg.style.display = 'none';
                }
            }

            foodModal.classList.add('is-open');
            foodModal.setAttribute('aria-hidden', 'false');
            body.classList.add('modal-open');
            if (btnCloseFoodModal) btnCloseFoodModal.focus();
        }

        function closeFoodModal() {
            if (!isFoodModalOpen()) return;
            foodModal.classList.remove('is-open');
            foodModal.setAttribute('aria-hidden', 'true');
            body.classList.remove('modal-open');
            if (lastFocus && lastFocus.focus) lastFocus.focus();
            lastFocus = null;
        }

        if (foodModal) {
            document.querySelectorAll('.btn-detail').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    openFoodModal(btn);
                });
            });

            if (btnCloseFoodModal) {
                btnCloseFoodModal.addEventListener('click', closeFoodModal);
            }

            foodModal.addEventListener('click', function (e) {
                if (e.target === foodModal) closeFoodModal();
            });

            var portionMinus = document.getElementById('portionMinus');
            var portionPlus = document.getElementById('portionPlus');
            if (portionMinus) portionMinus.addEventListener('click', function () { setPortions(portions - 1); });
            if (portionPlus) portionPlus.addEventListener('click', function () { setPortions(portions + 1); });
        }

        /* ---------- Filter Form & Category Pills Interaction ---------- */
        var foodFilterForm = document.getElementById('foodFilterForm');
        var categoryInput = document.getElementById('categoryInput');
        var sortSelect = document.getElementById('sortSelect');
        var searchInput = document.getElementById('searchInput');

        if (foodFilterForm) {
            document.querySelectorAll('.category-pills .pill').forEach(function (pill) {
                pill.addEventListener('click', function () {
                    document.querySelectorAll('.category-pills .pill').forEach(function (p) {
                        p.classList.remove('active');
                    });
                    pill.classList.add('active');
                    if (categoryInput) {
                        categoryInput.value = pill.getAttribute('data-category');
                    }
                    foodFilterForm.submit();
                });
            });

            if (sortSelect) {
                sortSelect.addEventListener('change', function () {
                    foodFilterForm.submit();
                });
            }
        }

        /* ---------- Keyboard: Esc & Tab di Modal ---------- */
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                if (isAuthModalOpen()) closeAuthModal();
                if (isCalcModalOpen()) closeCalculator();
                if (isFoodModalOpen()) closeFoodModal();
            }

            var activeModal = isAuthModalOpen() ? authModal
                : (isCalcModalOpen() ? calcModal : (isFoodModalOpen() ? foodModal : null));
            if (e.key === 'Tab' && activeModal) {
                var focusable = activeModal.querySelectorAll(
                    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                );
                if (!focusable.length) return;
                var first = focusable[0];
                var last = focusable[focusable.length - 1];
                if (e.shiftKey && document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                } else if (!e.shiftKey && document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        });

        /* ---------- Animasi muncul untuk kartu fitur & CTA ---------- */
        var items = document.querySelectorAll('.reveal');
        if (items.length > 0) {
            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.15 });

                items.forEach(function (el, i) {
                    el.style.transitionDelay = (i % 4) * 80 + 'ms';
                    observer.observe(el);
                });
            } else {
                items.forEach(function (el) { el.classList.add('visible'); });
            }
        }

        /* ---------- Password toggle (halaman profil & lainnya) ---------- */
        document.querySelectorAll('.pw-wrap .pw-toggle').forEach(function (btn) {
            // skip tombol di dalam auth modal (sudah ditangani di atas)
            if (authModal && authModal.contains(btn)) return;
            btn.addEventListener('click', function () {
                var input = btn.parentElement.querySelector('input');
                if (!input) return;
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.classList.toggle('on', show);
                btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
            });
        });

        /* ---------- Flash success — tutup & auto-dismiss ---------- */
        var flash = document.getElementById('flash');
        if (flash) {
            var closeBtn = flash.querySelector('.flash-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function () {
                    flash.style.transition = 'opacity 0.3s';
                    flash.style.opacity = '0';
                    setTimeout(function () { flash.remove(); }, 320);
                });
            }
            // auto-dismiss setelah 5 detik
            setTimeout(function () {
                if (!flash.parentNode) return;
                flash.style.transition = 'opacity 0.5s';
                flash.style.opacity = '0';
                setTimeout(function () { if (flash.parentNode) flash.remove(); }, 520);
            }, 5000);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNutriMate);
    } else {
        initNutriMate();
    }
})();
