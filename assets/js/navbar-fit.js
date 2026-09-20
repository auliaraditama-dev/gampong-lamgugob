(()=>{
  const body=document.body;
  const wrap=document.querySelector('.nav-wrap');
  const brand=wrap?.querySelector('.brand');
  const nav=wrap?.querySelector('.desktop-nav');
  const actions=wrap?.querySelector('.nav-actions');
  if(!body||!wrap||!brand||!nav||!actions)return;

  let frame=0;
  let pending=false;
  let lastMode='';

  const isHidden=el=>!el||getComputedStyle(el).display==='none'||el.hidden;

  const hasOverlap=()=>{
    if(isHidden(nav))return false;
    const w=wrap.getBoundingClientRect();
    const b=brand.getBoundingClientRect();
    const n=nav.getBoundingClientRect();
    const a=actions.getBoundingClientRect();
    const available=Math.max(0,w.width);
    const scrollOverflow=wrap.scrollWidth>available+2||nav.scrollWidth>nav.clientWidth+2;
    const collision=b.right>n.left-6||n.right>a.left-6;
    const bounds=b.left<w.left-1||a.right>w.right+1;
    return scrollOverflow||collision||bounds;
  };

  const setMode=mode=>{
    if(lastMode===mode)return;
    lastMode=mode;
    body.classList.toggle('nav-fit-compact',mode==='compact'||mode==='mobile');
    body.classList.toggle('nav-fit-mobile',mode==='mobile');
  };

  const measure=()=>{
    pending=false;
    cancelAnimationFrame(frame);
    frame=requestAnimationFrame(()=>{
      setMode('normal');
      if(innerWidth<=1080){setMode('mobile');return;}
      requestAnimationFrame(()=>{
        if(!hasOverlap())return;
        setMode('compact');
        requestAnimationFrame(()=>{
          if(hasOverlap())setMode('mobile');
        });
      });
    });
  };

  const schedule=()=>{
    if(pending)return;
    pending=true;
    requestAnimationFrame(measure);
  };

  addEventListener('resize',schedule,{passive:true});
  addEventListener('orientationchange',schedule,{passive:true});
  addEventListener('pageshow',schedule,{passive:true});
  addEventListener('portal:fontscale',schedule);

  if(document.fonts?.ready)document.fonts.ready.then(schedule).catch(()=>{});
  if('ResizeObserver' in window){
    const ro=new ResizeObserver(schedule);
    ro.observe(wrap);
    ro.observe(brand);
    ro.observe(nav);
    ro.observe(actions);
  }
  if('MutationObserver' in window){
    const mo=new MutationObserver(schedule);
    mo.observe(wrap,{subtree:true,childList:true,characterData:true,attributes:true,attributeFilter:['class','hidden','style','href','aria-hidden']});
    mo.observe(document.documentElement,{attributes:true,attributeFilter:['style','data-theme']});
  }

  ['fontDecrease','fontReset','fontIncrease'].forEach(id=>document.getElementById(id)?.addEventListener('click',()=>setTimeout(schedule,0)));
  schedule();
})();
