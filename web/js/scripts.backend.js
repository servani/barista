/*global $, console, Barista, window, document */

$(function () {
	"use strict";

	var Barista = window.Barista = window.Barista || {};

	Barista.Backend = (function () {
		var BaristaBackend = function () {
			this.$content = $('#content');
			this.$loading = $('#loading');
		};
		BaristaBackend.prototype = {
			init: function () {
				this.setBaseUrl();
				this.bindEvents();
			},
			bindEvents: function () {
				var self = this;
				// Toggle state
				this.$content.find('.toggle-state').on('click', 'span', function () {
					self.toogleEntityState($(this));
					return false;
				});
				// Toggle starred
				this.$content.find('.toggle-starred').on('click', 'span', function () {
					self.toogleEntityStarred($(this));
					return false;
				});
				// Delete File
				this.$content.on('click', '.delete-file', function () {
					self.deleteFile($(this));
					return false;
				});
				// Submit Order
				this.$content.on('click', '.submit-order', function (e) {
					e.preventDefault();
					var en = $(this).data('en');
					self.saveOrder(en);
				});
				// Bind close to new alerts
				this.$content.on('click', '.alert-box .close', function (e) {
					e.preventDefault();
					console.log('explode!');
					$(this).parent().hide('fade');
				});
			},
			saveOrder: function (en) {
				var items = [], self = this, msg, alertclass;
				this.$content.find('.sortable tr').each(function () {
					items.push($(this).data('id'));
				});
				this.openLoading();
				$.post(this.baseUrl + "/xhr/saveorder", {items: items, en: en}, function (res) {
					if (res.success) {
						msg = 'Los cambios fueron guardados exitosamente.';
						alertclass = 'success';
					} else {
						msg = 'Error inesperado. Los cambios no fueron guardados. Por favor, recargue la página y vuelva a intentarlo.';
						alertclass = 'warning';
					}
					self.closeLoading();
					self.createAlert(alertclass, msg);
				}, 'json');
			},
			createAlert: function (classname, msg) {
				var $box = $('<div></div>');
				var time = new Date();
				msg = time.getHours() + ':' + time.getMinutes() + ':' + time.getSeconds() + ' / ' + msg;
				$box
					.attr('data-alert', '')
					.attr('class', 'alert-box radius ' + classname)
					.html(msg + '<a href="#" class="close">&times;</a>');
				this.$content.prepend($box);
			},
			openLoading: function () {
				this.$loading.foundation('reveal', 'open');
			},
			closeLoading: function () {
				this.$loading.foundation('reveal', 'close');
			},
			setBaseUrl: function () {
				var url = document.domain;
				this.baseUrl = 'http://' + url + '/admin';
			},
			toogleEntityState: function ($elem) {
				var id = $elem.data('id'),
					en = $elem.data('en');
				// Toogle state
				$elem.toggleClass('hidden');
				$.post(this.baseUrl + "/xhr/togglestate", {id: id, en: en});
			},
			toogleEntityStarred: function ($elem) {
				var id = $elem.data('id'),
					en = $elem.data('en');
				// Toogle state
				$elem.toggleClass('starred');
				$.post(this.baseUrl + "/xhr/togglestarred", {id: id, en: en});
			},
			deleteFile: function ($elem) {
				var self = this,
					data = $elem.data();
				var $msg = $elem.parent();
				if (confirm(data.confirm)) {
					$.post(this.baseUrl + "/xhr/deletefile", {en: data.en, prop: data.prop, id: data.id}, function () {
						$msg.remove();
						self.$content.find('#' + data.en + data.prop).remove();
					});
				}
			}
		};
		return new BaristaBackend();
	}());

	Barista.init = function () {
		this.Backend.init();
	};
	Barista.init();
});