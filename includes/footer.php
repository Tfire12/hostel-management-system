    </div>
</div>

<script>
    setTimeout(function() {
    let alert = document.getElementById("alertBox");
    if(alert){
        alert.classList.remove("show"); // fade out
        setTimeout(() => alert.remove(), 500); // remove completely
    }
}, 3000);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
