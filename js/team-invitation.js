(() => {
  'use strict';
  window.RepoTeamInvitation={token(value){try{const url=new URL(value),expected=new URL('sucursales.php',document.baseURI),token=url.searchParams.get('invitacion')||'';return url.origin===expected.origin&&url.pathname===expected.pathname&&/^[a-f0-9]{64}$/.test(token)?token:null;}catch{return null;}},open(value){const token=this.token(value);if(!token)return;const url=new URL('sucursales.php',document.baseURI);url.searchParams.set('invitacion',token);location.assign(url.href);}};
})();
