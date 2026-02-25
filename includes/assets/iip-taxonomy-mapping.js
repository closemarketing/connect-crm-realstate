/**
 * Taxonomy Mapping Repeater JavaScript
 *
 * Handles add/remove rows for the CRM Field ↔ Taxonomy repeater.
 *
 * @package Connect CRM Real State
 */

(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var addBtn   = document.getElementById('ccrmre-add-taxonomy-row');
		var tbody    = document.getElementById('ccrmre-taxonomy-mapping-body');
		var tmplNode = document.getElementById('tmpl-ccrmre-taxonomy-row');

		if (!addBtn || !tbody || !tmplNode) {
			return;
		}

		/** Returns the next available row index. */
		function getNextIndex() {
			var rows  = tbody.querySelectorAll('.ccrmre-taxonomy-row');
			var maxId = -1;
			rows.forEach(function (row) {
				var idx = parseInt(row.getAttribute('data-index'), 10);
				if (!isNaN(idx) && idx > maxId) {
					maxId = idx;
				}
			});
			return maxId + 1;
		}

		/** Replaces the {{INDEX}} placeholder in the template. */
		function buildRowHtml(index) {
			return tmplNode.innerHTML.replace(/\{\{INDEX\}\}/g, index);
		}

		// Add row handler.
		addBtn.addEventListener('click', function () {
			var index = getNextIndex();
			var temp  = document.createElement('tbody');
			temp.innerHTML = buildRowHtml(index);

			var newRow = temp.querySelector('tr');
			if (newRow) {
				tbody.appendChild(newRow);
				bindRemoveButton(newRow);
			}
		});

		/** Binds the remove button inside a single row. */
		function bindRemoveButton(row) {
			var btn = row.querySelector('.ccrmre-remove-taxonomy-row');
			if (!btn) {
				return;
			}
			btn.addEventListener('click', function () {
				var totalRows = tbody.querySelectorAll('.ccrmre-taxonomy-row').length;
				if (totalRows <= 1) {
					row.querySelectorAll('select').forEach(function (sel) {
						sel.value = '';
					});
					return;
				}
				row.remove();
			});
		}

		// Bind existing rows.
		tbody.querySelectorAll('.ccrmre-taxonomy-row').forEach(bindRemoveButton);

		// Client-side validation on form submit.
		var form = document.getElementById('ccrmre-taxonomy-form');
		if (form) {
			form.addEventListener('submit', function (e) {
				var rows    = tbody.querySelectorAll('.ccrmre-taxonomy-row');
				var isValid = true;

				rows.forEach(function (row) {
					var crmSel = row.querySelector('.ccrmre-crm-field-select');
					var taxSel = row.querySelector('.ccrmre-taxonomy-select');

					if (!crmSel || !taxSel) {
						return;
					}

					var crmVal = crmSel.value;
					var taxVal = taxSel.value;

					// Both empty is OK (will be skipped on save).
					if (!crmVal && !taxVal) {
						return;
					}

					// One filled, one empty — invalid.
					if (!crmVal || !taxVal) {
						isValid = false;
						row.classList.add('ccrmre-row-error');
					} else {
						row.classList.remove('ccrmre-row-error');
					}
				});

				if (!isValid) {
					e.preventDefault();
					alert(ccrmreTaxonomyMapping.noFieldSelected);
				}
			});
		}
	});
})();
