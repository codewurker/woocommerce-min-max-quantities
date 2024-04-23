(()=>{"use strict";const t=window.wp.blocks,e=window.React,o=window.wc.blockTemplates,r=window.wc.productEditor,n=window.wp.i18n,i=window.wp.data,a=window.wp.coreData;function p(t){return t||""}const s=JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"woocommerce-min-max/group-of-quantity-field","version":"0.1.0","title":"Group of Quantity","category":"widgets","icon":"flag","description":"Group of Quantity","attributes":{"label":{"type":"string","__experimentalRole":"content"}},"supports":{"html":false,"inserter":false},"textdomain":"woocommerce-min-max-quantities","editorScript":"file:./index.js","usesContext":["postType"]}');(0,t.registerBlockType)(s,{edit:function({attributes:t,context:{postType:s}}){const u=(0,o.useWooBlockProps)(t),[c,l]=(0,r.__experimentalUseProductEntityProp)("meta_data.group_of_quantity",{postType:s}),[m,d]=(0,r.__experimentalUseProductEntityProp)("meta_data.minimum_allowed_quantity",{postType:s}),{groupOf:y,isCategoryGroupOf:f}=function(t){const e="product",o=(0,a.useEntityId)("postType",e),r=o,{selectedCategories:n,groupOf:p}=(0,i.useSelect)((t=>{const{getEditedEntityRecord:o}=t("core"),{meta_data:n}=o("postType",e,r),{categories:i}=o("postType",e,r),a=n?.find((t=>"group_of_quantity"===t.key))?.value;return{groupOf:a,selectedCategories:t("core").getEntityRecords("taxonomy","product_cat",{include:i?.map((t=>t.id))})||[]}}),[r]),s=Math.min(...n.filter((t=>""!==t.meta?.group_of_quantity)).map((t=>parseFloat(t.meta?.group_of_quantity||"0")))),u=Boolean(s&&s!==1/0),c=Boolean(u&&!p);return{groupOf:p&&String(p||"")||c&&String(s)||"1",isCategoryGroupOf:c,hasCategoryGroupOf:u,categoryGroupOf:String(s)}}();return(0,e.createElement)("div",{...u},(0,e.createElement)(r.__experimentalNumberControl,{...t,onChange:t=>{(function(t){return parseFloat(t||"0")}(t)>0||""===t)&&l(t)},onBlur:()=>{!m&&c&&d(c)},value:p(c),placeholder:f?(0,n.sprintf)(
// translators: %d is the value of the category group of quantity.
(0,n.__)("%d (category default)","woocommerce-min-max-quantities"),y):(0,n.__)("1 (default)","woocommerce-min-max-quantities")}))}})})();