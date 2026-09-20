(()=>{
  const NS='http://www.w3.org/2000/svg';
  const defs={
    circle:'<circle cx="12" cy="12" r="9"/>',
    house:'<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9 21v-7h6v7"/>',
    landmark:'<path d="M3 10h18"/><path d="m4 7 8-4 8 4"/><path d="M5 10v8M9 10v8M15 10v8M19 10v8"/><path d="M3 21h18M4 18h16"/>',
    file:'<path d="M6 2h8l4 4v16H6z"/><path d="M14 2v5h5M9 13h6M9 17h6"/>',
    chart:'<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
    money:'<circle cx="12" cy="12" r="9"/><path d="M16 8.5c-.8-1-2-1.5-3.6-1.5-2 0-3.4 1-3.4 2.5 0 3.8 7 1.5 7 5.4 0 1.5-1.5 2.6-3.8 2.6-1.6 0-3-.5-4-1.5M12 5v14"/>',
    news:'<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h6M7 12h10M7 16h10M16 8h1"/>',
    image:'<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-8 8"/>',
    images:'<rect x="5" y="5" width="16" height="14" rx="2"/><path d="M3 17V5a2 2 0 0 1 2-2h14"/><circle cx="11" cy="10" r="2"/><path d="m21 15-4-4-8 8"/>',
    pin:'<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
    login:'<path d="M10 17l5-5-5-5M15 12H3"/><path d="M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"/>',
    logout:'<path d="m14 8 4 4-4 4M18 12H7"/><path d="M10 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5"/>',
    user:'<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    users:'<circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M16 5a3 3 0 0 1 0 6M18 20a5 5 0 0 0-3-4.6"/>',
    search:'<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
    moon:'<path d="M20 15.5A8.5 8.5 0 0 1 8.5 4 8.5 8.5 0 1 0 20 15.5Z"/>',
    sun:'<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>',
    menu:'<path d="M4 6h16M4 12h16M4 18h16"/>',
    x:'<path d="m6 6 12 12M18 6 6 18"/>',
    left:'<path d="m15 18-6-6 6-6"/>',
    right:'<path d="m9 18 6-6-6-6"/>',
    up:'<path d="m6 15 6-6 6 6"/>',
    down:'<path d="m6 9 6 6 6-6"/>',
    external:'<path d="M14 3h7v7M21 3l-9 9"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
    megaphone:'<path d="M3 11v2a2 2 0 0 0 2 2h2l3 5h3l-2-5 8-3V6L7 9H5a2 2 0 0 0-2 2Z"/><path d="M19 9v3"/>',
    clock:'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    network:'<rect x="9" y="3" width="6" height="5" rx="1"/><rect x="3" y="16" width="6" height="5" rx="1"/><rect x="15" y="16" width="6" height="5" rx="1"/><path d="M12 8v4M6 16v-4h12v4"/>',
    play:'<circle cx="12" cy="12" r="9"/><path d="m10 8 6 4-6 4Z"/>',
    maximize:'<path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5"/>',
    send:'<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
    shield:'<path d="M12 3 20 6v6c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6Z"/><path d="m9 12 2 2 4-4"/>',
    ticket:'<path d="M3 7a2 2 0 0 0 2-2h14a2 2 0 0 0 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 0-2 2H5a2 2 0 0 0-2-2v-3a2 2 0 0 0 0-4Z"/><path d="M13 5v14"/>',
    lock:'<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
    calendar:'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 15l2 2 5-5"/>',
    building:'<path d="M4 21V6l8-3 8 3v15M9 9h1M14 9h1M9 13h1M14 13h1M9 17h6"/>',
    construction:'<path d="M2 20h20M5 20V9h14v11M8 9V5h8v4"/><path d="M7 13h10M9 5V3h6v2"/>',
    store:'<path d="M4 10v11h16V10M3 10l2-6h14l2 6"/><path d="M3 10a3 3 0 0 0 5 2 3 3 0 0 0 4 0 3 3 0 0 0 4 0 3 3 0 0 0 5-2M9 21v-6h6v6"/>',
    info:'<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/>',
    help:'<circle cx="12" cy="12" r="9"/><path d="M9.8 9a2.5 2.5 0 1 1 4.2 1.8c-1 .8-2 1.2-2 2.7M12 17h.01"/>',
    database:'<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>',
    globe:'<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
    download:'<path d="M12 3v12M7 10l5 5 5-5M4 21h16"/>',
    mail:'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    phone:'<path d="M7 3h3l2 5-2 2c1.5 3 3 4.5 6 6l2-2 5 2v3c0 1-1 2-2 2C11 21 3 13 3 5c0-1 1-2 2-2Z"/>',
    plus:'<path d="M12 5v14M5 12h14"/>',
    route:'<circle cx="6" cy="18" r="2"/><circle cx="18" cy="6" r="2"/><path d="M8 18h4a4 4 0 0 0 4-4V10"/>',
    save:'<path d="M5 3h12l4 4v14H3V3Z"/><path d="M7 3v6h10V4M8 21v-7h8v7"/>',
    share:'<circle cx="18" cy="5" r="2"/><circle cx="6" cy="12" r="2"/><circle cx="18" cy="19" r="2"/><path d="m8 11 8-5M8 13l8 5"/>',
    sliders:'<path d="M4 6h8M16 6h4M12 4v4M4 12h3M11 12h9M7 10v4M4 18h10M18 18h2M14 16v4"/>',
    warehouse:'<path d="M3 10 12 4l9 6v11H3Z"/><path d="M7 14h10v7H7z"/>',
    wifi:'<path d="M5 12a10 10 0 0 1 14 0M8 15a6 6 0 0 1 8 0M11 18a2 2 0 0 1 2 0"/><circle cx="12" cy="20" r=".5"/>',
    eye:'<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    key:'<circle cx="8" cy="15" r="4"/><path d="m11 12 9-9M16 7l2 2M14 9l2 2"/>',
    history:'<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/>',
    panels:'<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M9 9v11"/>',
    scroll:'<path d="M6 3h12v16a2 2 0 0 1-2 2H7a3 3 0 0 1-3-3h2Z"/><path d="M9 8h6M9 12h6M9 16h4"/>',
    wand:'<path d="m4 20 11-11 2 2L6 22Z"/><path d="M14 4V2M19 7h2M17 4l1.5-1.5M8 8H6M10 5 8.5 3.5"/>',
    check:'<path d="m5 12 4 4L19 6"/>',
    heart:'<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/>',
    graduation:'<path d="m2 10 10-5 10 5-10 5Z"/><path d="M6 12v5c3 2 9 2 12 0v-5M22 10v6"/>',
    mars:'<circle cx="10" cy="14" r="5"/><path d="m14 10 7-7M16 3h5v5"/>',
    venus:'<circle cx="12" cy="9" r="5"/><path d="M12 14v8M8 18h8"/>',
    dashboard:'<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
    scan:'<path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h10"/>',
    trending:'<path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
    scale:'<path d="M12 3v18M5 7h14M5 7l-3 6h6ZM19 7l-3 6h6ZM8 21h8"/>',
    percent:'<path d="M19 5 5 19"/><circle cx="7" cy="7" r="2"/><circle cx="17" cy="17" r="2"/>',
    checkcircle:'<circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/>',
    alert:'<path d="M10.3 3.6 2.6 17a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 3.6a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
    link:'<path d="M10 13a5 5 0 0 0 7.1.1l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1"/><path d="M14 11a5 5 0 0 0-7.1-.1l-2 2A5 5 0 0 0 12 20l1.1-1.1"/>',
    message:'<path d="M21 15a4 4 0 0 1-4 4H8l-5 3 1.5-5A7 7 0 0 1 3 13V8a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/>',
    map:'<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3Z"/><path d="M9 3v15M15 6v15"/>'
  };
  const resolve=name=>{
    const n=String(name||'circle').toLowerCase();
    const exact={
      'house':'house','landmark':'landmark','newspaper':'news','image':'image','images':'images','map-pin':'pin','map-pinned':'pin','navigation':'pin','log-in':'login','log-out':'logout','search':'search','search-check':'search','scan-search':'scan','scan-line':'scan','moon':'moon','sun':'sun','menu':'menu','x':'x','arrow-left':'left','arrow-right':'right','chevron-right':'right','arrow-up':'up','arrow-down':'down','arrow-up-right':'external','external-link':'external','megaphone':'megaphone','clock-3':'clock','network':'network','circle-play':'play','maximize-2':'maximize','send':'send','ticket-check':'ticket','calendar-check':'calendar','calendar-check-2':'calendar','calendar-days':'calendar','download':'download','mail':'mail','phone':'phone','plus':'plus','route':'route','save':'save','share-2':'share','sliders-horizontal':'sliders','warehouse':'warehouse','wifi':'wifi','eye':'eye','eye-off':'eye','key-round':'key','lock-keyhole':'lock','history':'history','panels-top-left':'panels','scroll-text':'scroll','wand-sparkles':'wand','circle-help':'help','info':'info','database':'database','globe-2':'globe','store':'store','construction':'construction','building-2':'building','badge-dollar-sign':'money','chart-no-axes-combined':'chart','chart-no-axes-column':'chart','bar-chart-3':'chart','pie-chart':'chart','layout-dashboard':'dashboard','file-check-2':'file','file-plus-2':'file','file-text':'file','user-plus':'users','user-round-cog':'user','user-round-check':'user','circle-user-round':'user','users-round':'users','shield-user':'shield','shield-check':'shield','badge-check':'check','circle-check':'checkcircle','triangle-alert':'alert','link':'link','message-square-plus':'message','message-square-warning':'message','heart-pulse':'heart','graduation-cap':'graduation','mars':'mars','venus':'venus','trending-up':'trending','scale':'scale','percent':'percent','map':'map'
    };
    if(exact[n])return exact[n];
    if(/shield|lock/.test(n))return 'shield';
    if(/user|contact/.test(n))return n.includes('users')?'users':'user';
    if(/file|scroll/.test(n))return 'file';
    if(/chart|dollar|budget/.test(n))return 'chart';
    if(/image|gallery/.test(n))return 'image';
    if(/map|route|navigation/.test(n))return 'pin';
    if(/calendar|agenda/.test(n))return 'calendar';
    if(/message|megaphone/.test(n))return 'megaphone';
    if(/building|warehouse|landmark/.test(n))return 'building';
    if(/search|scan/.test(n))return 'search';
    return defs[n]?n:'circle';
  };
  const createOne=node=>{
    if(!node||!node.getAttribute)return;
    const name=node.getAttribute('data-lucide');
    if(!name)return;
    const svg=document.createElementNS(NS,'svg');
    const cls=node.getAttribute('class');
    svg.setAttribute('viewBox','0 0 24 24');
    svg.setAttribute('fill','none');
    svg.setAttribute('stroke','currentColor');
    svg.setAttribute('stroke-width','2');
    svg.setAttribute('stroke-linecap','round');
    svg.setAttribute('stroke-linejoin','round');
    svg.setAttribute('aria-hidden','true');
    svg.setAttribute('focusable','false');
    svg.setAttribute('class',`lucide portal-icon lucide-${String(name).replace(/[^a-z0-9-]/gi,'')}${cls?' '+cls:''}`);
    svg.setAttribute('data-icon-name',name);
    svg.innerHTML=defs[resolve(name)]||defs.circle;
    node.replaceWith(svg);
  };
  const createIcons=(root=document)=>{
    const scope=root&&root.querySelectorAll?root:document;
    [...scope.querySelectorAll('[data-lucide]')].forEach(createOne);
    if(scope.matches&&scope.matches('[data-lucide]'))createOne(scope);
  };
  window.PortalIcons={createIcons};
  window.lucide={createIcons};
  const run=()=>createIcons(document);
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',run,{once:true});else run();
  let queued=false;
  const observer=new MutationObserver(records=>{
    if(queued)return;
    if(!records.some(r=>r.addedNodes.length))return;
    queued=true;
    queueMicrotask(()=>{queued=false;createIcons(document)});
  });
  observer.observe(document.documentElement,{childList:true,subtree:true});
})();
