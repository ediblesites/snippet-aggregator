/**
 * Live filter for the integrations archive: the filter pills show and hide the
 * integration cards already on the page (each card has the class
 * integ-type-<slug>), without a reload. The URL, heading and page title follow
 * the filter, so a filtered view can be shared, and Back and Forward work.
 * Without this script the pills are plain links to the integration type pages.
 */
(function () {
	var nav = document.querySelector('.integration-filter-nav[data-live]');
	if (!nav) return;
	var links = Array.prototype.slice.call(nav.querySelectorAll('a[data-integ-type]'));
	var cards = Array.prototype.slice.call(document.querySelectorAll('.wp-block-post-template > .wp-block-post'));
	var heading = document.querySelector('h1.wp-block-query-title');
	var status = nav.querySelector('.integration-filter-status');
	if (!links.length || !cards.length) return;

	var base = { url: location.pathname, heading: heading ? heading.textContent : '', title: document.title };

	// The pills' active and inactive looks come from their markup (theme or block
	// style classes, inline styles), so copy them from the pills as rendered
	// instead of assuming a class name.
	function look(a) {
		return { wrap: a.parentNode.className, link: a.className, style: a.getAttribute('style') };
	}
	var activeLink = links.filter(function (a) { return a.hasAttribute('aria-current'); })[0] || links[0];
	var inactiveLink = links.filter(function (a) { return a !== activeLink; })[0] || activeLink;
	var looks = { on: look(activeLink), off: look(inactiveLink) };
	function setLook(a, l) {
		a.parentNode.className = l.wrap;
		a.className = l.link;
		if (l.style) a.setAttribute('style', l.style); else a.removeAttribute('style');
	}

	function apply(slug) {
		var link = links.filter(function (a) { return a.dataset.integType === slug; })[0] || links[0];
		var shown = 0;
		cards.forEach(function (card) {
			var match = !slug || card.classList.contains('integ-type-' + slug);
			card.style.display = match ? '' : 'none';
			if (match) shown++;
		});
		links.forEach(function (a) {
			var active = a === link;
			setLook(a, active ? looks.on : looks.off);
			if (active) a.setAttribute('aria-current', 'page'); else a.removeAttribute('aria-current');
		});
		var name = slug ? link.dataset.name : '';
		if (heading) heading.textContent = name || base.heading;
		var parts = base.title.split(' | ');
		document.title = name ? parts[0] + ': ' + name + (parts.length > 1 ? ' | ' + parts.slice(1).join(' | ') : '') : base.title;
		if (status) status.textContent = shown + (shown === 1 ? ' integration' : ' integrations');
	}

	nav.addEventListener('click', function (e) {
		var a = e.target.closest('a[data-integ-type]');
		if (!a || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
		e.preventDefault();
		var slug = a.dataset.integType;
		var url = slug ? a.pathname : base.url;
		// Keep the existing history state: WordPress's Interactivity runtime stamps it
		// with a session id and reloads the page on Back when an entry lacks it.
		if (location.pathname !== url) history.pushState(Object.assign({}, history.state, { integType: slug }), '', url);
		apply(slug);
	});

	window.addEventListener('popstate', function (e) {
		if (!e.state || typeof e.state.integType !== 'string') return;
		apply(e.state.integType);
	});

	history.replaceState(Object.assign({}, history.state, { integType: '' }), '', location.href);
})();
