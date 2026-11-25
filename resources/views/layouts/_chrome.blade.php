{{-- Toasts, the confirm dialog and the small amount of page JavaScript shared by every layout. --}}
<div class="toasts" aria-live="polite">
    @foreach (['success' => 'ok', 'error' => 'bad'] as $key => $tone)
        @if (session($key))
            <div class="slip {{ $tone }}" role="{{ $tone === 'ok' ? 'status' : 'alert' }}" @if($tone === 'ok') data-autohide @endif>
                <span class="slip-text">{{ session($key) }}</span>
                <button class="slip-x" type="button" aria-label="Dismiss" onclick="this.parentElement.remove()"><x-icon name="x" /></button>
            </div>
        @endif
    @endforeach
</div>

<dialog class="confirm" id="confirmDialog" aria-labelledby="confirmTitle">
    <form method="dialog">
        <div class="dlg-body">
            <div class="dlg-ico"><x-icon name="alert" /></div>
            <h2 id="confirmTitle">Are you sure?</h2>
            <p id="confirmText"></p>
        </div>
        <div class="dlg-actions">
            <button class="btn" value="cancel" autofocus>Cancel</button>
            <button class="btn btn-confirm" value="ok" id="confirmOk">Delete</button>
        </div>
    </form>
</dialog>

<script>
(function () {
    // Success toasts fade on their own; errors stay until dismissed.
    document.querySelectorAll('[data-autohide]').forEach(function (t) { setTimeout(function () { t.remove(); }, 6000); });

    // Mobile drawer
    var btn = document.getElementById('menuBtn'), side = document.getElementById('side');
    function drawer(open) { if (!side || !btn) return; side.classList.toggle('open', open); btn.setAttribute('aria-expanded', open); }
    if (btn && side) btn.addEventListener('click', function (e) { e.stopPropagation(); drawer(!side.classList.contains('open')); });
    document.addEventListener('click', function (e) {
        if (side && side.classList.contains('open') && !side.contains(e.target)) drawer(false);
        document.querySelectorAll('details.pop[open], details.who[open], details.acct[open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        drawer(false);
        document.querySelectorAll('details.pop[open], details.who[open], details.acct[open], details.rename[open], details.adder[open]').forEach(function (d) { d.removeAttribute('open'); });
    });

    // Focus the first input when an inline editor or popover opens.
    document.addEventListener('toggle', function (e) {
        if (e.target.open && e.target.matches('details.rename, details.adder, details.pop')) {
            var f = e.target.querySelector('input:not([type=hidden]), textarea');
            if (f) { f.focus(); if (f.select && f.type !== 'date') f.select(); }
        }
    }, true);

    // Destructive forms confirm through the dialog; every form shows a busy button once submitted.
    var dlg = document.getElementById('confirmDialog');
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form.method === 'dialog') return;
        var msg = form.getAttribute('data-confirm');
        if (msg && !form.dataset.confirmed && dlg.showModal) {
            e.preventDefault();
            document.getElementById('confirmText').textContent = msg;
            document.getElementById('confirmTitle').textContent = form.getAttribute('data-confirm-title') || 'Delete this?';
            document.getElementById('confirmOk').textContent = form.getAttribute('data-confirm-ok') || 'Delete';
            dlg.returnValue = '';
            dlg.onclose = function () {
                if (dlg.returnValue === 'ok') { form.dataset.confirmed = '1'; form.requestSubmit ? form.requestSubmit() : form.submit(); }
            };
            dlg.showModal();
            return;
        }
        if (e.defaultPrevented) return;
        document.dispatchEvent(new CustomEvent('oas:submitting', { detail: form })); // e.g. the "sending" overlay
        var submit = form.querySelector('[type=submit]:not(.ibtn):not(.link)');
        if (submit) setTimeout(function () { submit.classList.add('is-busy'); }, 0);
    });

    // Tab rail follows the section in view.
    var tabs = document.querySelectorAll('.rail .tab[href^="#"]');
    if (tabs.length && 'IntersectionObserver' in window) {
        var map = {};
        tabs.forEach(function (t) { map[t.getAttribute('href').slice(1)] = t; });
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (!en.isIntersecting) return;
                tabs.forEach(function (t) { t.classList.remove('on'); t.removeAttribute('aria-current'); });
                var t = map[en.target.id]; if (t) { t.classList.add('on'); t.setAttribute('aria-current', 'true'); }
            });
        }, { rootMargin: '-15% 0px -70% 0px' });
        Object.keys(map).forEach(function (id) { var el = document.getElementById(id); if (el) io.observe(el); });
    }
})();
</script>
@stack('scripts')
