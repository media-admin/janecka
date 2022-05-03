document.addEventListener("DOMContentLoaded", function() {

  dywc.init({

   cookie_version: 1, // Version der Cookiedefinition, damit bei Konfigurationsänderung erneutes Opt-In erforderlich wird
   cookie_name: 'dywc', // Name des Cookies, der zur Speicherung der Entscheidung verwendet wird
   cookie_expire: 31536e3, // Laufzeit des Cookies in Sekunden (31536e3 = 1Jahr)
   cookie_path: '/', // Pfad auf dem der Cookie gespeichert wird
   mode: 1, // 1 oder 2, bestimmt den Buttonstil
   bglayer: true, // Verdunklung des Hintegrundes aktiv (true) oder inaktiv (false)
   position: 'mt', // mt, mm, mb, lt, lm, lb, rt, rm, rb

   id_bglayer: 'dywc_bglayer',
   id_cookielayer: 'dywc',
   id_cookieinfo: 'dywc_info',

   url_legalnotice: '/datenschutzerklaerung.html', // or null
   url_imprint: '/impressum.html', // or null

   text_title: 'Datenschutzeinstellungen',
   text_dialog: 'Wir nutzen Cookies auf unserer Website. Einige von ihnen sind essenziell, während andere uns helfen, diese Website und Ihre Erfahrung zu verbessern.',

   cookie_groups: [
	{
	 label: 'Notwendig',
	 fixed: true,
	 info: 'Zum Betrieb der Seite notwendige Cookies:',
	 cookies: [
	  {
	   label: 'PHP Session Cookie',
	   publisher: 'Eigentümer dieser Website',
	   aim: 'Absicherung Kontaktformular / SPAM Schutz',
	   name: 'PHPSESSID',
	   duraction: 'Session'
	  }, {
	   label: 'Cookiespeicherung Entscheidungscookie',
	   publisher: 'Eigentümer dieser Website',
	   aim: 'Speichert die Einstellungen der Besucher bezüglich der Speicherung von Cookies.',
	   name: 'dywc',
	   duration: '1 Jahr'
	  }
	 ]
	}, {
	 label: 'Statistiken',
	 fixed: false,
	 info: 'Cookies die zur Auswertung des Benutzerverhaltens notwendig sind:',
	 cookies: [
	  {
	   label: 'Google Analytics',
	   publisher: 'Google LLC',
	   aim: 'Cookie von Google für Website-Analysen. Erzeugt statistische Daten darüber, wie der Besucher die Website nutzt.',
	   name: '_ga,_gid',
	   duration: '2 Jahre'
	  }
	 ],
	 accept: function() {

   dywc.log("Load Statistic Tracking");

		var el = document.createElement('script');
		el.src = 'https://www.googletagmanager.com/gtag/js?id=G-EG4NB8NX4L';
		el.async = 1;
		document.getElementsByTagName('head')[0].appendChild(el);

		window.dataLayer = window.dataLayer || [];

		function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());

			gtag('config', 'UA-11678917-1', { 'anonymize_ip': true });
			gtag('config', 'G-EG4NB8NX4L', { 'anonymize_ip': true });

	 },

	 reject: function() {

			dywc.log("Reject Statistic Tracking");

			var el = document.createElement('script');
			el.src = 'https://www.googletagmanager.com/gtag/js?id=G-EG4NB8NX4L';
			el.async = 1;
			document.getElementsByTagName('head')[0].appendChild(el);

			window['ga-disable-G-EG4NB8NX4L'] = true;
			window.dataLayer = window.dataLayer || [];

			function gtag(){ dataLayer.push(arguments); }

			gtag('js', new Date());

			gtag('config', 'UA-11678917-1');
			gtag('config', 'G-EG4NB8NX4L');

	 }

	}
   ]

  });

  });