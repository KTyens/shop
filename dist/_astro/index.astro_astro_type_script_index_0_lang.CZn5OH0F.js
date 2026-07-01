var e=`crtlu-cart-v1`,t=[],n=`all`,r=``,i=`featured`,a={},o=l();function s(e){return e.flatMap(e=>e.variants.map(t=>({...t,seriesId:e.id,seriesName:e.name,brand:e.brand,tier:e.tier,category:e.category,description:e.description})))}function c(e){return t.find(t=>t.id===e)}function l(){try{let t=JSON.parse(localStorage.getItem(e)||`[]`);return Array.isArray(t)?t:[]}catch{return[]}}function u(){localStorage.setItem(e,JSON.stringify(o))}function d(e){return Math.min(...e.variants.map(e=>e.price_cents))}function f(e){if(e===`all`)return`All`;if(e.startsWith(`category:`)){let t=e.slice(9);return t===`tv-box`?`TV Boxes`:t===`premium`?`Premium`:t===`projector`?`Projectors`:t}return e.startsWith(`brand:`)?e.slice(6):e.startsWith(`tier:`)?{budget:`Budget`,"best-value":`Best Value`,main:`Main`,performance:`Performance`,flagship:`Flagship`}[e.slice(5)]||e.slice(5):e}function p(e){return[e.id,e.name,e.brand,e.category,e.tier,e.description,...e.specs?Object.values(e.specs):[],...e.variants.flatMap(e=>[e.id,e.label,e.sku,String(e.price_cents)])].join(` `).toLowerCase()}function m(){let e=r.trim().toLowerCase().split(/\s+/).filter(Boolean),t=catalogData;n.startsWith(`category:`)?t=t.filter(e=>e.category===n.slice(9)):n.startsWith(`brand:`)?t=t.filter(e=>e.brand===n.slice(6)):n.startsWith(`tier:`)&&(t=t.filter(e=>e.tier===n.slice(5))),e.length&&(t=t.filter(t=>e.every(e=>p(t).includes(e))));let a=[...t];return i===`price-asc`&&a.sort((e,t)=>d(e)-d(t)||e.name.localeCompare(t.name)),i===`price-desc`&&a.sort((e,t)=>d(t)-d(e)||e.name.localeCompare(t.name)),i===`name-asc`&&a.sort((e,t)=>e.name.localeCompare(t.name)),a}function h(){let e=m(),t=e.reduce((e,t)=>e+t.variants.length,0);if(document.getElementById(`summary`).innerHTML=`<strong>${e.length}</strong> models / <strong>${t}</strong> configs / Filter: <strong>${f(n)}</strong>`+(r.trim()?` / Search: "${r.trim()}"`:``),!e.length){document.getElementById(`productGrid`).innerHTML=`
          <div class="empty-state">
            <div>
              <strong>No matches found</strong><br />
              Try adjusting your search or filters.
            </div>
          </div>`;return}document.getElementById(`productGrid`).innerHTML=e.map(e=>{let t=a[e.id]||e.variants[0].id,n=e.variants.find(e=>e.id===t)||e.variants[0];return`
          <article class="product-card reveal" id="${e.id}">
            ${e.image?`<div class="product-media"><img src="${e.image}" alt="${e.name}"></div>`:``}
            <div class="card-head">
              <div class="meta"><span>${e.brand}</span><span>${e.tier.replace(`-`,` `)}</span></div>
              <h2>${e.name}</h2>
              <p>${e.description}</p>
            </div>
            <div class="card-body">
              ${e.variants.map(t=>`
                <div class="variant-row ${t.id===n.id?`active`:``}">
                  <button type="button" data-select="${e.id}" data-variant="${t.id}">
                    <strong>${t.label}</strong>
                    <span>${t.sku}</span>
                  </button>
                  <div class="price">$${(t.price_cents/100).toFixed(2)}${t.compare_at_cents&&t.compare_at_cents!==t.price_cents?`<span class="compare">$${(t.compare_at_cents/100).toFixed(2)}</span>`:``}</div>
                </div>
              `).join(``)}
              <div class="card-foot">
                <span class="sku">Selected: ${n.sku}</span>
                <span class="card-actions">
                  <a class="secondary-button" href="${e.detail_url||`/products/${e.id}/`}" style="padding:0 12px;font-size:12px;">Details</a>
                  <button class="primary-button" type="button" data-add="${n.id}" style="padding:0 12px;font-size:12px;">Add to Cart</button>
                </span>
              </div>
            </div>
          </article>`}).join(``),g()}function g(){let e=new IntersectionObserver(e=>{e.forEach(e=>{e.isIntersecting&&e.target.classList.add(`visible`)})},{threshold:.12});document.querySelectorAll(`.reveal:not(.visible)`).forEach(t=>e.observe(t))}function _(){let e=[...new Set(catalogData.map(e=>e.brand))].sort(),t={"tv-box":`TV Boxes`,premium:`Premium`,projector:`Projectors`},r=[`tv-box`,`premium`,`projector`].filter(e=>catalogData.some(t=>t.category===e)).map(e=>[`category:${e}`,t[e]||e]),i=[[`budget`,`Budget`],[`best-value`,`Best Value`],[`main`,`Main`],[`performance`,`Performance`],[`flagship`,`Flagship`]].filter(([e])=>catalogData.some(t=>t.tier===e)),a=[[`all`,`All`],...r,...e.map(e=>[`brand:${e}`,e]),...i.map(([e,t])=>[`tier:${e}`,t])];document.getElementById(`filters`).innerHTML=a.map(([e,t])=>`<button class="filter ${e===n?`active`:``}" type="button" data-filter="${e}">${t}</button>`).join(``)}function v(){o=o.filter(e=>c(e.id)),u();let e=o.reduce((e,t)=>e+t.qty,0),t=o.reduce((e,t)=>e+c(t.id).price_cents*t.qty,0),n=t>0?1200:0;document.getElementById(`cartCount`).textContent=String(e),document.getElementById(`cartSubtotal`).textContent=`$`+(t/100).toFixed(2),document.getElementById(`cartShipping`).textContent=t>0?`$12.00`:`$0.00`,document.getElementById(`cartTotal`).textContent=`$`+((t+n)/100).toFixed(2),document.getElementById(`checkoutButton`).disabled=e===0,document.getElementById(`cartItems`).innerHTML=o.length?o.map(e=>{let t=c(e.id);return t?`
              <div class="cart-item">
                <div>
                  <h3>${t.seriesName}</h3>
                  <p>${t.label} / ${t.sku}</p>
                  <div class="quantity">
                    <button class="qty-btn" type="button" data-qty="${t.id}" data-change="-1">-</button>
                    <span>${e.qty}</span>
                    <button class="qty-btn" type="button" data-qty="${t.id}" data-change="1">+</button>
                  </div>
                  <button class="remove" type="button" data-remove="${t.id}">Remove</button>
                </div>
                <strong class="cart-price">$${(t.price_cents*e.qty/100).toFixed(2)}</strong>
              </div>`:``}).join(``):`<div class="empty">Your cart is empty.</div>`,document.querySelectorAll(`[data-qty]`).forEach(e=>{e.addEventListener(`click`,()=>{let t=e.getAttribute(`data-qty`),n=parseInt(e.getAttribute(`data-change`)),r=o.find(e=>e.id===t);r&&(r.qty=Math.max(1,r.qty+n),u(),v())})}),document.querySelectorAll(`[data-remove]`).forEach(e=>{e.addEventListener(`click`,()=>{let t=e.getAttribute(`data-remove`);o=o.filter(e=>e.id!==t),u(),v()})})}function y(){document.body.style.overflow=`hidden`,document.getElementById(`cartDrawer`).classList.add(`open`),document.getElementById(`cartDrawer`).setAttribute(`aria-hidden`,`false`),document.getElementById(`cartBackdrop`).classList.add(`open`)}function b(){document.body.style.overflow=``,document.getElementById(`cartDrawer`).classList.remove(`open`),document.getElementById(`cartDrawer`).setAttribute(`aria-hidden`,`true`),document.getElementById(`cartBackdrop`).classList.remove(`open`)}function x(e){let t=o.find(t=>t.id===e);t?t.qty+=1:o.push({id:e,qty:1}),u(),v(),y()}async function S(){let e=document.getElementById(`checkoutStatus`);e.className=`status`,e.textContent=`Creating checkout session...`;try{let e=await fetch(`/api/create-checkout-session.php`,{method:`POST`,headers:{"Content-Type":`application/json`},body:JSON.stringify({items:o})}),t=await e.json();if(!e.ok||!t.url)throw Error(t.error||`Checkout not configured`);window.location.href=t.url}catch(t){e.className=`status error`,e.textContent=t instanceof Error?t.message:`Something went wrong`}}function C(){t=s(catalogData),_(),h(),v(),g(),document.getElementById(`filters`).addEventListener(`click`,e=>{let t=e.target.closest(`[data-filter]`);t&&(n=t.dataset.filter,_(),h())});let e=document.getElementById(`catalogSearch`);e.addEventListener(`input`,e=>{r=e.target.value,h()}),document.getElementById(`clearSearch`).addEventListener(`click`,()=>{r=``,e.value=``,e.focus(),h()}),document.getElementById(`catalogSort`).addEventListener(`change`,e=>{i=e.target.value,h()}),document.getElementById(`productGrid`).addEventListener(`click`,e=>{let t=e.target.closest(`[data-select]`);t&&(a[t.dataset.select]=t.dataset.variant,h());let n=e.target.closest(`[data-add]`);n&&x(n.dataset.add)}),document.getElementById(`openCart`).addEventListener(`click`,y),document.getElementById(`closeCart`).addEventListener(`click`,b),document.getElementById(`cartBackdrop`).addEventListener(`click`,b),document.getElementById(`checkoutButton`).addEventListener(`click`,S);let o=document.getElementById(`mobileOpenCart`);o&&o.addEventListener(`click`,y)}C();