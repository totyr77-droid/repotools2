// Keep speculative browser sockets away from PHP's single-threaded Windows dev server.
const http=require('http');
module.exports=async function browserProxy(port,target){
  let queue=Promise.resolve();
  const server=http.createServer((req,res)=>{
    const chunks=[];req.on('data',c=>chunks.push(c));
    req.on('end',()=>{queue=queue.then(()=>new Promise(resolve=>{
      const forward=http.request({hostname:'127.0.0.1',port:target,path:req.url,method:req.method,headers:{...req.headers,host:'127.0.0.1:'+target,connection:'close'},agent:false},up=>{
        // WebKit may cancel images/scripts on navigation. Drain the upstream so the queue continues.
        res.on('close',()=>{up.unpipe(res);up.resume();});
        if(!res.destroyed){res.writeHead(up.statusCode,{...up.headers,connection:'close'});up.pipe(res);}else up.resume();
        up.on('end',resolve);up.on('error',resolve);up.on('close',resolve);
      });
      forward.setTimeout(60000,()=>forward.destroy(Error('Local benchmark request timeout: '+req.url)));
      forward.on('error',()=>{if(!res.destroyed){res.writeHead(502);res.end();}resolve();});
      forward.end(Buffer.concat(chunks));
    }));});
  });
  await new Promise(r=>server.listen(port,'127.0.0.1',r));return server;
};
