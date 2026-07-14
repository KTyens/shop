var e=Array.isArray(window.__CATALOG__)?window.__CATALOG__:[],t=Array.isArray(window.__PLUG_TYPES__)&&window.__PLUG_TYPES__.length?window.__PLUG_TYPES__:[{id:`uk`,label:`UK Plug`,code:`BS 1363`},{id:`eu`,label:`EU Plug`,code:`Europlug`},{id:`us`,label:`US Plug`,code:`NEMA 1-15`}],n=1200,r=`crtlu-cart-v1`,i=new URLSearchParams(window.location.search),a=i.get(`category`),o=[],s=a===`tv-box`||a===`projector`?`category:${a}`:`all`,c=``,l=`featured`,u={},d={},f={},p=b(),m=!1;function h(e){return e.flatMap(e=>e.variants.map(t=>({...t,seriesId:e.id,seriesName:e.name,brand:e.brand,tier:e.tier,category:e.category,description:e.description,image:e.image})))}function g(e){return o.find(t=>t.id===e)}function _(e){return t.find(t=>t.id===e)}function v(e){let t=_(e);return t?`${t.label}${t.code?` (${t.code})`:``}`:``}function y(e){return!!_(e)}function b(){try{let e=JSON.parse(localStorage.getItem(r)||`[]`);return Array.isArray(e)?e:[]}catch{return[]}}function x(){localStorage.setItem(r,JSON.stringify(p))}function S(e){return window.crtluApiUrl?window.crtluApiUrl(e):e}function C(e){return Math.min(...e.variants.map(e=>e.price_cents))}function w(e){if(e===`all`)return`All`;if(e.startsWith(`category:`)){let t=e.slice(9);return t===`tv-box`?`TV Boxes`:t===`premium`?`Premium`:t===`projector`?`Projectors`:t}return e.startsWith(`brand:`)?e.slice(6):e.startsWith(`tier:`)?{budget:`Budget`,"best-value":`Best Value`,main:`Main`,performance:`Performance`,flagship:`Flagship`}[e.slice(5)]||e.slice(5):e}function T(e){return[e.id,e.name,e.brand,e.category,e.tier,e.description,...e.specs?Object.values(e.specs):[],...e.variants.flatMap(e=>[e.id,e.label,e.sku,String(e.price_cents)])].join(` `).toLowerCase()}function E(){let t=c.trim().toLowerCase().split(/\s+/).filter(Boolean),n=e;s.startsWith(`category:`)?n=n.filter(e=>e.category===s.slice(9)):s.startsWith(`brand:`)?n=n.filter(e=>e.brand===s.slice(6)):s.startsWith(`tier:`)&&(n=n.filter(e=>e.tier===s.slice(5))),t.length&&(n=n.filter(e=>t.every(t=>T(e).includes(t))));let r=[...n];return l===`price-asc`&&r.sort((e,t)=>C(e)-C(t)||e.name.localeCompare(t.name)),l===`price-desc`&&r.sort((e,t)=>C(t)-C(e)||e.name.localeCompare(t.name)),l===`name-asc`&&r.sort((e,t)=>e.name.localeCompare(t.name)),r}function D(){let e=E(),n=e.reduce((e,t)=>e+t.variants.length,0);if(document.getElementById(`summary`).innerHTML=`<strong>${e.length}</strong> models / <strong>${n}</strong> configs / Filter: <strong>${w(s)}</strong>`+(c.trim()?` / Search: "${c.trim()}"`:``),!e.length){document.getElementById(`productGrid`).innerHTML=`
          <div class="empty-state">
            <div>
              <strong>No matches found</strong><br />
              Try adjusting your search or filters.
            </div>
          </div>`;return}document.getElementById(`productGrid`).innerHTML=e.map(e=>{let n=u[e.id]||e.variants[0].id,r=d[e.id]||``,i=!!f[e.id],a=e.variants.find(e=>e.id===n)||e.variants[0],o=t.map(t=>`<button class="plug-option ${t.id===r?`active`:``}" type="button" data-plug="${e.id}:${t.id}">
            <span class="plug-icon">
              <span class="plug-${t.id}-body">
                ${t.id===`uk`?`<span class="uk-pin"></span><span class="uk-pin-l"></span><span class="uk-pin-r"></span>`:``}
                ${t.id===`eu`?`<span class="eu-pin"></span><span class="eu-pin"></span>`:``}
                ${t.id===`us`?`<span class="us-pin"></span><span class="us-pin"></span><span class="us-ground"></span>`:``}
              </span>
            </span>
            <span class="plug-option-label">${t.label}</span>
            <span class="plug-option-sublabel">${t.code||``}</span>
          </button>`).join(``);return`
          <article class="product-card reveal" id="${e.id}">
            ${e.image?`<div class="product-media"><img src="${e.image}" alt="${e.name}" loading="lazy" decoding="async"></div>`:``}
            <div class="card-head">
              <div class="meta"><span>${e.brand}</span><span>${e.tier.replace(`-`,` `)}</span></div>
              <h2>${e.name}</h2>
              <p>${e.description}</p>
            </div>
            <div class="card-body">
              ${e.variants.map(t=>`
                <div class="variant-row ${t.id===a.id?`active`:``}">
                  <button type="button" data-select="${e.id}" data-variant="${t.id}">
                    <strong>${t.label}</strong>
                    <span>${t.sku}</span>
                  </button>
                  <div class="price">$${(t.price_cents/100).toFixed(2)}${t.compare_at_cents&&t.compare_at_cents!==t.price_cents?`<span class="compare">$${(t.compare_at_cents/100).toFixed(2)}</span>`:``}</div>
                </div>
              `).join(``)}
              <div class="plug-section" style="margin-top:12px;">
                <div class="plug-section-title">Plug Type <span class="plug-required">*</span></div>
                <p class="plug-helper ${i?`error`:``}">${i?`Choose a plug type before adding to cart.`:`Choose the wall plug for the destination country.`}</p>
                <div class="plug-selector plug-selector-inline">${o}</div>
              </div>
              <div class="card-foot">
                <span class="sku">Selected: ${a.sku}</span>
                <span class="card-actions">
                  <a class="secondary-button" href="/products/${e.id}/" style="padding:0 12px;font-size:12px;">Details</a>
                  <button class="primary-button" type="button" data-add="${a.id}" style="padding:0 12px;font-size:12px;">Add to Cart</button>
                </span>
              </div>
            </div>
          </article>`}).join(``),O()}function O(){let e=new IntersectionObserver(e=>{e.forEach(e=>{e.isIntersecting&&e.target.classList.add(`visible`)})},{threshold:.12});document.querySelectorAll(`.reveal:not(.visible)`).forEach(t=>e.observe(t))}function k(){let t=[...new Set(e.map(e=>e.brand))].sort(),n={"tv-box":`TV Boxes`,premium:`Premium`,projector:`Projectors`},r=[`tv-box`,`premium`,`projector`].filter(t=>e.some(e=>e.category===t)).map(e=>[`category:${e}`,n[e]||e]),i=[[`budget`,`Budget`],[`best-value`,`Best Value`],[`main`,`Main`],[`performance`,`Performance`],[`flagship`,`Flagship`]].filter(([t])=>e.some(e=>e.tier===t)),a=[[`all`,`All`],...r,...t.map(e=>[`brand:${e}`,e]),...i.map(([e,t])=>[`tier:${e}`,t])];document.getElementById(`filters`).innerHTML=a.map(([e,t])=>`<button class="filter ${e===s?`active`:``}" type="button" data-filter="${e}">${t}</button>`).join(``)}function A(){p=p.filter(e=>g(e.id)&&y(e.plug)),x();let e=p.reduce((e,t)=>e+t.qty,0),t=p.reduce((e,t)=>e+g(t.id).price_cents*t.qty,0),r=t>0?n:0,i=window.CRTLU_PHASE4,a=i?.formatMoney||(e=>`$`+(e/100).toFixed(2));document.getElementById(`cartCount`).textContent=String(e),document.getElementById(`cartSubtotal`).textContent=a(t),document.getElementById(`cartShipping`).textContent=a(t>0?r:0),document.getElementById(`cartTotal`).textContent=a(t+r),i?.renderAdjustments&&i.renderAdjustments(t,r),document.getElementById(`checkoutButton`).disabled=e===0,document.getElementById(`cartItems`).innerHTML=p.length?p.map((e,t)=>{let n=g(e.id);return n?`
              <div class="cart-item">
                <img class="cart-thumb" src="${n.image||``}" alt="${n.seriesName}" loading="lazy" decoding="async">
                <div>
                  <h3>${n.seriesName}</h3>
                  <p>${n.label} / ${n.sku}<br><strong class="cart-plug">${v(e.plug)}</strong></p>
                  <div class="quantity">
                    <button class="qty-btn" type="button" data-cart-index="${t}" data-change="-1">-</button>
                    <span>${e.qty}</span>
                    <button class="qty-btn" type="button" data-cart-index="${t}" data-change="1">+</button>
                  </div>
                  <button class="remove" type="button" data-remove-index="${t}">Remove</button>
                </div>
                <strong class="cart-price">$${(n.price_cents*e.qty/100).toFixed(2)}</strong>
              </div>`:``}).join(``):`<div class="empty">Your cart is empty.</div>`,document.querySelectorAll(`[data-cart-index]`).forEach(e=>{e.addEventListener(`click`,()=>{let t=parseInt(e.getAttribute(`data-cart-index`),10),n=parseInt(e.getAttribute(`data-change`)),r=p[t];r&&(r.qty=Math.max(1,r.qty+n),x(),A())})}),document.querySelectorAll(`[data-remove-index]`).forEach(e=>{e.addEventListener(`click`,()=>{let t=parseInt(e.getAttribute(`data-remove-index`),10);p=p.filter((e,n)=>n!==t),x(),A()})})}function j(){document.body.style.overflow=`hidden`,document.getElementById(`cartDrawer`).classList.add(`open`),document.getElementById(`cartDrawer`).setAttribute(`aria-hidden`,`false`),document.getElementById(`cartBackdrop`).classList.add(`open`)}function M(){document.body.style.overflow=``,document.getElementById(`cartDrawer`).classList.remove(`open`),document.getElementById(`cartDrawer`).setAttribute(`aria-hidden`,`true`),document.getElementById(`cartBackdrop`).classList.remove(`open`)}function N(e,t){let n=t&&d[t]?d[t]:``;if(!n){t&&(f[t]=!0,D(),document.getElementById(t)?.scrollIntoView({behavior:`smooth`,block:`center`}));return}let r=p.find(t=>t.id===e&&t.plug===n);r?r.qty+=1:p.push({id:e,qty:1,plug:n}),x(),A(),j()}function P(){let e=window.CRTLU_PHASE4;m||!e?.initControls||(m=!0,e.initControls({validateUrl:S(`/api/validate-coupon.php`),getItems:()=>p,onChange:A}),A())}async function F(){let e=document.getElementById(`checkoutStatus`);e.className=`status`,e.textContent=`Creating checkout session...`;let t=window.CRTLU_PHASE4,n=t?.checkoutMeta?t.checkoutMeta():{};if(!p.every(e=>y(e.plug))){e.className=`status error`,e.textContent=`Please choose a power adapter plug type for every item.`;return}try{let e=await fetch(S(`/api/create-checkout-session.php`),{method:`POST`,credentials:`include`,headers:{"Content-Type":`application/json`},body:JSON.stringify({items:p,...n})}),r=t?.readJsonResponse?await t.readJsonResponse(e,`Checkout API returned an empty response.`):await e.json();if(!e.ok||!r.url)throw Error(r.error||`Checkout not configured`);window.location.href=r.url}catch(t){e.className=`status error`,e.textContent=t instanceof Error?t.message:`Something went wrong`}}function I(){o=h(e),k(),D(),A(),O(),document.getElementById(`filters`).addEventListener(`click`,e=>{let t=e.target.closest(`[data-filter]`);t&&(s=t.dataset.filter,k(),D())});let t=document.getElementById(`catalogSearch`);t.addEventListener(`input`,e=>{c=e.target.value,D()}),document.getElementById(`clearSearch`).addEventListener(`click`,()=>{c=``,t.value=``,t.focus(),D()}),document.getElementById(`catalogSort`).addEventListener(`change`,e=>{l=e.target.value,D()}),document.getElementById(`productGrid`).addEventListener(`click`,e=>{let t=e.target.closest(`[data-select]`);t&&(u[t.dataset.select]=t.dataset.variant,D());let n=e.target.closest(`[data-plug]`);if(n){let[e,t]=n.dataset.plug.split(`:`);d[e]=t,f[e]=!1,D()}let r=e.target.closest(`[data-add]`);if(r){let e=r.closest(`article`)?.id;N(r.dataset.add,e||void 0)}}),document.getElementById(`openCart`).addEventListener(`click`,j),document.getElementById(`closeCart`).addEventListener(`click`,M),document.getElementById(`cartBackdrop`).addEventListener(`click`,M),document.getElementById(`checkoutButton`).addEventListener(`click`,F),P(),window.addEventListener(`crtlu:phase4-ready`,P);let n=document.getElementById(`mobileOpenCart`);n&&n.addEventListener(`click`,j),i.get(`cart`)===`open`&&j()}I();