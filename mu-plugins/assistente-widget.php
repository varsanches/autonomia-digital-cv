<?php
/**
 * Plugin Name: Autonomia Digital — Assistente (widget flutuante)
 * Description: Botão de chat em todas as páginas/artigos, ligado a /assistente/api.php.
 * Version: 1.0
 *
 * Como é um mu-plugin, carrega sozinho em todo o site. A chave da API vive no
 * servidor (o widget só fala com /assistente/api.php); nada de segredos aqui.
 */
if (!defined('ABSPATH')) { exit; }

add_action('wp_footer', function () { ?>
<style>
  #ad-assist-btn{position:fixed!important;right:20px!important;bottom:20px!important;z-index:99999!important;
    width:58px!important;height:58px!important;min-width:0!important;max-width:none!important;
    padding:0!important;margin:0!important;border:0!important;border-radius:50%!important;
    background:#2E6DB4!important;color:#fff!important;cursor:pointer!important;line-height:0!important;
    box-shadow:0 6px 20px rgba(31,42,68,.28)!important;display:flex!important;align-items:center!important;
    justify-content:center!important;}
  #ad-assist-btn:hover{background:#255c9a!important;}
  #ad-assist-btn svg{display:block!important;width:30px!important;height:30px!important;}
  #ad-assist{position:fixed;right:20px;bottom:88px;z-index:99999;width:340px;max-width:calc(100vw - 32px);
    background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 12px 34px rgba(31,42,68,.3);
    display:none;flex-direction:column;font-family:'Segoe UI',Calibri,Arial,sans-serif;}
  #ad-assist.aberto{display:flex;}
  #ad-assist .cab{background:#1F2A44;color:#fff;padding:12px 14px;}
  #ad-assist .cab b{font-size:.98rem;}
  #ad-assist .cab span{display:block;font-size:.72rem;color:#c7d2e2;}
  #ad-assist .cab .x{position:absolute;right:12px;top:10px;background:none;border:0;color:#c7d2e2;
    font-size:20px;cursor:pointer;line-height:1;}
  #ad-assist .cab{position:relative;}
  #ad-assist .msgs{padding:12px;max-height:46vh;overflow-y:auto;display:flex;flex-direction:column;gap:8px;}
  #ad-assist .m{padding:9px 12px;border-radius:11px;font-size:.9rem;line-height:1.4;max-width:90%;white-space:pre-wrap;}
  #ad-assist .m.eu{align-self:flex-end;background:#2E6DB4;color:#fff;border-bottom-right-radius:3px;}
  #ad-assist .m.ai{align-self:flex-start;background:#F2F6FB;color:#2A3647;border-bottom-left-radius:3px;}
  #ad-assist .barra{display:flex;gap:6px;padding:10px;border-top:1px solid #e6ebf2;}
  #ad-assist .barra input{flex:1;padding:9px 11px;border:1px solid #cfd8e6;border-radius:9px;font-size:.9rem;}
  #ad-assist .barra button{padding:9px 12px;border:0;border-radius:9px;background:#2E6DB4;color:#fff;
    font-weight:700;cursor:pointer;}
  #ad-assist .barra button:disabled{opacity:.55;cursor:default;}
</style>

<button id="ad-assist-btn" aria-label="Abrir assistente de dúvidas" title="Dúvidas? Pergunta-me">
  <svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <circle cx="12" cy="3" r="1.5" fill="#fff"/>
    <path d="M12 4.5V7" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
    <rect x="4" y="7.5" width="16" height="11" rx="3.5" fill="#fff"/>
    <path d="M2 12v3M22 12v3" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
    <circle cx="9.2" cy="13" r="1.7" fill="#2E6DB4"/>
    <circle cx="14.8" cy="13" r="1.7" fill="#2E6DB4"/>
    <path d="M9.5 16.3h5" stroke="#2E6DB4" stroke-width="1.6" stroke-linecap="round"/>
  </svg>
</button>

<div id="ad-assist" role="dialog" aria-label="Assistente de dúvidas">
  <div class="cab"><b>Assistente de Dúvidas</b><span>Excel · Word · Windows · Internet · e mais</span>
    <button class="x" id="ad-assist-x" aria-label="Fechar">×</button></div>
  <div class="msgs" id="ad-assist-msgs">
    <div class="m ai">Olá! 👋 Pergunta o que quiseres sobre informática — Excel, Word, Windows, internet ou o teu computador.</div>
  </div>
  <div class="barra">
    <input id="ad-assist-q" type="text" maxlength="500" placeholder="Ex.: como organizo os meus ficheiros?" autocomplete="off">
    <button id="ad-assist-send">Enviar</button>
  </div>
</div>

<script>
(function(){
  var btn=document.getElementById('ad-assist-btn'),
      box=document.getElementById('ad-assist'),
      x=document.getElementById('ad-assist-x'),
      msgs=document.getElementById('ad-assist-msgs'),
      q=document.getElementById('ad-assist-q'),
      send=document.getElementById('ad-assist-send');
  function toggle(open){ box.classList.toggle('aberto', open); if(open){ q.focus(); } }
  btn.addEventListener('click', function(){ toggle(!box.classList.contains('aberto')); });
  x.addEventListener('click', function(){ toggle(false); });
  function add(t, quem){ var d=document.createElement('div'); d.className='m '+(quem==='eu'?'eu':'ai');
    d.textContent=t; msgs.appendChild(d); msgs.scrollTop=msgs.scrollHeight; return d; }
  async function ask(){
    var t=q.value.trim(); if(!t) return;
    add(t,'eu'); q.value=''; send.disabled=true;
    var wait=add('A pensar…','ai');
    try{
      var r=await fetch('/assistente/api.php',{method:'POST',
        headers:{'Content-Type':'application/json'},body:JSON.stringify({pergunta:t})});
      var d=await r.json(); wait.textContent=d.resposta||d.erro||'Sem resposta.';
    }catch(e){ wait.textContent='Não consegui ligar ao assistente. Tenta outra vez.'; }
    finally{ send.disabled=false; q.focus(); }
  }
  send.addEventListener('click', ask);
  q.addEventListener('keydown', function(e){ if(e.key==='Enter') ask(); });
})();
</script>
<?php });
