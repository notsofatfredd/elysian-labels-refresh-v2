/* Synchronous, render-blocking by design — sets data-theme before first paint
   so there is no flash of the wrong theme. Manual choice wins over system
   preference; absence of a stored choice means "follow system", so no
   attribute is set and the CSS prefers-color-scheme media query decides. */
(function(){
  try{
    var stored = localStorage.getItem('elysian-theme');
    if(stored === 'dark' || stored === 'light'){
      document.documentElement.setAttribute('data-theme', stored);
    }
  }catch(e){}
})();
