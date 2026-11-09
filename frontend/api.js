let token='';
export async function api(path,options={}){
 const method=options.method||'GET';const response=await fetch('/api/v1/'+path,{credentials:'same-origin',method,headers:{'Accept':'application/json',...(options.body?{'Content-Type':'application/json'}:{}),...(method!=='GET'?{'X-CSRF-Token':token}:{})},...(options.body?{body:JSON.stringify(options.body)}:{})});
 let data;try{data=await response.json()}catch{throw new Error('API tidak dapat dibaca. Semak sambungan server PHP.')}
 if(data.csrf)token=data.csrf;
 if(!response.ok){const error=new Error(data.error||'Permintaan gagal.');error.status=response.status;if(response.status===401&&!['login','me'].includes(path))window.dispatchEvent(new Event('session-expired'));throw error}return data;
}
export async function uploadAttachment(kind,id,file){
 const data=new FormData();data.set('entity_type',kind);data.set('entity_id',id);data.set('file',file);
 const r=await fetch('/api/v1/ops/attachments',{method:'POST',credentials:'same-origin',headers:{'X-CSRF-Token':token},body:data});let value;try{value=await r.json()}catch{throw new Error('Lampiran gagal dihantar. Semak saiz fail.')}if(!r.ok)throw new Error(value.error||'Lampiran gagal');return value;
}
