<button
    type="button"
    class="pdc-version"
    onclick="window.pdcVersionBurst && window.pdcVersionBurst(this)"
    aria-label="Pārdod Laimīgs CRM versija 1.0"
>v1.0</button>

<script>
// Cheerful emoji + confetti burst from the sidebar version label.
window.pdcVersionBurst = function (el) {
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches
    if (reduced) {
        return
    }

    var rect = el.getBoundingClientRect()
    var originX = rect.left + rect.width / 2
    var originY = rect.top + rect.height / 2

    var layer = document.createElement('div')
    layer.className = 'pdc-burst-layer'
    document.body.appendChild(layer)

    var emojis = ['🎉', '🎊', '😄', '🙌', '✨', '🥳', '💚', '🌟', '👏', '🍀']
    var colors = ['#285854', '#3f7d76', '#f4b400', '#e85d75', '#5aa9e6', '#7fd1ae', '#ffffff']

    var pick = function (list) {
        return list[Math.floor(Math.random() * list.length)]
    }

    var i
    for (i = 0; i < 14; i++) {
        var emoji = document.createElement('span')
        emoji.className = 'pdc-burst-emoji'
        emoji.textContent = pick(emojis)
        emoji.style.left = originX + 'px'
        emoji.style.top = originY + 'px'
        emoji.style.fontSize = (14 + Math.random() * 14) + 'px'
        layer.appendChild(emoji)

        var angle = (-160 + Math.random() * 140) * Math.PI / 180
        var dist = 70 + Math.random() * 90
        var dx = Math.cos(angle) * dist
        var dy = Math.sin(angle) * dist
        var rot = -180 + Math.random() * 360
        emoji.animate([
            { transform: 'translate(-50%, -50%) scale(0.3) rotate(0deg)', opacity: 0 },
            { transform: 'translate(calc(-50% + ' + (dx * 0.6) + 'px), calc(-50% + ' + (dy * 0.6) + 'px)) scale(1) rotate(' + (rot * 0.5) + 'deg)', opacity: 1, offset: 0.35 },
            { transform: 'translate(calc(-50% + ' + dx + 'px), calc(-50% + ' + (dy + 70) + 'px)) scale(0.9) rotate(' + rot + 'deg)', opacity: 0 }
        ], {
            duration: 850 + Math.random() * 500,
            easing: 'cubic-bezier(0.22, 0.8, 0.3, 1)',
            fill: 'forwards'
        })
    }

    for (i = 0; i < 28; i++) {
        var bit = document.createElement('i')
        bit.className = 'pdc-burst-confetti'
        bit.style.left = originX + 'px'
        bit.style.top = originY + 'px'
        bit.style.background = pick(colors)
        var size = 4 + Math.random() * 5
        bit.style.width = size + 'px'
        bit.style.height = (size * (0.5 + Math.random())) + 'px'
        bit.style.borderRadius = Math.random() > 0.5 ? '50%' : '1px'
        layer.appendChild(bit)

        var cAngle = (-170 + Math.random() * 160) * Math.PI / 180
        var cDist = 60 + Math.random() * 130
        var cdx = Math.cos(cAngle) * cDist
        var cdy = Math.sin(cAngle) * cDist
        bit.animate([
            { transform: 'translate(-50%, -50%) rotate(0deg)', opacity: 1 },
            { transform: 'translate(calc(-50% + ' + (cdx * 0.7) + 'px), calc(-50% + ' + (cdy * 0.7) + 'px)) rotate(' + (Math.random() * 360) + 'deg)', opacity: 1, offset: 0.4 },
            { transform: 'translate(calc(-50% + ' + cdx + 'px), calc(-50% + ' + (cdy + 150) + 'px)) rotate(' + (Math.random() * 720) + 'deg)', opacity: 0 }
        ], {
            duration: 950 + Math.random() * 700,
            easing: 'cubic-bezier(0.3, 0.7, 0.4, 1)',
            fill: 'forwards'
        })
    }

    setTimeout(function () {
        layer.remove()
    }, 1900)
}
</script>
