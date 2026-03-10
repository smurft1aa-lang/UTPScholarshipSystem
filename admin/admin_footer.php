    </div><!-- .admin-main -->
</div><!-- .admin-layout -->
<script src="/assets/js/main.js"></script>
<script>
    function openModal(id) {
        document.getElementById(id).classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
        document.body.style.overflow = '';
    }
</script>
</body>
</html>
<!-- Page rendered in <?= endTimer('page_load') ?>ms -->
