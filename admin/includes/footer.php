        </div>
    </div>

    <!-- Refresh Confirmation Modal -->
    <div id="refreshConfirmModal">
        <div class="dash-card" style="width: 90%; max-width: 500px; text-align: center; padding: 50px 40px; border: 2px solid var(--primary);">
            <div class="refresh-icon" style="color: var(--primary); font-size: 4em; margin-bottom: 25px; animation: fa-spin 10s linear infinite;">
                <i class="fas fa-sync-alt"></i>
            </div>
            <h2 style="font-size: 1.6em; font-weight: 900; margin-bottom: 15px; color: var(--text-main);">PROTECTED SESSION</h2>
            <div style="color: var(--text-muted); line-height: 1.6; margin-bottom: 35px; font-weight: 500;">
                Are you sure you want to refresh? Your current session will be closed, and all UN-SAVED data will be lost. You will be logged out automatically.<br><br>
                <div style="font-weight: 700; color: var(--primary-dark);">क्या आप वाकई रिफ्रेश करना चाहते हैं? आपका वर्तमान सत्र बंद कर दिया जाएगा, और सारा असुरक्षित डेटा खो जाएगा।</div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <button class="btn-primary" style="background: var(--bg-main); color: var(--text-main);" onclick="closeRefreshModal()">RESUME WORK / वापस चलें</button>
                <button class="btn-primary" onclick="executeRefresh()">YES, LOGOUT / हाँ, लॉग आउट</button>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('mobile-active');
        }

        function toggleDropdown() {
            const dropdown = document.getElementById('dropdownMenu');
            if (dropdown) dropdown.classList.toggle('active');
        }

        function toggleTheme() {
            const html = document.documentElement;
            html.classList.toggle('dark');
            const isDark = html.classList.contains('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateThemeIcon();
        }

        function updateThemeIcon() {
            const icon = document.getElementById('themeIcon');
            if (!icon) return;
            const isDark = document.documentElement.classList.contains('dark');
            icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            
            // Add pulse effect on toggle
            icon.parentElement.style.transform = 'scale(0.8)';
            setTimeout(() => { icon.parentElement.style.transform = 'scale(1)'; }, 100);
        }

        window.addEventListener('load', updateThemeIcon);

        window.addEventListener('click', function(e) {
            const dropdown = document.getElementById('dropdownMenu');
            const userProfile = document.querySelector('.user-profile');
            if (dropdown && userProfile && !userProfile.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });

        // Refresh Guard
        const showRefreshModal = () => {
            const modal = document.getElementById('refreshConfirmModal');
            if (modal) modal.classList.add('active');
        };
        const closeRefreshModal = () => {
            const modal = document.getElementById('refreshConfirmModal');
            if (modal) modal.classList.remove('active');
        };
        const executeRefresh = () => {
            window.location.href = 'logout.php';
        };

        window.addEventListener('keydown', e => {
            if (e.key === 'F5' || (e.ctrlKey && e.key === 'r') || (e.metaKey && e.key === 'r')) {
                e.preventDefault();
                showRefreshModal();
            }
        });

        window.onbeforeunload = (e) => {
            const msg = "Unsaved data will be lost.";
            e.returnValue = msg;
            return msg;
        };
    </script>
</body>
</html>