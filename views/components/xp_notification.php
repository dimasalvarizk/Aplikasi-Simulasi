<!-- Floating Gamification Notification Component -->
<div id="floatingXpToast" class="fixed bottom-6 right-6 z-50 pointer-events-none transition-all duration-500 transform translate-y-10 opacity-0">
    <div class="bg-slate-900/95 backdrop-blur-md border border-slate-700 text-white px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3">
        <span class="text-2xl animate-bounce" id="xpToastIcon">⭐</span>
        <div>
            <h5 class="text-xs font-black text-amber-400" id="xpToastTitle">+XP Reward!</h5>
            <p class="text-[11px] text-slate-300" id="xpToastMsg">Kamu memperoleh poin XP baru.</p>
        </div>
    </div>
</div>

<script>
function triggerXpToast(amount, message = 'Aktivitas berhasil diselesaikan!') {
    const toast = document.getElementById('floatingXpToast');
    const title = document.getElementById('xpToastTitle');
    const msg = document.getElementById('xpToastMsg');

    title.textContent = `+${amount} XP Berhasil Diraih!`;
    msg.textContent = message;

    toast.classList.remove('translate-y-10', 'opacity-0');
    toast.classList.add('translate-y-0', 'opacity-100');

    setTimeout(() => {
        toast.classList.remove('translate-y-0', 'opacity-100');
        toast.classList.add('translate-y-10', 'opacity-0');
    }, 4000);
}
</script>
