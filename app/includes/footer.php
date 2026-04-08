</main>

<footer class="bg-light border-top py-3 mt-5">
  <div class="container small text-muted">
    © <?= date('Y') ?> eAyurvedic — Ayurvedic Consultation + Medicine Store
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePwd(ids, btn) {
  var idArray = Array.isArray(ids) ? ids : [ids];
  var isPassword = document.getElementById(idArray[0]).type === 'password';
  var newType = isPassword ? 'text' : 'password';
  
  idArray.forEach(function(id) {
    var inp = document.getElementById(id);
    if (inp) inp.type = newType;
  });

  var icon = btn.querySelector('i');
  if (isPassword) {
    icon.classList.replace('bi-eye', 'bi-eye-slash');
  } else {
    icon.classList.replace('bi-eye-slash', 'bi-eye');
  }
}
</script>
</body>
</html>
