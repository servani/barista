/*global $, console, Barista, window, document */

$(function () {
	"use strict";

	var Barista = window.Barista = window.Barista || {};

	Barista.Backend = (function () {
		var BaristaBackend = function () {
			this.$wrapper = $('#wrapper');
			this.$loading = $('#loading');
			this.$confirm = $('#confirm');
			this.$loading = $('#loading');
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
				// ctrl + save
				$(document).bind('keydown', function(e) {
					if(e.ctrlKey && (e.which == 83)) {
						e.preventDefault();
						self.$wrapper.find('form input#ctrls').click();
						return false;
					}
				});
				// Toggle Flag
				this.$wrapper.find('.toggleflag').on('click', 'span', function (e) {
					e.preventDefault();
					self.toggleEntityFlag($(this));
					return false;
				});
				// Items x page
				this.$wrapper.find('#results-page').on('submit', function (e){
					e.preventDefault();
				});
				this.$wrapper.find('#results-page').on('keypress', 'input', function (e){
					if (e.keyCode == 13) {
						self.$wrapper.find('#results-page a.btn').click();
					}
				});
				this.$wrapper.find('#results-page').on('click', 'a', function (e) {
					e.preventDefault();
					var $input = $('input', e.delegateTarget);
					if ($input.val()) {
						var href = $(this).attr('href') + $input.val();
						window.location = href;
					}
				});
				// Delete File
				this.$wrapper.on('click', '.delete-file', function () {
					self.deleteFile($(this));
					return false;
				});
				// Bind close to new alerts
				this.$wrapper.on('click', '.alert-box .close', function (e) {
					e.preventDefault();
					$(this).parent().hide('fade');
				});
				// Confirm modal
				this.$confirm.on('click', 'button', function (e) {
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
				this.$wrapper.on('click', '.delen', function (e) {
					e.preventDefault();
					var $btn = $(this);
					self.openConfirm('Confirma que desea borrar ' + $btn.data('value') + '?');
					self.confirmCallback = function () {
						window.location = $btn.attr('href');
					};
				}),
				// sendmails
				this.$wrapper.on('click', '.sendmails', function (e) {
					e.preventDefault();
					var $btn = $(this);
					self.openConfirm('Confirma que desea enviar mails?', 'Enviar');
					self.confirmCallback = function () {
						window.location = $btn.attr('href');
					};
				}),
				// Remove CF
				this.$wrapper.on('click', '.rmcf', function (e) {
					e.preventDefault();
					self.openConfirm('Desea borrar el campo personalizado?');
					var $btn = $(this);
					self.confirmCallback = function () {
						$btn.parents('.cf').remove();
					};
				});
				// add CF
				this.$wrapper.on('click', '.addcf', function (e) {
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
				this.$wrapper.find('.autocomplete')
				.on('keydown', function(event) {
					self.availableTags = $(this).nextAll('.availabletags').text().split(',');
					if (event.keyCode === $.ui.keyCode.TAB && $(this).data("ui-autocomplete").menu.active) {
						event.preventDefault();
					}
				})
				.autocomplete({
					minLength: 0,
					source: function(request, response) {
						// delegate back to autocomplete, but extract the last term
						response($.ui.autocomplete.filter(self.availableTags, self.extractLast(request.term)));
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
				this.$wrapper.find('.fileupload.single').fileupload({
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
						if (data.result.success) {
							var file = data.result.files[0].filename,
								path = data.result.files[0].path;
							self.FUV.upcontainer.html('');
							self.appendFile(file, path);
							self.FUV.button.text('Reemplazar');
							self.FUV.button.addClass('secondary');
							self.FUV.hidden.val(file);
							// link file
							file = '<a href="' + self.frontBaseUrl + '/' + path + '/' + file +'" target="_blank">' + file + '</a>';
							var msg = 'Se subio el archivo ' + file + ' correctamente',
								classname = 'alert-success';
						} else {
							var msg = 'Error en la carga del archivo',
								classname = 'alert-danger';
						}
						self.createAlert(classname, msg, self.FUV.alertcontainer);
					},
					fail: function (e, data) {
						var msg = 'Error en la carga del archivo';
						self.createAlert('alert-danger', msg, self.FUV.alertcontainer);
					},
					always: function (e, data) {
						console.log(data.result);
						self.FUV.progressbarContainer.css('display', 'none');
					},
					progress: function (e, data) {
						var progress = parseInt(data.loaded / data.total * 100, 10);
						self.FUV.progressbarContainer.css({
							'display': 'block'
						});
						self.FUV.progressbar.css({
							'width': progress + '%'
						});
					}
				});
				// Fileupload Multi
				this.$wrapper.find('.fileupload.multiple').fileupload({
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
							var file = data.result.files[0].filename,
								path = data.result.files[0].path;
							self.appendFile(file, path);
							var files = self.FUV.hidden.val().split(', ');
							files.push(file);
							self.FUV.hidden.val(files.join(', '));
							// link file
							file = '<a href="' + self.frontBaseUrl + '/' + path + '/' + file +'" target="_blank">' + file + '</a>';
							var msg = 'Se subio el archivo ' + file + ' correctamente',
								classname = 'alert-success';
						} else {
							var msg = 'Error en la carga del archivo',
								classname = 'alert-danger';
						}
						self.createAlert(classname, msg, self.FUV.alertcontainer);
					},
					fail: function (e, data) {
						var msg = 'Error en la carga del archivo';
						self.createAlert('alert-danger', msg, self.FUV.alertcontainer);
					},
					always: function (e, data) {
						console.log(data.result);
						self.FUV.progressbarContainer.css('display', 'none');
					},
					progress: function (e, data) {
						var progress = parseInt(data.loaded / data.total * 100, 10);
						self.FUV.progressbarContainer.css({
							'display': 'block'
						});
						self.FUV.progressbar.css({
							'width': progress + '%'
						});
					}
				});
				// Remove Fileuploaded
				this.$wrapper.find('.upload-container').on('click', '.remove', function () {
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
				// submit prices fv
				this.$wrapper.find('#prices-submit').on('click', function () {
					self.submitPrices();
				});
				// form
				this.$wrapper.find('#form form').on('submit', function () {
					if (self.validate()) {
						return true;
					}
					return false;
				});
			},
			validate: function () {
				var errors = false,
					self = this;
				var $form = this.$wrapper.find('#form'),
					$pass = $form.find('input#password'),
					$email = $form.find('input[type="email"]');

				$form.find('[required]').removeClass('error');
				$form.find('[required]').siblings('.error').hide();
				$form.find('[required]').siblings('.error').hide();

				$form.find('input, select, textarea').each(function () {
					if ($(this).is('[required]')) {
						if (!$(this).val()) {
							$(this).addClass('error');
							if ($(this).siblings('.error').size()) {
								$(this).siblings('.error').show();
							} else {
								$(this).parent().siblings('.error').show();
							}
							errors = true;
						}
					}
				});
				if ($email.size() && !this.isEmail($email.val())) {
					$email.addClass('error');
					$email.siblings('.error').show();
					errors = true;
				}
				if ($pass.size() && $pass.val()) {
					var $passr = $form.find('input#passwordr');
					if ($pass.val() !== $passr.val()) {
						$passr.addClass('error');
						$passr.siblings('.error').show();
						errors = true;
					}
				}

				return !errors;
			},
			isEmail: function (email) {
				var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
				return regex.test(email);
			},
			submitPrices: function () {
				var $fields = this.$wrapper.find('.price'),
				$combos = this.$wrapper.find('.combo-price'),
				update_str = "",
				update_combos_str = "",
				update_arr = [],
				update_combos_arr = [],
				id, price,
				self = this, msg, alertclass;
				$fields.each(function () {
					id = parseFloat($(this).data('uid'));
					price = parseFloat($(this).text());
					update_arr.push(id + ':' + price);
				});
				update_str = update_arr.join(',');
				$combos.each(function () {
					id = parseFloat($(this).data('id'));
					price = parseFloat($(this).text());
					update_combos_arr.push(id + ':' + price);
				});
				update_combos_str = update_combos_arr.join(',');
				alertclass = 'alert-danger';
				$.post(this.baseUrl + "/xhr/updatePrices", {values: update_str, combo_values: update_combos_str}, function (res) {
					if (res.success) {
						msg = 'Precios actulaizados exitosamente.';
						alertclass = 'alert-success';
					} else {
						msg = 'Error inesperado. Los cambios no fueron guardados. Por favor, vuelva a intentarlo.';
						alertclass = 'alert-danger';
					}
					self.createAlert(alertclass, msg);
				}, 'json');
			},
			appendFile: function (file, path) {
				this.FUV.upcontainer.append('<span class="label label-info"><a href="http://' + this.frontBaseUrl + '/' + path + '/' + file + '" target="_blank" title="' + file + '">' + file + '</a><span class="remove">&times;</span></span>');
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
					progressbar: $btnc.nextAll('.progress').find('.progress-bar'),
					progressbarContainer: $btnc.nextAll('.progress'),
					upcontainer: $btnc.nextAll('.upload-container'),
					alertcontainer: $btnc.nextAll('.alerts')
				}
			},
			saveOrder: function () {
				var items = [], self = this, msg, alertclass;
				this.$wrapper.find('.sortable tr').each(function () {
					items.push($(this).data('id'));
				});
				this.openLoading();
				$.post(this.baseUrl + "/xhr/saveorder", {items: items, en: self.entityName}, function (res) {
					if (res.success) {
						msg = 'Los cambios fueron guardados exitosamente.';
						alertclass = 'alert-success';
					} else {
						msg = 'Error inesperado. Los cambios no fueron guardados. Por favor, recargue la página y vuelva a intentarlo.';
						alertclass = 'alert-danger';
					}
					self.closeLoading();
					self.createAlert(alertclass, msg);
				}, 'json');
			},
			createAlert: function (classname, msg, $context) {
				var $box = $('<div role="alert"></div>');
				var time = new Date();
				$box
				.attr('data-alert', '')
				.attr('class', 'alert alert-dismissible ' + classname)
				.html('<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>' + msg);
				if ($context) {
					$context.prepend($box);
				} else {
					this.$wrapper.find('#alerts').prepend($box);
				}
			},
			openConfirm: function (msg, button) {
				this.$confirm.find('p').text(msg);
				if (button !== undefined) {
					this.$confirm.find('.true').text(button);
				}
				this.$confirm.modal('show');
			},
			closeConfirm: function () {
				this.$confirm.modal('hide');
			},
			openLoading: function () {
				this.$loading.modal('show');
			},
			closeLoading: function () {
				var self = this;
				self.$loading.modal('hide');
			},
			setBaseUrl: function () {
				var url = document.domain;
				this.baseUrl = 'http://' + url + '/admin';
				this.frontBaseUrl = 'http://' + url;
			},
			toggleEntityFlag: function ($elem) {
				var data = $elem.data(),
				self = this, msg, alertclass;
				$elem.toggleClass('null');
				$.post(this.baseUrl + "/xhr/toggleflag", {id: data.id, prop: data.prop, en: data.en}, function (res) {
					if (res.success) {
						msg = 'Los cambios fueron guardados exitosamente.';
						alertclass = 'alert-success';
					} else {
						msg = 'Error inesperado. Los cambios no fueron guardados. Por favor, recargue la página y vuelva a intentarlo.';
						alertclass = 'alert-danger';
					}
					self.createAlert(alertclass, msg);
				}, 'json');
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


