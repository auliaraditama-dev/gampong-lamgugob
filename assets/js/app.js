(() => {
  const API = 'api.php';
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
  const state = { data: null, searchIndex: [], tickerTimer: null, auth: { identity:null, profile:null, csrf:null, requests:[], complaints:[] } };

  const escHtml = value => String(value ?? '')
    .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;').replaceAll("'", '&#039;');

  const safeUrl = value => {
    const url = String(value ?? '').trim();
    if (!url) return '';
    if (url.startsWith('#') || url.startsWith('assets/') || url.startsWith('./') || url.startsWith('../')) return url;
    try {
      const parsed = new URL(url, location.href);
      return ['http:', 'https:'].includes(parsed.protocol) ? url : '';
    } catch { return ''; }
  };

  const mediaUrl = value => {
    const url = safeUrl(value);
    return url || '';
  };

  const numberId = value => new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(value || 0));
  const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value || 0));
  const fmtDate = value => {
    if (!value) return '';
    const d = new Date(String(value).replace(' ', 'T'));
    return Number.isNaN(d.getTime()) ? String(value) : new Intl.DateTimeFormat('id-ID', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }).format(d);
  };
  const fmtDateOnly = value => {
    if (!value) return '';
    const d = new Date(String(value).replace(' ', 'T'));
    return Number.isNaN(d.getTime()) ? String(value) : new Intl.DateTimeFormat('id-ID', { day:'2-digit', month:'short', year:'numeric' }).format(d);
  };
  const icon = (name, fallback = 'circle') => String(name || fallback).replace(/[^a-z0-9-]/gi, '') || fallback;
  const initials = value => {
    const parts = String(value || 'Portal Gampong').trim().split(/\s+/).filter(Boolean);
    return (parts.slice(0,2).map(v => v[0]).join('') || 'PG').toUpperCase();
  };

  async function apiFetch(action, options = {}) {
    const query = options.query ? `&${options.query}` : '';
    const method = String(options.method || 'GET').toUpperCase();
    const headers = new Headers(options.headers || {});
    if (state.auth.csrf && method !== 'GET') headers.set('X-CSRF-Token', state.auth.csrf);
    const response = await fetch(`${API}?action=${encodeURIComponent(action)}${query}`, { credentials:'same-origin', ...options, method, headers });
    let result;
    try { result = await response.json(); } catch { throw new Error('Respons server tidak valid.'); }
    if (!response.ok || !result.success) throw new Error(result.message || 'Permintaan gagal.');
    return result;
  }

  function initIcons() { if (window.lucide) window.lucide.createIcons(); }
  function showToast(message, iconName = 'circle-check') {
    const region = $('#toastRegion');
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `<i data-lucide="${icon(iconName)}"></i><span>${escHtml(message)}</span>`;
    region.appendChild(toast); initIcons();
    setTimeout(() => { toast.style.opacity='0'; toast.style.transform='translateY(8px)'; setTimeout(()=>toast.remove(),220); }, 4300);
  }
  function setModalState(el, open) {
    el.classList.toggle('open', open); el.setAttribute('aria-hidden', String(!open));
    document.body.classList.toggle('modal-open', $$('.modal.open,.search-modal.open,.lightbox.open').length > 0);
  }
  function text(id, value, fallback = '') { const el=$(id); if(el) el.textContent = String(value || fallback); }
  function show(el, yes=true) { if (el) el.classList.toggle('hidden-section', !yes); }

  function renderSettings(settings) {
    const village = settings.village_name || 'Portal Gampong';
    const short = settings.village_short_name || village;
    const location = [settings.district, settings.city, settings.province].filter(Boolean).join(' · ');
    const cityLocation = [settings.district, settings.city].filter(Boolean).join(', ');
    const mark = initials(short);

    const seoTitle = settings.seo_title || village;
    const seoDescription = settings.seo_description || `Portal informasi dan layanan ${village}.`;
    const canonical = new URL('./', location.href).href;
    const seoImage = mediaUrl(settings.seo_image_url || settings.logo_url || settings.hero_background_url);
    document.title = seoTitle;
    $('#metaDescription')?.setAttribute('content', seoDescription);
    $('#metaKeywords')?.setAttribute('content', settings.seo_keywords || '');
    $('#ogTitle')?.setAttribute('content', seoTitle);
    $('#ogDescription')?.setAttribute('content', seoDescription);
    $('#ogUrl')?.setAttribute('content', canonical);
    $('#ogSiteName')?.setAttribute('content', village);
    $('#canonicalLink')?.setAttribute('href', canonical);
    $('#twitterTitle')?.setAttribute('content', seoTitle);
    $('#twitterDescription')?.setAttribute('content', seoDescription);
    if (seoImage) {
      const absoluteImage = new URL(seoImage, location.href).href;
      $('#ogImage')?.setAttribute('content', absoluteImage);
      $('#twitterImage')?.setAttribute('content', absoluteImage);
    }

    text('#brandName', village); text('#footerBrandName', village); text('#copyrightName', village);
    text('#brandLocation', location, 'Data wilayah belum diisi'); text('#footerBrandLocation', location, 'Data wilayah belum diisi');
    text('#brandMark', mark); text('#footerBrandMark', mark);
    if (settings.logo_url && mediaUrl(settings.logo_url)) {
      const logo = `<img src="${escHtml(mediaUrl(settings.logo_url))}" alt="Logo ${escHtml(village)}">`;
      $('#brandMark').innerHTML = logo; $('#footerBrandMark').innerHTML = logo;
    }

    $('#topbarLocation').innerHTML = `<i data-lucide="map-pin"></i><b>${escHtml(cityLocation || 'Lokasi belum diisi')}</b>`;
    $('#topbarHours').innerHTML = `<i data-lucide="clock-3"></i><b>${escHtml(settings.office_hours || 'Jam pelayanan belum diisi')}</b>`;

    text('#heroEyebrow', settings.hero_eyebrow, 'Portal Gampong');
    text('#heroTitle', settings.hero_title, 'Informasi gampong belum diisi.');
    text('#heroSubtitle', settings.hero_subtitle, 'Lengkapi konten dari Dashboard Admin.');
    const heroBg = mediaUrl(settings.hero_background_url);
    $('#heroBg').style.backgroundImage = heroBg ? `linear-gradient(90deg,rgba(6,22,16,.95) 0%,rgba(6,22,16,.84) 48%,rgba(6,22,16,.52) 100%),url("${heroBg.replaceAll('"','%22')}")` : 'linear-gradient(135deg,#0b241a,#164b39)';

    const hasLeader = !!(settings.keuchik_name || settings.keuchik_photo_url || settings.keuchik_message);
    show($('#keuchikCard'), hasLeader);
    if (hasLeader) {
      text('#keuchikName', settings.keuchik_name, 'Nama belum diisi');
      text('#keuchikTitle', settings.keuchik_title, 'Pimpinan Gampong'); text('#keuchikBadge', settings.keuchik_title, 'Pimpinan Gampong');
      text('#keuchikMessage', settings.keuchik_message, '');
      $('#keuchikPhoto').src = mediaUrl(settings.keuchik_photo_url) || 'assets/images/geuchik-placeholder.svg';
      $('#keuchikPhoto').alt = settings.keuchik_name ? `Foto ${settings.keuchik_name}` : 'Foto pimpinan gampong';
    }

    text('#profileHeading', settings.profile_heading, village);
    text('#profileSummary', settings.profile_summary, 'Profil belum diisi.');
    text('#profileStoryTitle', settings.profile_heading, 'Profil Gampong');
    text('#profileHistory', settings.profile_history, 'Belum ada deskripsi profil yang dipublikasikan.');
    const profileImage = mediaUrl(settings.profile_image_url);
    $('#profilePhotoWrap').innerHTML = profileImage ? `<img src="${escHtml(profileImage)}" alt="Foto profil ${escHtml(village)}" loading="lazy">` : '<div class="empty-media-label">Foto profil belum diunggah</div>';
    const visionItems = [
      ['telescope','Visi',settings.vision], ['target','Misi',settings.mission], ['handshake','Nilai Pelayanan',settings.profile_values], ['shield-check','Komitmen',settings.profile_commitment]
    ].filter(([, , value]) => value);
    $('#visionGrid').innerHTML = visionItems.length ? visionItems.map(([ic,title,value]) => `<article class="info-panel"><span class="icon-box"><i data-lucide="${ic}"></i></span><h3>${escHtml(title)}</h3><p>${escHtml(value)}</p></article>`).join('') : '<div class="empty-card">Visi, misi, nilai, dan komitmen belum diisi.</div>';

    text('#governmentHeading', settings.government_heading, 'Struktur pemerintahan'); text('#governmentSummary', settings.government_summary, '');
    text('#servicesHeading', settings.services_heading, 'Layanan gampong'); text('#servicesSummary', settings.services_summary, '');
    text('#dataHeading', settings.data_heading, 'Statistik gampong'); text('#dataSummary', settings.data_summary, '');
    text('#transparencyHeading', settings.transparency_heading, 'APBG dan pembangunan'); text('#transparencySummary', settings.transparency_summary, '');
    text('#newsHeading', settings.news_heading, 'Berita, pengumuman, dan agenda'); text('#newsSummary', settings.news_summary, '');
    text('#umkmHeading', settings.umkm_heading, 'UMKM dan ekonomi lokal'); text('#umkmSummary', settings.umkm_summary, '');
    text('#galleryHeading', settings.gallery_heading, 'Dokumentasi gampong'); text('#gallerySummary', settings.gallery_summary, '');
    text('#complaintHeading', settings.complaint_heading, 'Sampaikan aspirasi atau pengaduan'); text('#complaintSummary', settings.complaint_summary, '');
    text('#faqHeading', settings.faq_heading, 'Pertanyaan umum'); text('#faqSummary', settings.faq_summary, '');
    text('#contactHeading', settings.contact_heading, 'Kontak kantor gampong'); text('#contactSummary', settings.contact_summary, '');
    text('#footerDescription', settings.footer_description, '');

    const contacts = [
      ['map-pin','Alamat',settings.office_address], ['building-2','Wilayah',location], ['mail','Email',settings.office_email], ['phone','Telepon / WhatsApp',settings.office_phone], ['clock-3','Jam Pelayanan',settings.office_hours]
    ].filter(([, , v]) => v);
    $('#contactList').innerHTML = contacts.length ? contacts.map(([ic,label,value]) => `<div><span><i data-lucide="${ic}"></i></span><div><small>${escHtml(label)}</small><strong>${escHtml(value)}</strong></div></div>`).join('') : '<div class="empty-card">Informasi kontak belum diisi.</div>';

    const actions = [];
    if (safeUrl(settings.map_direction_url)) actions.push(`<a class="btn btn-primary" href="${escHtml(safeUrl(settings.map_direction_url))}" target="_blank" rel="noopener noreferrer"><i data-lucide="navigation"></i> Petunjuk Arah</a>`);
    if (settings.office_email) actions.push(`<a class="btn btn-outline" href="mailto:${encodeURIComponent(settings.office_email)}"><i data-lucide="mail"></i> Email</a>`);
    if (settings.office_phone) actions.push(`<a class="btn btn-outline" href="tel:${escHtml(settings.office_phone.replace(/[^+\d]/g,''))}"><i data-lucide="phone"></i> Telepon</a>`);
    $('#contactActions').innerHTML = actions.join('');
    const mapUrl = safeUrl(settings.map_embed_url);
    show($('#mapCard'), !!mapUrl); if (mapUrl) $('#mapFrame').src = mapUrl;

    const categories = String(settings.complaint_categories || '').split(/\r?\n/).map(v=>v.trim()).filter(Boolean);
    $('#complaintCategory').innerHTML = categories.length ? `<option value="">Pilih kategori</option>${categories.map(v=>`<option>${escHtml(v)}</option>`).join('')}` : '<option value="">Belum ada kategori pengaduan</option>';
  }

  function renderQuickLinks(rows) {
    const root = $('#quickLinksGrid'); show($('#quickAccessSection'), rows.length > 0);
    root.innerHTML = rows.map(row => `<a class="quick-card reveal" href="${escHtml(safeUrl(row.url) || '#')}"><span><i data-lucide="${icon(row.icon,'link')}"></i></span><div><strong>${escHtml(row.title)}</strong><small>${escHtml(row.subtitle || '')}</small></div><i data-lucide="arrow-up-right"></i></a>`).join('');
  }

  function renderOfficials(rows) {
    $('#officialsGrid').innerHTML = rows.length ? rows.map((row,i) => `<article class="leader-card ${i===0?'primary':''} reveal"><span class="leader-avatar">${row.photo_url && mediaUrl(row.photo_url) ? `<img src="${escHtml(mediaUrl(row.photo_url))}" alt="${escHtml(row.name)}">` : escHtml(initials(row.name))}</span><div><span class="mini-label">${escHtml(row.position)}</span><h3>${escHtml(row.name)}</h3><p>${escHtml(row.description || '')}</p></div></article>`).join('') : '<div class="empty-card">Belum ada perangkat gampong yang dipublikasikan.</div>';
  }
  function renderInstitutions(rows) {
    $('#institutionsGrid').innerHTML = rows.length ? rows.map(row => `<article class="leader-card reveal"><span class="leader-avatar"><i data-lucide="${icon(row.icon,'users')}"></i></span><div><span class="mini-label">${escHtml(row.short_name || 'Lembaga')}</span><h3>${escHtml(row.name)}</h3><p>${escHtml(row.description || '')}</p></div></article>`).join('') : '<div class="empty-card">Belum ada lembaga gampong yang dipublikasikan.</div>';
  }

  function renderServices(rows) {
    const categories = [...new Set(rows.map(r=>r.category).filter(Boolean))];
    $('#serviceFilters').innerHTML = rows.length ? `<button class="filter-tab active" data-filter="all">Semua</button>${categories.map(c=>`<button class="filter-tab" data-filter="${escHtml(c)}">${escHtml(c)}</button>`).join('')}` : '';
    $('#servicesGrid').innerHTML = rows.length ? rows.map(row => `<article class="service-card reveal" data-category="${escHtml(row.category)}"><span class="service-icon"><i data-lucide="${icon(row.icon,'file-text')}"></i></span><span class="service-type">${escHtml(row.category)}</span><h3>${escHtml(row.name)}</h3><p>${escHtml(row.description || '')}</p><div class="service-meta">${row.estimated_time?`<span><i data-lucide="clock-3"></i>${escHtml(row.estimated_time)}</span>`:''}${Number(row.is_online)?'<span><i data-lucide="wifi"></i>Online</span>':''}</div><button class="text-button service-detail" data-service-id="${Number(row.id)}">Lihat detail <i data-lucide="arrow-right"></i></button></article>`).join('') : '<div class="empty-card">Belum ada layanan yang dipublikasikan.</div>';
    const online = rows.filter(r=>Number(r.is_online));
    show($('#onlineServiceBox'), online.length > 0);
    $('#requestService').innerHTML = online.length ? `<option value="">Pilih layanan</option>${online.map(r=>`<option value="${escHtml(r.name)}">${escHtml(r.name)}</option>`).join('')}` : '<option value="">Belum ada layanan online</option>';

    $$('#serviceFilters .filter-tab').forEach(button => button.addEventListener('click', () => {
      $$('#serviceFilters .filter-tab').forEach(b=>b.classList.remove('active')); button.classList.add('active');
      $$('#servicesGrid .service-card').forEach(card => card.classList.toggle('hidden', button.dataset.filter!=='all' && card.dataset.category!==button.dataset.filter));
    }));
    $$('.service-detail').forEach(button => button.addEventListener('click', () => openService(Number(button.dataset.serviceId))));
  }

  function openService(id) {
    const service = state.data.services.find(r=>Number(r.id)===id); if(!service) return;
    text('#serviceModalTitle', service.name);
    const requirements = service.requirements || [];
    $('#serviceModalContent').innerHTML = `${service.description?`<p>${escHtml(service.description)}</p>`:''}${service.estimated_time?`<p><strong>Estimasi:</strong> ${escHtml(service.estimated_time)}</p>`:''}<h3>Persyaratan</h3>${requirements.length?`<ul>${requirements.map(r=>`<li>${escHtml(r.requirement_text)}</li>`).join('')}</ul>`:'<p class="muted">Belum ada persyaratan yang dipublikasikan.</p>'}${service.flow_text?`<h3>Alur</h3><p>${escHtml(service.flow_text)}</p>`:''}`;
    const apply = $('#serviceApplyButton'); show(apply, Number(service.is_online)===1); apply.dataset.select = service.name;
    setModalState($('#serviceModal'), true);
  }

  function renderPopulation(rows, settings) {
    const root=$('#statsGrid');
    root.innerHTML = rows.length ? rows.map(row => `<article class="stat-card reveal"><span><i data-lucide="${icon(row.icon,'bar-chart-3')}"></i></span><strong class="counter" data-target="${Number(row.stat_value)}" data-decimals="${String(row.stat_value).includes('.')?2:0}">0</strong><small>${escHtml(row.label)}${row.unit?` · ${escHtml(row.unit)}`:''}${row.data_year?` · ${escHtml(row.data_year)}`:''}</small></article>`).join('') : '<div class="empty-card dark-empty">Belum ada statistik yang dipublikasikan.</div>';
    const featured = rows.filter(r=>Number(r.is_featured)).slice(0,3);
    $('#heroStats').innerHTML = featured.length ? featured.map(r=>`<div><strong>${escHtml(numberId(r.stat_value))}${r.unit?` ${escHtml(r.unit)}`:''}</strong><span>${escHtml(r.label)}${r.data_year?` · ${escHtml(r.data_year)}`:''}</span></div>`).join('') : '<div class="empty-inline">Statistik unggulan belum diisi.</div>';

    const male = rows.find(r=>['male','laki_laki','laki-laki'].includes(String(r.stat_key).toLowerCase()));
    const female = rows.find(r=>['female','perempuan'].includes(String(r.stat_key).toLowerCase()));
    const hasGender = male && female && Number(male.stat_value)+Number(female.stat_value)>0;
    const hasFacts=[settings.district,settings.city,settings.province,settings.postal_code,settings.keuchik_name].some(Boolean); show($('#dataPanels'),rows.length>0||hasFacts); show($('#genderChartCard'),!!hasGender);
    if (hasGender) {
      const m=Number(male.stat_value), f=Number(female.stat_value), total=m+f, pct=total?m/total*100:0;
      $('#genderDonut').style.background=`conic-gradient(#46b88d 0 ${pct}%,#d3a646 ${pct}% 100%)`;
      text('#genderTotal', numberId(total));
      $('#genderLegend').innerHTML=`<div><span class="dot dot-a"></span><b>${escHtml(male.label)}</b><strong>${escHtml(numberId(m))}</strong></div><div><span class="dot dot-b"></span><b>${escHtml(female.label)}</b><strong>${escHtml(numberId(f))}</strong></div>`;
    }
    const facts=[['Kecamatan',settings.district],['Kota/Kabupaten',settings.city],['Provinsi',settings.province],['Kode Pos',settings.postal_code],['Pimpinan',settings.keuchik_name]].filter(([,v])=>v);
    $('#factsList').innerHTML=facts.length?facts.map(([l,v])=>`<div><span>${escHtml(l)}</span><strong>${escHtml(v)}</strong></div>`).join(''):'<div class="empty-inline">Informasi wilayah belum diisi.</div>';
  }

  function renderBudget(rows) {
    if (!rows.length) { $('#budgetSummary').innerHTML='<div class="empty-card">Belum ada data APBG yang dipublikasikan.</div>'; return; }
    const year=Math.max(...rows.map(r=>Number(r.budget_year)||0)); const current=rows.filter(r=>Number(r.budget_year)===year);
    const total=current.reduce((s,r)=>s+Number(r.budget_amount||0),0), realized=current.reduce((s,r)=>s+Number(r.realization_amount||0),0);
    $('#budgetSummary').innerHTML=`<div class="card-head"><div><span class="mini-label">Tahun Anggaran</span><h3>APBG ${escHtml(year)}</h3></div><span class="status-neutral">${current.length} bidang</span></div><div class="budget-total"><span>Total Anggaran</span><strong>${escHtml(money(total))}</strong><small>Realisasi: ${escHtml(money(realized))}</small></div><div class="progress-list">${current.map(r=>{const b=Number(r.budget_amount||0), rel=Number(r.realization_amount||0), pct=b?Math.min(100,rel/b*100):0;return `<div class="progress-item"><div><span>${escHtml(r.field_name)}</span><strong>${pct.toFixed(1)}%</strong></div><div class="progress"><span style="width:${pct}%"></span></div>${r.description?`<small>${escHtml(r.description)}</small>`:''}</div>`}).join('')}</div><button class="btn btn-outline" id="downloadBudget"><i data-lucide="download"></i> Unduh APBG CSV</button>`;
    $('#downloadBudget').addEventListener('click',()=>downloadBudgetCsv(current));
  }
  function downloadBudgetCsv(rows){
    const csv=[['Tahun','Bidang','Anggaran','Realisasi','Deskripsi'],...rows.map(r=>[r.budget_year,r.field_name,r.budget_amount,r.realization_amount,r.description||''])].map(row=>row.map(v=>`"${String(v).replaceAll('"','""')}"`).join(',')).join('\n');
    const blob=new Blob([csv],{type:'text/csv;charset=utf-8'}); const url=URL.createObjectURL(blob); const a=document.createElement('a'); a.href=url;a.download='apbg.csv';a.click();URL.revokeObjectURL(url);
  }
  function renderProjects(rows){
    $('#developmentList').innerHTML=rows.length?rows.map(row=>`<article class="development-card"><span class="development-icon"><i data-lucide="construction"></i></span><div><span class="mini-label">${escHtml(row.category||'Pembangunan')}</span><h3>${escHtml(row.title)}</h3><p>${escHtml(row.description||'')}</p><div class="project-meta">${row.location?`<span>${escHtml(row.location)}</span>`:''}${row.budget_amount?`<span>${escHtml(money(row.budget_amount))}</span>`:''}</div><div class="progress"><span style="width:${Math.max(0,Math.min(100,Number(row.progress_percent||0)))}%"></span></div></div><span class="status neutral">${escHtml(row.status||`${numberId(row.progress_percent)}%`)}</span></article>`).join(''):'<div class="empty-card">Belum ada data pembangunan yang dipublikasikan.</div>';
  }

  function renderContent(posts, agendas) {
    const news=posts.filter(r=>r.type==='news'), announcements=posts.filter(r=>r.type==='announcement');
    $('#newsGrid').innerHTML=news.length?news.map(row=>`<article class="news-card reveal">${row.image_url&&mediaUrl(row.image_url)?`<div class="news-cover dynamic-cover"><img src="${escHtml(mediaUrl(row.image_url))}" alt="${escHtml(row.title)}" loading="lazy"><span>${escHtml(fmtDateOnly(row.published_at))}</span></div>`:`<div class="news-cover"><span>${escHtml(fmtDateOnly(row.published_at))}</span></div>`}<div class="news-body"><span class="news-category">${escHtml(row.category||'Berita')}</span><h3>${escHtml(row.title)}</h3><p>${escHtml(row.excerpt||'')}</p><div class="card-actions"><a class="text-button" href="post.php?slug=${encodeURIComponent(row.slug)}">Baca selengkapnya <i data-lucide="arrow-right"></i></a><button class="text-button post-detail" data-post-id="${Number(row.id)}" aria-label="Pratinjau ${escHtml(row.title)}">Pratinjau</button>${row.source_url&&safeUrl(row.source_url)?`<a href="${escHtml(safeUrl(row.source_url))}" target="_blank" rel="noopener noreferrer">Sumber <i data-lucide="arrow-up-right"></i></a>`:''}</div></div></article>`).join(''):'<div class="empty-card">Belum ada berita yang dipublikasikan.</div>';
    $('#announcementList').innerHTML=announcements.length?announcements.map(row=>`<article class="notice-item"><span class="date-box"><b>${escHtml(row.published_at?new Date(String(row.published_at).replace(' ','T')).getDate():'—')}</b><small>${escHtml(row.published_at?new Intl.DateTimeFormat('id-ID',{month:'short'}).format(new Date(String(row.published_at).replace(' ','T'))).toUpperCase():'INFO')}</small></span><div><span class="mini-label">${escHtml(row.category||'Pengumuman')}</span><h3>${escHtml(row.title)}</h3><p>${escHtml(row.excerpt||'')}</p><a class="text-button" href="post.php?slug=${encodeURIComponent(row.slug)}">Baca detail <i data-lucide="arrow-right"></i></a></div><i data-lucide="chevron-right"></i></article>`).join(''):'<div class="empty-card">Belum ada pengumuman yang dipublikasikan.</div>';
    $('#agendaGrid').innerHTML=agendas.length?agendas.map(row=>`<article class="agenda-card"><span><i data-lucide="calendar-check"></i></span><div><small>${escHtml(row.category||'Agenda')}</small><h3>${escHtml(row.title)}</h3><p>${escHtml(row.description||'')}</p><div class="agenda-meta">${row.start_at?`<b>${escHtml(fmtDate(row.start_at))}</b>`:''}${row.location?`<span>${escHtml(row.location)}</span>`:''}</div></div></article>`).join(''):'<div class="empty-card">Belum ada agenda yang dipublikasikan.</div>';
    $$('.post-detail').forEach(button=>button.addEventListener('click',()=>openPost(Number(button.dataset.postId))));
    setupTicker(announcements);
  }
  function openPost(id){
    const row=(state.data?.posts||[]).find(r=>Number(r.id)===id);if(!row)return;
    text('#postModalCategory',row.category|| (row.type==='announcement'?'Pengumuman':'Berita'));text('#postModalTitle',row.title);
    text('#postModalMeta',fmtDate(row.published_at));
    const body=String(row.content||row.excerpt||'').split(/\n{2,}/).map(p=>p.trim()).filter(Boolean);
    $('#postModalContent').innerHTML=body.length?body.map(p=>`<p>${escHtml(p).replaceAll('\n','<br>')}</p>`).join(''):'<p>Belum ada isi lengkap yang dipublikasikan.</p>';
    $('#postModalActions').innerHTML=row.source_url&&safeUrl(row.source_url)?`<a class="btn btn-outline" href="${escHtml(safeUrl(row.source_url))}" target="_blank" rel="noopener noreferrer"><i data-lucide="external-link"></i> Buka Sumber</a>`:'';
    setModalState($('#postModal'),true);initIcons();
  }
  function setupTicker(rows){
    if(state.tickerTimer) clearInterval(state.tickerTimer); show($('#announcementBar'),rows.length>0); if(!rows.length)return;
    let i=0; const ticker=$('#tickerText'); ticker.textContent=rows[0].title;
    if(rows.length>1) state.tickerTimer=setInterval(()=>{i=(i+1)%rows.length;ticker.textContent=rows[i].title;},5200);
  }

  function renderUmkm(rows){
    $('#umkmGrid').innerHTML=rows.length?rows.map(row=>`<article class="umkm-card reveal">${row.image_url&&mediaUrl(row.image_url)?`<img class="umkm-image" src="${escHtml(mediaUrl(row.image_url))}" alt="${escHtml(row.name)}" loading="lazy">`:`<span class="umkm-icon"><i data-lucide="store"></i></span>`}<span class="mini-label">${escHtml(row.category)}${Number(row.is_verified)?' · Terverifikasi':''}</span><h3>${escHtml(row.name)}</h3><p>${escHtml(row.description||'')}</p>${row.owner_name?`<small>${escHtml(row.owner_name)}</small>`:''}${row.contact?`<a class="text-button" href="tel:${escHtml(row.contact.replace(/[^+\d]/g,''))}">Hubungi <i data-lucide="phone"></i></a>`:''}</article>`).join(''):'<div class="empty-card">Belum ada UMKM yang dipublikasikan.</div>';
  }
  function renderGallery(rows){
    const root=$('#galleryGrid');
    if(!rows.length){root.classList.add('empty-gallery');root.innerHTML='<div class="empty-card">Belum ada galeri yang dipublikasikan.</div>';return}
    root.classList.remove('empty-gallery'); root.innerHTML=rows.slice(0,12).map((row,i)=>`<button class="gallery-item ${i===0?'wide':''} reveal" data-src="${escHtml(mediaUrl(row.image_url))}" data-caption="${escHtml(row.caption||row.title)}"><img src="${escHtml(mediaUrl(row.image_url))}" alt="${escHtml(row.title)}" loading="lazy"><span><b>${escHtml(row.title)}</b><small>${escHtml(fmtDateOnly(row.event_date))}</small></span></button>`).join(''); bindGalleryItems();
  }
  function renderFaq(rows){ $('#faqList').innerHTML=rows.length?rows.map(row=>`<details class="faq-item reveal"><summary>${escHtml(row.question)}<i data-lucide="plus"></i></summary><p>${escHtml(row.answer)}</p></details>`).join(''):'<div class="empty-card">Belum ada FAQ yang dipublikasikan.</div>'; }
  function renderSocial(rows){ $('#socialLinks').innerHTML=rows.map(row=>`<a href="${escHtml(safeUrl(row.url)||'#')}" target="_blank" rel="noopener noreferrer" aria-label="${escHtml(row.label||row.platform)}"><i data-lucide="${icon(row.icon,'link')}"></i><span>${escHtml(row.label||row.platform)}</span></a>`).join(''); }
  function renderExternal(rows){ $('#footerExternalLinks').innerHTML=rows.length?rows.map(row=>`<a href="${escHtml(safeUrl(row.url)||'#')}" target="_blank" rel="noopener noreferrer">${escHtml(row.label)}</a>`).join(''):'<span class="footer-empty">Belum ada tautan eksternal.</span>'; }
  function renderSources(rows){
    show($('#sourceStrip'),rows.length>0); if(!rows.length)return;
    text('#sourceSummary',rows.map(r=>r.title).slice(0,3).join(', ')+(rows.length>3?'…':''));
    $('#sourceButton').onclick=()=>alert(rows.map((r,i)=>`${i+1}. ${r.title}${r.description?`\n${r.description}`:''}${r.url?`\n${r.url}`:''}`).join('\n\n'));
  }

  function buildSearchIndex(data){
    const items=[]; const push=(title,desc,href)=>{if(title)items.push({title:String(title),desc:String(desc||''),href})};
    push(data.settings.profile_heading||'Profil','Profil gampong','#profil');
    data.services.forEach(r=>push(r.name,`${r.category} ${r.description||''}`,'#layanan'));
    data.officials.forEach(r=>push(r.name,r.position,'#pemerintahan'));
    data.institutions.forEach(r=>push(r.name,r.description,'#pemerintahan'));
    data.population.forEach(r=>push(r.label,`${r.stat_value} ${r.unit||''}`,'#data'));
    data.posts.forEach(r=>push(r.title,`${r.category||''} ${r.excerpt||''}`,'#berita'));
    data.agendas.forEach(r=>push(r.title,`${r.category||''} ${r.description||''}`,'#agenda'));
    data.umkm.forEach(r=>push(r.name,`${r.category} ${r.description||''}`,'#umkm'));
    data.faqs.forEach(r=>push(r.question,r.answer,'#faq'));
    data.projects.forEach(r=>push(r.title,r.description,'#transparansi'));
    state.searchIndex=items;
  }

  function renderAll(data){
    state.data=data; renderSettings(data.settings||{}); renderQuickLinks(data.quickLinks||[]); renderOfficials(data.officials||[]); renderInstitutions(data.institutions||[]); renderServices(data.services||[]); renderPopulation(data.population||[],data.settings||{}); renderBudget(data.budget||[]); renderProjects(data.projects||[]); renderContent(data.posts||[],data.agendas||[]); renderUmkm(data.umkm||[]); renderGallery(data.galleries||[]); renderFaq(data.faqs||[]); renderSocial(data.socialLinks||[]); renderExternal(data.externalLinks||[]); renderSources(data.sources||[]); buildSearchIndex(data); initIcons(); bindReveal(); animateCounters();
  }

  async function loadContent(){
    try { const result=await apiFetch('content',{method:'GET'}); renderAll(result.data); }
    catch(error){ showToast(`Backend belum dapat dimuat: ${error.message}`,'triangle-alert'); }
  }



  function setAuth(result={}){
    state.auth.identity=result.identity||null;
    state.auth.profile=result.profile||null;
    state.auth.csrf=result.csrf||null;
    applyAuthUi();
    prefillPublicForms();
  }

  function applyAuthUi(){
    const identity=state.auth.identity;
    const guest=!identity;
    $('#guestAuthNav')?.classList.toggle('hidden',!guest);
    $('#memberAuthNav')?.classList.toggle('hidden',guest);
    $('#mobileGuestAuth')?.classList.toggle('hidden',!guest);
    $('#mobileMemberAuth')?.classList.toggle('hidden',guest);
    if(identity){
      const hasProfile=!!state.auth.profile;
      const target=identity.home_url||'index.html';
      text('#accountNavLabel',hasProfile?(identity.name||'Akun Saya'):'Dashboard');
      text('#mobileAccountLabel',hasProfile?'Akun Saya':'Dashboard');
      const account=$('#accountButton'),mobileAccount=$('#mobileAccountButton');
      if(account){account.href=target;account.title=hasProfile?'Buka akun saya':'Buka dashboard'}
      if(mobileAccount)mobileAccount.href=target;
    }
    initIcons();
  }

  function prefillPublicForms(){
    const profile=state.auth.profile;
    if(!profile)return;
    const request=$('#requestForm'),complaint=$('#complaintForm');
    if(request){
      if(request.elements.name&&!request.elements.name.value)request.elements.name.value=profile.name||'';
      if(request.elements.nik&&!request.elements.nik.value)request.elements.nik.value=profile.nik||'';
      if(request.elements.phone&&!request.elements.phone.value)request.elements.phone.value=profile.phone||'';
      if(request.elements.address&&!request.elements.address.value)request.elements.address.value=profile.address||'';
    }
    if(complaint){
      if(complaint.elements.name&&!complaint.elements.name.value)complaint.elements.name.value=profile.name||'';
      if(complaint.elements.contact&&!complaint.elements.contact.value)complaint.elements.contact.value=profile.phone||profile.email||'';
    }
  }

  async function loadAuth(){
    try{const result=await apiFetch('auth.me',{method:'GET'});setAuth(result)}
    catch{setAuth({})}
  }

  async function logoutPortal(){
    if(!state.auth.identity)return;
    try{
      await apiFetch('auth.logout',{method:'POST',headers:{'Content-Type':'application/json'},body:'{}'});
      setAuth({});
      showToast('Anda telah keluar dari akun.','log-out');
    }catch(err){showToast(err.message,'triangle-alert')}
  }

  $('#authLogoutButton')?.addEventListener('click',logoutPortal);
  $('#mobileLogoutButton')?.addEventListener('click',()=>{
    mobileMenu?.classList.remove('open');
    logoutPortal();
  });

  const header=$('#siteHeader'), backToTop=$('#backToTop'), menuButton=$('#menuButton'), mobileMenu=$('#mobileMenu');
  window.addEventListener('scroll',()=>{header.classList.toggle('scrolled',window.scrollY>10);backToTop.classList.toggle('visible',window.scrollY>600)},{passive:true});
  backToTop.addEventListener('click',()=>window.scrollTo({top:0,behavior:'smooth'}));
  menuButton.addEventListener('click',()=>{const open=mobileMenu.classList.toggle('open');menuButton.setAttribute('aria-expanded',String(open));menuButton.innerHTML=`<i data-lucide="${open?'x':'menu'}"></i>`;initIcons()});
  $$('#mobileMenu a').forEach(link=>link.addEventListener('click',()=>{mobileMenu.classList.remove('open');menuButton.innerHTML='<i data-lucide="menu"></i>';initIcons()}));
  const themeButton=$('#themeButton');
  function applyPublicTheme(theme,persist=false){
    const next=theme==='dark'?'dark':'light';document.documentElement.dataset.theme=next;
    if(persist)localStorage.setItem('gampong-theme',next);
    const dark=next==='dark';themeButton.innerHTML=`<i data-lucide="${dark?'sun':'moon'}"></i>`;
    themeButton.setAttribute('aria-label',dark?'Gunakan tema terang':'Gunakan tema gelap');themeButton.title=dark?'Gunakan tema terang':'Gunakan tema gelap';
    const meta=document.querySelector('meta[name="theme-color"]');if(meta)meta.setAttribute('content',dark?'#09130f':'#087352');initIcons();
  }
  const savedTheme=localStorage.getItem('gampong-theme');
  const systemDark=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;
  applyPublicTheme(savedTheme==='dark'||savedTheme==='light'?savedTheme:(systemDark?'dark':'light'),false);
  themeButton.addEventListener('click',()=>applyPublicTheme(document.documentElement.dataset.theme==='dark'?'light':'dark',true));
  let fontScale=Number(localStorage.getItem('gampong-font-scale')||1); const applyFontScale=()=>{fontScale=Math.max(.9,Math.min(1.18,fontScale));document.documentElement.style.setProperty('--font-scale',fontScale);localStorage.setItem('gampong-font-scale',fontScale.toFixed(2))}; applyFontScale();
  $('#fontDecrease').addEventListener('click',()=>{fontScale-=.05;applyFontScale()});$('#fontReset').addEventListener('click',()=>{fontScale=1;applyFontScale()});$('#fontIncrease').addEventListener('click',()=>{fontScale+=.05;applyFontScale()});

  const searchModal=$('#searchModal'), siteSearch=$('#siteSearch'), searchResults=$('#searchResults');
  $('#searchButton').addEventListener('click',()=>{setModalState(searchModal,true);setTimeout(()=>siteSearch.focus(),50)}); $$('[data-close-modal]').forEach(el=>el.addEventListener('click',()=>setModalState(searchModal,false)));
  siteSearch.addEventListener('input',()=>{const q=siteSearch.value.trim().toLowerCase();if(!q){searchResults.innerHTML='<p class="empty-state">Ketik kata kunci untuk mencari data yang sudah dipublikasikan.</p>';return}const matches=state.searchIndex.filter(item=>`${item.title} ${item.desc}`.toLowerCase().includes(q)).slice(0,20);searchResults.innerHTML=matches.length?matches.map(item=>`<a class="search-result" href="${item.href}"><div><strong>${escHtml(item.title)}</strong><small>${escHtml(item.desc)}</small></div><i data-lucide="arrow-right"></i></a>`).join(''):`<p class="empty-state">Tidak ada hasil untuk “${escHtml(siteSearch.value)}”.</p>`;$$('.search-result',searchResults).forEach(link=>link.addEventListener('click',()=>setModalState(searchModal,false)));initIcons()});

  const serviceModal=$('#serviceModal'), requestModal=$('#requestModal'), statusModal=$('#statusModal'), postModal=$('#postModal');
  $$('[data-close-service]').forEach(el=>el.addEventListener('click',()=>setModalState(serviceModal,false))); $$('[data-close-request]').forEach(el=>el.addEventListener('click',()=>setModalState(requestModal,false))); $$('[data-close-status]').forEach(el=>el.addEventListener('click',()=>setModalState(statusModal,false))); $$('[data-close-post]').forEach(el=>el.addEventListener('click',()=>setModalState(postModal,false)));
  function openRequest(select=''){setModalState(serviceModal,false);prefillPublicForms();if(select)$('#requestService').value=select;setModalState(requestModal,true)}
  $('#openRequestForm').addEventListener('click',()=>openRequest()); $('#serviceApplyButton').addEventListener('click',e=>openRequest(e.currentTarget.dataset.select||'')); $('#statusButton').addEventListener('click',()=>setModalState(statusModal,true));
  $('#requestForm').addEventListener('submit',async e=>{e.preventDefault();const formEl=e.currentTarget,fd=new FormData(formEl),nik=String(fd.get('nik')||'').replace(/\D/g,'');if(!/^\d{16}$/.test(nik)){showToast('NIK harus terdiri dari 16 digit angka.','triangle-alert');return}fd.set('nik',nik);const submit=formEl.querySelector('button[type="submit"]'),old=submit.innerHTML;submit.disabled=true;submit.textContent='Mengirim...';try{const result=await apiFetch('service-request',{method:'POST',body:fd});setModalState(requestModal,false);showToast(`Pengajuan berhasil. Simpan tiket: ${result.ticket}`,'ticket-check');formEl.reset();}catch(err){showToast(err.message,'triangle-alert')}finally{submit.disabled=false;submit.innerHTML=old;initIcons()}});
  $('#complaintForm').addEventListener('submit',async e=>{e.preventDefault();const formEl=e.currentTarget,fd=new FormData(formEl),payload={name:fd.get('name'),category:fd.get('category'),contact:fd.get('contact'),message:fd.get('message'),private:fd.has('private')};const submit=formEl.querySelector('button[type="submit"]'),old=submit.innerHTML;submit.disabled=true;submit.textContent='Mengirim...';try{const result=await apiFetch('complaint',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});showToast(`Pengaduan diterima. Simpan tiket: ${result.ticket}`,'ticket-check');formEl.reset();}catch(err){showToast(err.message,'triangle-alert')}finally{submit.disabled=false;submit.innerHTML=old;initIcons()}});
  $('#statusForm').addEventListener('submit',async e=>{e.preventDefault();const ticket=String(new FormData(e.currentTarget).get('ticket')||'').trim().toUpperCase(),box=$('#statusResult');box.innerHTML='<div class="status-box">Memeriksa status...</div>';try{const result=await apiFetch('status',{method:'GET',query:`ticket=${encodeURIComponent(ticket)}`}),d=result.data,label=result.type==='service'?d.service:d.category;box.innerHTML=`<div class="status-box"><strong>${escHtml(d.ticket)}</strong><p>${escHtml(label||'')}</p><p>Status: <b>${escHtml(d.status)}</b></p>${d.admin_note?`<p>Catatan petugas: ${escHtml(d.admin_note)}</p>`:''}<p>Diperbarui: ${escHtml(fmtDate(d.updated_at))}</p></div>`}catch(err){box.innerHTML=`<div class="status-box"><strong>Tidak ditemukan</strong><p>${escHtml(err.message)}</p></div>`}});

  $$('#contentTabs button').forEach(button=>button.addEventListener('click',()=>{$$('#contentTabs button').forEach(b=>b.classList.remove('active'));button.classList.add('active');$$('.content-panel').forEach(panel=>panel.classList.toggle('active',panel.dataset.panel===button.dataset.tab))}));
  const lightbox=$('#lightbox'); function bindGalleryItems(){$$('#galleryGrid .gallery-item').forEach(item=>{if(item.dataset.bound==='1')return;item.dataset.bound='1';item.addEventListener('click',()=>{$('#lightboxImage').src=item.dataset.src;$('#lightboxImage').alt=item.dataset.caption||'Galeri';$('#lightboxCaption').textContent=item.dataset.caption||'';setModalState(lightbox,true)})})}
  $('#lightboxClose').addEventListener('click',()=>setModalState(lightbox,false));lightbox.addEventListener('click',e=>{if(e.target===lightbox)setModalState(lightbox,false)});

  let revealObserver; function bindReveal(){if(revealObserver)revealObserver.disconnect();revealObserver=new IntersectionObserver(entries=>entries.forEach(entry=>{if(entry.isIntersecting){entry.target.classList.add('visible');revealObserver.unobserve(entry.target)}}),{threshold:.08,rootMargin:'0px 0px -30px 0px'});$$('.reveal').forEach(el=>revealObserver.observe(el))}
  function animateCounters(){const observer=new IntersectionObserver(entries=>entries.forEach(entry=>{if(!entry.isIntersecting)return;const el=entry.target,target=Number(el.dataset.target||0),dec=Number(el.dataset.decimals||0),start=performance.now(),duration=850;const tick=now=>{const p=Math.min((now-start)/duration,1),v=target*(1-Math.pow(1-p,3));el.textContent=new Intl.NumberFormat('id-ID',{maximumFractionDigits:dec,minimumFractionDigits:dec&&p===1?dec:0}).format(v);if(p<1)requestAnimationFrame(tick)};requestAnimationFrame(tick);observer.unobserve(el)}),{threshold:.35});$$('.counter').forEach(el=>observer.observe(el))}

  const navObserver=new IntersectionObserver(entries=>{const visible=entries.filter(e=>e.isIntersecting).sort((a,b)=>b.intersectionRatio-a.intersectionRatio)[0];if(!visible)return;$$('.desktop-nav .nav-link').forEach(link=>link.classList.toggle('active',link.getAttribute('href')===`#${visible.target.id}`))},{rootMargin:'-25% 0px -65% 0px',threshold:[0,.1,.25]});$$('main section[id]').forEach(section=>navObserver.observe(section));

  document.addEventListener('keydown',e=>{if(e.key==='Escape'){$$('.modal.open,.search-modal.open,.lightbox.open').forEach(el=>setModalState(el,false));mobileMenu.classList.remove('open')}});
  $('#year').textContent=new Date().getFullYear();
  if('serviceWorker' in navigator && location.protocol.startsWith('http')) window.addEventListener('load',()=>navigator.serviceWorker.register('sw.js').catch(()=>{}));
  initIcons(); bindReveal(); loadContent(); loadAuth();
})();
