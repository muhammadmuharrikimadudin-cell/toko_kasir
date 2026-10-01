<?php
// templates/footer.php
// Menutup tag yang dibuka di header.php dan sidebar.php
?>
</main><!-- /main-content -->
</div><!-- /flex wrapper -->

<!-- Global JS Utilities -->
<script>
// ============================================================
// Global Utilities
// ============================================================
const formatRupiah = (num) =>
    'Rp ' + parseInt(num).toLocaleString('id-ID');

function formatRupiahInputField(input) {
    let val = input.value.replace(/[^0-9]/g, '');
    input.value = val ? new Intl.NumberFormat('id-ID').format(val) : '';
}

function parseInputRupiah(id) {
    return parseInt(document.getElementById(id).value.replace(/[^0-9]/g, '') || '0');
}

function formatNumberInput(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

const showToast = (msg, icon = 'success') => {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon,
        title: msg,
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true,
    });
};

// Fetch helper with JSON
const apiFetch = async (url, options = {}) => {
    try {
        const res = await fetch(url, {
            headers: { 'Content-Type': 'application/json', ...options.headers },
            credentials: 'same-origin',
            cache: 'no-store',
            ...options,
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return await res.json();
    } catch (err) {
        console.error('API Error:', err);
        showToast(err.message || 'Terjadi kesalahan', 'error');
        throw err;
    }
};
</script>
</body>
</html>
