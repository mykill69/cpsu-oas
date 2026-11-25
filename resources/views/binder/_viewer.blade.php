{{--
    In-app file viewer. Media is loaded from this app's own /documents route, which
    streams from local disk or Google Drive server-side — no Drive link reaches the browser.
--}}
<dialog class="viewer" id="viewer" aria-labelledby="viewerTitle">
    <div class="viewer-bar">
        <div style="flex:1;min-width:0">
            <div class="vt" id="viewerTitle"></div>
            <div class="vm" id="viewerMeta"></div>
        </div>
        <a id="viewerDl" href="#" title="Download" aria-label="Download"><x-icon name="download" /></a>
        <button type="button" id="viewerClose" title="Close" aria-label="Close"><x-icon name="x" /></button>
    </div>
    <div class="viewer-stage" id="viewerStage"></div>
</dialog>

<script>
(function () {
    var dlg = document.getElementById('viewer'), stage = document.getElementById('viewerStage');
    if (!dlg || !dlg.showModal) return;

    function el(tag, attrs) { var e = document.createElement(tag); for (var k in attrs) e.setAttribute(k, attrs[k]); return e; }

    function open(btn) {
        var kind = btn.dataset.kind, src = btn.dataset.src;
        document.getElementById('viewerTitle').textContent = btn.dataset.name;
        document.getElementById('viewerMeta').textContent = btn.dataset.meta;
        document.getElementById('viewerDl').href = btn.dataset.dl;
        stage.innerHTML = '';
        stage.classList.add('viewer-loading');
        var done = function () { stage.classList.remove('viewer-loading'); }, node;

        if (kind === 'pdf') {
            node = el('iframe', { src: src + '#toolbar=1&navpanes=0', title: btn.dataset.name });
            node.onload = done;
        } else if (kind === 'image') {
            node = el('img', { src: src, alt: btn.dataset.name });
            node.onload = node.onerror = done;
        } else if (kind === 'video') {
            node = el('video', { src: src, controls: '', autoplay: '', playsinline: '', controlslist: 'nodownload', preload: 'metadata' });
            node.onloadeddata = node.onerror = done;
        } else if (kind === 'audio') {
            node = el('audio', { src: src, controls: '', autoplay: '' });
            node.onloadeddata = node.onerror = done;
        } else if (kind === 'text') {
            node = el('pre', {});
            fetch(src, { credentials: 'same-origin' }).then(function (r) { return r.text(); })
                .then(function (t) { node.textContent = t; done(); }).catch(done);
        } else {
            done();
            node = el('div', { class: 'viewer-none' });
            node.innerHTML = '<svg class="i" viewBox="0 0 24 24" style="width:42px;height:42px"><path d="M14 3H7a1.5 1.5 0 0 0-1.5 1.5v15A1.5 1.5 0 0 0 7 21h10a1.5 1.5 0 0 0 1.5-1.5V7.5z"/><path d="M14 3v4.5h4.5"/></svg>'
                + '<b>No preview for this file type</b><span>Word, Excel, PowerPoint and ZIP files open after downloading.</span>';
        }
        node.addEventListener('contextmenu', function (e) { e.preventDefault(); });
        stage.appendChild(node);
        dlg.showModal();
    }

    function close() { dlg.close(); }
    dlg.addEventListener('close', function () { stage.innerHTML = ''; }); // stops video/audio playback
    document.getElementById('viewerClose').addEventListener('click', close);
    dlg.addEventListener('click', function (e) { if (e.target === dlg) close(); });
    document.querySelectorAll('[data-view]').forEach(function (b) { b.addEventListener('click', function () { open(b); }); });
})();
</script>
