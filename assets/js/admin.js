(function () {
	function parseJsonAttribute(element, attributeName) {
		var rawValue = element.getAttribute(attributeName);

		if (!rawValue) {
			return [];
		}

		try {
			return JSON.parse(rawValue);
		} catch (error) {
			return [];
		}
	}

	function renderCountryDonuts() {
		var donuts = document.querySelectorAll('.vwfw-country-donut[data-segments]');
		var swatches = document.querySelectorAll('.vwfw-country-swatch[data-color]');

		donuts.forEach(function (donut) {
			var segments = parseJsonAttribute(donut, 'data-segments');

			if (!Array.isArray(segments) || !segments.length) {
				return;
			}

			var gradientStops = segments
				.map(function (segment) {
					return [segment.color, Number(segment.start).toFixed(2) + '%', Number(segment.end).toFixed(2) + '%'].join(' ');
				})
				.join(', ');

			donut.style.background = 'conic-gradient(' + gradientStops + ')';
		});

		swatches.forEach(function (swatch) {
			swatch.style.background = String(swatch.getAttribute('data-color') || '');
		});
	}

	function bindConfirmationForms() {
		document.querySelectorAll('form[data-confirm]').forEach(function (form) {
			form.addEventListener('submit', function (event) {
				if (!window.confirm(String(form.getAttribute('data-confirm') || 'Continue?'))) {
					event.preventDefault();
				}
			});
		});
	}

	function initializeAdminAssets() {
		renderCountryDonuts();
		bindConfirmationForms();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initializeAdminAssets);
		return;
	}

	initializeAdminAssets();
})();
