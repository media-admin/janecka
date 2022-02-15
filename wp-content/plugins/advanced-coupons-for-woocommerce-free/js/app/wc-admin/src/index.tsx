import {render} from '@wordpress/element';
import AdminUpsellApp from "./pages";

declare var wp: any;

wp.domReady(() => {
  const appRoot = document.querySelector('#wpbody-content .wrap');

  if (appRoot) {
    render(
      <AdminUpsellApp />, 
      appRoot.appendChild(document.createElement('div'))
    );
  }
});
