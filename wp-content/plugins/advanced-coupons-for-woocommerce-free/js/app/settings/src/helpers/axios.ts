declare var axios: any;
declare var wpApiSettings: any;

// #endregion [Variables]

export default axios.create({
  baseURL: wpApiSettings.root,
  timeout: 30000,
  headers: { "X-WP-Nonce": wpApiSettings.nonce },
});
