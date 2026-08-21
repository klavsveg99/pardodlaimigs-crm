<script>
document.addEventListener('mouseover', function (e) {
    const cell = e.target.closest('td');
    if (!cell) return;
    const text = cell.textContent.trim();
    if (!text || cell.querySelector('button, a, select, input, [x-ref]')) return;
    if (cell.scrollWidth > cell.clientWidth + 2) {
        cell.setAttribute('title', text);
    } else {
        cell.removeAttribute('title');
    }
});
</script>
