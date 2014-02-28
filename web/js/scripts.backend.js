/*global $, console, Barista, window, document */

$(function () {
	"use strict";

	var Barista = window.Barista = window.Barista || {};

	Barista.Backend = (function () {
		var BaristaBackend = function () {
			this.$content = $('#content');
			this.$loading = $('#loading');
			this.$confirm = $('#confirm');
			this.entityName = $('#entityName').text();
			this.confirmCallback = function () {};
			this.availableTags = [];
			this.FUV = {};
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
				// Bind close to new alerts
				this.$content.on('click', '.alert-box .close', function (e) {
					e.preventDefault();
					$(this).parent().hide('fade');
				});
				// Confirm modal
				this.$confirm.on('click', 'a', function (e) {
					e.preventDefault();
					self.closeConfirm();
					if ($(this).hasClass('true')) {
						if (typeof self.confirmCallback === 'function') {
							self.confirmCallback.call();
							self.confirmCallback = null;
						}
						return true;
					}
					return false;
				});
				// Delete entity
				this.$content.on('click', '.delen', function (e) {
					e.preventDefault();
					var $btn = $(this);
					self.openConfirm('Desea borrar ' + $btn.data('value') + '?');
					self.confirmCallback = function () {
						window.location = $btn.attr('href');
					};
				}),
				// Remove CF
				this.$content.on('click', '.rmcf', function (e) {
					e.preventDefault();
					self.openConfirm('Desea borrar el campo personalizado?');
					var $btn = $(this);
					self.confirmCallback = function () {
						$btn.parents('.cf').remove();
					};
				});
				// add CF
				this.$content.on('click', '.addcf', function (e) {
					e.preventDefault();
					var i = $(this).data('index'),
						j = i + 1;
					$(this).data('index', j);
					var $ncf = $(this).prev('.cf').clone();
					$ncf.find('input').each(function() {
						if ($(this).attr('type') !== 'hidden') {
							$(this).val(null);
						}
						$(this).attr('name', $(this).attr('name').replace(i, j));
					});
					$(this).before($ncf);
				});
				// Tags
				this.$content.find('.autocomplete')
					.on('keydown', function(event) {
						self.availableTags = $(this).nextAll('.availabletags').text().split(',');
						if (event.keyCode === $.ui.keyCode.TAB &&
							$(this).data("ui-autocomplete").menu.active) {
							event.preventDefault();
						}
					})
					.autocomplete({
						minLength: 0,
						source: function(request, response) {
							// delegate back to autocomplete, but extract the last term
							response($.ui.autocomplete.filter(
							self.availableTags, self.extractLast(request.term)));
						},
						focus: function() {
							// prevent value inserted on focus
							return false;
						},
						select: function(event, ui) {
							var terms = self.split(this.value);
							// remove the current input
							terms.pop();
							// add the selected item
							terms.push(ui.item.value);
							// add placeholder to get the comma-and-space at the end
							terms.push("");
							this.value = terms.join(", ");
							return false;
						}
					});
				// Fileupload Single
				this.$content.find('.fileupload.single').fileupload({
					url: self.baseUrl + '/xhr/upload',
					dataType: 'json',
					add: function (e, data) {
						self.setFileUploadVars($(e.target));
						data.submit();
					},
					submit: function (e, data) {
						data.formData = {filetype: $(e.target).hasClass('image') ? 'image' : 'file' };
					},
					done: function (e, data) {
						var msg, classname;
						if (data.result.success) {
							var file = data.result.files[0];
							self.FUV.upcontainer.html('');
							self.appendFile(file);
							self.FUV.button.text('Reemplazar');
							self.FUV.button.addClass('secondary');
							self.FUV.hidden.val(file);
							msg = 'Se subio el archivo ' + file + ' correctamente';
							classname = 'success';
						} else {
							msg = 'Error en la carga del archivo';
							classname = 'alert';
						}
						self.createAlert(classname, msg, self.FUV.alertcontainer);
					},
					fail: function (e, data) {
						var msg = 'Error en la carga del archivo';
						self.createAlert('alert', msg, self.FUV.alertcontainer);
					},
					always: function (e, data) {
						console.log(data.result);
						self.FUV.progressbar.css('display', 'none');
					},
					progress: function (e, data) {
						var progress = parseInt(data.loaded / data.total * 100, 10);
						self.FUV.progressbar.css({
								'width': progress + '%',
								'display': 'block'
							}
						);
					}
				});
				// Fileupload Multi
				this.$content.find('.fileupload.multiple').fileupload({
					url: self.baseUrl + '/xhr/upload',
					dataType: 'json',
					add: function (e, data) {
						self.setFileUploadVars($(e.target));
						data.submit();
					},
					submit: function (e, data) {
						data.formData = {filetype: $(e.target).hasClass('image') ? 'image' : 'file' };
					},
					done: function (e, data) {
						var msg, classname;
						if (data.result.success) {
							var file = data.result.files[0];
							self.appendFile(file);
							var files = self.FUV.hidden.val().split(', ');
							files.push(file);
							self.FUV.hidden.val(files.join(', '));
							msg = 'Se subio el archivo ' + file + ' correctamente';
							classname = 'success';
						} else {
							msg = 'Error en la carga del archivo';
							classname = 'alert';
						}
						self.createAlert(classname, msg, self.FUV.alertcontainer);
					},
					fail: function (e, data) {
						var msg = 'Error en la carga del archivo';
						self.createAlert('alert', msg, self.FUV.alertcontainer);
					},
					always: function (e, data) {
						console.log(data.result);
						self.FUV.progressbar.css('display', 'none');
					},
					progress: function (e, data) {
						var progress = parseInt(data.loaded / data.total * 100, 10);
						self.FUV.progressbar.css({
								'width': progress + '%',
								'display': 'block'
							}
						);
					}
				});
				// Remove Fileuploaded
				this.$content.find('.upload-container').on('click', '.remove', function () {
					var multiple = $(this).parents('.upload-container').hasClass('multiple');
					var file = $(this).prevAll('a').text();
					if (!self.FUV.input) {
						self.setFileUploadVars($(this));
					}
					self.deleteFile(file);
					if (multiple) {
						var files = self.FUV.hidden.val().split(', ');
						var index = $.inArray(file, files);
						if (index !== -1) {
							files.splice(index, 1);
						}
						self.FUV.hidden.val(files.join(', '));
					} else {
						self.FUV.button.removeClass('secondary').text('Agregar +');
						self.FUV.hidden.val('');
					}
					$(this).parent().remove();
				});
			},
			appendFile: function (file) {
				this.FUV.upcontainer.append('<div class="radius label upload"><a href="http://' + document.domain + '/content/' + file + '" target="_blank" title="' + file + '">' + file + '</a><span class="remove">&times;</span></div>');
			},
			setFileUploadVars: function ($target) {
				var $btnc = $target.parents('.file-upload-module').find('.btn-container');
				if (!$target.hasClass('fileupload')) {
					$target = $btnc.find('.fileupload');
				}
				this.FUV = {
					input: $target,
					hidden: $target.nextAll('input'),
					button: $target.prevAll('.button'),
					btncontainer: $btnc,
					progressbar: $btnc.nextAll('.progressbar-container').find('.bar'),
					upcontainer: $btnc.nextAll('.upload-container'),
					alertcontainer: $btnc.nextAll('.alerts')
				}
			},
			saveOrder: function () {
				var items = [], self = this, msg, alertclass;
				this.$content.find('.sortable tr').each(function () {
					items.push($(this).data('id'));
				});
				this.openLoading();
				$.post(this.baseUrl + "/xhr/saveorder", {items: items, en: self.entityName}, function (res) {
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
			createAlert: function (classname, msg, $context) {
				var $box = $('<div></div>');
				var time = new Date();
				msg = time.getHours() + ':' + time.getMinutes() + ':' + time.getSeconds() + ' // ' + msg;
				$box
					.attr('data-alert', '')
					.attr('class', 'alert-box radius ' + classname)
					.html(msg + '<a href="#" class="close">&times;</a>');
				if ($context) {
					$context.prepend($box);
				} else {
					this.$content.prepend($box);
				}
			},
			openConfirm: function (msg) {
				this.$confirm.find('p').text(msg);
				this.$confirm.foundation('reveal', 'open');
			},
			closeConfirm: function () {
				this.$confirm.foundation('reveal', 'close');
			},
			openLoading: function () {
				this.$loading.foundation('reveal', 'open');
			},
			closeLoading: function () {
				var self = this;
				setTimeout(function () {
					self.$loading.foundation('reveal', 'close');
				}, 200);
			},
			setBaseUrl: function () {
				var url = document.domain;
				this.baseUrl = 'http://' + url + '/admin';
			},
			toogleEntityState: function ($elem) {
				/*
				var id = $elem.data('id'),
					en = $elem.data('en');
				// Toogle state
				$elem.toggleClass('hidden');
				$.post(this.baseUrl + "/xhr/togglestate", {id: id, en: en});
				*/
			},
			toogleEntityStarred: function ($elem) {
				/*
				var id = $elem.data('id'),
					en = $elem.data('en');
				// Toogle state
				$elem.toggleClass('starred');
				$.post(this.baseUrl + "/xhr/togglestarred", {id: id, en: en});
				*/
			},
			deleteFile: function (filename) {
				$.post(this.baseUrl + "/xhr/deletefile", {filename: filename});
			},
			split: function (val) {
				return val.split(/,\s*/);
			},
			extractLast: function (term) {
				return this.split(term).pop();
			}
		};
		return new BaristaBackend();
	}());

	Barista.init = function () {
		this.Backend.init();
	};
});


