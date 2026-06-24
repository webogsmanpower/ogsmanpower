import{D as L,r as p,u as U,aD as X,j as e,X as ee,h as te,aE as D,ah as q,a as O,b as se,L as F,aC as ae,R as ne}from"./index-BFWN3IEs.js";import{u as ie}from"./useDocumentTitle-IxwdvZQC.js";import{a as N}from"./index-DDPWRUIH.js";import{S as V}from"./search-CBaDR9rY.js";import{L as Y}from"./loader-circle-umOKLW_1.js";import{B as J}from"./briefcase-CXLbMjlE.js";import{B}from"./building-2-Bwr2jYdf.js";import{U as K}from"./user-BfDxdEEC.js";import{T as oe}from"./triangle-alert-BhPs5Ebh.js";import"./index-Ch2N3mHn.js";import{B as k}from"./button-CEKYObrG.js";import{C,a as I,b as S,d as M,c as E}from"./card-C_SunAGs.js";import{S as j}from"./switch-R7_rNec5.js";import{L as y}from"./label-Dz_I2qP0.js";import{D as re,a as le,b as ce,c as de,d as me}from"./dialog-BOvlMCy-.js";import{S as z}from"./settings-B3Rknkx5.js";import{L as pe}from"./layout-dashboard-Dm3EMl8l.js";import{U as $}from"./user-plus-2gCBvwbP.js";import{U as _}from"./users-Cw81e-x_.js";import{R as A}from"./rectangle-ellipsis-CqNb4b36.js";import{C as P}from"./crown-D4WQfel7.js";import{P as xe}from"./package-BC9pxhcP.js";import{T as he}from"./trending-up-OWmNsAWR.js";import{C as ue}from"./credit-card-yZeYFh0E.js";import{D as fe}from"./dollar-sign-DSt_O_YV.js";import{S as R}from"./shield-B49G5FWl.js";import{U as ge}from"./user-cog-D9cl7nYc.js";import{B as be}from"./badge-check-CtjiSpG0.js";import{S as ve}from"./scroll-text-U2iEgwAN.js";import{M as T}from"./menu-B5Sfm8Or.js";import{B as je}from"./bell-JvdaljOx.js";import{C as ye}from"./chevron-down-DcWre7cm.js";import{C as G}from"./chevron-right-C13NPNbH.js";import"./clsx-B-dksMZM.js";import"./index-BeSpi0Yv.js";const we=[["rect",{width:"8",height:"4",x:"8",y:"2",rx:"1",ry:"1",key:"tgr4d6"}],["path",{d:"M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2",key:"116196"}],["path",{d:"M12 11h4",key:"1jrz19"}],["path",{d:"M12 16h4",key:"n85exb"}],["path",{d:"M8 11h.01",key:"1dfujw"}],["path",{d:"M8 16h.01",key:"18s6g9"}]],Ne=L("clipboard-list",we);const ke=[["path",{d:"M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z",key:"zw3jo"}],["path",{d:"M2 12a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 12",key:"1wduqc"}],["path",{d:"M2 17a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 17",key:"kqbvx6"}]],Ce=L("layers",ke);const Ie=[["path",{d:"M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9 5 5 0 0 1-5 5h-2.25a1.75 1.75 0 0 0-1.4 2.8l.3.4a1.75 1.75 0 0 1-1.4 2.8z",key:"e79jfc"}],["circle",{cx:"13.5",cy:"6.5",r:".5",fill:"currentColor",key:"1okk4w"}],["circle",{cx:"17.5",cy:"10.5",r:".5",fill:"currentColor",key:"f64h9f"}],["circle",{cx:"6.5",cy:"12.5",r:".5",fill:"currentColor",key:"qy21gx"}],["circle",{cx:"8.5",cy:"7.5",r:".5",fill:"currentColor",key:"fotxhn"}]],Se=L("palette",Ie),Ee={seeker:K,employer:B,job:J},Z={seeker:"bg-blue-100 text-blue-600",employer:"bg-emerald-100 text-emerald-600",job:"bg-purple-100 text-purple-600"};function Ae({className:r}){const[o,n]=p.useState(""),[i,l]=p.useState([]),[c,v]=p.useState(!1),[g,h]=p.useState(!1),[d,m]=p.useState(-1),b=p.useRef(null),x=p.useRef(null),w=U();p.useEffect(()=>{if(o.length<2){l([]);return}const a=setTimeout(async()=>{v(!0);try{const u=await X.search(o);l(u.results||[])}catch(u){console.error("Search failed:",u),l([])}finally{v(!1)}},300);return()=>clearTimeout(a)},[o]),p.useEffect(()=>{const a=u=>{x.current&&!x.current.contains(u.target)&&h(!1)};return document.addEventListener("mousedown",a),()=>document.removeEventListener("mousedown",a)},[]);const t=p.useCallback(a=>{if(!(!g||i.length===0))switch(a.key){case"ArrowDown":a.preventDefault(),m(u=>u<i.length-1?u+1:0);break;case"ArrowUp":a.preventDefault(),m(u=>u>0?u-1:i.length-1);break;case"Enter":a.preventDefault(),d>=0&&i[d]&&s(i[d]);break;case"Escape":h(!1),b.current?.blur();break}},[g,i,d]),s=a=>{h(!1),n(""),w(a.link)},f=()=>{n(""),l([]),b.current?.focus()};return e.jsxs("div",{ref:x,className:N("relative",r),children:[e.jsxs("div",{className:"relative",children:[e.jsx(V,{className:"absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"}),e.jsx("input",{ref:b,type:"text",value:o,onChange:a=>{n(a.target.value),h(!0),m(-1)},onFocus:()=>h(!0),onKeyDown:t,placeholder:"Search users, employers, jobs...",className:"w-full pl-10 pr-10 py-2 text-sm bg-slate-100 dark:bg-slate-800 border border-transparent rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent placeholder:text-slate-400"}),o&&e.jsx("button",{onClick:f,className:"absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600",children:e.jsx(ee,{className:"h-4 w-4"})}),c&&e.jsx(Y,{className:"absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 animate-spin"})]}),g&&o.length>=2&&e.jsxs("div",{className:"absolute top-full left-0 right-0 mt-2 bg-white dark:bg-slate-900 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 max-h-96 overflow-y-auto z-50",children:[i.length===0&&!c?e.jsxs("div",{className:"p-4 text-center text-slate-500 text-sm",children:['No results found for "',o,'"']}):e.jsx("ul",{className:"py-2",children:i.map((a,u)=>{const Q=Ee[a.type]||K;return e.jsx("li",{children:e.jsxs("button",{onClick:()=>s(a),className:N("w-full px-4 py-3 flex items-center gap-3 text-left hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors",d===u&&"bg-slate-50 dark:bg-slate-800"),children:[e.jsxs("div",{className:N("w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0",a.avatar?"bg-slate-100":Z[a.type]),children:[a.avatar?e.jsx("img",{src:te(a.avatar),alt:"",className:"w-10 h-10 rounded-lg object-cover",onError:H=>{H.target.style.display="none",H.target.nextSibling.style.display="flex"}}):null,e.jsx(Q,{className:N("h-5 w-5",a.avatar&&"hidden")})]}),e.jsxs("div",{className:"flex-1 min-w-0",children:[e.jsxs("div",{className:"flex items-center gap-2",children:[e.jsx("span",{className:"font-medium text-slate-900 dark:text-white truncate",children:a.title}),e.jsx("span",{className:N("px-2 py-0.5 text-xs font-medium rounded capitalize",Z[a.type]),children:a.type})]}),e.jsx("p",{className:"text-sm text-slate-500 truncate",children:a.subtitle})]}),a.meta?.status&&e.jsx("span",{className:N("px-2 py-1 text-xs font-medium rounded capitalize",a.meta.status==="verified"&&"bg-green-100 text-green-700",a.meta.status==="pending"&&"bg-yellow-100 text-yellow-700",a.meta.status==="rejected"&&"bg-red-100 text-red-700",a.meta.status==="published"&&"bg-green-100 text-green-700",a.meta.status==="draft"&&"bg-slate-100 text-slate-700"),children:a.meta.status})]})},`${a.type}-${a.id}`)})}),e.jsxs("div",{className:"px-4 py-2 border-t border-slate-100 dark:border-slate-800 flex items-center gap-4 text-xs text-slate-400",children:[e.jsxs("span",{children:[e.jsx("kbd",{className:"px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded",children:"↑↓"})," Navigate"]}),e.jsxs("span",{children:[e.jsx("kbd",{className:"px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded",children:"Enter"})," Select"]}),e.jsxs("span",{children:[e.jsx("kbd",{className:"px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded",children:"Esc"})," Close"]})]})]})]})}function De(){const[r,o]=p.useState(!1);U();const n=D.isImpersonating(),i=D.getImpersonatedUser();if(!n||!i)return null;const l=async()=>{o(!0);try{const c=await D.stopImpersonation();window.location.href=c.redirect_to||"/admin/dashboard"}catch(c){console.error("Failed to stop impersonation:",c),localStorage.removeItem("impersonation_token"),localStorage.removeItem("impersonation_return_data"),localStorage.removeItem("impersonation_user"),window.location.href="/admin/login"}finally{o(!1)}};return e.jsx("div",{className:"fixed top-0 left-0 right-0 z-50 bg-amber-500 text-amber-950 px-4 py-2 shadow-lg",children:e.jsxs("div",{className:"max-w-7xl mx-auto flex items-center justify-between gap-4",children:[e.jsxs("div",{className:"flex items-center gap-3",children:[e.jsx(oe,{className:"h-5 w-5 flex-shrink-0"}),e.jsxs("span",{className:"text-sm font-medium",children:["You are viewing as ",e.jsx("strong",{children:i.name})," (",i.email,")",e.jsx("span",{className:"ml-2 px-2 py-0.5 bg-amber-600/30 rounded text-xs uppercase",children:i.role})]})]}),e.jsxs("button",{onClick:l,disabled:r,className:"flex items-center gap-2 px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50",children:[r?e.jsx(Y,{className:"h-4 w-4 animate-spin"}):e.jsx(q,{className:"h-4 w-4"}),"Return to Admin"]})]})})}const Fe=()=>{const[r,o]=p.useState(!1),[n,i]=p.useState({fixZIndex:!0,fixOverflow:!0,fixBackdrop:!0,fixFocus:!0,fixResponsive:!0,showDebugInfo:!1,highContrast:!1,reducedMotion:!1});p.useEffect(()=>{l()},[n]);const l=()=>{n.fixZIndex&&c(),n.fixOverflow&&v(),n.fixBackdrop&&g(),n.fixFocus&&h(),n.fixResponsive&&d(),n.highContrast?document.body.classList.add("high-contrast"):document.body.classList.remove("high-contrast"),n.reducedMotion?document.body.classList.add("reduced-motion"):document.body.classList.remove("reduced-motion")},c=()=>{const t="ui-polish-zindex-fixes";let s=document.getElementById(t);s||(s=document.createElement("style"),s.id=t,document.head.appendChild(s)),s.textContent=`
      /* Z-index fixes for dialogs and modals */
      .fixed.inset-0.z-50 { z-index: 9999 !important; }
      .fixed.inset-0.z-40 { z-index: 9998 !important; }
      .fixed.inset-0.z-30 { z-index: 9997 !important; }
      
      /* Ensure dropdown menus are above content */
      [role="menu"] { z-index: 9996 !important; }
      [role="listbox"] { z-index: 9996 !important; }
      
      /* Fix sheet/drawer z-index */
      .fixed.right-0.top-0.h-full.w- { z-index: 9995 !important; }
      .fixed.left-0.top-0.h-full.w- { z-index: 9995 !important; }
      
      /* Fix tooltip z-index */
      [role="tooltip"] { z-index: 10000 !important; }
      
      /* Fix notification z-index */
      [data-radix-toast] { z-index: 10001 !important; }
    `},v=()=>{const t="ui-polish-overflow-fixes";let s=document.getElementById(t);s||(s=document.createElement("style"),s.id=t,document.head.appendChild(s)),s.textContent=`
      /* Overflow fixes for dialogs */
      .fixed.inset-0.z-50 {
        overflow: hidden !important;
        padding: 0 !important;
      }
      
      /* Fix dialog content overflow */
      .max-h-\\[90vh\\] {
        max-height: 90vh !important;
        overflow-y: auto !important;
      }
      
      /* Fix sheet overflow */
      .transform.translate-x-0 {
        overflow-y: auto !important;
        max-height: 100vh !important;
      }
      
      /* Fix table overflow in dialogs */
      .dialog-table-container {
        overflow-x: auto !important;
        max-width: 100% !important;
      }
      
      /* Fix form overflow */
      .form-scroll-container {
        overflow-y: auto !important;
        max-height: calc(90vh - 200px) !important;
      }
    `},g=()=>{const t="ui-polish-backdrop-fixes";let s=document.getElementById(t);s||(s=document.createElement("style"),s.id=t,document.head.appendChild(s)),s.textContent=`
      /* Backdrop fixes */
      .fixed.inset-0.bg-black\\/80 {
        background-color: rgba(0, 0, 0, 0.8) !important;
        backdrop-filter: blur(4px) !important;
      }
      
      /* Ensure backdrop covers everything */
      .fixed.inset-0 {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
      }
      
      /* Fix backdrop animation */
      @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
      }
      
      .backdrop-fade {
        animation: fadeIn 0.15s ease-in-out !important;
      }
    `},h=()=>{const t="ui-polish-focus-fixes";let s=document.getElementById(t);s||(s=document.createElement("style"),s.id=t,document.head.appendChild(s)),s.textContent=`
      /* Focus management fixes */
      .focus-trap {
        outline: none !important;
      }
      
      /* Better focus indicators */
      button:focus-visible,
      input:focus-visible,
      textarea:focus-visible,
      select:focus-visible {
        outline: 2px solid #3b82f6 !important;
        outline-offset: 2px !important;
      }
      
      /* Skip to content link */
      .skip-link {
        position: absolute !important;
        top: -40px !important;
        left: 6px !important;
        background: #000 !important;
        color: #fff !important;
        padding: 8px !important;
        text-decoration: none !important;
        z-index: 10002 !important;
        border-radius: 4px !important;
      }
      
      .skip-link:focus {
        top: 6px !important;
      }
      
      /* Fix dialog focus trap */
      .dialog-content:focus {
        outline: none !important;
      }
    `},d=()=>{const t="ui-polish-responsive-fixes";let s=document.getElementById(t);s||(s=document.createElement("style"),s.id=t,document.head.appendChild(s)),s.textContent=`
      /* Responsive fixes */
      @media (max-width: 640px) {
        .dialog-content {
          width: 95vw !important;
          max-width: 95vw !important;
          margin: 2.5vh auto !important;
        }
        
        .sheet-content {
          width: 90vw !important;
          max-width: 90vw !important;
        }
        
        .grid-cols-3 {
          grid-template-columns: 1fr !important;
        }
        
        .grid-cols-2 {
          grid-template-columns: 1fr !important;
        }
      }
      
      @media (max-width: 768px) {
        .md\\:grid-cols-2 {
          grid-template-columns: 1fr !important;
        }
        
        .lg\\:grid-cols-3 {
          grid-template-columns: 1fr !important;
        }
      }
      
      /* Fix table responsiveness */
      .table-responsive {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
      }
      
      /* Fix button groups on mobile */
      .button-group-mobile {
        flex-direction: column !important;
        gap: 0.5rem !important;
      }
      
      .button-group-mobile button {
        width: 100% !important;
      }
    `},m=(t,s)=>{i(f=>({...f,[t]:s}))},b=()=>{i({fixZIndex:!0,fixOverflow:!0,fixBackdrop:!0,fixFocus:!0,fixResponsive:!0,showDebugInfo:!1,highContrast:!1,reducedMotion:!1})},x=()=>{const t=JSON.stringify(n,null,2),s="data:application/json;charset=utf-8,"+encodeURIComponent(t),f="ui-polish-settings.json",a=document.createElement("a");a.setAttribute("href",s),a.setAttribute("download",f),a.click()},w=()=>n.showDebugInfo?e.jsxs(C,{className:"mt-4",children:[e.jsx(I,{children:e.jsx(S,{className:"text-sm",children:"Debug Information"})}),e.jsxs(E,{className:"space-y-2 text-xs",children:[e.jsxs("div",{children:["Screen Size: ",window.innerWidth,"x",window.innerHeight]}),e.jsxs("div",{children:["Viewport: ",document.documentElement.clientWidth,"x",document.documentElement.clientHeight]}),e.jsxs("div",{children:["Device Pixel Ratio: ",window.devicePixelRatio]}),e.jsxs("div",{children:["Touch Support: ","ontouchstart"in window?"Yes":"No"]}),e.jsxs("div",{children:["Dark Mode: ",window.matchMedia("(prefers-color-scheme: dark)").matches?"Yes":"No"]}),e.jsxs("div",{children:["Reduced Motion: ",window.matchMedia("(prefers-reduced-motion: reduce)").matches?"Yes":"No"]})]})]}):null;return e.jsxs(e.Fragment,{children:[e.jsxs(k,{size:"sm",className:"fixed bottom-4 right-4 z-50 shadow-lg",onClick:()=>o(!0),children:[e.jsx(z,{className:"h-4 w-4 mr-2"}),"UI Settings"]}),e.jsx(re,{open:r,onOpenChange:o,children:e.jsxs(le,{className:"max-w-2xl max-h-[90vh] overflow-y-auto",children:[e.jsxs(ce,{children:[e.jsxs(de,{className:"flex items-center gap-2",children:[e.jsx(z,{className:"h-5 w-5"}),"UI Polish Settings"]}),e.jsx(me,{children:"Configure UI fixes and accessibility options for better user experience."})]}),e.jsxs("div",{className:"space-y-6",children:[e.jsxs(C,{children:[e.jsxs(I,{children:[e.jsx(S,{className:"text-base",children:"UI Fixes"}),e.jsx(M,{children:"Enable fixes for common UI issues"})]}),e.jsxs(E,{className:"space-y-4",children:[e.jsxs("div",{className:"flex items-center justify-between",children:[e.jsxs("div",{className:"space-y-0.5",children:[e.jsx(y,{children:"Fix Z-Index Issues"}),e.jsx("p",{className:"text-sm text-muted-foreground",children:"Resolve layering problems with dialogs and dropdowns"})]}),e.jsx(j,{checked:n.fixZIndex,onCheckedChange:t=>m("fixZIndex",t)})]}),e.jsxs("div",{className:"flex items-center justify-between",children:[e.jsxs("div",{className:"space-y-0.5",children:[e.jsx(y,{children:"Fix Overflow Issues"}),e.jsx("p",{className:"text-sm text-muted-foreground",children:"Handle scrolling and overflow in dialogs"})]}),e.jsx(j,{checked:n.fixOverflow,onCheckedChange:t=>m("fixOverflow",t)})]}),e.jsxs("div",{className:"flex items-center justify-between",children:[e.jsxs("div",{className:"space-y-0.5",children:[e.jsx(y,{children:"Fix Backdrop Issues"}),e.jsx("p",{className:"text-sm text-muted-foreground",children:"Improve modal backdrop appearance and behavior"})]}),e.jsx(j,{checked:n.fixBackdrop,onCheckedChange:t=>m("fixBackdrop",t)})]}),e.jsxs("div",{className:"flex items-center justify-between",children:[e.jsxs("div",{className:"space-y-0.5",children:[e.jsx(y,{children:"Fix Focus Management"}),e.jsx("p",{className:"text-sm text-muted-foreground",children:"Improve keyboard navigation and focus indicators"})]}),e.jsx(j,{checked:n.fixFocus,onCheckedChange:t=>m("fixFocus",t)})]}),e.jsxs("div",{className:"flex items-center justify-between",children:[e.jsxs("div",{className:"space-y-0.5",children:[e.jsx(y,{children:"Fix Responsive Issues"}),e.jsx("p",{className:"text-sm text-muted-foreground",children:"Handle mobile and tablet layout problems"})]}),e.jsx(j,{checked:n.fixResponsive,onCheckedChange:t=>m("fixResponsive",t)})]})]})]}),e.jsxs(C,{children:[e.jsxs(I,{children:[e.jsx(S,{className:"text-base",children:"Accessibility"}),e.jsx(M,{children:"Configure accessibility preferences"})]}),e.jsxs(E,{className:"space-y-4",children:[e.jsxs("div",{className:"flex items-center justify-between",children:[e.jsxs("div",{className:"space-y-0.5",children:[e.jsx(y,{children:"High Contrast"}),e.jsx("p",{className:"text-sm text-muted-foreground",children:"Increase contrast for better visibility"})]}),e.jsx(j,{checked:n.highContrast,onCheckedChange:t=>m("highContrast",t)})]}),e.jsxs("div",{className:"flex items-center justify-between",children:[e.jsxs("div",{className:"space-y-0.5",children:[e.jsx(y,{children:"Reduced Motion"}),e.jsx("p",{className:"text-sm text-muted-foreground",children:"Minimize animations and transitions"})]}),e.jsx(j,{checked:n.reducedMotion,onCheckedChange:t=>m("reducedMotion",t)})]})]})]}),e.jsxs(C,{children:[e.jsxs(I,{children:[e.jsx(S,{className:"text-base",children:"Debug Options"}),e.jsx(M,{children:"Development and debugging tools"})]}),e.jsx(E,{className:"space-y-4",children:e.jsxs("div",{className:"flex items-center justify-between",children:[e.jsxs("div",{className:"space-y-0.5",children:[e.jsx(y,{children:"Show Debug Info"}),e.jsx("p",{className:"text-sm text-muted-foreground",children:"Display technical information about the current view"})]}),e.jsx(j,{checked:n.showDebugInfo,onCheckedChange:t=>m("showDebugInfo",t)})]})})]}),e.jsx(w,{}),e.jsxs("div",{className:"flex justify-between pt-4",children:[e.jsx(k,{variant:"outline",onClick:b,children:"Reset to Defaults"}),e.jsxs("div",{className:"space-x-2",children:[e.jsx(k,{variant:"outline",onClick:x,children:"Export Settings"}),e.jsx(k,{onClick:()=>o(!1),children:"Apply Settings"})]})]})]})]})})]})},W=[{id:"dashboard",label:"Dashboard",icon:pe,path:"/admin/dashboard"},{id:"seekers",label:"Seekers",icon:_,children:[{label:"Create New Seeker",path:"/admin/seekers/create",icon:$},{label:"View All Seekers",path:"/admin/seekers",icon:_},{label:"Seeker Forms",path:"/admin/configuration/forms?module=seeker",icon:A},{label:"Seeker Plans",path:"/admin/plans/seeker",icon:P},{label:"Search Candidates",path:"/admin/seekers/search",icon:V}]},{id:"employers",label:"Employers",icon:B,children:[{label:"Create New Employer",path:"/admin/employers/create",icon:$},{label:"View All Employers",path:"/admin/employers",icon:B},{label:"Employer Forms",path:"/admin/configuration/forms?module=employer",icon:A},{label:"Employer Plans",path:"/admin/plans/employer",icon:P},{label:"Job Management",path:"/admin/jobs",icon:J}]},{id:"finance",label:"Finance",icon:fe,children:[{label:"All Plans",path:"/admin/finance/plans",icon:xe},{label:"Revenue",path:"/admin/finance/revenue",icon:he},{label:"Credits",path:"/admin/finance/transactions",icon:ue}]},{id:"access-control",label:"Access Control",icon:R,children:[{label:"Roles",path:"/admin/access-control/roles",icon:R},{label:"Staff",path:"/admin/access-control/staff",icon:ge},{label:"Permissions",path:"/admin/access-control/permissions",icon:be}]},{id:"configuration",label:"Configuration",icon:z,children:[{label:"Form Builder",path:"/admin/configuration/forms",icon:A},{label:"Skills",path:"/admin/configuration/skills",icon:Ce},{label:"Assessments",path:"/admin/configuration/assessments",icon:Ne},{label:"Branding",path:"/admin/configuration/branding",icon:Se},{label:"Settings",path:"/admin/configuration/settings",icon:z}]},{id:"content",label:"Content",icon:A,path:"/admin/content-management"},{id:"logs",label:"Audit Logs",icon:ve,path:"/admin/logs"}],ze=({item:r,isCollapsed:o,expandedModules:n,toggleModule:i})=>{const l=O(),c=r.children&&r.children.length>0,v=n.includes(r.id),g=r.icon,h={seekers:{active:"bg-blue-50 text-blue-700",hover:"text-blue-600 hover:bg-blue-50",childActive:"bg-blue-100 text-blue-700",childHover:"text-blue-500 hover:bg-blue-50 hover:text-blue-700",iconActive:"text-blue-600",iconHover:"text-blue-600 group-hover:text-blue-600"},employers:{active:"bg-purple-50 text-purple-700",hover:"text-purple-600 hover:bg-purple-50",childActive:"bg-purple-100 text-purple-700",childHover:"text-purple-500 hover:bg-purple-50 hover:text-purple-700",iconActive:"text-purple-600",iconHover:"text-purple-600 group-hover:text-purple-600"},default:{active:"bg-red-50 text-red-700",hover:"text-slate-600 hover:bg-slate-100",childActive:"bg-red-100 text-red-700",childHover:"text-slate-500 hover:bg-slate-100 hover:text-slate-700",iconActive:"text-red-600",iconHover:"text-slate-400 group-hover:text-slate-600"}},d=h[r.id]||h.default,m=c&&r.children.some(x=>l.pathname===x.path||l.pathname.startsWith(x.path+"/")),b=!c&&(l.pathname===r.path||l.pathname.startsWith(r.path+"/"));return c?e.jsxs("div",{className:"mb-1",children:[e.jsxs("button",{onClick:()=>i(r.id),className:`w-full flex items-center justify-between px-3 py-2.5 rounded-lg transition-all duration-200 group ${m?d.active:d.hover}`,children:[e.jsxs("div",{className:"flex items-center gap-3",children:[e.jsx(g,{className:`h-5 w-5 flex-shrink-0 ${m?d.iconActive:d.iconHover}`}),!o&&e.jsx("span",{className:"font-medium text-sm",children:r.label})]}),!o&&(v?e.jsx(ye,{className:"h-4 w-4 text-slate-400"}):e.jsx(G,{className:"h-4 w-4 text-slate-400"}))]}),!o&&v&&e.jsx("div",{className:"mt-1 ml-4 pl-4 border-l border-slate-200 space-y-1",children:r.children.map(x=>{const w=x.icon,t=l.pathname===x.path||l.pathname.startsWith(x.path+"/");return e.jsxs(F,{to:x.path,className:`flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-200 ${t?d.childActive:d.childHover}`,children:[e.jsx(w,{className:"h-4 w-4 flex-shrink-0"}),e.jsx("span",{children:x.label})]},x.path)})})]}):e.jsxs(F,{to:r.path,className:`flex items-center gap-3 px-3 py-2.5 rounded-lg mb-1 transition-all duration-200 group ${b?d.childActive:d.hover}`,children:[e.jsx(g,{className:`h-5 w-5 flex-shrink-0 ${b?d.iconActive:d.iconHover}`}),!o&&e.jsx("span",{className:"font-medium text-sm",children:r.label})]})},Me=()=>{const o=O().pathname.split("/").filter(Boolean),n=o.map((i,l)=>{const c="/"+o.slice(0,l+1).join("/");return{label:i.charAt(0).toUpperCase()+i.slice(1).replace(/-/g," "),path:c}});return e.jsx("nav",{className:"flex items-center gap-2 text-sm text-slate-500",children:n.map((i,l)=>e.jsxs(ne.Fragment,{children:[l>0&&e.jsx(G,{className:"h-4 w-4"}),l===n.length-1?e.jsx("span",{className:"font-medium text-slate-900",children:i.label}):e.jsx(F,{to:i.path,className:"hover:text-slate-700 transition-colors",children:i.label})]},i.path))})};function ut({children:r}){const o=U(),n=O(),{appName:i,logoUrl:l}=se();ie("Admin");const[c,v]=p.useState(!1),[g,h]=p.useState(!1),[d,m]=p.useState(["seekers","employers","finance"]),b=D.isImpersonating();p.useEffect(()=>{W.forEach(t=>{t.children&&t.children.some(f=>n.pathname===f.path||n.pathname.startsWith(f.path+"/"))&&!d.includes(t.id)&&m(f=>[...f,t.id])})},[n.pathname]);const x=t=>{m(s=>s.includes(t)?s.filter(f=>f!==t):[...s,t])},w=()=>{localStorage.removeItem("admin_token"),localStorage.removeItem("admin_user"),o("/admin/login")};return e.jsxs("div",{className:"min-h-screen bg-slate-50",children:[e.jsx(De,{}),g&&e.jsx("div",{className:"fixed inset-0 bg-black/50 z-40 lg:hidden",onClick:()=>h(!1)}),e.jsxs("aside",{className:`fixed top-0 left-0 h-full bg-white border-r border-slate-200 z-50 transition-all duration-300 ${c?"w-20":"w-64"} ${g?"translate-x-0":"-translate-x-full lg:translate-x-0"} ${b?"pt-12":""}`,children:[e.jsxs("div",{className:"h-16 flex items-center justify-between px-4 border-b border-slate-200",children:[e.jsxs(F,{to:"/admin/dashboard",className:"flex items-center gap-3",children:[e.jsx("div",{className:"w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center shadow-lg shadow-red-500/20 overflow-hidden",children:l?e.jsx("img",{src:l,alt:i,className:"w-full h-full object-contain p-1"}):e.jsx(R,{className:"w-5 h-5 text-white"})}),!c&&e.jsxs("div",{children:[e.jsx("span",{className:"text-lg font-bold text-slate-900",children:i?.split(" ")[0]||"Admin"}),e.jsx("span",{className:"text-xs text-slate-500 block -mt-1",children:"Admin Center"})]})]}),e.jsx("button",{onClick:()=>v(!c),className:"hidden lg:flex p-2 rounded-lg hover:bg-slate-100 transition-colors",children:e.jsx(T,{className:"h-5 w-5 text-slate-500"})})]}),e.jsx("nav",{className:"p-4 space-y-1 overflow-y-auto h-[calc(100vh-8rem)]",children:W.map(t=>e.jsx(ze,{item:t,isCollapsed:c,expandedModules:d,toggleModule:x},t.id))}),e.jsx("div",{className:"absolute bottom-0 left-0 right-0 p-4 border-t border-slate-200 bg-white",children:e.jsxs("button",{onClick:w,className:`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 hover:bg-red-50 hover:text-red-600 transition-colors ${c?"justify-center":""}`,children:[e.jsx(q,{className:"h-5 w-5"}),!c&&e.jsx("span",{className:"font-medium text-sm",children:"Logout"})]})})]}),e.jsxs("div",{className:`transition-all duration-300 ${c?"lg:ml-20":"lg:ml-64"}`,children:[e.jsx("header",{className:`sticky top-0 z-30 bg-white border-b border-slate-200 ${b?"mt-12":""}`,children:e.jsxs("div",{className:"flex items-center justify-between h-16 px-4 lg:px-8",children:[e.jsx("button",{onClick:()=>h(!0),className:"lg:hidden p-2 rounded-lg hover:bg-slate-100 transition-colors",children:e.jsx(T,{className:"h-5 w-5 text-slate-600"})}),e.jsx("div",{className:"hidden lg:block",children:e.jsx(Me,{})}),e.jsxs("div",{className:"flex items-center gap-3",children:[e.jsx("div",{className:"hidden md:block w-64",children:e.jsx(Ae,{})}),e.jsxs("button",{className:"p-2 rounded-lg hover:bg-slate-100 transition-colors relative",children:[e.jsx(je,{className:"h-5 w-5 text-slate-500"}),e.jsx("span",{className:"absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"})]})]})]})}),e.jsx("main",{className:"p-4 lg:p-8 relative",children:r||e.jsx(ae,{})})]}),e.jsx(Fe,{})]})}export{ut as default};
