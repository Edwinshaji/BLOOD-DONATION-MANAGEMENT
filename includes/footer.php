<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<script>
        setTimeout(() => {
            const flashMsg = document.querySelector('.flash-msg');
            if (flashMsg) {
                flashMsg.style.transition = "opacity 0.5s ease";
                flashMsg.style.opacity = "0";
                setTimeout(() => flashMsg.remove(), 500); // remove after fade-out
            }
        }, 3000); // 3000 ms = 3 sec
    </script>

<script>
    window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
</script>