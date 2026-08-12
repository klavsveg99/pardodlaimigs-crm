<script>
document.addEventListener('DOMContentLoaded', function () {
    var map = { deals: '/deals', tasks: '/tasks', wpforms: '/wpform-entries' };

    function getBadge(href) {
        var found = null;
        document.querySelectorAll('.fi-sidebar-item-badge-ctn').forEach(function (ctn) {
            var link = ctn.closest('a');
            if (!link) return;
            if ((link.getAttribute('href') || '').indexOf(href) !== -1) found = ctn;
        });
        return found;
    }

    function setBadgeCount(ctn, count) {
        var label = ctn.querySelector('.fi-badge-label');
        if (!label) return;
        if (count > 0) {
            label.textContent = count;
            ctn.style.display = '';
        } else {
            ctn.style.display = 'none';
        }
    }

    function getBadgeCount(ctn) {
        var label = ctn.querySelector('.fi-badge-label');
        return label ? parseInt(label.textContent) || 0 : 0;
    }

    function refreshBadges() {
        fetch('/api/badges', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var wpformBadge = getBadge(map.wpforms);
            if (wpformBadge) setBadgeCount(wpformBadge, data.wpforms);
            var dealsBadge = getBadge(map.deals);
            if (dealsBadge) setBadgeCount(dealsBadge, data.deals);
            var tasksBadge = getBadge(map.tasks);
            if (tasksBadge) setBadgeCount(tasksBadge, data.tasks);
        })
        .catch(function () {});
    }

    setInterval(refreshBadges, 30000);

    document.addEventListener('livewire:message.processed', function () {
        var url = window.location.href;
        if (url.indexOf('/wpform-entries') !== -1) {
            setTimeout(refreshBadges, 50);
        }
    });

    document.addEventListener('change', function (e) {
        var select = e.target;
        if (!select.matches || !select.name) return;
        if (select.name.indexOf('status') === -1) return;

        var wpformBadge = getBadge(map.wpforms);
        if (!wpformBadge) return;

        var oldVal = select.dataset.prevStatus;
        var newVal = select.value;
        select.dataset.prevStatus = newVal;

        if (oldVal === newVal) return;

        var count = getBadgeCount(wpformBadge);
        if (oldVal === 'new' && newVal !== 'new') {
            setBadgeCount(wpformBadge, Math.max(0, count - 1));
        } else if (oldVal !== 'new' && newVal === 'new') {
            setBadgeCount(wpformBadge, count + 1);
        }
    });

    document.querySelectorAll('select[name*="status"]').forEach(function (s) {
        s.dataset.prevStatus = s.value;
    });
});
</script>
