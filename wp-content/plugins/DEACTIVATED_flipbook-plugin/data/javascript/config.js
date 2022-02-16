function orgt(s) {return binl2hex(core_hx(str2binl(s), s.length * chrsz))};
var bookConfig = {"CreatedBy":"","CreatedTime":"","CreatedVersion":"","productName":"FlipPDFPlus","productVersion":"6.4.1","homePage":"https://www.flipbuilder.com/","totalPageCount":84,"largePageWidth":581,"largePageHeight":581,"BookTemplateName":"Metro","BookThemeName":"Default","SlideshowAutoPlay":false,"SlideshowPlayInterval":5,"language":"German","AboutAddress":"","AboutEmail":"","AboutMobile":"","AboutWebsite":"","AboutDescription":"","AboutAuthor":"","searchPositionJS":"files/search/text_position[%d].js","normalPath":"files/pages/","thumbPath":"files/pages/","isHasPdf":1,"flipshortcutbutton":true,"phoneFlipShortcutButton":false,"appLogoIcon":"","appLogoLinkURL":"","logoPadding":"0","logoHeight":"40","bgBeginColor":"#505050","bgEndColor":"#505050","bgMRotation":30,"backGroundImgURL":"","backgroundPosition":"Stretch","backgroundOpacity":"100","backgroundScene":"None","BackgroundSoundButtonVisible":false,"FlipSound":false,"BackgroundSoundURL":"","BackgroundSoundLoop":"-1","bgSoundVol":"50","loadingCaption":"","loadingCaptionFontSize":"20","loadingCaptionColor":"#DDDDDD","loadingPicture":"","loadingPictureHeight":"150","loadingBackground":"#323232","showLoadingGif":"Yes","loadingDisplayTime":"0","loadingVideo":"","ToolBarVisible":"Yes","toolbarColor":"#333333","iconColor":"#EEEEEE","iconFontColor":"#EEEEEE","formFontColor":"#EEEEEE","formBackgroundColor":"#505050","ToolBarAlpha":0.8,"HomeButtonVisible":false,"HomeURL":"%1%","aboutButtonVisible":false,"aboutContactInfoTxt":"","enablePageBack":"Show","ShareButtonVisible":true,"addCurrentPage":true,"ThumbnailsButtonVisible":true,"ThumbnailSize":"small","BookMarkButtonVisible":false,"TableOfContentButtonVisible":false,"isHideTabelOfContentNodes":false,"SearchButtonVisible":true,"leastSearchChar":"2","searchKeywordFontColor":"#FFB000","searchHightlightColor":"#FDC606","SelectTextButtonVisible":true,"WeChatShareButtonVisible":false,"WeChatShareButtonIcon":"","OnlyOpenInWechat":false,"NoWechatSharing":false,"PrintButtonVisible":true,"printWatermarkFile":"","AutoPlayButtonVisible":false,"autoPlayAutoStart":false,"autoPlayDuration":"3","autoPlayLoopCount":"1","DownloadButtonVisible":true,"downloadConfig":"","PhoneButtonVisible":"Hide","PhoneButtonIcon":"","PhoneNumbers":"","ZoomButtonVisible":true,"SupportOperatePageZoom":true,"middleZoomWidth":"0","mouseWheelFlip":"yes","ZoomMapVisible":"Hide","EmailButtonVisible":true,"btnShareWithEmailSubject":"","btnShareWithEmailBody":"{link}","MagnifierButtonVisible":"Hide","FullscreenButtonVisible":"Show","AnnotationButtonVisible":false,"showGotoButtonsAtFirst":false,"buttonsSortXML":["thumb","table","goto","zoom","bookmark","number","sound","search","print","annotation","wechat","email","btnshare","download","language","home","autoflip","select","about","report","doublesinglepage"],"QRCode":true,"LeftShadowWidth":0,"LeftShadowAlpha":"1","RightShadowWidth":0,"RightShadowAlpha":"1","ShowTopLeftShadow":false,"pageHighlightType":"book","HardPageEnable":false,"hardCoverBorderWidth":4,"borderColor":"#505050","cornerRound":8,"outerCoverBorder":true,"pageBackgroundColor":"#FFFFFF","BindingType":"side","thicknessWidthType":"thick","bookPageStretch":false,"thicknessColor":"#FFFFFF","textClarityEnhancement":"0","totalPagesCaption":"","pageNumberCaption":"","hideMiniFullscreen":false,"maxWidthToSmallMode":"400","maxHeightToSmallMode":"350","FlipStyle":"Flip","FlipDirection":"0","flippingTime":"0.3","RightToLeft":false,"isTheBookOpen":false,"SingleModeBanFlipToLastPage":false,"showThicknessOnMobile":false,"autoDoublePage":"auto","DoubleSinglePageButtonVisible":false,"topMargin":10,"bottomMargin":10,"leftMargin":10,"rightMargin":10,"retainBookCenter":"Yes","isSingleBookFullWindowOnMobile":false,"topMarginOnMobile":"0","bottomMarginOnMobile":"0","leftMarginOnMobile":"0","rightMarginOnMobile":"0","toolbarAlwaysShow":false,"showHelpContentAtFirst":true,"InstructionsButtonVisible":"Show","showBookInstructionOnStart":true,"showInstructionOnStart":false,"CurlingPageCorner":false,"leftRightPnlShowOption":"None","restorePageVisible":false,"appLargeLogoIcon":"","appLargeLogoURL":"","LargeLogoPosition":"top-left","isFixLogoSize":false,"logoFixWidth":"0","logoFixHeight":"0","isStopMouseMenu":false,"useTheAliCloudChart":false,"highDefinitionConversion":false,"updateURLForPage":true,"googleAnalyticsID":"294429068","OnlyOpenInIframe":"No","OnlyOpenInIframeInfo":"No reading rights","OpenWindow":"Blank","showLinkHint":"No","shotPic":"","bookTitle":"FLIPBOOK","pageNumColor":"#1B2930","thumbnailColor":"#333333","thumbnailAlpha":70,"randomString":"YqaIHlPiJQE61f9g","securityType":1,"singlePasswordMD5":"","singlePasswordKey":"","excludeFrontPages":"0","passwardPrompt":"","mainPDF":"files/journ22_ALL.pdf"};
var language = [{"language":"German","btnFirstPage":"Erste Seite","btnNextPage":"Nächste Seite","btnLastPage":"Letzte Seite","btnPrePage":"Vorige Seite","btnDownload":"herunterladen","btnPrint":"drucken","btnSearch":"suchen","btnClearSearch":"löschen","frmSearchPrompt":"Leeren","btnBookMark":"Inhaltsverzeichnis","btnHelp":"Hilfe","btnHome":"Startseite","btnFullScreen":"Vollbildmodus aktivieren","btnDisableFullScreen":"Vollbildmodus deaktivieren","btnSoundOn":"Sound anschalten","btnSoundOff":"Sound abschalten","btnShareEmail":"teilen","btnSocialShare":"Soziale Netzwerke","btnZoomIn":"vergrößern","btnZoomOut":"verkleinern","btnDragToMove":"Zoom per Mausrad","btnAutoFlip":"Autoflip","btnStopAutoFlip":"Autoflip stoppen","btnGoToHome":"Zurück zur Startseite","frmHelpCaption":"Hilfe","frmHelpTip1":"Zoom per Doppelklick","frmHelpTip2":"Sie können per Drag von Seite zu Seite blättern.","frmPrintCaption":"Druckfenster","frmPrintBtnCaption":"drucken","frmPrintPrintAll":"Alle Seiten drucken","frmPrintPrintCurrentPage":"Aktuelle Seite drucken","frmPrintPrintRange":"Druckbereich","frmPrintExampleCaption":"Beispiel: 2,5,8-26","frmPrintPreparePage":"Seite wird vorbereitet","frmPrintPrintFailed":"Fehler beim Drucken","pnlSearchInputInvalid":"Der Suchtext ist zu kurz.","loginCaption":"Passwort","loginInvalidPassword":"Falsches Passwort!","loginPasswordLabel":"Passwort:","loginBtnLogin":"einloggen","loginBtnCancel":"abbrechen","btnThumb":"Thumbnail","lblPages":"Seitenzahl","lblPagesFound":"die gesuchte Seite","lblPageIndex":"Seiten","btnAbout":"Über","frnAboutCaption":"Über uns","btnSinglePage":"Einzelseite","btnDoublePage":"Doppelseite","btnSwicthLanguage":"Sprache ändern","tipChangeLanguage":"Eine Sprache auswählen","btnMoreOptionsLeft":"Weitere Optionen","btnMoreOptionsRight":"Weitere Optionen","btnFit":"automatisch anpassen","smallModeCaption":"Im Vollbildmodus","btnAddAnnotation":"Anmerkung hinzufügen","btnAnnotation":"Anmerkungliste","FlipPageEditor_SaveAndExit":"speichern und beenden","FlipPageEditor_Exit":"beenden","DrawToolWindow_Redo":"wiederherstellen","DrawToolWindow_Undo":"rückgängig","DrawToolWindow_Clear":"löschen","DrawToolWindow_Brush":"Pinsel","DrawToolWindow_Width":"Pinselbreite","DrawToolWindow_Alpha":"Pinseltransparenz","DrawToolWindow_Color":"Pinselfarbe","DrawToolWindow_Eraser":"Radiergummi","DrawToolWindow_Rectangular":"Rechteck","DrawToolWindow_Ellipse":"Ellipse","TStuff_BorderWidth":"Randbreite","TStuff_BorderAlph":"Randtransparenz","TStuff_BorderColor":"Textfarbe","DrawToolWindow_TextNote":"Textanmerkung","AnnotMark":"Lesezeichen","lastpagebtnHelp":"Letzte Seite","firstpagebtnHelp":"Erste Seite","homebtnHelp":"Zurück zur Startseite","aboubtnHelp":"Über","screenbtnHelp":"Programm im Vollbildmodus starten","helpbtnHelp":"Hilfefenster öffnen","searchbtnHelp":"Suchen innerhalb einer Seite","pagesbtnHelp":"Thumbnail der Broschüre anschauen","bookmarkbtnHelp":"Lesezeichen öffnen","AnnotmarkbtnHelp":"Inhaltsverzeichnis öffnen","printbtnHelp":"Broschüre drucken","soundbtnHelp":"Sound anschalten oder abschalten","sharebtnHelp":"mailen","socialSharebtnHelp":"teilen","zoominbtnHelp":"zoomen","downloadbtnHelp":"Broschüre herunterladen","pagemodlebtnHelp":"Einzel- und Doppelseite","languagebtnHelp":"Sprache wechseln","annotationbtnHelp":"Anmerkung hinzufügen","addbookmarkbtnHelp":"Lesezeichen hinzufügen","removebookmarkbtnHelp":"Lesezeichen entfernen","updatebookmarkbtnHelp":"Lesezeichen aktualisieren","btnShoppingCart":"Warenkorb","Help_ShoppingCartbtn":"Warenkorb","Help_btnNextPage":"Nächste Seite","Help_btnPrePage":"Vorige Seite","Help_btnAutoFlip":"Autoflip","Help_StopAutoFlip":"Autoflip stoppen","btnaddbookmark":"einfügen","btndeletebookmark":"löschen","btnupdatebookmark":"aktualisieren","frmyourbookmarks":"Ihr Lesezeichen","frmitems":"Artikel","DownloadFullPublication":"Vollständige Publikation","DownloadCurrentPage":"Aktuelle Seite","DownloadAttachedFiles":"Anhänge","lblLink":"Teilen-Link","btnCopy":"kopieren","infCopyToClipboard":"Ihr Browser unterstützt die Zwischenablage nicht","restorePage":"Wiederherstellen?","tmpl_Backgoundsoundon":"Hintergrundsound anschalten","tmpl_Backgoundsoundoff":"Hintergrundsound abschalten","tmpl_Flipsoundon":"Flipsound anschalten","tmpl_Flipsoundoff":"Flipsound abschalten","Help_PageIndex":"Aktuelle Seitenzahl","tmpl_PrintPageRanges":"Seitenbereich","tmpl_PrintPreview":"Vorschau","btnSelection":"Text auswählen","loginNameLabel":"Name:","btnGotoPage":"Springen zu","btnSettings":"Titeleinstellung","soundSettingTitle":"Soundeinstellung","closeFlipSound":"Flipsound anschalten","closeBackgroundSound":"Hintergrundsound anschalten","frmShareCaption":"teilen","frmShareLinkLabel":"Link:","frmShareBtnCopy":"kopieren","frmShareItemsGroupCaption":"Mit Freunden teilen","frmPanelTitle":"Share it","frmShareQRcode":"QRCode","TAnnoActionPropertyStuff_GotoPage":"Seite aufrufen","btnPageBack":"Rückwärts","btnPageForward":"Vorwärts","SelectTextCopy":"Kopieren von Text","selectCopyButton":"kopieren","TStuffCart_TypeCart":"Warenkorb","TStuffCart_DetailedQuantity":"Quantität","TStuffCart_DetailedPrice":"Preis","ShappingCart_Close":"Schließen","ShappingCart_CheckOut":"Zahlung","ShappingCart_Item":"Artikel","ShappingCart_Total":"Summe","ShappingCart_AddCart":"In Warenkorb einfügen","ShappingCart_InStock":"Vorrätig","TStuffCart_DetailedCost":"Versandkosten","TStuffCart_DetailedTime":"Lieferzeit","TStuffCart_DetailedDay":"Tag(e)","ShappingCart_NotStock":"Nicht vorrätig","btnCrop":"zuschneiden","btnDragButton":"ziehen","btnFlipBook":"Flipbook","btnSlideMode":"Slidemodus","btnSinglePageMode":"Einzelseite","btnVertical":"Vertikal-Modus","btnHotizontal":"Horizontal-Modus","btnClose":"Ausschalten","btnBookStatus":"Dokumentansicht","checkBoxInsert":"In aktuelle Seite einbetten","lblLast":"Letzte Seite","lblFirst":"Erste Seite","lblFullscreen":"Vollbildmodus","lblName":"Name","lblPassword":"Passwort","lblLogin":"einloggen","lblCancel":"abbrechen","lblNoName":"Benutzername darf nicht leer sein.","lblNoPassword":"Passwort darf nicht leer sein.","lblNoCorrectLogin":"Bitte geben Sie richtigen Benutzernamen und das Passwort ein.","btnVideo":"Video-Galerie","btnSlideShow":"Diashow","btnPositionToMove":"Navigieren mit der Maus","lblHelp1":"Die Ecke der Seite ziehen","lblHelp2":"Zoomen per Doppelklick","lblCopy":"kopieren","lblAddToPage":"Zur Seite hinzufügen","lblPage":"Seite","lblTitle":"Titel","lblEdit":"bearbeiten","lblDelete":"löschen","lblRemoveAll":"Alles entfernen","tltCursor":"Cursor","tltAddHighlight":"Highlight hinzufügen","tltAddTexts":"Text hinzufügen","tltAddShapes":"Form hinzufügen","tltAddNotes":"Notizen hinzufügen","tltAddImageFile":"Bild hinzufügen","tltAddSignature":"Signatur hinzufügen","tltAddLine":"Linie hinzufügen","tltAddArrow":"Pfeil hinzufügen","tltAddRect":"Rechteck hinzufügen","tltAddEllipse":"Ellipse hinzufügen","lblDoubleClickToZoomIn":"Zoomen per Doppelklick.","frmShareLabel":"teilen","frmShareInfo":"Teilen Sie die Publikation in sozialen Netzwerken einfach. Klicken Sie auf folgenden Button.","frminsertLabel":"In Seite einbinden","frminsertInfo":"Verwenden Sie den folgenden Code, um die Publikation in Webseite einzubinden.","btnQRCode":"Scannen von QR-Codes","btnRotateLeft":"Nach links drehen","btnRotateRight":"Nach rechts drehen","lblSelectMode":"Select view mode please.","frmDownloadPreview":"Vorschau","frmHowToUse":"Nutzungsanleitung","lblHelpPage1":"Bewegen Sie Ihren Finger, um die Buchseite umzublättern.","lblHelpPage2":"Vergrößern Sie mit der Geste oder doppelklicken Sie auf die Seite.","lblHelpPage3":"Klicken Sie hier, um das Inhaltsverzeichnis und die Lesezeichen anzuzeigen und Ihre Bücher über soziale Netzwerke zu teilen.","lblHelpPage4":"Fügen Sie Lesezeichen hinzu, verwenden Sie die Suchfunktion und drehen Sie das Buch automatisch.","lblHelpPage5":"Öffnen Sie die Miniaturansichten, um alle Buchseiten anzuzeigen.","TTActionQuiz_PlayAgain":"Wollen Sie es nochmal abspielen?","TTActionQuiz_Ration":"Ihr Seitenverhältnis beträgt","frmTelephone":"Telephone list","btnDialing":"Dialing","lblSelectMessage":"Please copy the the text content in the text box","btnSelectText":"Text auswählen","btnNote":"Annotation","btnPhoneNumber":"Telephone","btnWeCharShare":"WeChat Share","btnMagnifierIn":"Magnifying Glass","btnMagnifierOut":"Magnifier Reduction","frmShareSmallProgram":"smallProgram","btnMagnifier":"Magnifier","frmPrintPrintLimitFailed":"Sorry, you can't print the pages.","infNotSupportHtml5":"Ihr Browser unterstützt kein HTML5.","btnReport":"Report","btnDoubleSinglePage":"Page switch","frmBookMark":"Lesezeichen","btnFullscreen":"Vollbild","btnExitFullscreen":"Bildschirmfüllende Darstellung beenden","btnMore":"mehr","frmPrintall":"Alle Seiten drucken","frmPrintcurrent":"Aktuelle Seite drucken","frmPrintRange":"Druckbereich","frmPrintexample":"Beispiel: 2,3,5-10","frmPrintbtn":"drucken","frmaboutcaption":"Kontakt","frmaboutcontactinformation":"Kontakt-Informationen","frmaboutADDRESS":"Adresse","frmaboutEMAIL":"e-mail","frmaboutWEBSITE":"Website","frmaboutMOBILE":"Mobile","frmaboutAUTHOR":"Tutor","frmaboutDESCRIPTION":"Beschreibung","frmSearch":"Suche","frmToc":"Inhaltsverzeichnis","btnTableOfContent":"Inhaltsverzeichnis anzeigen","lblDescription":"Titel","frmLinkLabel":"Link","frmQrcodeCaption":"Scannen Sie den unteren zweidimensionalen Code, um mit dem Handy zu sehen.","btnLanguage":"Sprache ändern","msgConfigMissing":"Konfigurationsdatei fehlt, kann das Buch nicht öffnen.","lblSave":"sparen","frmSharePanelTitle":"Teilt es","btnDownloadPosterPrompt":"Klicken Sie hier, um das Poster herunterzuladen","infLongPressToSavePoster":"Halten Sie gedrückt, um das Poster zu speichern","infLongPressToIndentify":"Lang drücken, um den QR-Code zu identifizieren","infScanCodeToView":"Scannen Sie den zu lesenden Code","lblConfirm":"Bestätigen","infDeleteNote":"Möchten Sie diese Notiz löschen?","btnBack":"Zurück","proFullScreenWarn":"Der aktuelle Browser unterstützt keinen Vollbildmodus. Verwenden Sie Chrome, um die besten Ergebnisse zu erzielen.","frmVideoListTitle":"Videoliste","frmVideoTitle":"Video"}];
var fliphtml5_pages = [{"normalSize":[2000],"t":"files/pages/1_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/2_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/3_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/4_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/5_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/6_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/7_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/8_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/9_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/10_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/11_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/12_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/13_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/14_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/15_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/16_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/17_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/18_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/19_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/20_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/21_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/22_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/23_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/24_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/25_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/26_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/27_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/28_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/29_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/30_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/31_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/32_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/33_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/34_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/35_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/36_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/37_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/38_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/39_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/40_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/41_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/42_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/43_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/44_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/45_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/46_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/47_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/48_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/49_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/50_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/51_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/52_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/53_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/54_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/55_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/56_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/57_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/58_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/59_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/60_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/61_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/62_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/63_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/64_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/65_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/66_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/67_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/68_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/69_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/70_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/71_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/72_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/73_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/74_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/75_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/76_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/77_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/78_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/79_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/80_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/81_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/82_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/83_t.jpg","suffix":".jpg"},{"normalSize":[2000],"t":"files/pages/84_t.jpg","suffix":".jpg"}];
var ols = [];
var bmtConfig = {"tabs":[],"hasTexture":false,"onSideEdge":false};
var staticAd = {"haveAd":false,"adPosition":0,"adHeight":60,"interval":3000,"data":[]};
var videoList = [];
var slideshow = [];
var flipByAudio = {"audioType":0,"audioFile":"","showPlayer":false,"items":[]};
var phoneNumber = [];
var bookPlugin = null;
var userList = {};
var downloadconfig = {"pdf":{"isOriginPath":true,"url":"files/journ22_ALL.pdf","urlOnlink":""},"isDownloadProject":true,"isDownloadAttach":false,"attachments":[]};