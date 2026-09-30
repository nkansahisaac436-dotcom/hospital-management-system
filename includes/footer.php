        </main>
    </div>

    <!-- Global App Footer -->
    <footer class="bg-white border-t border-slate-200 py-3 px-6 text-center text-xs text-slate-400 no-print flex flex-wrap justify-between items-center">
        <div>
            &copy; <?= date('Y') ?> <strong><?= e(getSetting('hospital_name', DEFAULT_HOSPITAL_NAME)) ?></strong>. All rights reserved.
        </div>
        <div class="flex items-center space-x-4 mt-1 sm:mt-0">
            <span class="inline-flex items-center text-slate-500">
                <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5"></span> System Online (v<?= APP_VERSION ?>)
            </span>
        </div>
    </footer>

    <!-- Global Javascript Helper -->
    <script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
