<script>
document.addEventListener('DOMContentLoaded', function () {
    var map = { deals: '/deals', tasks: '/tasks', wpforms: '/wpform-entries' };

    function refreshBadges() {
        fetch('/api/badges', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            document.querySelectorAll('.fi-sidebar-item-badge-ctn').forEach(function (ctn) {
                var link = ctn.closest('a');
                if (!link) return;
                var href = link.getAttribute('href') || '';
                var count = 0;

                if (href.indexOf(map.deals) !== -1) count = data.deals;
                else if (href.indexOf(map.tasks) !== -1) count = data.tasks;
                else if (href.indexOf(map.wpforms) !== -1) count = data.wpforms;

                var label = ctn.querySelector('.fi-badge-label');
                if (!label) return;

                if (count > 0) {
                    label.textContent = count;
                    ctn.style.display = '';
                } else {
                    ctn.style.display = 'none';
                }
            });
        })
        .catch(function () {});
    }

    setInterval(refreshBadges, 30000);
});
</script>
