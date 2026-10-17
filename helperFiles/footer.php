<footer class="site-footer sticky background-gradient text-white">
    <div class="container-fluid px-4">
        <div class="row text-center text-md-left align-items-center">
            <!-- Column 1: School Name and Campus -->
            <div class="col-12 col-md-5 mb-5 mb-md-0 text-left" style="padding-left: 50px;">
                <div class="col-12 col-md-8">
                    <br />
                    <h5 class="mb-1" style="font-weight: bold; font-size: 20px;">PHILIPPINE SCIENCE HIGH SCHOOL</h5>
                    <h5 class="mb-2" style="font-weight: bold; font-size: 20px;">ILOCOS REGION CAMPUS</h5>
                    <p class="mb-0" style="margin-top: 5px;"><i class="bi bi-geo-alt-fill"></i>Poblacion East, San
                        Ildefonso 2728, Ilocos Sur</p>
                </div>

                <div class="mb-2 col-12 col-md-4 text-center">
                    <br />
                    <br />
                    <p class="mt-2 mb-1 font-weight-bold">About the</p>
                    <p class="mt-2 mb-1 font-weight-bold" style="font-size: 16px; font-weight: bold;">DEVELOPERS</p>

                </div>
            </div>

            <!-- Column 3: Center logo -->
            <div class="col-12 col-md-2 mb-2 mb-md-0 d-flex justify-content-center" style="padding-top: 15px;">
                <img src="<?php echo $asset_base ?? ''; ?>img/logo.png" alt="PSHS Logo" class="footer-logo">
            </div>

            <!-- Column 4: Contact and About -->
            <div class="col-12 col-md-5 text-md-right text-center">
                <div class="mb-2 col-12 col-md-6">
                    <p class="mb-1 font-weight-bold">Developed by:</p>
                    <br />

                    <p class="mt-2 mb-1 font-weight-bold">Gabriel James Valdez</p>
                    <p class="mt-2 mb-1 font-weight-bold">Zyx Leiabe A. Barangan</p>
                    <p class="mt-2 mb-1 font-weight-bold">Xyzy De Vera</p>
                    <p class="mt-2 mb-1 font-weight-bold">Elijah Alimpia</p>
                    <p class="mt-2 mb-1 font-weight-bold">Rojan Joefel C. Dumlao</p>

                </div>
                <div class="mb-2 col-12 col-md-6 ">
                    <br />
                    <p class="mt-2 mb-1 font-weight-bold" style="font-size: 16px; font-weight: bold;">FILIPINO PROJECT
                    </p>
                    <p class="mt-2 mb-1 font-weight-bold">Grade 12</p>
                    <br />
                    <p class="mt-2 mb-1 font-weight-bold" style="font-size: 16px; font-weight: bold;">Adviser: Dominic Patric Galdonez</p>
                    <p class="mt-2 mb-1 font-weight-bold">School Year: 2026-2027</p>
                </div>
            </div>
        </div>
        <div class="text-center mt-3" style="margin-top: 0">
            <small>&copy; 2026 PSHS IRC. All rights reserved.</small>
        </div>
    </div>
</footer>

<!-- Toast Container -->
<div id="toast-container"></div>

<script>
function showToast(message, type = 'info', duration = 3000) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = 'toast-notification ' + type;

    let icon = '';
    switch (type) {
        case 'success': icon = '<i class="bi bi-check-circle-fill" style="color:#28a745; margin-right:10px;"></i>'; break;
        case 'error': icon = '<i class="bi bi-exclamation-circle-fill" style="color:#dc3545; margin-right:10px;"></i>'; break;
        case 'warning': icon = '<i class="bi bi-exclamation-triangle-fill" style="color:#ffc107; margin-right:10px;"></i>'; break;
        default: icon = '<i class="bi bi-info-circle-fill" style="color:#17a2b8; margin-right:10px;"></i>';
    }

    toast.innerHTML =
        '<div style="display:flex; align-items:center;">' + icon + '<span>' + message + '</span></div>' +
        '<button class="toast-close" onclick="this.parentElement.remove()">&times;</button>';

    container.appendChild(toast);

    setTimeout(function() {
        toast.classList.add('hide');
        toast.addEventListener('animationend', function() { toast.remove(); });
    }, duration);
}
</script>