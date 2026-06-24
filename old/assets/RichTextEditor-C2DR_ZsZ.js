import{D as i,R as h,j as e}from"./index-BFWN3IEs.js";import{u as p,i as m,a as u,E as x}from"./index-CYs6BDt8.js";import{L as g}from"./list-CXjwOV_E.js";import"./index-Ch2N3mHn.js";const y=[["path",{d:"M6 12h9a4 4 0 0 1 0 8H7a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h7a4 4 0 0 1 0 8",key:"mg9rjx"}]],f=i("bold",y);const k=[["path",{d:"M4 12h8",key:"17cfdx"}],["path",{d:"M4 18V6",key:"1rz3zl"}],["path",{d:"M12 18V6",key:"zqpxq5"}],["path",{d:"m17 12 3-2v8",key:"1hhhft"}]],j=i("heading-1",k);const M=[["path",{d:"M4 12h8",key:"17cfdx"}],["path",{d:"M4 18V6",key:"1rz3zl"}],["path",{d:"M12 18V6",key:"zqpxq5"}],["path",{d:"M21 18h-4c0-4 4-3 4-6 0-1.5-2-2.5-4-1",key:"9jr5yi"}]],v=i("heading-2",M);const b=[["line",{x1:"19",x2:"10",y1:"4",y2:"4",key:"15jd3p"}],["line",{x1:"14",x2:"5",y1:"20",y2:"20",key:"bu0au3"}],["line",{x1:"15",x2:"9",y1:"4",y2:"20",key:"uljnxc"}]],w=i("italic",b);const N=[["path",{d:"M11 5h10",key:"1cz7ny"}],["path",{d:"M11 12h10",key:"1438ji"}],["path",{d:"M11 19h10",key:"11t30w"}],["path",{d:"M4 4h1v5",key:"10yrso"}],["path",{d:"M4 9h2",key:"r1h2o0"}],["path",{d:"M6.5 20H3.4c0-1 2.6-1.925 2.6-3.5a1.5 1.5 0 0 0-2.6-1.02",key:"xtkcd5"}]],_=i("list-ordered",N);const A=[["path",{d:"M21 7v6h-6",key:"3ptur4"}],["path",{d:"M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 2.7",key:"1kgawr"}]],C=i("redo",A);const L=[["path",{d:"M4 7V4h16v3",key:"9msm58"}],["path",{d:"M5 20h6",key:"1h6pxn"}],["path",{d:"M13 4 8 20",key:"kqq6aj"}],["path",{d:"m15 15 5 5",key:"me55sn"}],["path",{d:"m20 15-5 5",key:"11p7ol"}]],$=i("remove-formatting",L);const P=[["path",{d:"M3 7v6h6",key:"1v2h90"}],["path",{d:"M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13",key:"1r6uu6"}]],z=i("undo",P),s=({onClick:t,isActive:a,disabled:n,children:r,title:c})=>e.jsx("button",{type:"button",onClick:t,disabled:n,title:c,className:`p-2 rounded-lg transition-colors ${a?"bg-indigo-100 text-indigo-700":"text-slate-600 hover:bg-slate-100"} ${n?"opacity-50 cursor-not-allowed":""}`,children:r}),E=({editor:t})=>t?e.jsxs("div",{className:"flex flex-wrap items-center gap-1 p-2 border-b border-slate-200 bg-slate-50 rounded-t-xl",children:[e.jsx(s,{onClick:()=>t.chain().focus().toggleBold().run(),isActive:t.isActive("bold"),title:"Bold",children:e.jsx(f,{className:"h-4 w-4"})}),e.jsx(s,{onClick:()=>t.chain().focus().toggleItalic().run(),isActive:t.isActive("italic"),title:"Italic",children:e.jsx(w,{className:"h-4 w-4"})}),e.jsx("div",{className:"w-px h-6 bg-slate-300 mx-1"}),e.jsx(s,{onClick:()=>t.chain().focus().toggleHeading({level:1}).run(),isActive:t.isActive("heading",{level:1}),title:"Heading 1",children:e.jsx(j,{className:"h-4 w-4"})}),e.jsx(s,{onClick:()=>t.chain().focus().toggleHeading({level:2}).run(),isActive:t.isActive("heading",{level:2}),title:"Heading 2",children:e.jsx(v,{className:"h-4 w-4"})}),e.jsx("div",{className:"w-px h-6 bg-slate-300 mx-1"}),e.jsx(s,{onClick:()=>t.chain().focus().toggleBulletList().run(),isActive:t.isActive("bulletList"),title:"Bullet List",children:e.jsx(g,{className:"h-4 w-4"})}),e.jsx(s,{onClick:()=>t.chain().focus().toggleOrderedList().run(),isActive:t.isActive("orderedList"),title:"Numbered List",children:e.jsx(_,{className:"h-4 w-4"})}),e.jsx("div",{className:"w-px h-6 bg-slate-300 mx-1"}),e.jsx(s,{onClick:()=>t.chain().focus().undo().run(),disabled:!t.can().undo(),title:"Undo",children:e.jsx(z,{className:"h-4 w-4"})}),e.jsx(s,{onClick:()=>t.chain().focus().redo().run(),disabled:!t.can().redo(),title:"Redo",children:e.jsx(C,{className:"h-4 w-4"})}),e.jsx(s,{onClick:()=>t.chain().focus().clearNodes().unsetAllMarks().run(),title:"Clear Formatting",children:e.jsx($,{className:"h-4 w-4"})})]}):null;function I({value:t="",onChange:a,placeholder:n="Start typing...",minHeight:r="200px",error:c}){const o=p({extensions:[m.configure({heading:{levels:[1,2]}}),u.configure({placeholder:n,emptyEditorClass:"is-editor-empty"})],content:t,onUpdate:({editor:d})=>{const l=d.getHTML();a(l==="<p></p>"||l===""?"":l)},editorProps:{attributes:{class:"prose prose-slate max-w-none focus:outline-none px-4 py-3",style:`min-height: ${r}`}}});return h.useEffect(()=>{o&&t!==o.getHTML()&&o.commands.setContent(t||"")},[t,o]),e.jsxs("div",{className:`border rounded-xl overflow-hidden transition-colors ${c?"border-rose-300 focus-within:border-rose-400 focus-within:ring focus-within:ring-rose-200":"border-slate-200 focus-within:border-indigo-300 focus-within:ring focus-within:ring-indigo-200"} focus-within:ring-opacity-50`,children:[e.jsx(E,{editor:o}),e.jsx(x,{editor:o}),e.jsx("style",{children:`
        .ProseMirror {
          min-height: ${r};
        }
        .ProseMirror p.is-editor-empty:first-child::before {
          content: attr(data-placeholder);
          float: left;
          color: #94a3b8;
          pointer-events: none;
          height: 0;
        }
        .ProseMirror:focus {
          outline: none;
        }
        .ProseMirror ul {
          list-style-type: disc;
          padding-left: 1.5rem;
        }
        .ProseMirror ol {
          list-style-type: decimal;
          padding-left: 1.5rem;
        }
        .ProseMirror h1 {
          font-size: 1.5rem;
          font-weight: 700;
          margin-bottom: 0.5rem;
        }
        .ProseMirror h2 {
          font-size: 1.25rem;
          font-weight: 600;
          margin-bottom: 0.5rem;
        }
        .ProseMirror p {
          margin-bottom: 0.5rem;
        }
        .ProseMirror li {
          margin-bottom: 0.25rem;
        }
      `})]})}export{I as default};
