(() => {
  const API='api.php';
  const $=(s,r=document)=>r.querySelector(s);
  const $$=(s,r=document)=>[...r.querySelectorAll(s)];
  const state={identity:null,profile:null,requests:[],complaints:[],csrf:null};

  const esc=value=>String(value??'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;');
  const initials=value=>{
    const parts=String(value||'User').trim().split(/\s+/).filter(Boolean);
    return (parts.slice(0,2).map(v=>v[0]).join('')||'U').toUpperCase();
  };
  const fmt=value=>{
    if(!value)return '';
    const d=new Date(String(value).replace(' ','T'));
    return Number.isNaN(d.getTime())?String(value):new Intl.DateTimeFormat('id-ID',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}).format(d);
  };
  function icons(){if(window.lucide)window.lucide.createIcons()}

  async function api(action,options={}){
    const method=String(options.method||'GET').toUpperCase();
    const headers=new Headers(options.headers||{});
    if(state.csrf&&method!=='GET')headers.set('X-CSRF-Token',state.csrf);
    const response=await fetch(`${API}?action=${encodeURIComponent(action)}`,{credentials:'same-origin',...options,method,headers});
    let result;
    try{result=await response.json()}catch{throw new Error('Respons server tidak valid.')}
    if(!response.ok||!result.success)throw new Error(result.message||'Permintaan gagal.');
    return result;
  }

  function toast(message,type='success'){
    const region=$('#toastRegion');
    const el=document.createElement('div');
    el.className=`toast ${type==='error'?'error':''}`;
    el.textContent=message;
    region.appendChild(el);
    setTimeout(()=>{el.style.opacity='0';setTimeout(()=>el.remove(),220)},4200);
  }

  function themeApply(theme,persist=false){
    const next=theme==='dark'?'dark':'light';
    document.documentElement.dataset.theme=next;
    if(persist)localStorage.setItem('gampong-theme',next);
    const b=$('#pageThemeButton');
    if(b){b.innerHTML=`<i data-lucide="${next==='dark'?'sun':'moon'}"></i>`;b.title=next==='dark'?'Gunakan tema terang':'Gunakan tema gelap'}
    const meta=$('meta[name="theme-color"]');if(meta)meta.content=next==='dark'?'#09130f':'#087352';
    icons();
  }

  async function loadBrand(){
    try{
      const result=await api('content',{method:'GET'});
      const s=result.data?.settings||{};
      const name=s.village_name||'Portal Gampong';
      const location=[s.district,s.city,s.province].filter(Boolean).join(' · ');
      $('#profileBrandName').textContent=name;
      $('#profileBrandLocation').textContent=location||'Akun Warga';
      const mark=$('#profileBrandMark');
      if(s.logo_url)mark.innerHTML=`<img src="${esc(s.logo_url)}" alt="Logo ${esc(name)}">`;
      else mark.textContent=initials(s.village_short_name||name);
      document.title=`Akun Saya · ${name}`;
    }catch(_){}
  }

  function render(){
    const p=state.profile||{};
    $('#profileAvatar').textContent=initials(p.name||'User');
    $('#profileName').textContent=p.name||'Akun Saya';
    $('#profileIdentity').textContent=[p.email,p.phone].filter(Boolean).join(' · ');
    const verified=$('#profileVerified');
    const isVerified=Number(p.is_verified)===1;
    verified.textContent=isVerified?'Warga terverifikasi':'Belum diverifikasi';
    verified.classList.toggle('verified',isVerified);

    $('#profileSummaryGrid').innerHTML=`
      <article class="profile-summary-card"><small>Pengajuan</small><strong>${state.requests.length}</strong></article>
      <article class="profile-summary-card"><small>Pengaduan</small><strong>${state.complaints.length}</strong></article>
      <article class="profile-summary-card"><small>Status Akun</small><strong>${isVerified?'Verified':'User'}</strong></article>`;

    const form=$('#profilePageForm');
    form.elements.name.value=p.name||'';
    form.elements.email.value=p.email||'';
    form.elements.nik.value=p.nik||'';
    form.elements.phone.value=p.phone||'';
    form.elements.address.value=p.address||'';

    $('#profileRequests').innerHTML=state.requests.length
      ?state.requests.map(r=>`
        <article class="profile-history-card">
          <div>
            <h3>${esc(r.service)}</h3>
            <p>${esc(r.ticket)}</p>
            <div class="profile-history-meta"><span>Dibuat ${esc(fmt(r.created_at))}</span><span>Diperbarui ${esc(fmt(r.updated_at))}</span></div>
          </div>
          <span class="profile-history-status">${esc(r.status)}</span>
          ${r.admin_note?`<div class="profile-history-note">Catatan petugas: ${esc(r.admin_note)}</div>`:''}
        </article>`).join('')
      :'<div class="profile-empty">Belum ada pengajuan yang terkait dengan akun ini.</div>';

    $('#profileComplaints').innerHTML=state.complaints.length
      ?state.complaints.map(r=>`
        <article class="profile-history-card">
          <div>
            <h3>${esc(r.category)}</h3>
            <p>${esc(r.ticket)}</p>
            <div class="profile-history-meta"><span>Dibuat ${esc(fmt(r.created_at))}</span><span>Diperbarui ${esc(fmt(r.updated_at))}</span></div>
          </div>
          <span class="profile-history-status">${esc(r.status)}</span>
          ${r.admin_note?`<div class="profile-history-note">Catatan petugas: ${esc(r.admin_note)}</div>`:''}
        </article>`).join('')
      :'<div class="profile-empty">Belum ada pengaduan yang terkait dengan akun ini.</div>';

    $('#profileLoading').hidden=true;
    $('#profileApp').hidden=false;
    icons();
  }

  async function boot(){
    try{
      const auth=await api('auth.me',{method:'GET'});
      if(!auth.identity){location.replace('login.html?return=profile.html');return}
      if(!auth.profile){location.replace(auth.identity.home_url||'index.html');return}
      state.identity=auth.identity;
      state.profile=auth.profile;
      state.csrf=auth.csrf||null;
      const account=await api('user.account',{method:'GET'});
      state.profile=account.profile||state.profile;
      state.requests=account.requests||[];
      state.complaints=account.complaints||[];
      state.csrf=account.csrf||state.csrf;
      render();
    }catch(err){
      $('#profileLoading').innerHTML=`<div class="profile-empty"><strong>Profil tidak dapat dimuat.</strong><br>${esc(err.message)}<br><br><a class="btn btn-primary" href="login.html">Masuk kembali</a></div>`;
    }
  }

  $('#profilePageForm')?.addEventListener('submit',async event=>{
    event.preventDefault();
    const form=event.currentTarget;
    const fd=new FormData(form);
    const nik=String(fd.get('nik')||'').replace(/\D/g,'');
    if(nik&&!/^\d{16}$/.test(nik)){toast('NIK harus 16 digit atau dikosongkan.','error');return}
    const button=form.querySelector('button[type="submit"]'),old=button.innerHTML;
    button.disabled=true;button.textContent='Menyimpan...';
    try{
      const result=await api('user.profile-save',{
        method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({name:fd.get('name'),email:fd.get('email'),nik,phone:fd.get('phone'),address:fd.get('address')})
      });
      state.profile=result.profile;
      state.identity=result.identity||state.identity;
      render();
      toast('Profil berhasil diperbarui.');
    }catch(err){toast(err.message,'error')}
    finally{button.disabled=false;button.innerHTML=old;icons()}
  });

  $('#profilePasswordForm')?.addEventListener('submit',async event=>{
    event.preventDefault();
    const form=event.currentTarget,fd=new FormData(form);
    if(fd.get('new_password')!==fd.get('password_confirmation')){toast('Konfirmasi password tidak sama.','error');return}
    const button=form.querySelector('button[type="submit"]'),old=button.innerHTML;
    button.disabled=true;button.textContent='Mengubah...';
    try{
      await api('user.change-password',{
        method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({current_password:fd.get('current_password'),new_password:fd.get('new_password'),password_confirmation:fd.get('password_confirmation')})
      });
      form.reset();
      toast('Password berhasil diubah.');
    }catch(err){toast(err.message,'error')}
    finally{button.disabled=false;button.innerHTML=old;icons()}
  });

  $('#profileLogoutButton')?.addEventListener('click',async()=>{
    try{
      await api('auth.logout',{method:'POST',headers:{'Content-Type':'application/json'},body:'{}'});
      location.replace('index.html');
    }catch(err){toast(err.message,'error')}
  });

  $('#pageThemeButton')?.addEventListener('click',()=>themeApply(document.documentElement.dataset.theme==='dark'?'light':'dark',true));

  const sections=$$('.profile-section[id]');
  const links=$$('#profileNav a');
  if('IntersectionObserver' in window){
    const observer=new IntersectionObserver(entries=>{
      const visible=entries.filter(e=>e.isIntersecting).sort((a,b)=>b.intersectionRatio-a.intersectionRatio)[0];
      if(!visible)return;
      links.forEach(link=>link.classList.toggle('active',link.getAttribute('href')===`#${visible.target.id}`));
    },{rootMargin:'-18% 0px -70% 0px',threshold:[0,.1,.3]});
    sections.forEach(s=>observer.observe(s));
  }

  const saved=localStorage.getItem('gampong-theme');
  const systemDark=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;
  themeApply(saved==='dark'||saved==='light'?saved:(systemDark?'dark':'light'));
  icons();
  loadBrand();
  boot();
})();
