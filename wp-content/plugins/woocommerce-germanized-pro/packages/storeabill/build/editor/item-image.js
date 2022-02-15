this.sab=this.sab||{},this.sab.blocks=this.sab.blocks||{},this.sab.blocks["item-image"]=function(e){function t(t){for(var r,i,l=t[0],a=t[1],h=t[2],v=0,s=[];v<l.length;v++)i=l[v],Object.prototype.hasOwnProperty.call(c,i)&&c[i]&&s.push(c[i][0]),c[i]=0;for(r in a)Object.prototype.hasOwnProperty.call(a,r)&&(e[r]=a[r]);for(u&&u(t);s.length;)s.shift()();return o.push.apply(o,h||[]),n()}function n(){for(var e,t=0;t<o.length;t++){for(var n=o[t],r=!0,l=1;l<n.length;l++){var a=n[l];0!==c[a]&&(r=!1)}r&&(o.splice(t--,1),e=i(i.s=n[0]))}return e}var r={},c={22:0,17:0,38:0},o=[];function i(t){if(r[t])return r[t].exports;var n=r[t]={i:t,l:!1,exports:{}};return e[t].call(n.exports,n,n.exports,i),n.l=!0,n.exports}i.m=e,i.c=r,i.d=function(e,t,n){i.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:n})},i.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},i.t=function(e,t){if(1&t&&(e=i(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var n=Object.create(null);if(i.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var r in e)i.d(n,r,function(t){return e[t]}.bind(null,r));return n},i.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return i.d(t,"a",t),t},i.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},i.p="";var l=window.webpackStoreaBillJsonp=window.webpackStoreaBillJsonp||[],a=l.push.bind(l);l.push=t,l=l.slice();for(var h=0;h<l.length;h++)t(l[h]);var u=a;return o.push([104,0]),n()}({0:function(e,t){e.exports=window.wp.element},1:function(e,t){e.exports=window.lodash},104:function(e,t,n){"use strict";n.r(t);var r=n(3),c=n(16),o=(n(7),n(15)),i=n(0),l=(n(10),n(2)),a=n(8),h=n(9);var u=function(e){var t=e.attributes,n=e.setAttributes,c=(e.className,t.customWidth),o=Object(h.getSetting)("assets_url")+"images/placeholder.png",u={maxWidth:c+"px",width:c+"px"};return Object(i.createElement)(i.Fragment,null,Object(i.createElement)(l.InspectorControls,null,Object(i.createElement)(a.PanelBody,null,Object(i.createElement)(a.RangeControl,{label:Object(r._x)("Width","storeabill-core","woocommerce-germanized-pro"),value:c,onChange:function(e){return n({customWidth:e})},min:25,max:100}))),Object(i.createElement)("div",null,Object(i.createElement)("img",{src:o,style:u,alt:"",className:"sab-document-image-placeholder"})))},v={title:Object(r._x)("Item Image","storeabill-core","woocommerce-germanized-pro"),description:Object(r._x)("Inserts the item image.","storeabill-core","woocommerce-germanized-pro"),category:"storeabill",icon:o.img,parent:["storeabill/item-table-column"],example:{},attributes:{customWidth:{type:"number"}},edit:u};Object(c.registerBlockType)("storeabill/item-image",v)},11:function(e,t){e.exports=window.wp.primitives},12:function(e,t){e.exports=window.wp.apiFetch},13:function(e,t){e.exports=window.wp.url},15:function(e,t,n){"use strict";n.r(t);var r=n(4),c=n.n(r),o=n(17),i=n.n(o),l=n(26),a=["srcElement","size"];function h(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(e);t&&(r=r.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,r)}return n}var u=function(e){var t=e.srcElement,n=e.size,r=void 0===n?24:n,o=i()(e,a);return Object(l.isValidElement)(t)&&Object(l.cloneElement)(t,function(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?h(Object(n),!0).forEach((function(t){c()(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):h(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}({width:r,height:r},o))},v=n(0),s=n(11),d=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("path",{d:"M15 18.5c-2.51 0-4.68-1.42-5.76-3.5H15v-2H8.58c-.05-.33-.08-.66-.08-1s.03-.67.08-1H15V9H9.24C10.32 6.92 12.5 5.5 15 5.5c1.61 0 3.09.59 4.23 1.57L21 5.3C19.41 3.87 17.3 3 15 3c-3.92 0-7.24 2.51-8.48 6H3v2h3.06c-.04.33-.06.66-.06 1 0 .34.02.67.06 1H3v2h3.52c1.24 3.49 4.56 6 8.48 6 2.31 0 4.41-.87 6-2.3l-1.78-1.77c-1.13.98-2.6 1.57-4.22 1.57z"}),Object(v.createElement)("path",{d:"M0 0h24v24H0z",fill:"none"})),m=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("path",{d:"M7 21h2v-2H7v2zm0-8h2v-2H7v2zm4 0h2v-2h-2v2zm0 8h2v-2h-2v2zm-8-4h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2v-2H3v2zm0-4h2V7H3v2zm8 8h2v-2h-2v2zm8-8h2V7h-2v2zm0 4h2v-2h-2v2zM3 3v2h18V3H3zm16 14h2v-2h-2v2zm-4 4h2v-2h-2v2zM11 9h2V7h-2v2zm8 12h2v-2h-2v2zm-4-8h2v-2h-2v2z"}),"        ",Object(v.createElement)("path",{d:"M0 0h24v24H0z",fill:"none"}),Object(v.createElement)("path",{d:"M0 0h24v24H0z",fill:"none"})),f=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("path",{d:"M9 11H7v2h2v-2zm4 4h-2v2h2v-2zM9 3H7v2h2V3zm4 8h-2v2h2v-2zM5 3H3v2h2V3zm8 4h-2v2h2V7zm4 4h-2v2h2v-2zm-4-8h-2v2h2V3zm4 0h-2v2h2V3zm2 10h2v-2h-2v2zm0 4h2v-2h-2v2zM5 7H3v2h2V7zm14-4v2h2V3h-2zm0 6h2V7h-2v2zM5 11H3v2h2v-2zM3 21h18v-2H3v2zm2-6H3v2h2v-2z"}),Object(v.createElement)("path",{d:"M0 0h24v24H0z",fill:"none"})),b=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("path",{d:"M11 21h2v-2h-2v2zm0-4h2v-2h-2v2zm0-12h2V3h-2v2zm0 4h2V7h-2v2zm0 4h2v-2h-2v2zm-4 8h2v-2H7v2zM7 5h2V3H7v2zm0 8h2v-2H7v2zm-4 8h2V3H3v18zM19 9h2V7h-2v2zm-4 12h2v-2h-2v2zm4-4h2v-2h-2v2zm0-14v2h2V3h-2zm0 10h2v-2h-2v2zm0 8h2v-2h-2v2zm-4-8h2v-2h-2v2zm0-8h2V3h-2v2z"}),Object(v.createElement)("path",{d:"M0 0h24v24H0z",fill:"none"})),p=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("path",{d:"M7 21h2v-2H7v2zM3 5h2V3H3v2zm4 0h2V3H7v2zm0 8h2v-2H7v2zm-4 8h2v-2H3v2zm8 0h2v-2h-2v2zm-8-8h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm8 8h2v-2h-2v2zm4-4h2v-2h-2v2zm4-10v18h2V3h-2zm-4 18h2v-2h-2v2zm0-16h2V3h-2v2zm-4 8h2v-2h-2v2zm0-8h2V3h-2v2zm0 4h2V7h-2v2z"}),Object(v.createElement)("path",{d:"M0 0h24v24H0z",fill:"none"})),O=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("path",{d:"M7 5h2V3H7v2zm0 8h2v-2H7v2zm0 8h2v-2H7v2zm4-4h2v-2h-2v2zm0 4h2v-2h-2v2zm-8 0h2v-2H3v2zm0-4h2v-2H3v2zm0-4h2v-2H3v2zm0-4h2V7H3v2zm0-4h2V3H3v2zm8 8h2v-2h-2v2zm8 4h2v-2h-2v2zm0-4h2v-2h-2v2zm0 8h2v-2h-2v2zm0-12h2V7h-2v2zm-8 0h2V7h-2v2zm8-6v2h2V3h-2zm-8 2h2V3h-2v2zm4 16h2v-2h-2v2zm0-8h2v-2h-2v2zm0-8h2V3h-2v2z"}),Object(v.createElement)("path",{d:"M0 0h24v24H0z",fill:"none"})),g=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("path",{d:"M3 21h2v-2H3v2zm4 0h2v-2H7v2zM5 7H3v2h2V7zM3 17h2v-2H3v2zM9 3H7v2h2V3zM5 3H3v2h2V3zm12 0h-2v2h2V3zm2 6h2V7h-2v2zm0-6v2h2V3h-2zm-4 18h2v-2h-2v2zM13 3h-2v8H3v2h8v8h2v-8h8v-2h-8V3zm6 18h2v-2h-2v2zm0-4h2v-2h-2v2z"}),Object(v.createElement)("path",{d:"M0 0h24v24H0z",fill:"none"})),w=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("path",{d:"M13 7h-2v2h2V7zm0 4h-2v2h2v-2zm4 0h-2v2h2v-2zM3 3v18h18V3H3zm16 16H5V5h14v14zm-6-4h-2v2h2v-2zm-4-4H7v2h2v-2z"}),Object(v.createElement)("path",{d:"M0 0h24v24H0z",fill:"none"})),j=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("path",{d:"M3 21h2v-2H3v2zM5 7H3v2h2V7zM3 17h2v-2H3v2zm4 4h2v-2H7v2zM5 3H3v2h2V3zm4 0H7v2h2V3zm8 0h-2v2h2V3zm-4 4h-2v2h2V7zm0-4h-2v2h2V3zm6 14h2v-2h-2v2zm-8 4h2v-2h-2v2zm-8-8h18v-2H3v2zM19 3v2h2V3h-2zm0 6h2V7h-2v2zm-8 8h2v-2h-2v2zm4 4h2v-2h-2v2zm4 0h2v-2h-2v2z"}),Object(v.createElement)("path",{d:"M0 0h24v24H0z",fill:"none"})),z=Object(v.createElement)(s.SVG,{viewBox:"0 0 24 24",xmlns:"http://www.w3.org/2000/svg"},Object(v.createElement)(s.Path,{d:"M8.5,21.4l1.9,0.5l5.2-19.3l-1.9-0.5L8.5,21.4z M3,19h4v-2H5V7h2V5H3V19z M17,5v2h2v10h-2v2h4V5H17z"})),y=Object(v.createElement)(s.SVG,{viewBox:"0 0 24 24",xmlns:"http://www.w3.org/2000/svg"},Object(v.createElement)("path",{d:"M0 0h24v24H0z",fill:"none"}),Object(v.createElement)("path",{d:"M9 4v3h5v12h3V7h5V4H9zm-6 8h3v7h3v-7h3V9H3v3z"})),E=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("path",{d:"M0 0h24v24H0z",fill:"none"}),Object(v.createElement)("path",{d:"M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"}),Object(v.createElement)("path",{d:"M12.5 7H11v6l5.25 3.15.75-1.23-4.5-2.67z"})),V=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("path",{d:"M0 0h24v24H0z",fill:"none"}),Object(v.createElement)("path",{d:"M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"})),x=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("path",{d:"M0 0h24v24H0z",fill:"none"}),Object(v.createElement)("path",{d:"M20.5 11H19V7c0-1.1-.9-2-2-2h-4V3.5C13 2.12 11.88 1 10.5 1S8 2.12 8 3.5V5H4c-1.1 0-1.99.9-1.99 2v3.8H3.5c1.49 0 2.7 1.21 2.7 2.7s-1.21 2.7-2.7 2.7H2V20c0 1.1.9 2 2 2h3.8v-1.5c0-1.49 1.21-2.7 2.7-2.7 1.49 0 2.7 1.21 2.7 2.7V22H17c1.1 0 2-.9 2-2v-4h1.5c1.38 0 2.5-1.12 2.5-2.5S21.88 11 20.5 11z"})),M=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("path",{d:"M0 0h24v24H0z",fill:"none"}),Object(v.createElement)("path",{d:"M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"})),H=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("path",{d:"M0 0h24v24H0z",fill:"none"}),Object(v.createElement)("path",{d:"M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14h-2V9h-2V7h4v10z"})),S=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("path",{d:"M0 0h24v24H0V0z",fill:"none"}),Object(v.createElement)("path",{d:"M5 4v3h5.5v12h3V7H19V4z"})),C=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("g",null,Object(v.createElement)("path",{d:"M0,0h24v24H0V0z",fill:"none"}),Object(v.createElement)("path",{d:"M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"}))),T=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("path",{d:"M0 0h24v24H0V0z",fill:"none"}),Object(v.createElement)("path",{d:"M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"})),P=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("path",{d:"M0 0h24v24H0z",fill:"none"}),Object(v.createElement)("path",{d:"M17.81 4.47c-.08 0-.16-.02-.23-.06C15.66 3.42 14 3 12.01 3c-1.98 0-3.86.47-5.57 1.41-.24.13-.54.04-.68-.2-.13-.24-.04-.55.2-.68C7.82 2.52 9.86 2 12.01 2c2.13 0 3.99.47 6.03 1.52.25.13.34.43.21.67-.09.18-.26.28-.44.28zM3.5 9.72c-.1 0-.2-.03-.29-.09-.23-.16-.28-.47-.12-.7.99-1.4 2.25-2.5 3.75-3.27C9.98 4.04 14 4.03 17.15 5.65c1.5.77 2.76 1.86 3.75 3.25.16.22.11.54-.12.7-.23.16-.54.11-.7-.12-.9-1.26-2.04-2.25-3.39-2.94-2.87-1.47-6.54-1.47-9.4.01-1.36.7-2.5 1.7-3.4 2.96-.08.14-.23.21-.39.21zm6.25 12.07c-.13 0-.26-.05-.35-.15-.87-.87-1.34-1.43-2.01-2.64-.69-1.23-1.05-2.73-1.05-4.34 0-2.97 2.54-5.39 5.66-5.39s5.66 2.42 5.66 5.39c0 .28-.22.5-.5.5s-.5-.22-.5-.5c0-2.42-2.09-4.39-4.66-4.39-2.57 0-4.66 1.97-4.66 4.39 0 1.44.32 2.77.93 3.85.64 1.15 1.08 1.64 1.85 2.42.19.2.19.51 0 .71-.11.1-.24.15-.37.15zm7.17-1.85c-1.19 0-2.24-.3-3.1-.89-1.49-1.01-2.38-2.65-2.38-4.39 0-.28.22-.5.5-.5s.5.22.5.5c0 1.41.72 2.74 1.94 3.56.71.48 1.54.71 2.54.71.24 0 .64-.03 1.04-.1.27-.05.53.13.58.41.05.27-.13.53-.41.58-.57.11-1.07.12-1.21.12zM14.91 22c-.04 0-.09-.01-.13-.02-1.59-.44-2.63-1.03-3.72-2.1-1.4-1.39-2.17-3.24-2.17-5.22 0-1.62 1.38-2.94 3.08-2.94 1.7 0 3.08 1.32 3.08 2.94 0 1.07.93 1.94 2.08 1.94s2.08-.87 2.08-1.94c0-3.77-3.25-6.83-7.25-6.83-2.84 0-5.44 1.58-6.61 4.03-.39.81-.59 1.76-.59 2.8 0 .78.07 2.01.67 3.61.1.26-.03.55-.29.64-.26.1-.55-.04-.64-.29-.49-1.31-.73-2.61-.73-3.96 0-1.2.23-2.29.68-3.24 1.33-2.79 4.28-4.6 7.51-4.6 4.55 0 8.25 3.51 8.25 7.83 0 1.62-1.38 2.94-3.08 2.94s-3.08-1.32-3.08-2.94c0-1.07-.93-1.94-2.08-1.94s-2.08.87-2.08 1.94c0 1.71.66 3.31 1.87 4.51.95.94 1.86 1.46 3.27 1.85.27.07.42.35.35.61-.05.23-.26.38-.47.38z"})),_=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("path",{d:"M0 0h24v24H0V0z",fill:"none"}),Object(v.createElement)("path",{d:"M19 5v14H5V5h14m0-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-4.86 8.86l-3 3.87L9 13.14 6 17h12l-3.86-5.14z"})),k=Object(v.createElement)(s.SVG,{x:"0.0000mm",y:"0.0000mm",width:"2.5cm",viewBox:"0.0000 0.0000 50.2710 20.4580",xmlns:"http://www.w3.org/2000/svg"},Object(v.createElement)("g",null,Object(v.createElement)("rect",{x:"0.0000",y:"0.0000",width:"0.5292",height:"20.1864"}),Object(v.createElement)("rect",{x:"2.1167",y:"0.0000",width:"0.5292",height:"20.1864"}),Object(v.createElement)("rect",{x:"3.1750",y:"0.0000",width:"1.5875",height:"20.1864"}),Object(v.createElement)("rect",{x:"5.2917",y:"0.0000",width:"1.5875",height:"20.1864"}),Object(v.createElement)("rect",{x:"7.4084",y:"0.0000",width:"0.5292",height:"20.1864"}),Object(v.createElement)("rect",{x:"8.4667",y:"0.0000",width:"1.5875",height:"20.1864"}),Object(v.createElement)("rect",{x:"10.5834",y:"0.0000",width:"0.5292",height:"20.1864"}),Object(v.createElement)("rect",{x:"12.7000",y:"0.0000",width:"0.5292",height:"20.1864"}),Object(v.createElement)("rect",{x:"13.7584",y:"0.0000",width:"0.5292",height:"20.1864"}),Object(v.createElement)("rect",{x:"14.8167",y:"0.0000",width:"1.5875",height:"20.1864"}),Object(v.createElement)("rect",{x:"16.9334",y:"0.0000",width:"0.5292",height:"20.1864"}),Object(v.createElement)("rect",{x:"17.9917",y:"0.0000",width:"1.5875",height:"20.1864"}),Object(v.createElement)("rect",{x:"21.1667",y:"0.0000",width:"0.5292",height:"20.1864"}),Object(v.createElement)("rect",{x:"22.2251",y:"0.0000",width:"0.5292",height:"20.1864"}),Object(v.createElement)("rect",{x:"23.2834",y:"0.0000",width:"1.5875",height:"20.1864"}),Object(v.createElement)("rect",{x:"25.4001",y:"0.0000",width:"1.5875",height:"20.1864"}),Object(v.createElement)("rect",{x:"27.5168",y:"0.0000",width:"1.5875",height:"20.1864"}),Object(v.createElement)("rect",{x:"30.6918",y:"0.0000",width:"0.5292",height:"20.1864"}),Object(v.createElement)("rect",{x:"31.7501",y:"0.0000",width:"0.5292",height:"20.1864"}),Object(v.createElement)("rect",{x:"32.8084",y:"0.0000",width:"0.5292",height:"20.1864"}),Object(v.createElement)("rect",{x:"33.8668",y:"0.0000",width:"0.5292",height:"20.1864"}),Object(v.createElement)("rect",{x:"34.9251",y:"0.0000",width:"0.5292",height:"20.1864"}),Object(v.createElement)("rect",{x:"37.0418",y:"0.0000",width:"1.5875",height:"20.1864"}),Object(v.createElement)("rect",{x:"39.1585",y:"0.0000",width:"0.5292",height:"20.1864"}),Object(v.createElement)("rect",{x:"40.2168",y:"0.0000",width:"1.5875",height:"20.1864"}),Object(v.createElement)("rect",{x:"42.3335",y:"0.0000",width:"0.5292",height:"20.1864"}),Object(v.createElement)("rect",{x:"44.4501",y:"0.0000",width:"0.5292",height:"20.1864"}),Object(v.createElement)("rect",{x:"45.5085",y:"0.0000",width:"1.5875",height:"20.1864"}),Object(v.createElement)("rect",{x:"47.6252",y:"0.0000",width:"1.5875",height:"20.1864"}),Object(v.createElement)("rect",{x:"49.7418",y:"0.0000",width:"0.5292",height:"20.1864"}))),B=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("g",null,Object(v.createElement)("rect",{fill:"none",height:"24",width:"24"})),Object(v.createElement)("path",{d:"M15,21h-2v-2h2V21z M13,14h-2v5h2V14z M21,12h-2v4h2V12z M19,10h-2v2h2V10z M7,12H5v2h2V12z M5,10H3v2h2V10z M12,5h2V3h-2V5 z M4.5,4.5v3h3v-3H4.5z M9,9H3V3h6V9z M4.5,16.5v3h3v-3H4.5z M9,21H3v-6h6V21z M16.5,4.5v3h3v-3H16.5z M21,9h-6V3h6V9z M19,19v-3 l-4,0v2h2v3h4v-2H19z M17,12l-4,0v2h4V12z M13,10H7v2h2v2h2v-2h2V10z M14,9V7h-2V5h-2v4L14,9z M6.75,5.25h-1.5v1.5h1.5V5.25z M6.75,17.25h-1.5v1.5h1.5V17.25z M18.75,5.25h-1.5v1.5h1.5V5.25z"})),G=Object(v.createElement)(s.SVG,{xmlns:"http://www.w3.org/2000/SVG",viewBox:"0 0 24 24",width:"24",height:"24"},Object(v.createElement)("path",{d:"M0 0h24v24H0z",fill:"none"}),Object(v.createElement)("path",{d:"M19 5v14H5V5h14m0-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"}));n.d(t,"Icon",(function(){return u})),n.d(t,"euro",(function(){return d})),n.d(t,"borderTop",(function(){return m})),n.d(t,"borderBottom",(function(){return f})),n.d(t,"borderLeft",(function(){return b})),n.d(t,"borderRight",(function(){return p})),n.d(t,"borderClear",(function(){return O})),n.d(t,"borderInner",(function(){return g})),n.d(t,"borderOuter",(function(){return w})),n.d(t,"borderHorizontal",(function(){return j})),n.d(t,"shortcode",(function(){return z})),n.d(t,"fontSize",(function(){return y})),n.d(t,"date",(function(){return E})),n.d(t,"address",(function(){return V})),n.d(t,"meta",(function(){return x})),n.d(t,"discount",(function(){return M})),n.d(t,"quantity",(function(){return H})),n.d(t,"title",(function(){return S})),n.d(t,"settings",(function(){return C})),n.d(t,"arrowRight",(function(){return T})),n.d(t,"fingerprint",(function(){return P})),n.d(t,"img",(function(){return _})),n.d(t,"barcode",(function(){return k})),n.d(t,"qrCode",(function(){return B})),n.d(t,"field",(function(){return G}))},16:function(e,t){e.exports=window.wp.blocks},2:function(e,t){e.exports=window.wp.blockEditor},26:function(e,t){e.exports=window.React},3:function(e,t){e.exports=window.wp.i18n},6:function(e,t){e.exports=window.wp.data},7:function(e,t,n){"use strict";var r=n(1),c=n(2),o=n(20),i=n.n(o),l=n(18),a=n.n(l),h=n(4),u=n.n(h),v=n(0),s=n(21),d=n.n(s),m=n(10),f=n.n(m),b=n(3),p=n(6),O=n(14),g=n.n(O),w=n(17),j=n.n(w),z=["backgroundColor","textColor"],y=function(e,t,n){return"function"==typeof e?e(t):!0===e?n:e};function E(e){var t=e.title,n=e.colorSettings,o=e.colorPanelProps,i=e.contrastCheckers,l=e.detectedBackgroundColor,a=e.detectedColor,h=e.panelChildren,u=e.initialOpen;return Object(v.createElement)(c.PanelColorSettings,g()({title:t,initialOpen:u,colorSettings:Object.values(n)},o),i&&(Array.isArray(i)?i.map((function(e){var t=e.backgroundColor,r=e.textColor,o=j()(e,z);return t=y(t,n,l),r=y(r,n,a),Object(v.createElement)(c.ContrastChecker,g()({key:"".concat(t,"-").concat(r),backgroundColor:t,textColor:r},o))})):Object(r.map)(n,(function(e){var t=e.value,r=i.backgroundColor,o=i.textColor;return r=y(r||t,n,l),o=y(o||t,n,a),Object(v.createElement)(c.ContrastChecker,g()({},i,{key:"".concat(r,"-").concat(o),backgroundColor:r,textColor:o}))}))),"function"==typeof h?h(n):h)}function V(e,t){var n="undefined"!=typeof Symbol&&e[Symbol.iterator]||e["@@iterator"];if(!n){if(Array.isArray(e)||(n=function(e,t){if(!e)return;if("string"==typeof e)return x(e,t);var n=Object.prototype.toString.call(e).slice(8,-1);"Object"===n&&e.constructor&&(n=e.constructor.name);if("Map"===n||"Set"===n)return Array.from(e);if("Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n))return x(e,t)}(e))||t&&e&&"number"==typeof e.length){n&&(e=n);var r=0,c=function(){};return{s:c,n:function(){return r>=e.length?{done:!0}:{done:!1,value:e[r++]}},e:function(e){throw e},f:c}}throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}var o,i=!0,l=!1;return{s:function(){n=n.call(e)},n:function(){var e=n.next();return i=e.done,e},e:function(e){l=!0,o=e},f:function(){try{i||null==n.return||n.return()}finally{if(l)throw o}}}}function x(e,t){(null==t||t>e.length)&&(t=e.length);for(var n=0,r=new Array(t);n<t;n++)r[n]=e[n];return r}function M(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(e);t&&(r=r.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,r)}return n}function H(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?M(Object(n),!0).forEach((function(t){u()(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):M(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function S(e){return e.ownerDocument.defaultView.getComputedStyle(e)}var C=[],T={textColor:Object(b.__)("Text color"),backgroundColor:Object(b.__)("Background color")},P=function(e){return Object(v.createElement)(c.InspectorControls,null,Object(v.createElement)(E,e))};n.d(t,"g",(function(){return _})),n.d(t,"b",(function(){return k})),n.d(t,"a",(function(){return B})),n.d(t,"c",(function(){return G})),n.d(t,"e",(function(){return D})),n.d(t,"d",(function(){return A})),n.d(t,"f",(function(){return I}));var _=void 0===c.__experimentalUseColors?function(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{panelTitle:Object(b.__)("Color")},n=t.panelTitle,o=void 0===n?Object(b.__)("Color"):n,l=t.colorPanelProps,h=t.contrastCheckers,s=t.panelChildren,m=t.colorDetector,O=(m=void 0===m?{}:m).targetRef,g=m.backgroundColorTargetRef,w=void 0===g?O:g,j=m.textColorTargetRef,z=void 0===j?O:j,y=arguments.length>2&&void 0!==arguments[2]?arguments[2]:[],x=Object(c.useBlockEditContext)(),M=x.clientId,_=Object(c.useSetting)("color.palette")||C,k=Object(p.useSelect)((function(e){return{attributes:(0,e(c.store).getBlockAttributes)(M)}}),[M]),B=k.attributes,G=Object(p.useDispatch)(c.store),D=G.updateBlockAttributes,A=Object(v.useCallback)((function(e){return D(M,e)}),[D,M]),I=Object(v.useMemo)((function(){return d()((function(e,t,n,c,o,i){return function(l){var a,h=l.children,s=l.className,d=void 0===s?"":s,m=l.style,b=void 0===m?{}:m,p={};c?p=u()({},t,o):i&&(p=u()({},t,i));var O={className:f()(d,(a={},u()(a,"has-".concat(Object(r.kebabCase)(c),"-").concat(Object(r.kebabCase)(t)),c),u()(a,n||"has-".concat(Object(r.kebabCase)(e)),c||i),a)),style:H(H({},p),b)};return Object(r.isFunction)(h)?h(O):v.Children.map(h,(function(e){return Object(v.cloneElement)(e,{className:f()(e.props.className,O.className),style:H(H({},O.style),e.props.style||{})})}))}}),{maxSize:e.length})}),[e.length]),N=Object(v.useMemo)((function(){return d()((function(e,t){return function(n){var c=t.find((function(e){return e.color===n}));A(u()({},c?Object(r.camelCase)("custom ".concat(e)):e,void 0)),A(u()({},c?e:Object(r.camelCase)("custom ".concat(e)),c?c.slug:n))}}),{maxSize:e.length})}),[A,e.length]),L=Object(v.useState)(),F=a()(L,2),R=F[0],W=F[1],Y=Object(v.useState)(),q=a()(Y,2),U=q[0],J=q[1];return Object(v.useEffect)((function(){if(h){var e,t=!1,n=!1,c=V(Object(r.castArray)(h));try{for(c.s();!(e=c.n()).done;){var o=e.value,i=o.backgroundColor,l=o.textColor;if(t||(t=!0===i),n||(n=!0===l),t&&n)break}}catch(e){c.e(e)}finally{c.f()}if(n&&J(S(z.current).color),t){for(var a=w.current,u=S(a).backgroundColor;"rgba(0, 0, 0, 0)"===u&&a.parentNode&&a.parentNode.nodeType===a.parentNode.ELEMENT_NODE;)u=S(a=a.parentNode).backgroundColor;W(u)}}}),[e.reduce((function(e,t){return"".concat(e," | ").concat(B[t.name]," | ").concat(B[Object(r.camelCase)("custom ".concat(t.name))])}),"")].concat(i()(y))),Object(v.useMemo)((function(){var t={},n=e.reduce((function(e,n){"string"==typeof n&&(n={name:n});var c=H(H({},n),{},{color:B[n.name]}),o=c.name,i=c.property,l=void 0===i?o:i,a=c.className,h=c.panelLabel,u=void 0===h?n.label||T[o]||Object(r.startCase)(o):h,v=c.componentName,s=void 0===v?Object(r.startCase)(o).replace(/\s/g,""):v,d=c.color,m=void 0===d?n.color:d,f=c.colors,b=void 0===f?_:f,p=B[Object(r.camelCase)("custom ".concat(o))],O=p?void 0:b.find((function(e){return e.slug===m}));return e[s]=I(o,l,a,m,O&&O.color,p),e[s].displayName=s,e[s].color=p||O&&O.color,e[s].slug=m,e[s].setColor=N(o,b),t[s]={value:O?O.color:B[Object(r.camelCase)("custom ".concat(o))],onChange:e[s].setColor,label:u,colors:b},b||delete t[s].colors,e}),{}),c={title:o,initialOpen:!1,colorSettings:t,colorPanelProps:l,contrastCheckers:h,detectedBackgroundColor:R,detectedColor:U,panelChildren:s};return H(H({},n),{},{ColorPanel:Object(v.createElement)(E,c),InspectorControlsColorPanel:Object(v.createElement)(P,c)})}),[B,A,U,R].concat(i()(y)))}:c.__experimentalUseColors;function k(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:"";return'<span class="placeholder-content '+(Object(r.isEmpty)(t)?"":"sab-tooltip")+'" contenteditable="false" '+(Object(r.isEmpty)(t)?"":'data-tooltip="'+t+'"')+'><span class="editor-placeholder"></span>'+e+"</span>"}function B(e){return"string"==typeof e&&/^\d+$/.test(e)&&(e=parseInt(e)),e}function G(e){var t,n=e;return e&&e.hasOwnProperty("size")&&(n=e.size),n?(t=n,isNaN(parseFloat(t))||isNaN(t-0)?n:n+"px"):void 0}function D(e,t,n,c){var o=arguments.length>4&&void 0!==arguments[4]?arguments[4]:"";return e&&Object(r.includes)(e,n)||(e=Object(r.includes)(e,"{default}")?e.replace("{default}",c||k(n,o)):c||k(n,o)),e.replace(n,t)}function A(e,t,n){return e.replace(n,t)}function I(e,t){var n=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"placeholder-content",r=arguments.length>3&&void 0!==arguments[3]&&arguments[3],c=(new DOMParser).parseFromString(e,"text/html"),o=!1;if((o=r?c.querySelectorAll("[data-shortcode='"+n+"']"):c.getElementsByClassName(n)).length>0){var i=o[0].getElementsByClassName("editor-placeholder");if(i.length>0){for(var l=i[0].nextSibling,a=[];l;)l!==i[0]&&a.push(l),l=l.nextSibling;a.forEach((function(e){i[0].parentNode.removeChild(e)})),i[0].insertAdjacentHTML("afterEnd",t)}else o[0].innerHTML='<span class="editor-placeholder"></span>'+t;o[0].classList.remove("document-shortcode-needs-refresh"),e=c.body.innerHTML}return e}},8:function(e,t){e.exports=window.wp.components},9:function(e,t,n){"use strict";n.r(t);var r=n(4),c=n.n(r),o=n(22);function i(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(e);t&&(r=r.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,r)}return n}function l(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?i(Object(n),!0).forEach((function(t){c()(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):i(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}var a="object"===("undefined"==typeof sabSettings?"undefined":n.n(o)()(sabSettings))?sabSettings:{},h=l(l({},{itemTotalTypes:[],itemMetaTypes:[],itemTableBlockTypes:[],discountTotalTypes:{}}),a);function u(e){var t=arguments.length>1&&void 0!==arguments[1]&&arguments[1],n=arguments.length>2&&void 0!==arguments[2]?arguments[2]:function(e){return e},r=h.hasOwnProperty(e)?h[e]:t;return n(r,t)}var v=n(3),s=h.itemTotalTypes,d=h.itemMetaTypes,m=h.itemTableBlockTypes,f=h.discountTotalTypes,b=["core/bold","core/italic","core/text-color","core/underline","storeabill/document-shortcode","storeabill/font-size"],p=n(6),O=n(1),g=n(12),w=n.n(g),j=n(13);n(8);function z(e,t){var n=arguments.length>2&&void 0!==arguments[2]?arguments[2]:function(e){return e};h[e]=n(t)}function y(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"product";return d.hasOwnProperty(e)?d[e]:[]}function E(e,t){Array.isArray(t)||(t=[t]);var n=Object(p.select)("core/block-editor").getBlockParents(e);if(n.length>0){var r=Object(p.select)("core/block-editor").getBlock(n[0]);if(Object(O.includes)(t,r.name))return!0}return!1}function V(e){var t=u("supports");return Object(O.includes)(t,e)}function x(e){var t=u("defaultInnerBlocks");return t.hasOwnProperty(e)?t[e]:[]}function M(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:"product",n=y(t),r=n.filter((function(t){if(e===t.type)return!0})),c=r.length>0?r[0].preview:"",o=S(t),i=o.meta_data.filter((function(t){if(e===t.key)return!0}));return i.length>0?i[0].value:c}function H(){return u("preview",{})}function S(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"product",t=H();return t[e+"_items"][0]}function C(e){var t=s.filter((function(t){if(e===t.type)return!0}));return t&&t[0]?t[0].default:""}function T(e){var t=s.filter((function(t){if(e===t.type)return!0}));return t&&t[0]?t[0].title:""}function P(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"total",t=H(),n=t.totals,r=n.filter((function(t){return t.type===e}));return r.length>0?r[0].total_formatted:0}function _(){var e=H().tax_items;return e.length>0?e[0].rate.percent:"{rate}"}function k(){return H().formatted_discount_notice}function B(){var e=H().fee_items;return e.length>0?e[0].name:"{name}"}function G(e,t){var n=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"read",r={top:e.top?e.top:t.top,left:e.left?e.left:t.left,right:e.right?e.right:t.right,bottom:e.bottom?e.bottom:t.bottom};if("edit"===n){var c=u("marginTypesSupported"),o={};return c.forEach((function(e){o[e]=r[e]})),o}return r}function D(){return"document_template"===Object(p.select)("core/editor").getCurrentPostType()}function A(){return u("allowedBlockTypes")}function I(){var e=void 0,t=(0,Object(p.select)("core/block-editor").getBlocks)().filter((function(e){if("storeabill/document-styles"===e.name)return e}));return t.length>0&&(e=t[0]),e}function N(){return u("fonts")}function L(e){var t=N().filter((function(t){if(t.name===e)return!0}));if(!Object(O.isEmpty)(t))return t[0]}function F(){var e=(0,Object(p.select)("core/editor").getEditedPostAttribute)("meta");return e._fonts?e._fonts:void 0}function R(e){e=e||F();var t=Object(j.addQueryArgs)("/sab/v1/preview_fonts/css",{fonts:e,display_types:u("fontDisplayTypes")});return w()({path:t})}function W(e){var t=!(arguments.length>1&&void 0!==arguments[1])||arguments[1],n=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"",r=e;return"before_discounts"===n&&(r+="_subtotal",Object(O.includes)(e,"total")&&(r=e.replace("total","")),"total"===e&&(r="subtotal")),!1===t&&(Object(O.includes)(e,"_total")&&(r=r.replace("_total","")),r+="_net"),r+"_formatted"}function Y(e){var t="";return"document"===e?t=Object(v._x)("Document","storeabill-core","woocommerce-germanized-pro"):"document_item"===e?t=Object(v._x)("Document Item","storeabill-core","woocommerce-germanized-pro"):"document_total"===e?t=Object(v._x)("Document Total","storeabill-core","woocommerce-germanized-pro"):"setting"===e&&(t=Object(v._x)("Settings","storeabill-core","woocommerce-germanized-pro")),t}function q(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"",t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:"",n=arguments.length>2&&void 0!==arguments[2]&&arguments[2],r=u("shortcodes"),c=Object.entries(r),o=["blocks","setting"],i={};c.forEach((function(r,c){var l=r[0];if((!(e.length>0&&e!==l)||Object(O.includes)(o,l))&&("blocks"!==l||0!==t.length)){var a=[],h=Y(l);if(Object(O.isArray)(r[1]))a=r[1].flat();else if(t.length>0){a=r[1].hasOwnProperty(t)?r[1][t]:[];var u=Object(p.select)("core/blocks").getBlockType(t);h=u?u.title:t}i.hasOwnProperty(l)||(i[l]={label:h,value:l,children:{}}),a.map((function(e){if(!i[l].children.hasOwnProperty(e.shortcode)){if(!n&&e.hasOwnProperty("headerFooterOnly")&&e.headerFooterOnly)return;i[l].children[e.shortcode]={value:e.shortcode,label:e.title}}}))}}));var l=[];return Object.entries(i).map((function(e){var t=Object.values(e[1].children).flat();Object(O.isEmpty)(t)||l.push({value:e[1].value,label:e[1].label,children:t})})),l}function U(e){var t=u("shortcodes"),n=Object.entries(t),r={};return n.forEach((function(e,t){(Object(O.isArray)(e[1])?e[1].flat():Object.values(e[1]).flat()).map((function(e){r.hasOwnProperty(e.shortcode)||(r[e.shortcode]=e)}))})),!!r.hasOwnProperty(e)&&r[e]}function J(e){var t=U(e);return t?t.title:""}function K(){return u("dateTypes")}function Q(){return u("barcodeTypes")}function $(){return u("barcodeCodeTypes")}function X(e){var t=u("dateTypes"),n=Object(v._x)("Date","storeabill-core","woocommerce-germanized-pro");return Object.entries(t).map((function(t){t[0]===e&&(n=t[1])})),n}function Z(e){var t=Object(j.addQueryArgs)("/sab/v1/preview_shortcodes",{query:e,document_type:u("documentType")});return w()({path:t})}n.d(t,"getItemMetaTypes",(function(){return y})),n.d(t,"blockHasParent",(function(){return E})),n.d(t,"documentTypeSupports",(function(){return V})),n.d(t,"getDefaultInnerBlocks",(function(){return x})),n.d(t,"getItemMetaTypePreview",(function(){return M})),n.d(t,"getPreview",(function(){return H})),n.d(t,"getPreviewItem",(function(){return S})),n.d(t,"getItemTotalTypeDefaultTitle",(function(){return C})),n.d(t,"getItemTotalTypeTitle",(function(){return T})),n.d(t,"getPreviewTotal",(function(){return P})),n.d(t,"getPreviewTaxRate",(function(){return _})),n.d(t,"getPreviewDiscountNotice",(function(){return k})),n.d(t,"getPreviewFeeName",(function(){return B})),n.d(t,"formatMargins",(function(){return G})),n.d(t,"isDocumentTemplate",(function(){return D})),n.d(t,"getAllowedBlockTypes",(function(){return A})),n.d(t,"getDocumentStylesBlock",(function(){return I})),n.d(t,"getFonts",(function(){return N})),n.d(t,"getFont",(function(){return L})),n.d(t,"getCurrentFonts",(function(){return F})),n.d(t,"getFontsCSS",(function(){return R})),n.d(t,"getItemTotalKey",(function(){return W})),n.d(t,"getShortcodeCategoryTitle",(function(){return Y})),n.d(t,"getAvailableShortcodeTree",(function(){return q})),n.d(t,"getShortcodeData",(function(){return U})),n.d(t,"getShortcodeTitle",(function(){return J})),n.d(t,"getDateTypes",(function(){return K})),n.d(t,"getBarcodeTypes",(function(){return Q})),n.d(t,"getBarcodeCodeTypes",(function(){return $})),n.d(t,"getDateTypeTitle",(function(){return X})),n.d(t,"getShortcodePreview",(function(){return Z})),n.d(t,"ITEM_TOTAL_TYPES",(function(){return s})),n.d(t,"ITEM_META_TYPES",(function(){return d})),n.d(t,"ITEM_TABLE_BLOCK_TYPES",(function(){return m})),n.d(t,"DISCOUNT_TOTAL_TYPES",(function(){return f})),n.d(t,"FORMAT_TYPES",(function(){return b})),n.d(t,"setSetting",(function(){return z})),n.d(t,"getSetting",(function(){return u}))}});