    </main>

    <!-- Оверлей для попапов -->
    <div class="popup-overlay" id="popupOverlay"></div>

    <!-- Попап профиля -->
    <div class="popup" id="profilePopup">
        <span class="close-btn" onclick="closeAllPopups()">&times;</span>
        <h2>Профиль пользователя</h2>
        <div class="profile-info" id="profileContent">
            <div class="loading">Загрузка...</div>
        </div>
    </div>

    <script src="<?php echo BASE_URL; ?>scripts/main.js"></script>
    
    <script>
    function closeAllPopups() {
        document.querySelectorAll('.popup').forEach(function(p) {
            p.classList.remove('active');
        });
        var overlay = document.getElementById('popupOverlay');
        if (overlay) {
            overlay.classList.remove('active');
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        var overlay = document.getElementById('popupOverlay');
        if (overlay) {
            overlay.addEventListener('click', closeAllPopups);
        }
    });
    </script>
</body>
</html>
