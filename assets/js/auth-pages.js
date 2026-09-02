(() => {
  const API='api.php';
  const $=(s,r=document)=>r.querySelector(s);
  const $$=(s,r=document)=>[...r.querySelectorAll(s)];
  const page=document.body.dataset.authPage||'login';

  const esc=value=>String(value??'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;');
  const initials=value=>{
    const parts=String(value||'Portal Gampong').trim().split(/\s+/).filter(Boolean);
    return (parts.slice(0,2).map(v=>v[0]).join('')||'PG').toUpperCase();
  };

  function icons(){ if(window.lucide) window.lucide.createIcons(); }

  async function api(action,options={}){
    const response=await fetch(`${API}?action=${encodeURIComponent(action)}`,{credentials:'same-origin',...options});
    let result;
    try{result=await response.json()}catch{throw new Error('Respons server tidak valid.')}
    if(!response.ok||!result.success)throw new Error(result.message||'Permintaan gagal.');
    return result;
  }

  function message(text='',type=''){
    const el=$('#authMessage');
    if(!el)return;
    el.textContent=text;
    el.className=`account-message ${type}`.trim();
  }

  function themeApply(theme,persist=false){
    const next=theme==='dark'?'dark':'light';
    document.documentElement.dataset.theme=next;
    if(persist)localStorage.setItem('gampong-theme',next);
    const button=$('#pageThemeButton');
    if(button){
      button.innerHTML=`<i data-lucide="${next==='dark'?'sun':'moon'}"></i>`;
      button.title=next==='dark'?'Gunakan tema terang':'Gunakan tema gelap';
    }
    const meta=$('meta[name="theme-color"]');
    if(meta)meta.content=next==='dark'?'#09130f':'#087352';
    icons();
  }

  function safeReturn(){
    const raw=new URLSearchParams(location.search).get('return')||'';
    if(!raw)return '';
    if(raw.includes('://')||raw.startsWith('//')||raw.startsWith('javascript:'))return '';
    return raw.replace(/^\/+/,'');
  }

  async function loadBrand(){
    try{
      const result=await api('content',{method:'GET'});
      const s=result.data?.settings||{};
      const name=s.village_name||'Portal Gampong';
      const location=[s.district,s.city,s.province].filter(Boolean).join(' · ');
      $('#authBrandName').textContent=name;
      $('#authBrandLocation').textContent=location||(page==='register'?'Registrasi akun warga':'Website publik tetap dapat diakses sebagai guest');
      const mark=$('#authBrandMark');
      if(s.logo_url){
        mark.innerHTML=`<img src="${esc(s.logo_url)}" alt="Logo ${esc(name)}">`;
      }else{
        mark.textContent=initials(s.village_short_name||name);
      }
      document.title=`${page==='register'?'Daftar Warga':'Masuk'} · ${name}`;
    }catch(_){}
  }

  async function redirectIfAuthenticated(){
    try{
      const result=await api('auth.me',{method:'GET'});
      const identity=result.identity;
      if(!identity)return;
      location.replace(identity.home_url||safeReturn()||'index.html')
    }catch(_){}
  }

  $('#pageThemeButton')?.addEventListener('click',()=>{
    themeApply(document.documentElement.dataset.theme==='dark'?'light':'dark',true);
  });

  $$('[data-toggle-password]').forEach(button=>{
    button.addEventListener('click',()=>{
      const input=document.getElementById(button.dataset.togglePassword);
      if(!input)return;
      input.type=input.type==='password'?'text':'password';
      button.innerHTML=`<i data-lucide="${input.type==='password'?'eye':'eye-off'}"></i>`;
      icons();
    });
  });

  $('#loginPageForm')?.addEventListener('submit',async event=>{
    event.preventDefault();
    message('');
    const form=event.currentTarget;
    const button=form.querySelector('button[type="submit"]');
    const old=button.innerHTML;
    button.disabled=true;
    button.textContent='Memeriksa akun...';
    try{
      const fd=new FormData(form);
      const result=await api('auth.login',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({email:fd.get('email'),password:fd.get('password')})
      });
      message('Berhasil masuk. Mengalihkan...','success');
      if(result.redirect){
        location.replace(result.redirect);
      }else{
        location.replace(safeReturn()||'profile.html');
      }
    }catch(err){
      message(err.message,'error');
    }finally{
      button.disabled=false;
      button.innerHTML=old;
      icons();
    }
  });

  $('#registerPageForm')?.addEventListener('submit',async event=>{
    event.preventDefault();
    message('');
    const form=event.currentTarget;
    const fd=new FormData(form);
    const password=String(fd.get('password')||'');
    const confirmation=String(fd.get('password_confirmation')||'');
    const nik=String(fd.get('nik')||'').replace(/\D/g,'');
    if(password!==confirmation){message('Konfirmasi password tidak sama.','error');return}
    if(nik&&!/^\d{16}$/.test(nik)){message('NIK harus 16 digit atau dikosongkan.','error');return}
    const button=form.querySelector('button[type="submit"]');
    const old=button.innerHTML;
    button.disabled=true;
    button.textContent='Membuat akun...';
    try{
      await api('auth.register',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({
          name:fd.get('name'),
          email:fd.get('email'),
          nik,
          phone:fd.get('phone'),
          address:fd.get('address'),
          password,
          password_confirmation:confirmation
        })
      });
      message('Akun warga berhasil dibuat. Membuka halaman profil...','success');
      location.replace('profile.html');
    }catch(err){
      message(err.message,'error');
    }finally{
      button.disabled=false;
      button.innerHTML=old;
      icons();
    }
  });

  const saved=localStorage.getItem('gampong-theme');
  const systemDark=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;
  themeApply(saved==='dark'||saved==='light'?saved:(systemDark?'dark':'light'));
  icons();
  loadBrand();
  redirectIfAuthenticated();
})();
