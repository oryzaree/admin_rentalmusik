(function(){
  const root = document.documentElement;

  // --- Theme toggle (persist with cookie) ---
  function setTheme(t){
    root.setAttribute('data-theme', t);
    document.cookie = 'theme='+t+';path=/;max-age=' + (60*60*24*365);
    document.querySelectorAll('[data-theme-icon]').forEach(el=>{
      el.style.display = (el.dataset.themeIcon === t) ? '' : 'none';
    });
  }
  setTheme(root.getAttribute('data-theme') || 'dark');
  const btnTheme = document.getElementById('btnTheme');
  if (btnTheme) btnTheme.addEventListener('click', ()=>{
    const cur = root.getAttribute('data-theme') || 'dark';
    setTheme(cur === 'dark' ? 'light' : 'dark');
  });

  // --- Sidebar toggle ---
  const btnSb = document.getElementById('btnSidebarToggle');
  if (btnSb) btnSb.addEventListener('click', ()=>{
    document.body.classList.toggle('sidebar-collapsed');
    localStorage.setItem('sb', document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
  });
  if (localStorage.getItem('sb') === '1') document.body.classList.add('sidebar-collapsed');

  // --- Dropdowns ---
  document.querySelectorAll('.dd').forEach(dd=>{
    const trig = dd.querySelector('.dd-trigger');
    if (!trig) return;
    trig.addEventListener('click', e=>{
      e.stopPropagation();
      document.querySelectorAll('.dd.open').forEach(o=>{ if(o!==dd) o.classList.remove('open'); });
      dd.classList.toggle('open');
    });
  });
  document.addEventListener('click', ()=>{
    document.querySelectorAll('.dd.open').forEach(o=>o.classList.remove('open'));
  });

  // --- Card hover + click glow ---
  document.querySelectorAll('.card').forEach(card=>{
    card.addEventListener('click', ()=>{
      document.querySelectorAll('.card.glow').forEach(c=>{ if(c!==card) c.classList.remove('glow'); });
      card.classList.toggle('glow');
    });
  });

  // --- Live search ---
  const input = document.getElementById('searchInput');
  const results = document.getElementById('searchResults');
  const searchBox = document.getElementById('searchBox');
  if (input && results){
    let t;
    const render = (data, q) => {
      const sec = (title, items, render) => {
        if (!items || !items.length) return '';
        return '<div class="sr-head">'+title+'</div>' +
          items.map(render).join('');
      };
      const html =
        sec('Pelanggan', data.pelanggan, p =>
          '<a class="sr-item" href="pelanggan.php"><i data-lucide="user"></i><span><strong>'+esc(p.nama_lengkap)+'</strong><small>'+esc(p.no_wa||'')+'</small></span></a>') +
        sec('Alat Musik', data.alat, a =>
          '<a class="sr-item" href="alat.php"><i data-lucide="music-2"></i><span><strong>'+esc(a.nama_alat)+'</strong><small>'+esc(a.kategori||'')+' · '+esc(a.status||'')+'</small></span></a>') +
        sec('Transaksi', data.transaksi, r =>
          '<a class="sr-item" href="transaksi.php"><i data-lucide="clipboard-list"></i><span><strong>#TRX-'+String(r.id).padStart(4,'0')+'</strong><small>'+esc(r.nama_lengkap)+' · '+esc(r.nama_alat)+'</small></span></a>');
      results.innerHTML = html || '<div class="sr-empty">Tidak ada hasil untuk "'+esc(q)+'"</div>';
      if (window.lucide) lucide.createIcons();
      searchBox.classList.add('open');
    };
    const esc = s => String(s||'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    input.addEventListener('input', ()=>{
      clearTimeout(t);
      const q = input.value.trim();
      if (!q){ results.innerHTML=''; searchBox.classList.remove('open'); return; }
      t = setTimeout(()=>{
        fetch('search.php?q=' + encodeURIComponent(q))
          .then(r=>r.json()).then(d=>render(d,q))
          .catch(()=>{ results.innerHTML='<div class="sr-empty">Pencarian gagal.</div>'; searchBox.classList.add('open'); });
      }, 180);
    });
    input.addEventListener('focus', ()=>{ if (results.innerHTML) searchBox.classList.add('open'); });
    document.addEventListener('click', e=>{
      if (!searchBox.contains(e.target)) searchBox.classList.remove('open');
    });
  }
})();
