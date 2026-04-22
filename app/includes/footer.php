</main>

<footer style="background:#1a472a;color:#faf7f2;" class="mt-5 pt-5">
  <div class="container" style="max-width:1240px;">
    <div class="row g-4 pb-4">
      <div class="col-lg-7">
        <div class="d-flex align-items-center gap-3 mb-3">
          <span class="ea-brand-mark"><i class="bi bi-flower1"></i></span>
          <div class="font-display" style="font-size:2rem;color:#c9a84c;font-weight:700;">eAyurvedic</div>
        </div>
        <p class="mb-0" style="color:rgba(250,247,242,0.78);max-width:32rem;">
          A modern Ayurvedic consultation and medicine store platform designed for trusted wellness guidance, smoother care journeys, and a polished final-year project presentation.
        </p>
      </div>
      <div class="col-sm-6 col-lg-5">
        <h5 class="mb-3" style="color:#c9a84c;">Contact</h5>
        <div class="d-flex flex-column gap-2" style="color:rgba(250,247,242,0.78);">
          <div><i class="bi bi-geo-alt me-2"></i>Ayurvedic Care Demo Platform</div>
          <div><i class="bi bi-envelope me-2"></i>support@eayurvedic.local</div>
          <div><i class="bi bi-telephone me-2"></i>9876543210</div>
        </div>
      </div>
    </div>
  </div>

  <div style="border-top:1px solid rgba(250,247,242,0.12);">
    <div class="container py-3 small text-center" style="max-width:1240px;color:rgba(250,247,242,0.72);">
      © 2025 eAyurvedic. All rights reserved.
    </div>
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
